# Feeldee MediaBox

feeldee-mediaboxは、[Feeldee Framework](https://github.com/ryossi/feeldee-framework)ベースのアプリケーションに簡易的なコンテンツ管理機能を追加するためのLaravelパッケージです。

## 利用者

### 導入方法

1. `composer require ryossi/feeldee-mediabox`でパッケージを追加します。 
2. `php artisan migrate`でテーブルを作成します。
4. （オプション）`php artisan vendor:publish`を実行するとconfig/feeldee.phpにMediaBoのコンフィグレーションがマージされます。

### 使用方法

具体的な機能については、[wiki](https://github.com/ryossi/feeldee-mediabox/wiki)を参照してください。

## 開発者

### 導入方法

1. `git clone ryossi/feeldee-mediabox`でパッケージをダウンロードします。 
2. `composer install`でPHPの依存パッケージをインストールします。

### テスト環境

通常のテストは、コマンドプロンプトで以下のコマンドを実行してください。

`./vendor/bin/phpunit --testsuite Feature`

### XDebug利用

1. `cp .env.example .env`で.envをコピーして設定をカスタマイズしてください。
2. `docker compose up -d`でテストコンテナを起動してください。
3. `docker exec -it feeldee-mediabox bash`でテストコンテナに入ります。
4. ソースコードの必要な部分にブレイクポイントを設定します。
5. テストコンテナのコマンドプロンプトで`./vendor/bin/phpunit --testsuite Feature`を実行してください。
6. 最後に`docker compose down`でテストコンテナを終了します。

## 依存パッケージ

以下のサードパーティパッケージに依存しています。

| パッケージ名 | バージョン | ライセンス | 用途 |
| - | - | - | - |
| [Intervention Image](https://image.intervention.io/v2) | 2.x | MIT | メディアコンテンツの情報取得と操作 |
| [Hashids](https://github.com/vinkla/hashids?tab=readme-ov-file) | 5.x | MIT | メディアコンテンツURIの自動生成 |

## ライセンス

このプラグインは、[MIT licence.](https://opensource.org/licenses/MIT)のもとで公開されています。

## 参考

- テスト環境には、[Testbench](https://github.com/orchestral/testbench)を利用しています。
