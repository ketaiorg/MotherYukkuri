<?php
/**
 * Skype_Bot_Plugin_Nuko
 * ぬこ画像を検索し、ランダムで選んで発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Nuko extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:nuko( .+)?$/',
		'plugin_usage' => 'Usage: :nuko [OUTPUT_NUM]',
		'plugin_info' => 'ぬこ画像をランダムで出力します',

		'api_url' => 'https://www.googleapis.com/customsearch/v1?',
		'api_key' => '',							// Googleから発行されるAPIキー
		'api_cx' => '',								// Googleから発行されるAPI_CX
		'search_keyword' => 'ぬこ画像',				// 検索キーワード
		'search_lang' => 'lang_ja',					// 言語
		'search_num' => 10,							// 1ページの検索数、1-10で指定だが下げるメリットがないので事実上10固定
		'search_pages' => 20,						// 取得するページ数、この中からランダムで選択されるので、多い方がバリエーションが出る
		'search_safe' => 'medium',					// Googleのセーフサーチを利用するかどうか(high|medium|off)
		'defult_output_num' => 3,					// 第何位まで出力するか
		'message_head' => "☆ 今日の特選ぬこ画像 ☆\n",
		'message_info' => " * 第%d位: %s\n",
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 引数があるなら取得
		$body_arg = $this->getBodyWithoutCommand();
		if ('' == $body_arg) {
			$num = 0;
		} else {
			$num = intval($body_arg);
		}
		if (1 > $num) {
			// 引数が無いか不正な場合は、デフォルト値を使う
			$num = $this->config['defult_output_num'];
		}

		// APIキーのチェック
		if ('' == $this->config['api_key'] or '' == $this->config['api_cx']) {
			$this->postMessage('[error] APIキーを正しくセットしてください');
			return;
		}

		// ランダムにページを決定
		$page = mt_rand(1, $this->config['search_pages']);

		// パラメータを取得
		$param = array(
			'key' => $this->config['api_key'], 
			'cx' => $this->config['api_cx'], 
			'q' => $this->config['search_keyword'], 
			'alt' => 'json', 
			'lr' => $this->config['search_lang'], 
			'num' => $this->config['search_num'], 
			'safe' => $this->config['search_safe'], 
			'searchType' => 'image', 
			'start' => $page, 
		);

		// データを取得
		$url = $this->config['api_url'] . http_build_query($param);
		$images_data = json_decode($this->getUrl($url));

		// 表示する数を決定
		$data_count = count($images_data->items);
		if ($data_count < $num) {
			$num = $data_count;
		}

		// ランダムで抽選
		$tmp_arr = array_fill(0, $data_count, null);
		$target = array_rand($tmp_arr, $num);
		if (!is_array($target)) {
			$target = array($target);
		}
		shuffle($target);

		$msg = $this->config['message_head'];
		$i = 0;
		foreach ($target as $key) {
			// 整形と格納
			$i++;
			$msg .= sprintf($this->config['message_info'], $i, $images_data->items[$key]->link);
		}

		// 発言
		$this->postMessage($msg);
	}

	/**
	 * URLリクエストメソッド
	 * curlによるリクエストを行う
	 * @param string $url 対象URL
	 * @param string $last_access 前回アクセスしたUNIXTIME形式の時間(If-Modified-Sinceヘッダ用)
	 * @return string 結果文字列
	 * @throws Exception 予期せぬステータスコード
	 */
	protected function getUrl($url, $last_access = 0)
	{
		// cURLを利用
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEVALUE, $last_access);
		curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
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
		if ('200' == $status_code) {
			// 200 OKの場合、データを返す
			return $ret;
		} else {
			// その他の場合、例外を投げる
			throw new Exception('status code error. code=' . $status_code, $status_code);
		}
	}
}

