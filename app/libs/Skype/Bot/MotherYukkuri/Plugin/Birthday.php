<?php
/**
 * Skype_Bot_Plugin_Birthday
 * コンタクトリストから誕生日を取得し、指定した期間内に含まれるユーザを発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */


class Skype_Bot_Plugin_Birthday extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:birthday( .+)?$/',
		'plugin_usage' => 'Usage: :birthday',
		'plugin_info' => '誕生日が近いユーザをリストアップします',

		// 独自設定項目
		'title_msg' => "# 誕生日が近いメンバー #\n",		// 出力タイトルメッセージ
		'period_date' => 'mt',								// 取得範囲：範囲の終了日となる月日を指定
		'period_strtotime' => '+1 month',					// 取得範囲：上の日付に加える補正を指定
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 範囲を決定
		$start_date = date('md');		// 対象の始まり
		$end_date = date($this->config['period_date'], strtotime($this->config['period_strtotime']));		// 対象の終わり

		// メンバーを取得
		$members = $this->getMembers();

		// 誕生日が対象内のユーザを抽出
		$target_members = $this->listUpByBirthday($members, $start_date, $end_date);

		// 発言
		$msg = $this->config['title_msg'];
		foreach ($target_members as $birthday => $users) {
			$msg .= sprintf(" * %d月%d日: ", substr($birthday, 0, 2), substr($birthday, 2, 2));
			$push_arr = array();
			foreach ($users as $user_info) {
				$push_arr[] = $user_info['fullname'];
			}
			$msg .= implode(', ', $push_arr) . "\n";
		}
		$this->postMessage($msg);
	}

	/**
	 * 誕生日からの抽出
	 * @param array $members メンバーを格納した配列
	 * @param string $start_date 範囲開始日
	 * @param string $end_date 範囲終了日
	 * @return array 対象メンバーを格納した配列
	 */
	protected function listUpByBirthday($members, $start_date, $end_date)
	{
		$target_members = array();

		// メンバーの数だけ繰り返し
		foreach ($members as $skypename) {
			$birthday = $this->getUserProperty($skypename, 'BIRTHDAY');		// 誕生日を取得（未設定の人は0が返る）
			if (8 == strlen($birthday)) {
				// 誕生日がセットされている
				$cut_birthday = substr($birthday, 4);
				if ($start_date <= $cut_birthday and $end_date >= $cut_birthday) {
					// 誕生日が対象内
					$target_members[$cut_birthday][] = array(
						'skypename' => $skypename,
						'fullname' => $this->getUserProperty($skypename, 'FULLNAME'),
						'birthday' => $birthday,
					);
				}
			}
		}

		// ソート
		ksort($target_members);

		return $target_members;
	}
}

