<?php
/**
 * Skype_Bot_Plugin_Rss
 * RSSを監視し更新があれば発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Rss extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^:rss( .+)?$/',
		'plugin_usage' => 'Usage: :rss',
		'plugin_info' => '登録されたRSSに更新があれば発言します',

		// RSS取得結果を書き込むチャット
		'target_chat' => '',

		// 対象フィード定義
		'feed' => array(
			'http://www.infiniteloop.co.jp/blog/feed/',		// インフィニットループ技術ブログ
		),

		'interval' => 3600,					// RSSをチェックしに行く間隔
		'description_length' => 150,		// 本文を何文字まで表示するか
		'list_message' => "現在登録されているRSS\n",
		'list_format' => "* %%URL%%\n",
		'post_format' => "* %%TITLE%%\n%%LINK%%\n> %%DESCRIPTION%%\n\n",
	);

	/**
	 * 初期化処理
	 */
	protected function before()
	{
		// ポーリングするようにする
		$this->setPolling($this->config['interval']);
	}

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// リスト表示
		$this->postList();
	}

	/**
	 * リスト出力
	 */
	protected function postList()
	{
		$msg = $this->config['list_message'];
		foreach ($this->config['feed'] as $url) {
			$data_path = $this->getDataPath($url);
			$msg .= strtr($this->config['list_format'], array('%%URL%%' => $url));
		}
		$this->postMessage($msg);
	}

	/**
	 * ポーリング処理
	 */
	protected function poll_execute()
	{
		// RSSチェック処理を行う
		if ('' != $this->config['target_chat']) {
			$this->checkRss();
		}
	}

	/**
	 * RSSチェック処理
	 */
	protected function checkRss()
	{
		$msg = '';

		foreach ($this->config['feed'] as $url) {
			// RSSを取得
			$xml_str = '';
			try {
				$xml_str = $this->getUrl($url);
			} catch (Exception $e) {
				fputs(STDERR, sprintf("RSS PLUGIN ERROR! Could not get RSS. (%s)\n", $e->getMessage()));
				continue;
			}

			// RSSをパース
			$rss_arr = $this->parseRss($xml_str);
			if (empty($rss_arr)) {
				// パース失敗
				fputs(STDERR, sprintf("RSS PLUGIN ERROR! RSS parse faild. (%s)\n", $url));
				continue;
			} else {
				// RSSの取得に成功した

				// 保存されている時刻を取得
				$prev_time = @file_get_contents($this->getDataPath($url));
				if (false === $prev_time) {
					// ファイルが存在しない場合、全てのデータを取得するため遠い過去とする
					$prev_time = 0;
				}
			}

			// 出力のために整形
			$write_time = 0;
			foreach ($rss_arr['item'] as $value) {
				if ($value['date'] > $prev_time) {
					// 新しい記事を見つけた
					$tr_arr = array(
						'%%TITLE%%' => $value['title'],
						'%%LINK%%' => $value['link'],
						'%%DESCRIPTION%%' => $value['description'],
					);
					$msg .= strtr($this->config['post_format'], $tr_arr);

					// 最終更新日時を更新
					if ($value['date'] > $write_time) {
						// 現在の時刻を保存
						$write_time = $value['date'];
						if (false === @file_put_contents($this->getDataPath($url), $write_time)) {
							// ファイルの出力に失敗
							fputs(STDERR, sprintf("ERROR! Could not create data files. (%s)\n", $url));

							// 以後のポーリングをストップ
							fputs(STDERR, "Stop plugin.");
							$this->setPolling(0);
							break;
						}
					}
				}
			}
		}

		if ('' != $msg) {
			// 対象となるチャット情報を取得
			$target_chatlist = $this->getChatIdsByTopic($this->config['target_chat']);
			if (!empty($target_chatlist)) {
				$chat_target = $this->getInstanceById($target_chatlist[0]);		// 複数候補が見つかった場合は先頭を利用
				$chat_target->postMessage($msg);
			}
		}
	}

	/**
	 * データファイル名取得
	 * @param string $url RSSのURL
	 * @return string 設定ファイルのパス
	 */
	protected function getDataPath($url)
	{
		// RSSディレクトリの下にURLエンコードしたファイル名で格納
		return dirname(realpath(__FILE__)) . '/Rss/' . urlencode($url);
	}

	/**
	 * URLリクエストメソッド
	 * curlによるリクエストを行う
	 * @param string $url 対象URL
	 * @return string 結果文字列
	 * @throws Exception 予期せぬステータスコード
	 */
	protected function getUrl($url)
	{
		// cURLを利用
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		$ret = curl_exec($ch);

		if (false === $ret) {
			// curlに失敗した（通信エラー）
			curl_close($ch);
			throw new Exception('curl error.');
		}

		// ステータスコードを取得
		$header = curl_getinfo($ch);
		curl_close($ch);
		$status_code = $header['http_code'];

		// ステータスコードで分岐
		if ('200' != $status_code) {
			// ステータスコードが想定外の場合、例外を投げる
			throw new Exception('cURL status code error. code=' . $status_code, $status_code);
		}

		// 取得した値を返す
		return $ret;
	}

	/**
	 * RSS整形
	 * @param object $xml_str RSSのXML文字列
	 * @return array RSSをパースした連想配列
	 */
	protected function parseRss($xml_str)
	{
		$ret_arr = array();

		libxml_use_internal_errors(true);
		$xml = @simplexml_load_string($xml_str);
		if (false === $xml) {
			fputs(STDERR, "RSS PLUGIN ERROR! Could not parse XML.\n");
			return;
		}

		// RSSの種別を判定
		if ('2.0' == strval($xml->attributes()->version)) {
			// RSS2.0の場合
			$rss_type = 'RSS2.0';
			$ret_arr['title'] = strval($xml->channel->title);
			$item_arr = $xml->channel->item;
		} elseif (preg_match('/<feed .*?xmlns=.*?http\:\/\/www\.w3\.org.*\/Atom/i', $xml_str)) {
			// Atomの場合
			$rss_type = 'ATOM';
			$ret_arr['title'] = strval($xml->title);
			$item_arr = $xml->entry;
		} else {
			// それ以外はRSS1.0とみなす
			$rss_type = 'RSS1.0';
			$ret_arr['title'] = strval($xml->channel->title);
			$item_arr = $xml->item;
		}

		// アイテムを順に取得
		foreach ($item_arr as $item) {
			$push_arr = array();
			switch ($rss_type) {
			case 'RSS1.0':
			case 'RSS2.0':
				$push_arr['link'] = strval($item->link);
				$description = strval($item->description);
				if (isset($item->pubDate)) {
					$push_arr['date'] = intval(strtotime(strval($item->pubDate)));
				} else {
					$push_arr['date'] = intval(strtotime($item->children("http://purl.org/dc/elements/1.1/")->date));
				}
				break;
			case 'ATOM':
				$push_arr['link'] = $item->link->attributes()->href;
				foreach ($item->link as $link) {
					if ('text/html' == $link->attributes()->type) {
						// text/htmlのリンクを優先
						$push_arr['link'] = $link->attributes()->href;
					}
				}
				$description = strval($item->summary);
				$push_arr['date'] = intval(strtotime($item->updated));
				break;
			}

			// 本文は指定の文字数でカット
			$push_arr['description'] = mb_strcut(strip_tags($description), 0, $this->config['description_length'], 'UTF-8') . '...';

			// タイトルを格納
			$push_arr['title'] = strval($item->title);

			// 配列に格納し直す
			$ret_arr['item'][] = $push_arr;
		}

		// アイテムは古い方から順に格納する
		$ret_arr['item'] = array_reverse($ret_arr['item']);

		return $ret_arr;
	}
}
