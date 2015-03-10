<?php
/**
 * Skype_Bot_Plugin_Jr
 * JRの運行情報を取得して発言するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Jr extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:jr( .+)?$/',
		'plugin_usage' => 'Usage: :jr',
		'plugin_info' => 'JRの運行情報を表示します',

		// 対象URL定義
		'target_url' => 'http://mobile.jrhokkaido.co.jp/webunkou/area.asp?a=1',
		'source_encoding' => 'SJIS',
		'start_mark' => '北海道医療大学間',
		'end_mark' => '※上記情報は自動的には更新されませんので、継続してご覧になる場合は',
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

		$msg = html_entity_decode($html);						// エンティティを戻す
		$msg = strtr($msg, array("<HR SIZE=1 NOSHADE COLOR=#E6E6FA>" => "%%BR%%", "<BR>" => "%%BR%%"));
		$msg = trim(strip_tags($msg));							// タグを削除
		$msg = strtr($msg, array("\r" => "", "\t" => ""));		// 改行とタブを削除
		$msg = strtr($msg, array("\n\n" => "", "  " => ""));	// 連続する改行とスペースを削除
		$msg = strtr($msg, array("%%BR%%" => "\n"));
		$msg .= "\n\n" . $this->config['target_url'];

		// 発言
		$this->postMessage($msg);
	}
}

