<?php
/**
 * MotherYukkuriプラグイン設定ファイル
 */
return array(
	'plugin_is_disabled' => false,
	'plugin_ignore_self_messages' => false,
	'feed' => array(
		'http://www.infiniteloop.co.jp/blog/feed/',		// インフィニットループ技術ブログ
		'http://b.hatena.ne.jp/hotentry/it.rss',		// はてなブックマーク人気エントリー（テクノロジー）
	),
	'target_chat' => '',		// RSS取得結果を書き込むチャット
);
