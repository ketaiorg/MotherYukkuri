<?php
/**
 * MotherYukkuriプラグイン設定ファイル
 */
return array(
	'plugin_is_disabled' => false,
	'plugin_ignore_self_messages' => false,

	// JR北海道運行状況
	'target_url' => 'http://mobile.jrhokkaido.co.jp/webunkou/area.asp?a=1',
	'source_encoding' => 'SJIS',
	'start_mark' => '北海道医療大学間',
	'end_mark' => '※上記情報は自動的には更新されませんので、継続してご覧になる場合は',

	// JR東日本運行状況
	//'target_url' => 'http://traininfo.jreast.co.jp/train_info/kanto.aspx',
	//'source_encoding' => 'UTF-8',
	//'start_mark' => '遅延証明書についてはこちら',
	//'end_mark' => '<TD align="left" valign="top" class="text-m"><FONT color="#999999">※</FONT></TD>',
);
