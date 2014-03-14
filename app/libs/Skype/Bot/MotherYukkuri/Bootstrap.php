<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * SkypeBot起動
 * 各種プラグインを読み込んでSkypeBotを起動する
 * 
 * @package		MotherYukkuri
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @license		BSD
 * @link		https://github.com/ketaiorg/MotherYukkuri
 **/

// ボットの起動
$wb = new Skype_Bot_Bootstrap();
$wb->run(SKYPE_BOT_ID);		// 定数SKYPE_BOT_IDはappConfig.phpで定義されている


/**
 * SkypeBot起動クラス
 */
class Skype_Bot_Bootstrap
{
	/**
	 * コンストラクタ
	 */
	public function __construct()
	{
		// SkypeBotの初期化（相対パスで指定）
		require_once('Init.php');
		Skype_Bot_Init::init();
	}

	/**
	 * 実行
	 */
	public function run($skype_bot_id)
	{
		echo "[SkypeBot] Start.\n";

		// SkypeBotの初期化
		$bot = new Skype_Bot($skype_bot_id, DEBUG_MODE);

		// プラグインの読み込み
		try {
			$this->loadPlugin($bot);
		} catch (Exception $e) {
			fwrite(STDERR, $e->getMessage() . ".\n");
			exit(1);
		}

		// ボット実行開始（C-cなどで停止させるまで動き続ける）
		$bot->run();
	}

	/**
	 * プラグインの読み込み
	 * @param class $bot Skype_Botクラス
	 * @throws Exception ディレクトリのオープンに失敗
	 */
	protected function loadPlugin($bot)
	{
		$plugin_path = PLUGIN_DIR;
		if ($handle = opendir($plugin_path)) {
			// プラグインディレクトリの中のファイルを順に読み込み
			while (false !== ($file = readdir($handle))) {
				if (!is_file($plugin_path . $file) or '.php' !== substr($file, -4)) {
					// 拡張子が.phpのファイルのみを対象とする、それ以外はスキップ
					continue;
				}
				$basename = basename($file, '.php');

				// プラグインの設定ファイルを読み込み
				$config_value = $this->loadPluginConfig($basename);

				if (isset($config_value['plugin_is_disabled']) and true === $config_value['plugin_is_disabled']) {
					// 設定で無効されている場合はスキップ
					if (DEBUG_MODE) {
						printf("Skip plugin: %s\n", $basename);
					}
					continue;
				}

				// プラグインの読み込み
				if (DEBUG_MODE) {
					printf("Load plugin: %s\n", $basename);
				}
				require_once($plugin_path . $file);
				$bot->loadPlugin($basename, $config_value);
			}
			closedir($handle);
		} else {
			// プラグインディレクトリのオープンに失敗
			throw new Exception('Can not open plugin directory.');
		}
	}

	/**
	 * プラグイン設定の読み込み
	 * @param string $basename プラグイン名称
	 * @return array 設定値の連想配列
	 */
	protected function loadPluginConfig($basename)
	{
		$config_file = PLUGIN_CONFIG_DIR . $basename . '.config.php';
		$config_value = array();
		if (file_exists($config_file)) {
			// 設定ファイルが存在するなら先に読み込み
			if (DEBUG_MODE) {
				printf("Load plugin config: %s\n", $config_file);
			}
			$config_value = require($config_file);
		}

		return $config_value;
	}
}

