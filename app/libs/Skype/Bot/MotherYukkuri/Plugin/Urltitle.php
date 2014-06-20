<?php
/**
 * Skype_Bot_Plugin_Urltitle
 * URLが貼られた場合、そのタイトルを取得し発言する
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Urltitle extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/.+/',		// 発言がある度に起動
		'plugin_usage' => 'Usage: URLを含む文字列を発言すると実行されます',
		'plugin_info' => 'URLが書き込まれた際に、そのタイトルを取得し発言します',
		
		// 独自設定項目
		'default_encoding' => 'UTF-8',		// デフォルトの文字コード
		'format' => "title: [%s]",			// 出力フォーマット
		'encoding_list' => 'UTF-8,Shift-JIS,EUC-JP,JIS',		// 検出エンコーディングの順番
		'read_max_length' => 102400,		// 最大読み込みバイト数
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		$msg = '';
		$body = $this->getBody();

		if (preg_match_all('/https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+/', $body, $url_matches)) {
			// URLが含まれている場合、URLの数だけ繰り返し
			foreach ($url_matches[0] as $key => $url) {
				// ページ内容を取得
				$contents = @file_get_contents($url, false, null, -1, $this->config['read_max_length']);
				$contents = strtr($contents, array("\n" => '', "\r" => ''));		// 改行は取り除く

				// 文字エンコーディングを取得
				$encoding = $this->getEncoding($contents);

				// タイトルを取得
				$title = $this->getTitle($contents, $encoding);

				// 発言内容を格納
				if ('' != $title) {
					$msg .= sprintf($this->config['format'], $title) . "\n";
				}
			}
		}

		if ('' != $msg) {
			$this->postMessage($msg);
		}
	}

	/**
	 * 文字コード取得
	 * @param string $contents 取得したHTML（改行カット済）
	 * @return string エンコーディング
	 */
	protected function getEncoding($contents)
	{
		// metaタグから文字コードを取得
		if (preg_match('/<meta .*?content=(\'|")text\/html; ?charset=(.+?)(\'|")/i', $contents, $charset_matches)) {
			$encoding = $charset_matches[2];
		} else {
			$encoding = mb_detect_encoding($contents, $this->config['encoding_list']);
			if (false === $encoding) {
				$encoding = 'UTF-8';
			}
		}

		return $encoding;
	}

	/**
	 * タイトル取得
	 * titleタグからタイトルを取得する
	 * @param string $contents 取得したHTML（改行カット済）
	 * @param string $encoding エンコーディング
	 * @return string タイトルの文字列（取得できなかった場合はnull）
	 */
	protected function getTitle($contents, $encoding)
	{
		$title = null;
		if (preg_match('/<title>(.+?)<\/title>/i', $contents, $title_matches)) {
			$title = mb_convert_encoding(html_entity_decode($title_matches[1], ENT_COMPAT | ENT_HTML401, $encoding), $this->config['default_encoding'], $encoding);
		}

		return $title;
	}
}

