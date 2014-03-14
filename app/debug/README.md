## PluginTestRunnerについて

PluginTestRunnerはプラグインのテスト実行用プラグラムです。  
指定されたプラグインをSkype経由以外でも動くようにし、テストを容易にします。  


## 使い方

* 標準入力からプラグインに認識させるチャット文字列を入力します。通常は「:hello」のようなコマンドを指定することになります。
* 第一引数にプラグイン名称を指定します。

`$ echo "[プラグインに入力するチャット文字列]" | ./PluginTestRunner.php [プラグイン名称]`


## 実行例

Helloプラグインの実行例  

`cd app/debug`
`echo ":hello" | ./PluginTestRunner.php Hello`
