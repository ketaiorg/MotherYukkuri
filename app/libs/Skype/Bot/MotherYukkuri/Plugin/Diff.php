<?php
/**
 * Skype_Bot_Plugin_Diff
 * 現在のチャットに入っていると指定したチャットのメンバーを比べその差分を答えるプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Diff extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:diff /',
		'plugin_usage' => 'Usage: :diff TARGET_CHAT_TOPIC',
		'plugin_info' => 'チャットに参加しているメンバーを比較します',

		// 独自設定項目
		'msg_target_not_found' => '# 比較対象となるチャット「%s」が見つかりませんでした。',
		'msg_only_this_chat' => "* このチャットにのみ存在(%d名): %s\n",
		'msg_only_target_chat' => "* 対象チャットにのみ存在(%d名): %s\n",
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 比較対象となるチャット名を取得
		$target_topic = $this->getBodyWithoutCommand();

		// ベースとなるチャット情報を取得
		$members_base = $this->getMembers();

		// 対象となるチャット情報を取得
		$target_chatlist = $this->getChatIdsByTopic($target_topic);

		if ('' == $target_topic) {
			// 対象が指定されていない
			return;
		} elseif (empty($target_chatlist)) {
			// 比較対象が特定できなかったので、メッセージを出力して終了
			$this->postMessage(sprintf($this->config['msg_target_not_found'], $target_topic));
		} else {
			// 差分を抽出
			$skype = $this->getSkype();
			$chat_target = $this->getInstanceById($target_chatlist[0]);			// 複数候補が見つかった場合は先頭を利用
			$members_target = $chat_target->getMembers();
			$diff_base_only = array_diff($members_base, $members_target);		// targetにはいないが、baseには存在
			$diff_target_only = array_diff($members_target, $members_base);		// baseにはいないが、targetには存在

			// 並び替え
			sort($diff_base_only);
			sort($diff_target_only);

			// 整形
			foreach ($diff_base_only as $key => $skypename) {
				$fullname = $this->getUserProperty($skypename, 'FULLNAME');
				$diff_base_only[$key] = sprintf('%s(%s)', $fullname, $skypename);
			}
			foreach ($diff_target_only as $key => $skypename) {
				$fullname = $this->getUserProperty($skypename, 'FULLNAME');
				$diff_target_only[$key] = sprintf('%s(%s)', $fullname, $skypename);
			}
			$diff_base_only_txt = implode(', ', $diff_base_only);
			$diff_target_only_txt = implode(', ', $diff_target_only);

			$msg = sprintf($this->config['msg_only_this_chat'], count($diff_base_only), $diff_base_only_txt);
			$msg .= sprintf($this->config['msg_only_target_chat'], count($diff_target_only), $diff_target_only_txt);
			$this->postMessage($msg);
		}
	}
}

