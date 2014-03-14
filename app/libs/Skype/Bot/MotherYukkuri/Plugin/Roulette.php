<?php
/**
 * Skype_Bot_Plugin_Roulette
 * 参加メンバーの中からランダムで当たりの人を選んで発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Roulette extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:roulette( .+)?$/',
		'plugin_usage' => 'Usage: :roulette [online]',
		'plugin_info' => '参加メンバーで抽選を行い当たりの人を選びます',

		// 独自設定項目
		'message' => 'ドドドドド・・・ジャン！！　「%s」さんおめでとうございます！',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 引数を取得
		$arg = $this->getBodyWithoutCommand();

		// チャット内のメンバーを取得
		$tmp_members = $this->chat->get('MEMBERS');
		$members = explode(' ', $tmp_members[0]);

		// 自分自身は対象から外す
		foreach ($members as $key => $skypename) {
			if ($skypename === $this->skype->getCurrentUserHandle()) {
				unset($members[$key]);
				sort($members);		// 配列を詰める
				break;
			}
		}

		// 引数で挙動を変更
		if ('online' === $arg) {
			// オフラインの人を外す
			foreach ($members as $key => $skypename) {
				$online_status = $this->getUserProperty($skypename, 'ONLINESTATUS');
				if ('OFFLINE' === $online_status) {
					unset($members[$key]);
				}
			}
		}

		// ランダムで抽選
		$rand = array_rand($members);
		$selected_skypename = $members[$rand];

		// フルネームを取得
		$fullname = $this->getUserProperty($selected_skypename, 'FULLNAME');
		if ('' == $fullname) {
			$output_name = $skypename;
		} else {
			$output_name = $fullname;
		}

		// 発言
		$this->postMessage(sprintf($this->config['message'], $output_name));
	}
}

