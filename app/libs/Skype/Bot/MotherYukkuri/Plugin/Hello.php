<?php
/**
 * Skype_Bot_Plugin_Hello
 * 挨拶を行います
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Hello extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:hello$/',
		'plugin_usage' => 'Usage: :hello',
		'plugin_info' => '挨拶を行います',

		// 独自設定項目
		'answer_list' => array(
			'Hello', 
		),
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		$rand = array_rand($this->config['answer_list']);
		$this->postMessage($this->config['answer_list'][$rand]);
	}
}

