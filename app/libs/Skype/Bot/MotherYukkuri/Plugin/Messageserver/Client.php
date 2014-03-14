#!/usr/bin/php
<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Skypeメッセージ送信
 * Skypeメッセージサーバに接続してメッセージを送信する
 * （このファイルはdefine部分のコメントアウトを外すことでパッケージから独立してスタンドアロンでメッセージ送信プログラムとして使うことができる）
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

// メッセージサーバ設定（スタンドアロンで使う場合はコメントアウトを外す）
//define('MESSAGE_SERVER_ADDRESS', '127.0.0.1');
//define('MESSAGE_SERVER_PORT', 56786);

// パラメータを取得
$topic = $message = '';
if (isset($argv[1])) {
	$topic = $argv[1];
}
if (isset($argv[2])) {
	$message = $argv[2];
} else {
	// 第二引数が指定されていない場合は標準入力から読み込み
	$message = file_get_contents('php://stdin');
}

// 送信処理を実行
$sm = new Skype_Bot_Message_Client();
$sm->send($topic, $message);

// 正常終了
exit(0);


/**
 * メッセージ送信クライアントクラス
 */
class Skype_Bot_Message_Client
{
	/**
	 * Usage
	 */
	const USAGE = "Usage: SendMessage target_chat_topic [string...]\n";


	/**
	 * 発言
	 * @param string $topic Skypeチャットトピック名
	 * @param string $message 送信メッセージ
	 */
	public static function send($topic, $message)
	{
		// エラーフラグの初期化
		$err_flg = false;
		
		// スタンドアロンで使われているかを判定
		if (!defined('MESSAGE_SERVER_ADDRESS') or !defined('MESSAGE_SERVER_PORT')) {
			// パッケージのconfigを見に行くためSkypeBotの初期化（相対パスで指定）
			require_once(realpath(dirname(__FILE__)) . '/../../Init.php');
			Skype_Bot_Init::init();
			$config = require(PLUGIN_CONFIG_DIR . 'Messageserver.config.php');
			if (isset($config['address'])) {
				$server = $config['address'];
			} else {
				$server = '127.0.0.1';		// 設定が無い場合は自分自身を見る
			}
			$port = $config['port'];
		} else {
			$server = MESSAGE_SERVER_ADDRESS;
			$port = MESSAGE_SERVER_PORT;
		}

		// 対象チャット名を取得
		if (!isset($topic) or '' == $topic) {
			fputs(STDERR, self::USAGE);
			exit(1);
		}

		// 発言内容を取得
		if (!isset($message) or '' == $message) {
			fputs(STDERR, self::USAGE);
			exit(1);
		} else {
			$message = strtr($message, array('\n' => "\n"));
		}

		try {
			// 新しいソケットを作成する
			$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if (!is_resource($sock)) {
				throw new Exception("Socket create error.");
			}

			// 接続先アドレスと接続する
			if (!@socket_connect($sock, $server, $port)) {
				throw new Exception("Socket connect error.");
			}

			// ソケットに書き込み
			$out = sprintf("SENDMESSAGE %s %s\n", base64_encode($topic), base64_encode($message));
			if (false === socket_write($sock, $out)) {
				throw new Exception("Socket write error.");
			}

			// 結果を取得
			if (!socket_recv($sock, $buf, 3, MSG_WAITALL)) {
				throw new Exception("Socket recieve error.");
			} else {
				echo $buf . "\n";
			}
		} catch (Exception $e) {
			fputs(STDERR, sprintf("%s (%s)\n", $e->getMessage(), socket_strerror(socket_last_error())));
			$err_flg = true;
		}

		// ソケットを閉じる
		if (is_resource($sock)) {
			socket_close($sock);
		}

		// エラーの場合はここで終了
		if ($err_flg) {
			exit(1);
		}
	}
}
