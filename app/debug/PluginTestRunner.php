#!/usr/bin/php
<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * プラグインテスト実行用プラグラム
 * 指定されたプラグインをSkype経由以外でも動くようにし、テストを容易にするためのもの
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

// 引数からプラグイン名称を取得
if (isset($argv[1])) {
	$plugin_name = $argv[1];
} else {
	fwrite(STDERR, 'Usage: echo "chat_messages" | ./PluginTestRunner.php PLUGIN_NAME' . "\n");
	exit(1);
}

// 標準入力がある場合はそれを取得し発言として扱う
$chat_message = rtrim(file_get_contents('php://stdin'));

// 起動
$tr = new PluginTestRunner($plugin_name);
$tr->setChatMessage($chat_message);
$tr->run();


/**
 * プラグインテスト実行クラス
 */
class PluginTestRunner
{
	/**
	 * チャット情報のクラス変数
	 */
	protected $plugin_name;				// 対象となるプラグイン名
	protected $chat_message;			// 対象となる発言

	/**
	 * コンストラクタ
	 * @param string $plugin_name プラグイン名
	 */
	public function __construct($plugin_name)
	{
		// SkypeBotの初期化
		define('APPPATH', realpath(dirname(__FILE__) . '/../') . '/');
		require_once(APPPATH . 'config/appConfig.php');

		// デバッグ用ファイルの読み込み
		require_once(APPPATH . 'debug/debugFunctions.php');

		// プラグイン名のセット
		$this->plugin_name = $plugin_name;
	}

	/**
	 * チャットメッセージセット
	 * @param string $chat_message チャットメッセージ
	 */
	public function setChatMessage($chat_message)
	{
		print 'Set chat message: ' . chop($chat_message) . "\n";
		$this->chat_message = $chat_message;
	}

	/**
	 * 実行
	 */
	public function run()
	{
		// 対象プラグインの読み込み
		try {
			$config_value = $this->loadPlugin($this->plugin_name);
		} catch (Exception $e) {
			fwrite(STDERR, $e->getMessage() . ".\n");
			exit(1);
		}

		// 対象プラグインのインスタンスを作成
		$class_name = 'Skype_Bot_Plugin_' . $this->plugin_name;
		$pl = new $class_name();

		// 設定値の上書き
		$pl->mockSetConfig($config_value);
		$current_config = $pl->mockGetConfig();

		// プラグイン情報の出力
		print ' - ' . trim(strtr($current_config['plugin_usage'], array('Usage:' => ''))) . ' | ' . $current_config['plugin_info'] . "\n";

		// モッククラス変数の用意
		$pl->mockSetProperty($this->chat_message);

		// 事前処理実行
		$pl->callBefore();

		// 実行
		if ($pl->poll) {
			// ポーリング型の場合
			print "============ START (C-c to stop) ============\n";
			while (1) {
				$pl->poll(time(), time() - 1);
				sleep(1);
			}
		} else {
			// トリガー型の場合
			if (!preg_match($current_config['plugin_trigger'], $this->chat_message)) {
				fwrite(STDERR, "トリガーにマッチする文字列がありませんでした。 trriger=" . $current_config['plugin_trigger'] . "\n");
				exit(1);
			}

			// executeメソッドの実行
			print "============ START ============\n";
			print "[Foo]: " . chop($this->chat_message) . "\n";
			$refm = new ReflectionMethod($pl, 'execute');
			$refm->setAccessible(true);
			$refm->invoke($pl);
			print "============  END  ============\n";
		}
	}

	/**
	 * プラグインの読み込み
	 * @param string $plugin_name プラグイン名
	 * @return array 設定値の連想配列
	 * @throws Exception ディレクトリのオープンに失敗
	 */
	protected function loadPlugin($plugin_name)
	{
		// プラグインの設定ファイルを読み込み
		$config_value = $this->loadPluginConfig($plugin_name);

		// プラグインの読み込み
		$plugin_file = PLUGIN_DIR . $plugin_name . '.php';
		printf("Load plugin: %s\n", $plugin_name);
		if (file_exists($plugin_file)) {
			require($plugin_file);
		} else {
			// プラグインのオープンに失敗
			throw new Exception(sprintf('Can not open plugin file. (%s)', $plugin_file));
		}

		return $config_value;
	}

	/**
	 * プラグイン設定の読み込み
	 * @param string $plugin_name プラグイン名称
	 * @return array 設定値の連想配列
	 */
	protected function loadPluginConfig($plugin_name)
	{
		$config_file = PLUGIN_CONFIG_DIR . $plugin_name . '.config.php';
		$config_value = array();
		printf("Load plugin config: %s\n", $config_file);
		if (file_exists($config_file)) {
			// 設定ファイルが存在するなら先に読み込み
			$config_value = require($config_file);
		} else {
			printf("Plugin config is not found: %s\n", $config_file);
		}

		return $config_value;
	}
}

/**
 * 処理を通すためのモッククラス
 **/
class Skype_Bot_MotherYukkuri
{
	const VERSION = '-';

	// チャット情報格納用
	protected $skype;
	protected $chat;
	protected $chatmessage;

	// ポーリング制御
	public $poll = false;
	protected $polling_interval = 0;
	protected $polling_ts_prev = 0;

	/**
	 * テスト用configセット
	 * protectedなconfigに値をセットするためのメソッド
	 */
	public function mockSetConfig($config_value)
	{
		// 設定値の上書き
		foreach ($config_value as $key => $value) {
			$this->config[$key] = $value;
		}
	}

	/**
	 * テスト用config取得
	 * protectedなconfigに値を取得するためのメソッド
	 */
	public function mockGetConfig()
	{
		return $this->config;
	}

	/**
	 * テスト用クラス変数セット
	 * 動作に必要なダミークラス変数を用意する
	 */
	public function mockSetProperty($chat_message)
	{
		$this->skype = new Skype();
		$this->chat = new Skype_Chat();
		$this->chatmessage = new Skype_ChatMessage();
		$this->chatmessage->mockSetBody($chat_message);
	}

	/**
	 * 事前実行メソッド呼び出し
	 */
	public function callBefore()
	{
		$this->before();
	}

	/**
	 * 事前実行メソッド
	 */
	protected function before()
	{
	}

	/**
	 * ポーリング処理
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
	 * トピック名からチャット名を検索
	 * @param string $search_str トピック文字列（の一部）
	 * @param int $poll_max_retry リトライ回数
	 * @return array 対象のチャットIDを格納した配列
	 */
	public function getChatIdsByTopic($search_str, $poll_max_retry = 3)
	{
		return array('#foo', '#bar', '#baz');
	}

	/**
	 * インスタンス取得
	 * @param string $chat_id チャットID文字列
	 * @return class Skype_Bot_MotherYukkuriクラス
	 */
	public function getInstanceById($chat_id)
	{
		return $this;
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

/**
 * 処理を通すためのモッククラス
 **/
class Skype
{
	/**↵
	 * ユーザインスタンス取得
	 * @param string $user_id SkypeユーザID
	 **/
	public function getUser($user_id)
	{
		return new Skype_User($user_id);
	}

	/**↵
	 * 自分自身のIDを取得
	 **/
	public function getCurrentUserHandle()
	{
		return 'skypebot';
	}

	/**↵
	 * チャットリスト取得
	 **/
	public function getChatList()
	{
		return array();
	}
}

/**
 * 処理を通すためのモッククラス
 **/
class Skype_User
{
	/**↵
	 * 取得する
	 * @param string $target 取得する対象
	 **/
	public function get($target)
	{
		$ret = null;

		switch ($target) {
		case 'BIRTHDAY':
			$ret = '20010401';
			break;

		case 'FULLNAME':
			$ret = 'テストフルネーム';
			break;
		}

		return $ret;
	}
}

/**
 * 処理を通すためのモッククラス
 **/
class Skype_Chat
{
	/**↵
	 * 発言する
	 * @param string $message 発言文字列
	 **/
	public function invokeChatmessage($message)
	{
		print "[SkypeBot]: $message\n";
	}

	/**↵
	 * 取得する
	 * @param string $target 取得する対象
	 **/
	public function get($target)
	{
		$ret = null;

		switch ($target) {
		case 'MEMBERS':
			$ret = array('foo bar buz');
			break;
		}

		return $ret;
	}
}

/**
 * 処理を通すためのモッククラス
 **/
class Skype_ChatMessage
{
	protected $body;

	/**↵
	 * BODYセット
	 * @param string $body BODY
	 **/
	public function mockSetBody($body)
	{
		$this->body = $body;
	}

	/**↵
	 * 取得する
	 * @param string $target 取得する対象
	 **/
	public function get($target)
	{
		$ret = null;

		switch ($target) {
		case 'BODY':
			$ret = $this->body;
			break;
		}

		return $ret;
	}
}

