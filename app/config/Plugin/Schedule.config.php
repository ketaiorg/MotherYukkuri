<?php
/**
 * MotherYukkuriプラグイン設定ファイル
 */
return array(
	'plugin_is_disabled' => false,
	'plugin_ignore_self_messages' => false,
//	'chat_topic_filter' => '/^.*$/',		// セキュリティのためトピック名でフィルタ（全てのチャットで実行を許可）
	'calendar' => array(
		'default' => array(
			array('userid' => 'dal3aqnssjr76f2fpocnnb85h0%40group.calendar.google.com', 'magiccookie' => 'public'),		// 北海道IT勉強会カレンダー
			array('userid' => '9g97s33p6sg304c3k1l6pmt54g%40group.calendar.google.com', 'magiccookie' => 'public'),		// 東北IT勉強会カレンダー
			array('userid' => '9tpbceee3kjbn6aorimdkfqg88%40group.calendar.google.com', 'magiccookie' => 'public'),		// 関西IT勉強会カレンダー
		),
//		'example_groupname' => array(
//			array('userid' => '', 'magiccookie' => ''),
//		),
	),
);
