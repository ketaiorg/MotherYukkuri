<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * MotherYukkuri - SkypeBotPlugin拡張クラス
 * SkypeBotPluginクラスを拡張しプラグインの共通処理を記述したクラス、各プラグインの基底クラスとなる
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

class Skype_Bot_MotherYukkuri extends Skype_Bot_Plugin
{
	// バージョン
	const VERSION = 'MotherYukkuri-1.0.0';

	// チャット情報格納用
	protected $chat;
	protected $chatmessage;
	protected $chatmessage_id;
	protected $property;
	protected $value;

	// ループ防止用
	protected $prev_chat_name;
	protected $prev_chat_body;
	protected $prev_chat_time;

	// ポーリング制御
	protected $polling_interval = 0;
	protected $polling_ts_prev = 0;

	// 設定項目デフォルト値
	protected $config = array();
	protected $config_default = array(
		'plugin_trigger' => '',							// 起動のきっかけとなる文字列を正規表現で定義
		'plugin_usage' => '',							// ヘルプで表示されるUsage文字列を定義
		'plugin_info' => '',							// ヘルプで表示される説明書きを定義
		'plugin_is_disabled' => false,					// プラグインを無効にする
		'plugin_ignore_edited_messages' => true,		// 修正した発言を無視するか
		'plugin_ignore_self_messages' => true,			// 自分自身の発言を無視するか（無限ループ防止の意味もあるのでfalseにするときは注意）
		'plugin_loop_block_time' => 5,					// ループ防止のために同じ発言をブロックする時間（秒で指定）
		'plugin_sleep_time' => 1000000,					// 万が一ループした際に被害を抑えるためのスリープ時間（マイクロ秒で指定）
	);

	/**
	 * コンストラクタ
	 * @param class $skypebot SkypeBotクラス
	 * @param array $parameter loadPluginする際に渡されたパラメータ
	 */
	public function __construct($skype_bot, $parameter)
	{
		// 基底クラスのコンストラクタを呼び出し
		parent::__construct($skype_bot, $parameter);

		// 子クラスで未設定の項目はデフォルト値で上書き
		foreach ($this->config_default as $key => $value) {
			if (!isset($this->config[$key])) {
				$this->config[$key] = $value;
			}
		}

		if (!empty($parameter)) {
			// パラメータがセットされている場合、それで同名の設定用クラス変数を上書きしていく
			foreach ($parameter as $key => $val) {
				$this->config[$key] = $val;
			}
		}

		// 事前実行メソッドを読み込み
		$this->before();
	}

	/**
	 * 事前実行メソッド（オーバーライド用）
	 */
	protected function before()
	{
	}

	/**
	 * ポーリング処理メソッド（オーバーライド用）
	 */
	protected function poll_execute()
	{
	}

	/**
	 * ポーリング処理
	 * @param int $ts タイムスタンプ（ポーリング管理用）
	 * @param int $ts_prev 前のタイムスタンプ（ポーリング管理用）
	 */
	public function poll($ts, $ts_prev)
	{
		if ($ts - $this->polling_ts_prev >= $this->polling_interval) {
			$this->polling_ts_prev = $ts;
			$this->poll_execute();
		}
	}


	/**
	 * chatmessageイベントハンドラ
	 * @param class $chatmessage 受信したメッセージのオブジェクト
	 * @param string $chatmessage_id チャットメッセージID
	 * @param string $property 「変化のあった」プロパティ
	 * @param string $value 「変化のあった」値
	 */
	public function handleChatmessage($chatmessage, $chatmessage_id, $property, $value)
	{
		// スキップの判別
		if ($this->isSkipEvent($chatmessage, $chatmessage_id, $property, $value)) {
			return;
		}

		// チャット名と本文を取得
		$chat_name = $chatmessage->get('CHATNAME');
		$body = $chatmessage->get('BODY');

		// イベントから渡ってきた情報をクラス変数に格納
		$this->chatmessage = $chatmessage;
		$this->chatmessage_id = $chatmessage_id;
		$this->property = $property;
		$this->value = $value;

		// カレントとなるチャット情報を取得しクラス変数に格納
		$this->chat = $this->skype->getChat($chat_name);

		if (':help' === $body) {
			// ヘルプコマンドが実行された
			$this->recieveHelpCommand();
			return;
		}

		// IDとトピックによるフィルタリング
		if (!$this->_chatFilter($this->chat)) {
			// 何もしない
			return;
		}

		// トリガーの判定
		if ('' != $this->config['plugin_trigger'] and preg_match($this->config['plugin_trigger'], $body)) {
			// トリガーの条件にマッチする場合

			// 前回と同じ発言内容で、かつ指定秒数以内だった場合は何もしないで抜ける
			if (isset($this->prev_chat_name) and $this->prev_chat_name == $chat_name and $this->prev_chat_body == $body and time() < $this->prev_chat_time + $this->config['plugin_loop_block_time']) {
				// ループチェックに引っかかった
				return;
			}

			// ループチェック用に記録
			$this->prev_chat_name = $chat_name;
			$this->prev_chat_body = $body;
			$this->prev_chat_time = time();

			// 無限ループ時の被害を防ぐためスリープ
			usleep($this->config['plugin_sleep_time']);

			// プラグインが発動したので、インターフェイス用メソッドを実行
			$this->execute();
		}
	}

	/**
	 * イベントスキップ判定
	 * @param class $chatmessage 受信したメッセージのオブジェクト
	 * @param string $chatmessage_id チャットメッセージID
	 * @param string $property 「変化のあった」プロパティ
	 * @param string $value 「変化のあった」値
	 * @return bool true:スキップする, false:スキップしない
	 */
	protected function isSkipEvent($chatmessage, $chatmessage_id, $property, $value)
	{
		if ($property != 'BODY' && $property != 'STATUS') {
			return true;
		}

		if ($property == 'STATUS' && ($value == Skype_Chatmessage::status_read || $value == Skype_Chatmessage::status_sending)) {
			return true;
		}

		if (false !== $this->config['plugin_ignore_edited_messages'] and '0' < $chatmessage->get('EDITED_TIMESTAMP')) {
			// 修正されたメッセージはスキップする
			return true;
		}

		if (false !== $this->config['plugin_ignore_self_messages'] and $chatmessage->get('FROM_HANDLE') === $this->skype->getCurrentUserHandle()) {
			// 自分自身のメッセージはスキップする
			return true;
		}

		return false;
	}

	/**
	 * ヘルプコマンド受信
	 * それぞれのプラグインがinfoの内容を応答する
	 */
	protected function recieveHelpCommand()
	{
		$info = $this->config['plugin_info'];
		if (!$this->_chatFilter($this->chat)) {
			$info .= ' (# このチャットでは使用できません)';
		}
		$this->chat->invokeChatmessage(' - ' . trim(strtr($this->config['plugin_usage'], array('Usage:' => ''))) . ' | ' . $info);
	}

	/**
	 * チャット取得
	 * @return class クラス変数$chatを返す
	 */
	public function getChat()
	{
		return $this->chat;
	}

	/**
	 * チャットメッセージ取得
	 * @return class クラス変数$chatmessageを返す
	 */
	public function getChatMessage()
	{
		return $this->chatmessage;
	}

	/**
	 * チャットメッセージID取得
	 * @return class クラス変数$chatmessage_idを返す
	 */
	public function getChatMessageId()
	{
		return $this->chatmessage_id;
	}

	/**
	 * プロパティ取得
	 * @return class クラス変数$propertyを返す
	 */
	public function getProperty()
	{
		return $this->property;
	}

	/**
	 * value取得
	 * @return class クラス変数$valueを返す
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Skypeクラス取得
	 * @return class クラス変数$skypeを返す
	 */
	public function getSkype()
	{
		return $this->skype;
	}

	/**
	 * ポーリング設定
	 * @param int $interval 実行間隔（秒）、0以下を指定した場合はポーリングを停止
	 */
	public function setPolling($interval)
	{
		if ($interval < 1) {
			// ポーリング停止
			$this->poll = false;
		} else {
			// ポーリング設定
			$this->poll = true;
			$this->polling_interval = $interval;
		}
	}

	/**
	 * BODY取得
	 * @return string BODY文字列
	 */
	public function getBody()
	{
		return $this->chatmessage->get('BODY');
	}

	/**
	 * コマンド部分を除いたBODY取得
	 * @return string コマンド部分を除いたBODY文字列
	 */
	public function getBodyWithoutCommand()
	{
		$body = $this->getBody();

		return substr($body, strpos($body, ' ') + 1);
	}

	/**
	 * コマンド引数取得
	 * スペースで区切られたプラグインコマンドの引数を配列で取得
	 * @return array プラグインコマンドの引数
	 */
	public function getCommandArgument()
	{
		// BODYをスペース区切りとして分解
		$tmp_arr = explode(' ', $this->getBODY());

		// 配列の先頭はコマンドが格納されているはずなので捨てる
		array_shift($tmp_arr);

		return $tmp_arr;
	}

	/**
	 * メンバー取得
	 * @return array メンバー名を格納した配列
	 */
	public function getMembers()
	{
		// チャット内のメンバーを取得↵
		$tmp_members = $this->chat->get('MEMBERS');
		$members = explode(' ', $tmp_members[0]);

		return $members;
	}

	/**
	 * メッセージ書き込み
	 * @param string $msg 書き込むメッセージ
	 */
	public function postMessage($msg)
	{
		$this->chat->invokeChatmessage($msg);
	}

	/**
	 * トピック名からチャットを検索
	 * 同期が追いついていない場合は取得できない可能性がある
	 * リトライ回数を増やすことで取得しやすくできるが、
	 * 存在しないトピック名を指定した場合のレスポンスが落ちる
	 * @param string $search_str トピック文字列（の一部）
	 * @param int $poll_max_retry リトライ回数
	 * @return array 対象のチャットIDを格納した配列
	 */
	public function getChatIdsByTopic($search_str, $poll_max_retry = 3)
	{
		$return_arr = array();

		// 同期が追いついていない可能性があるので、トピック名が取れるまで繰り返して待つ
		for ($i = 0; $i < $poll_max_retry; $i++) {
			$chat_list = $this->skype->getChatList();
			foreach ($chat_list as $chat) {
				$topic = $chat->get('TOPIC');
				if (false !== strpos($topic , $search_str)) {
					// 検索対象が見つかったので、そのチャットIDを返す
					$return_arr[] = $chat->getId();
				}
			}
			if (!empty($return_arr)) {
				// 対象が取れている場合はそこでループ終了
				break;
			}

			// 同期を待つ
			$this->skype->poll(1);
		}

		return $return_arr;
	}

	/**
	 * インスタンス取得
	 * @param string $chat_id チャットID文字列
	 * @return class Skype_Bot_MotherYukkuriクラス
	 */
	public function getInstanceById($chat_id)
	{
		// 空のクラスを作る
		$instance = new Skype_Bot_MotherYukkuri($this->skype_bot, array());

		// 必要なクラス変数をセット
		$instance->skype = $this->skype;
		$instance->chat = $this->skype->getChat($chat_id);

		return $instance;
	}

	/**
	 * ユーザプロパティ取得
	 * @param string $skypename Skype名（SkypeユーザID文字列）
	 * @param string $property プロパティを示す定義文字列
	 * @return string プロパティ文字列
	 */
	public function getUserProperty($skypename, $property)
	{
		return $this->skype->getUser($skypename)->get($property);
	}
}

