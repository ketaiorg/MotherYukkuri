<?php
/**
 * Skype_Bot_Plugin_Echo
 * 指定したチャットに指定した文字を発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Echo extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:echo /',
		'plugin_usage' => 'Usage: :echo TARGET_CHAT_TOPIC STRING...',
		'plugin_info' => '指定したチャットに発言します',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 本文を分解
		$args = $this->getCommandArgument();
		$target_topic = array_shift($args);		// 対象チャット名
		$echo_body = implode(' ', $args);		// 発言させたい本文

		// 対象となるチャット情報を取得
		$target_chatlist = $this->getChatIdsByTopic($target_topic);

		if ('' == $target_topic or '' == $echo_body) {
			// 対象や本文が指定されていない
			$this->postMessage(sprintf("[Echo]\n%s", $this->config['plugin_usage']));
		} elseif (empty($target_chatlist)) {
			// 対象が特定できなかったので、メッセージを出力して終了
			$this->postMessage(sprintf("[Echo]\n対象となるチャット「%s」が見つかりませんでした。", $target_topic));
		} else {
			// 出力
			$chat_target = $this->getInstanceById($target_chatlist[0]);		// 複数候補が見つかった場合は先頭を利用
			$chat_target->postMessage($echo_body);
		}
	}
}

