<?php
/**
 * Skype_Bot_Plugin_Version
 * 挨拶を行います
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Version extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:version$/',
		'plugin_usage' => 'Usage: :version',
		'plugin_info' => 'バージョン番号を出力します',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		$this->postMessage('Version: ' . self::VERSION . "\n");
	}
}

