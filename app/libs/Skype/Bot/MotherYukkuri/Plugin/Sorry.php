<?php
/**
 * Skype_Bot_Plugin_Sorry
 * 謝罪の言葉を出力します
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_Sorry extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^\:sorry$/',
		'plugin_usage' => 'Usage: :sorry',
		'plugin_info' => '素直になれないあなたに代わって謝罪の言葉を述べます',

		// 独自設定項目
		'answer_list' => array(
			'ごめんなさい・・・', 
			'ごめんなさい！！', 
			'大変申し訳無い。。。', 
			'大変申し訳無い！', 
			'ごめんね！！（てへぺろ）', 
			'失礼しました。', 
			'許して！！', 
			'すまん！！', 
			'ソーリーwwwwww', 
			'すまぬ m(_ _)m', 
			'ごめんね ^^;', 
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

