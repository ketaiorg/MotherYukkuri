<?php
/**
 * MotherYukkuriプラグイン設定ファイル
 * @link http://weather.yahoo.co.jp/weather/rss/
 */
return array(
	'plugin_is_disabled' => false,
	'plugin_ignore_self_messages' => false,
	'rss_url' => 'http://rss.weather.yahoo.co.jp/rss/days/13.xml',	// 天気予報情報を提供するRSSのURL
	'display_num' => 2,		// 何日分取得するか
);
