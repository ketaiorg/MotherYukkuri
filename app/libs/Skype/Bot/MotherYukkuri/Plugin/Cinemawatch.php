<?php
/**
 * Skype_Bot_Plugin_Cinemawatch
 * 映画情報を監視し更新があれば発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Cinemawatch extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^:cinemawatch$/',
		'plugin_usage' => 'Usage: 自動的に定期実行されます',
		'plugin_info' => '映画情報を監視し更新があれば発言します',

		// 監視対象となるURL
		'target_url' => '',

		// 取得結果を書き込むチャット
		'target_chat' => '',

		'interval' => 3600,					// チェックしに行く間隔
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
	}

	/**
	 * ポーリング処理
	 */
	protected function poll_execute()
	{
		// チェック処理を行う
		if ('' != $this->config['target_chat']) {
			$this->checkCinemawatch();
		}
	}

	/**
	 * チェック処理
	 */
	protected function checkCinemawatch()
	{
		$msg = '';

		// ページを取得
		$html = '';
		try {
			$html = $this->getUrl($this->config['target_url']);
		} catch (Exception $e) {
			fputs(STDERR, sprintf("Cinemawatch PLUGIN ERROR! Could not get html. (%s)\n", $e->getMessage()));
			return;
		}

		// HTMLをパースして上映映画IDを配列で取得
		$cinema_arr = $this->parseCinemawatch($html);
		if (empty($cinema_arr)) {
			// パース失敗
			fputs(STDERR, sprintf("Cinemawatch PLUGIN ERROR! HTML parse faild. (%s)\n", $this->config['target_url']));
			return;
		} else {
			// HTMLの取得に成功した
			foreach ($cinema_arr as $id) {
				// データがあるか確かめる
				if (file_exists(($this->getDataPath($id)))) {
					// 既にファイルがある場合何もしない
					continue;
				} else {
					// ファイルがない場合作成
					$this->putData($id, time());

					// データを取得して出力
					$id_html = @file_get_contents('http://eiga.com/movie/' . $id . substr($this->config['target_url'], 15) . 'mail/');

					// 切り出しとタグの取り除き
					$id_html = $this->snipString($id_html, '<textarea name="form_area" rows="10" id="form_area">', '</textarea>');
					$id_text = strip_tags($id_html);

					$msg .= $id_text . "\n\n\n";
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
	 * @param string $id 映画ごとのID
	 * @return string 設定ファイルのパス
	 */
	protected function getDataPath($id)
	{
		// ディレクトリの下にURLエンコードしたファイル名で格納
		return dirname(realpath(__FILE__)) . '/Cinemawatch/' . urlencode($id);
	}

	/**
	 * データファイル書き込み
	 * @param string $id 映画ごとのID
	 * @param string $contents ファイルの中に書く文字列
	 */
	protected function putData($id, $contents)
	{
		if (false === @file_put_contents($this->getDataPath($id), $contents)) {
			// ファイルの出力に失敗
			fputs(STDERR, sprintf("ERROR! Could not create data files. (%s)\n", $this->config['target_url']));

			// 以後のポーリングをストップ
			fputs(STDERR, "Stop plugin.");
			$this->setPolling(0);
		}
	}

	/**
	 * テキスト切り出し
	 * 指定した開始位置から終了位置までの文字列を切り出す
	 * @param string $text 全体の文字列
	 * @param string $start_mark 開始位置を示す文字列
	 * @param string $endt_mark 終了位置を示す文字列
	 * @return string 切り出された文字列
	 */
	protected function snipString($text, $start_mark, $end_mark)
	{
		// 切り出し処理
		$tmp_text = mb_substr(strstr($text, $start_mark), mb_strlen($start_mark));
		if ('' != $tmp_text) {
			$text = $tmp_text;
		}
		$tmp_text = strstr($text, $end_mark, true);
		if ('' != $tmp_text) {
			$text = $tmp_text;
		}

		return $text;
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
	 * HTML整形
	 * @param string $html HTML文字列
	 * @return array パースした結果のID配列
	 */
	protected function parseCinemawatch($html)
	{
		preg_match_all('/<a href="\/movie\/([0-9]+)\/theater\//', $html, $matches);
		$ret_arr = array_unique($matches[1]);

		return $ret_arr;
	}
}
