![マザーゆっくり](http://ke-tai.org/matsui/software/motheryukkuri-logo1.png)

## このプログラムについて

MotherYukkuriは、PHPで作られたスカイプボットプログラムです。  
チャットによる命令やSkypeの各種イベントを受け取ることで駆動し、機能はプラグイン形式で容易に追加することができます。  
またMessageserverプラグインを使って、外部からボットに発言をさせることができます。  
SkypeAPIとのやり取りは、GREE LabsのPHP Skype API wrapper class (php-skype)を利用しています。  

* @author 松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
* @license BSD
* @link	https://github.com/ketaiorg/MotherYukkuri
* @link https://github.com/fujimoto/php-skype
* @link https://github.com/gree/php-dbus
* @link http://labs.gree.jp/Top/OpenSource/Skype.html (リンク切れ)
* @link http://labs.gree.jp/Top/OpenSource/DBus.html (リンク切れ)



## 動作環境

このプログラムの実行環境にはLinux+PHP5.3以降を推奨しています。  
また動作にはSkypeが起動できるX環境が必要です。  
なお64bit環境では、PHP-DBusがSegmentation faultで異常終了することがあるようです。  



## セットアップ

Ubuntu13.10へのインストールを例に説明します。  


### PHPをインストールします  
このプログラムはPHPで書かれており実行に必要です。  
`$ sudo apt-get install php5-cli php5-curl php5-dev`  


### PHP-DBusをインストールします  

先に依存するパッケージlibdbus-1-devをインストールします。  
`$ sudo apt-get install libdbus-1-dev`  

続いてPHP-DBusをダウンロードします。  
`$ git clone https://github.com/gree/php-dbus`  

PHP5.4以降を使用している場合は、以下のように修正します。（zend\_という記述を追加）  
`$ cd php-dbus`  
`$ vi dbus.c`  

    37c37  
    < static function_entry dbus_functions[] = {  
    ---  
    > static zend_function_entry dbus_functions[] = {  

インストールを行います。  
`$ phpize`  
`$ ./configure`  
`$ make`  
`$ sudo make install`  

PHPの設定ファイルを編集し、dbusを有効にします。  
`$ sudo vi /etc/php5/cli/php.ini`  

次の記述を追加。

    extension=dbus.so


### MotherYukkuriの設置

GitHubからMotherYukkuriをcloneします。  
`$ git clone https://github.com/ketaiorg/MotherYukkuri.git`  


### submoduleの更新

vendors/php-skypeとなるようにPHP Skype API wrapper classを設置します。  
前項でgit cloneによって設置した場合は、submoduleとして登録されていますので以下のコマンドで設置できます。  
`$ cd MotherYukkuri`  
`$ git submodule update --init vendors/php-skype`  


### Skypeの起動
SkypeのサイトからSkype for Linuxのdebパッケージをダウンロードしインストールします。  
続いてSkypeを立ち上げ、ボットとして利用したいアカウントでログインします。  


### MotherYukkuriの起動

以下のコマンドでボットを起動します。（X環境から実行する必要があります）  
`$ bin/motheryukkuri-server`  

初回起動時には、API利用の許可を求めるダイアログが表示されますので許可します。  


### 動作確認

* ボットがいるチャットでプラグインのコマンドを実行し、ボットが動作していることを確認します  
Skypeから「\:hello」と発言してみましょう。Helloと返信があるはずです。  


## プラグインの作成方法

例として「:hoge」と書きこむと、「puge」と返答してくるプラグインを作ってみます。  

* プラグインディレクトリに移動して、適当なプラグインをコピーします  
今回の場合は似た機能を持っているHello.phpあたりをベースとすると良いでしょう。  
`$ cd app/libs/Skype/Bot/Plugin/`  
`$ cp -p Hello.php Hoge.php`  

* プログラム上部のクラス変数`$config`にある標準設定項目を書き換えます  
`'plugin_trigger' => '/^\:hoge$/',`  
`'plugin_usage' => 'Usage: :hoge',`  
`'plugin_info' => 'pugeと発言します',`  
　  
ボットは`plugin_trigger`で定義された正規表現（この場合は:hoge）に反応します。  
`plugin_usage`には、このプラグインの使い方を記述し、`plugin_info`には、このプラグインの説明を書きます。  
下の二つはヘルプの表示に使われるだけなので、書き方の指定はありませんが、統一感が損なわれないよう、他のプラグインを真似て書くと良いでしょう。  

* 実行メソッド`execute()`の中身を書きます  
`plugin_trigger`で定義した条件にマッチする文字列を受信すると`execute()`が実行されますので、ここに行いたい処理を書きます。  
今回の場合はpugeと発言しただけですので、メソッド内に  
`$this->postMessage('puge');`  
と記述すれば良いでしょう。  

* 必要に応じて設定ファイルを作ります  
プラグイン利用者はここで指定された設定で、プラグイン側の設定を上書きできます。  
必要が無ければ設定ファイルは必ずしも作らなくても構いません。  
`$ vi app/config/Plugin/Hoge.config.php`

* プラグインを読み込むにはボットを再起動する必要があります。プロセスを一度停止しボットを再起動して動作を確認します  。  



## プラグイン開発の労力を減らすために

開発時にプラグインを修正するたびにボットを再起動するのは大変です。開発用ツールのPluginTestRunnerを活用しましょう。  
`$ app/debug/PluginTestRunner.php "動かしたいプラグインの名前"`で実行できます。  

実行例:  

    $ cd app/debug/
    $ ./PluginTestRunner.php Hello
    :hello # Enter+C-dでプラグインに対しチャットメッセージを送信できます
    Set chat message: :hello
    Load plugin config: /home/matsui/Dropbox/workspace/MotherYukkuri/app/config/Plugin/Hello.config.php
    Load plugin: Hello
     - :hello | 挨拶を行います
    ============ START ============
    [Foo]: :hello
    [SkypeBot]: Hello
    ============  END  ============

チャットメッセージは標準入力から入力されればよいので、例えば以下のような方法でも実行可能です。  

* パイプから入力  
`$ echo ":hello" | ./PluginTestRunner.php Hello`  

* リダイレクトから入力  
※hello.txtにチャットメッセージが入力されているとして  
`$ ./PluginTestRunner.php Hello < hello.txt`  

なおチャットメッセージの文字コードにはUTF-8を使用してください。  



## より複雑なプラグインを作成するには

### よく使う処理のサンプル

詳しくは、MotherYukkuriクラスのリファレンスを見ていただくことになりますが、よく使う処理をサンプルとしてご紹介します。

    // チャットに発言  
    $this->postMessage('string');  

    // メンバーを取得  
    $members = $this->getMembers();  

    // 誕生日を取得($skype_idには対象のSkypeIDが入ります)  
    $birthday = $this->getUserProperty($skype_id, 'BIRTHDAY');  

    // 発言された文章を取得（「:echo テストチャット テストです」と発言したとして）  
    $body = $this->getBody();               // 発言の全文を取得（返値：:echo テストチャット テストです）  
    $body = $this->getBodyWithoutCommand(); // コマンド部分を除いて取得（返値：テストチャット テストです）  
    $body = $this->getCommandArgument();    // 引数を配列で取得（返値：[0] => "テストチャット", [1] => "テストです"）  

    // チャットのトピック名からチャットを検索して発言  
    $target_chatlist = $this->getChatIdsByTopic($target_topic);     // トピック名からリストを作成  
    $chat_target = $this->getInstanceById($target_chatlist[0]);     // 対象チャットインスタンスを作成  
    $chat_target->postMessage('hoge');                              // 上で取得したチャットに対して発言  

    // 【上級者向け】その他アクセッサが用意されていないものを取る場合や、SkypeAPIを直接叩きたい場合  
    $this->getSkype()->invoke("GET USER [SkypeId] BIRTHDAY");  


### ポーリング型プラグインを作るには  

コマンド型と言われる`plugin_triger`の正規表現による起動を行わず、時間で起動するプラグインも作成可能です。  

    // 初期化メソッド  
    public function before()  
    {  
        $this->setPolling(60);    // 60秒に1度、poll_execute()が呼び出される  
    }  
    
    // ポーリング処理メソッド  
    public function poll_execute()  
    {  
        // ここに処理を書く  
    }  




## よくある質問と答え

**Q. 次のエラーが出て起動しません(1)**  
`PHP Warning:  dbusconnection::sendwithreplyandblock(): dbus_connection_send_with_reply_and_block() failed (The name com.Skype.API was not provided by any .service files)`  

**A.** Skypeを先に起動し、ログインした状態にしておいてください。  
Skypeが異常停止した場合も上記のメッセージが出ることがあります。その場合はSkypeを再起動してください。  

----

**Q. 次のエラーが出て起動しません(2)**  
`[Unable to run without a $DISPLAY for X11]`  

**A.** 動作にはX環境が必要です。X上のターミナルから起動するか$DISPLAYを適切に設定してください。  

----

**Q. 外部のサーバやPCからボットに発言をさせたいです**  
　  
**A.** Messageserverプラグインを使うことで可能です。  
セキュリティのためプラグインはデフォルトで無効になっています。  
`app/config/Plugin/Messageserver.config.php`を編集し、`'plugin_is_disabled' => true`となっている部分を`false`に変更し、プラグインを有効化します。  
プラグインの再読み込みには、motheryukkuri-serverの再起動が必要なことに注意してください。  

ボットに発言をさせたい場合は以下のコマンドを実行します  
`$ bin/motheryukkuri-client "対象チャット" "発言内容"`  

対象チャットはトピック名で指定します。  
部分一致検索のためトピック名の一部でも構いませんが、複数のチャットが見つかった場合は先にヒットした方に発言されます。  

外部から発言を行いたい場合は、`app/libs/Skype/Bot/MotherYukkuri/Plugin/Messageserver/Client.php`をコピーして発言したいPCに設定しましょう。  
Client.phpの冒頭に発言サーバのアドレスとポートを設定できる箇所がありますので修正して使用してください。  

----

**Q. ボットに定期的に発言をさせたいです**  

**A.** cronに設定しましょう。毎朝スケジュールを発言する設定例は以下のとおりです。  
`30 8 * * * /path/to/motheryukkuri-sendmessage "テストチャット名" ":schedule"`  

----

**Q. ボットが反応しません**  

**A.** まずMotherYukkuriが正しく起動していることを確認してください。  
またループ防止のため、ボット自身の発言に反応しなくなる`plugin_ignore_self_messages`や、同じ発言を一定時間無視する`plugin_loop_block_time`という設定項目があります。  それらに引っかかっていないか確認してみてください。  



## 注意事項

* このプログラムは無保証であり、利用して起こる全ての問題に責任は負いません。  
* SkypeおよびSkypeAPIのライセンスもご確認ください。  


