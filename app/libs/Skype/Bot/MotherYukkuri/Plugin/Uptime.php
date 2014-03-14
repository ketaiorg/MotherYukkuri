<?php
/**
 * Skype_Bot_Plugin_Uptime
 * uptimeコマンドを叩くプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Uptime extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:uptime( .+)?$/',
		'plugin_usage' => 'Usage: :uptime',
		'plugin_info' => 'uptimeコマンドを実行し表示します',

		// 独自設定項目
		'exec_cmd' => 'uptime',					// 実行されるコマンド
		'msg_ok' => '> %%RESULTS%%',			// 実行成功時のメッセージ
		'msg_ng' => '[error] ',					// エラーの場合のメッセージ
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		exec($this->config['exec_cmd'], $output, $return_var);			// コマンドの実行
		if ('' != $output[0] and 0 === $return_var) {
			$msg = strtr($this->config['msg_ok'], array('%%RESULTS%%' => trim($output[0])));
		} else {
			$msg = $this->config['msg_ng'] . implode('\n', $output);
		}
		$this->postMessage($msg);
	}
}

