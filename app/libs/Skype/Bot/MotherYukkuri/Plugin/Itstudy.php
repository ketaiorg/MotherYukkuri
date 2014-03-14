<?php
/**
 * Skype_Bot_Plugin_Itstudy
 * Googleカレンダーからスケジュールを取得し、それを発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */


class Skype_Bot_Plugin_Itstudy extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:itstudy( .+)?$/',
		'plugin_usage' => 'Usage: :itstudy',
		'plugin_info' => 'Googleカレンダーから勉強会情報を取得し表示します',

	 	// 対象カレンダー定義
		'calendar' => array(
			array('userid' => 'fvijvohm91uifvd9hratehf65k%40group.calendar.google.com', 'magiccookie' => 'public'),		// IT勉強会カレンダー
		),

	 	// カレンダー取得範囲
		'period' => "+7 day",
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 日付範囲を決定
		$start_date = date('Y-m-d', time());
		$end_date = date('Y-m-d', strtotime($this->config['period'], strtotime($start_date . ' 00:00:00')) - 1);

		// スケジュールを取得して、それを発言
		$msg = $this->getSchedule($start_date, $end_date);
		$this->postMessage($msg);
	}

	/**
	 * Googleカレンダー情報取得
	 * GoogleカレンダーのAPIにアクセスし、その情報を整形して返す
	 * @param string start_date 取得範囲開始日(Y-m-d形式)
	 * @param string end_date 取得範囲終了日(Y-m-d形式)
	 * @return string テキスト形式になった全てのカレンダー情報
	 */
	private function getSchedule($start_date, $end_date)
	{
		if ($start_date == $end_date) {
			$date_str = $start_date;
		} else {
			$date_str = $start_date . ' 〜 ' . $end_date;
		}
		$msg = "******** 予定 : $date_str ********\n";
		$msg .= "\n";

		// カレンダー定義の数だけ繰り返す
		foreach ($this->config['calendar'] as $row) {
			$url = 'http://www.google.com/calendar/feeds/'
				. $row['userid']
				. '/' . $row['magiccookie']
				. '/full?'
				. 'start-min=' . $start_date . 'T00:00:00'
				. '&start-max=' . $end_date . 'T23:59:59'
				. '&orderby=starttime&sortorder=a&singleevents=true';
			$xml = simplexml_load_file($url);

			// テキスト形式にして格納
			$msg .= $this->xml2Text($xml);
		}

		return $msg;
	}

	/**
	 * XML形式->Text変換
	 * Googleカレンダーから取得したXMLをテキスト形式に変換して返す
	 * @param object $xml Googleカレンダーから取得したXMLオブジェクト
	 * @return string テキスト形式に変換されたカレンダー情報
	 */
	private function xml2Text($xml)
	{
		// カレンダーのタイトル
		$msg = '# ' . $xml->title . "\n";

		// 予定の数だけ繰り返し
		foreach ($xml->entry as $item) {
			$gd = $item->children('http://schemas.google.com/g/2005');
			$msg .= sprintf(" - (%s～%s) %s\n", date('m/d H:i', strtotime($gd->when->attributes()->startTime)), date('H:i', strtotime($gd->when->attributes()->endTime)), $item->title);
			if (isset($item->content) and '' != $item->content) {
				$msg .= strip_tags($item->content) . "\n";
			}
		}
		$msg .= "\n";

		return $msg;
	}
}

