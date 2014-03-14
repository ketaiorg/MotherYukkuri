<?php
/**
 * Skype_Bot_Plugin_Thanks
 * 感謝の言葉を出力します
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Thanks extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:thanks$/',
		'plugin_usage' => 'Usage: :thanks',
		'plugin_info' => '照れ屋なあなたに代わって感謝の言葉を述べます',

		// 独自設定項目
		'answer_list' => array(
			'ありがとう！', 
			'どうもどうも！！', 
			'ありがとう。。。（ポッ）', 
			'かたじけない！', 
			'ありがとう！！(^^)', 
			'ありがとうございました。', 
			'感謝！！', 
			'サンクスwwwwww', 
			'感謝 m(_ _)m', 
			'どうも～ ^^;', 
		),
	);

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		$rand = array_rand($this->config['answer_list']);
		$this->postMessage($this->config['answer_list'][$rand]);
	}
}

