<?php
/**
 * Skype_Bot_Plugin_Lunch
 * GoogleMapからランチマップを取得し、その中からランダムで一つを選んで発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Lunch extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:lunch( .+)?$/',
		'plugin_usage' => 'Usage: :lunch [OUTPUT_NUM]',
		'plugin_info' => 'Googleマップからランチ情報を取得しオススメを表示します',

		// 対象マップ定義
		'map_kml_url' => '',			// GoogleMapのKMLアドレス
		'map_link_url' => '',			// 上記で定義されたマップにアクセスするためのアドレス
		'message_head' => "# %s (%s) より抽選 #\n\nゆっくり曰く、あなたが行くべきお店はここです\n==========\n",
		'message_shopinfo' => "* 第%d位【%s】\n%s\n==========\n",
		'message_shop_num' => 3,		// 第何位まで抽選するか
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 引数があるなら取得
		$body_arg = $this->getBodyWithoutCommand();
		if ('' == $body_arg) {
			$arg = 0;
		} else {
			$arg = intval($body_arg);
		}
		if (1 > $arg) {
			// 引数が無いか不正な場合は、デフォルト値を使う
			$arg = $this->config['message_shop_num'];
		}

		// KMLを取得
		$xml = simplexml_load_file($this->config['map_kml_url']);

		// マップ名を取得
		$map_name = strval($xml->Document->name);

		// 表示する数を決定
		$xml_count = count($xml->Document->Folder->Placemark);
		if ($xml_count < $arg) {
			$num = $xml_count;
		} else {
			$num = $arg;
		}

		// ランダムで抽選
		$tmp_arr = array_fill(0, $xml_count, null);
		$target = array_rand($tmp_arr, $num);
		if (!is_array($target)) {
			$target = array($target);
		}
		shuffle($target);

		$msg = sprintf($this->config['message_head'], $map_name, $this->config['map_link_url']);
		$i = 0;
		foreach ($target as $item) {
			// 整形と格納
			$shop_name = strval($xml->Document->Folder->Placemark[$item]->name);
			$shop_description = strip_tags(strtr(strval($xml->Document->Folder->Placemark[$item]->description), array('<br>' => "\n")));
			$i++;
			$msg .= sprintf($this->config['message_shopinfo'], $i, $shop_name, $shop_description);
		}

		// 発言
		$this->postMessage($msg);
	}
}

