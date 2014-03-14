<?php
/**
 * Skype_Bot_Plugin_MessageServer
 * 指定のポートを待ち受け、メッセージサーバとして機能するプラグイン
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 */

class Skype_Bot_Plugin_MessageServer extends Skype_Bot_MotherYukkuri
{
	/**
	 * プラグインの設定
	 */
	protected $config = array(
		// 標準設定項目
		'plugin_trigger' => '/^$/',		// Skypeは空メッセージを送れないので絶対にマッチしない
		'plugin_usage' => 'Usage: 接続があると実行されます',
		'plugin_info' => '指定のポートで待ち受け、発言サーバとして動作します',

		// 独自設定項目
		'port' => 56786,
		'max_repeat' => 10,
	);

	/**
	 * このプラグインで使う変数を定義
	 */
	protected $sock;		// ソケットリソース


	/**
	 * 初期化処理
	 */
	protected function before()
	{
		// ソケット作成
		$this->sock = @socket_create_listen($this->config['port']);
		if (!is_resource($this->sock)) {
			// ソケット作成失敗（ポーリング設定しないので、事実上このプラグインは動作しないで続行される）
			fputs(STDERR, sprintf("ERROR! Socket open faild. (%s)\n", socket_strerror(socket_last_error())));
			exit(1);
		} else {
			// オプション設定（失敗しても続行）
			if (!socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
				fputs(STDERR, sprintf("ERROR! Socket option set faild. (%s)\n", socket_strerror(socket_last_error())));
			}

			// ノンブロッキングモードで動作
			if (!socket_set_nonblock($this->sock)) {
				fputs(STDERR, sprintf("ERROR! Socket set nonblock faild. (%s)\n", socket_strerror(socket_last_error())));
			} else {
				// ポーリングするようにする
				$this->setPolling(1);		// 1秒間隔で実行
			}
		}
	}

	/**
	 * デストラクタ
	 */
	public function __destruct()
	{
		if (isset($this->sock) and is_resource($this->sock)) {
			// ソケットのクローズ
			socket_close($this->sock);
		}
	}

	/**
	 * 実行メソッド
	 */
	protected function execute()
	{
		// このプラグインはトリガーでは発動しない
	}

	/**
	 * ポーリング処理
	 */
	protected function poll_execute()
	{
		// 指定ポートでの待ち受けを行う
		$this->listen();
	}

	/**
	 * ソケット待ち受け処理
	 */
	protected function listen()
	{
		try {
			// 通信が来てないかチェック
			$clientsock = @socket_accept($this->sock);
			$buf = @socket_read($clientsock, 10240, PHP_NORMAL_READ);
			if (false !== $buf and '' != $buf) {
				// 通信が来ている
				list($topic, $message) = $this->parseBuffer($buf);
				if (isset($topic) and isset($message)) {
					// チャットへ発言し正常コードを応答（対象チャットが見つからない場合はSkype_Exceptionが発生）
					$this->send($topic, $message);
					socket_write($clientsock, "200\n", 4);
				} else {
					// コマンドは受け付けられなかった
					socket_write($clientsock, "500\n", 4);
				}
			}
		} catch (Skype_Exception $skype_e) {
			// Skype例外（大抵の場合、チャット名が間違っていて取得できなかった）
			fputs(STDERR, 'ERROR! Catched Skype_Exception = ' . $skype_e->getMessage() . "\n");
			socket_write($clientsock, "501\n", 4);
		} catch (Exception $e) {
			// それ以外の例外は標準エラー出力を吐いて続行
			fputs(STDERR, 'ERROR! Catched Exception = ' . $e->getMessage() . "\n");
		}

		// 必要があればクライアントのソケットはクローズ
		if (is_resource($clientsock)) {
			socket_close($clientsock);
		}
	}

	/**
	 * 通信バッファ解析
	 * @param string $buf 通信バッファ
	 * @return array array('Skypeトピック名', 'メッセージ内容')
	 */
	protected function parseBuffer($buf)
	{
		$topic = $message = null;

		$buf = trim($buf);
		$command_len = strpos($buf, ' ');
		$command = substr($buf, 0, $command_len);
		$body = substr($buf, $command_len + 1);
		
		// コマンドで分岐
		if ('SENDMESSAGE' == $command) {
			// 発言コマンドの場合
			$topic_len = strpos($body, ' ');
			$topic = base64_decode(substr($body, 0, $topic_len));
			$message = base64_decode(substr($body, $topic_len + 1));
		}

		return array($topic, $message);
	}

	/**
	 * 発言
	 * @param string $topic 対象Skypeトピック名
	 * @param string $message 発言内容
	 * @exception SkypeException
	 */
	protected function send($topic, $message)
	{
		// トピック名からチャットを検索
		$target_chatlist = $this->getChatIdsByTopic($topic, $this->config['max_repeat']);

		// 対象となるチャット情報を取得
		if (isset($target_chatlist[0])) {
			$chat_name = $target_chatlist[0];		// 対象が複数個ある場合は先頭を利用
		} else {
			$chat_name = '';
		}

		// 発言（対象チャットが無い場合はSkypeExceptionが発生）
		$chat_target = $this->getInstanceById($chat_name);
		$chat_target->postMessage($message);
	}
}
