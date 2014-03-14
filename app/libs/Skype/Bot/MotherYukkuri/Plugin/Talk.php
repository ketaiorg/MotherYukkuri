<?php
/**
 * Skype_Bot_Plugin_Count
 * 指定された文字を発声するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 * @link		https://github.com/ketaiorg/YukkuriTalk
 */

class Skype_Bot_Plugin_Talk extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:talk /',
		'plugin_usage' => 'Usage: :talk STRING...',
		'plugin_info' => '入力された文字を発声します',

		// 独自設定項目
		'exec_cmd' => '%APPPATH%/libs/Skype/Bot/MotherYukkuri/Plugin/Talk/yukkuritalk %MSG%',		// 発声で実行されるコマンド
		'msg_ng' => '[error] (%TALK_STRING%)',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		$body = $this->getBody();

		$talk_str = substr($body, strpos($body, ' ') + 1);		// 発声する文字列
		if ('' == $talk_str) {
			$this->postMessage($this->config['plugin_usage']);
			return;
		}

		$tr_arr = array(
			'%MSG%' => escapeshellarg($talk_str),
			'%APPPATH%' => escapeshellarg(APPPATH),
		);
		$cmd = strtr($this->config['exec_cmd'], $tr_arr);		// 実行されるコマンド
		exec($cmd, $output, $return_var);			// コマンドの実行
		if (!isset($output[0]) or 0 !== $return_var) {
			// エラー
			$msg = strtr($this->config['msg_ng'], array('%TALK_STRING%' => $talk_str));
		} else {
			// 受け取った標準出力の内容をそのまま出力
			$msg = $output[0];
		}
		$this->postMessage($msg);
	}
}

