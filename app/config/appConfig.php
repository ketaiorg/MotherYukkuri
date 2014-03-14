<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * アプリ設定
 * アプリ全般の設定を記述するファイル
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

// デバッグモード
define('DEBUG_MODE', false);

// SkypeBot識別子
define('SKYPE_BOT_ID', 'MotherYukkuri');

// ベンダーライブラリのディレクトリ
define('VENDORS_DIR', APPPATH . '../vendors/');

// プラグインのディレクトリ
define('PLUGIN_DIR', APPPATH . 'libs/Skype/Bot/MotherYukkuri/Plugin/');

// プラグインの設定ディレクトリ
define('PLUGIN_CONFIG_DIR', APPPATH . 'config/Plugin/');
