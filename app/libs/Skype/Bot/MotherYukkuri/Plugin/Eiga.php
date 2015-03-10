<?php
/**
 * Skype_Bot_Plugin_Eiga
 * 映画の上映情報を取得して発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Eiga extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:eiga( .+)?$/',
		'plugin_usage' => 'Usage: :eiga',
		'plugin_info' => '映画館の上映情報を表示します',

		// 対象URL定義
		'title' => 'ユナイテッドシネマの上映情報',
		'target_url' => 'http://www.eigakan.org/theaters/area/0101',
		'source_encoding' => 'UTF-8',
		'start_mark' => '0570-78-3011',
		'end_mark' => '<th class="thater_check01">札幌</th><th class="thater_check02"><a href="http://theaterkino.net/" target="theater">札幌シアターキノ</a>',
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// 対象ページを取得
		$html = mb_convert_encoding(file_get_contents($this->config['target_url']), 'UTF-8', $this->config['source_encoding']);

		// 切り出し処理
		if ('' != $this->config['start_mark']) {
			$tmp_html = mb_substr(strstr($html, $this->config['start_mark']), mb_strlen($this->config['start_mark']));
			if ('' != $tmp_html) {
				$html = $tmp_html;
			}
		}
		if ('' != $this->config['end_mark']) {
			$tmp_html = strstr($html, $this->config['end_mark'], true);
			if ('' != $tmp_html) {
				$html = $tmp_html;
			}
		}

		$msg = $this->config['title'] . "\n";
		$msg .= html_entity_decode($html);						// エンティティを戻す
		$msg = strtr($msg, array('<tr><td>' => "\n- "));		// 読みやすくする
		$msg = trim(strip_tags($msg));							// タグを削除
		$msg .= "\n\n" . $this->config['target_url'];

		// 発言
		$this->postMessage($msg);
	}
}

