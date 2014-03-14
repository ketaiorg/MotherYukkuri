<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * SkypeBot初期化
 * SkypeBotを初期化する
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

class Skype_Bot_Init
{
	/**
	 * 初期化
	 */
	static public function init()
	{
		// アプリパスを定義
		define('APPPATH', realpath(dirname(__FILE__) . '/../../../../') . '/');

		// 設定ファイルの読み込み
		require_once(APPPATH . 'config/appConfig.php');

		// デバッグ用ファイルの読み込み
		if (DEBUG_MODE) {
			require_once(APPPATH . 'debug/debugFunctions.php');
		}

		// php-skypeを読み込めるようにパスを追加して読み込み
		ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . VENDORS_DIR . 'php-skype');
		require_once('Skype/Bot.php');

		// libs以下から読み込み
		require_once(APPPATH . 'libs/Skype/Bot/MotherYukkuri.php');
	}
}

