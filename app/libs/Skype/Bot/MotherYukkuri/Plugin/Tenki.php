<?php
/**
 * Skype_Bot_Plugin_Tenki
 * 天気予報のRSSを取得し、天気情報を発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 * @link		http://weather.yahoo.co.jp/weather/rss/
 */

class Skype_Bot_Plugin_Tenki extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:tenki( .+)?$/',
		'plugin_usage' => 'Usage: :tenki',
		'plugin_info' => '天気予報を表示します',

		// 対象RSS定義
		'rss_url' => 'http://rss.weather.yahoo.co.jp/rss/days/1400.xml',	// 天気予報情報を提供するRSSのURL
		'display_num' => 2,		// 何日分取得するか
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// XMLを取得
		$xml = simplexml_load_file($this->config['rss_url']);

		// タイトルを取得
		$msg = strval($xml->channel->title) . "\n";

		$i = 0;
		foreach ($xml->channel->item as $item) {
			// 整形と格納
			$msg .= strval($item->title) . "\n";
			$i++;
			if ($i >= $this->config['display_num']) {
				break;
			}
		}

		// 発言
		$this->postMessage($msg);
	}
}

