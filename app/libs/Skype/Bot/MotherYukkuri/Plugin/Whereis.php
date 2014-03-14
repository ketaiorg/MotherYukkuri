<?php
/**
 * Skype_Bot_Plugin_Whereis
 * 対象のユーザがどのチャットに入っているかを調べるためのプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Whereis extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:whereis /',
		'plugin_usage' => 'Usage: :whereis TARGET_SKYPE_ID',
		'plugin_info' => '指定したユーザが参加しているチャットリストを表示します',

		// 独自設定項目
		'msg_target_not_found' => '# スカイプID「%s」が見つかりませんでした。',
		'msg_output' => "* \"%%FULL_NAME%% (%%SKYPE_ID%%)\" さんは、これらのチャットに参加しています:\n%%CHAT_LIST%%",
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 比較対象となるチャット名を取得
		$search_user = $this->getBodyWithoutCommand();
		if ('' == $search_user) {
			$this->postMessage($this->config['plugin_usage']);
			return;
		}

		// 全チャットを取得
		$chat_list = $this->getSkype()->getChatList();

		// 格納変数を初期化
		$existing_list = array();

		// チャットの数だけループ
		foreach ($chat_list as $chat) {
			$tmp_members = $chat->get('MEMBERS');
			$members = explode(' ', $tmp_members[0]);
			if (false !== array_search($search_user, $members)) {
				// 探しているユーザがチャットに存在した
				$topic = $chat->get('TOPIC');
				if ('' != $topic) {
					$existing_list[] = $topic;
				}
			}
		}

		// メッセージを生成
		$existing_list_str = ' - '. implode("\n - ", $existing_list);
		$tr_arr = array(
			'%%FULL_NAME%%' => $this->getUserProperty($search_user, 'FULLNAME'),
			'%%SKYPE_ID%%' => $search_user,
			'%%CHAT_LIST%%' => $existing_list_str,
		);
		$this->postMessage(strtr($this->config['msg_output'], $tr_arr));
	}
}

