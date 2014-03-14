<?php
/**
 * Skype_Bot_Plugin_Count
 * 現在のチャットに入っているメンバー数を答えるプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Count extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:count( .+)?$/',
		'plugin_usage' => 'Usage: :count',
		'plugin_info' => 'チャットに参加している人数を答えます',

		// 独自設定項目
		'msg_format' => '現在のメンバー数は、%d名です。',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		$msg = sprintf($this->config['msg_format'], count($this->getMembers()));
		$this->postMessage($msg);
	}
}

