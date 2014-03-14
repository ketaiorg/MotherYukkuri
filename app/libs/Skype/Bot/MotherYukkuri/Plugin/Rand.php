<?php
/**
 * Skype_Bot_Plugin_Rand
 * 指定された範囲の中で乱数を答えるプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Rand extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:rand/',
		'plugin_usage' => 'Usage: :rand NUM1 [NUM2]',
		'plugin_info' => '指定された範囲の中で乱数を答えます',

		// 独自設定項目
		'answer_format' => '[Rand] %d',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 引数を取得
		$body = $this->getBodyWithoutCommand();
		if (preg_match('/^([0-9]+)(\ ([0-9]+))?$/', $body, $matches)) {
			if (isset($matches[1])) {
				$from = $matches[1];
			}
			if (isset($matches[3])) {
				$to = $matches[3];
			}
		}
		if (isset($from) and !isset($to)) {
			// 引数が1つの場合、fromは1とする
			$to = $from;
			$from = 1;
		} elseif (!isset($from) or !isset($to)) {
			// 引数の値が不正な場合、Usageを出力して終了
			$this->postMessage($this->config['plugin_usage']);
			return;
		}
		if ($from > $to) {
			// fromよりtoが大きいようであれば入れ替え
			$tmp = $from;
			$from = $to;
			$to = $tmp;
		}

		// 乱数を取得
		$rand_num = mt_rand($from, $to);

		// 出力
		$msg = sprintf($this->config['answer_format'], $rand_num);
		$this->postMessage($msg);
	}
}

