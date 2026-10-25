# ATTENDANCE-APP

## 概要
COACHTECH 確認テストで作成した成果物です。
勤怠管理のアプリ。一般ユーザーは出退勤や休憩入り・戻りをアプリから打刻可能。一般ユーザーは自分の勤怠の確認や修正申請ができる。
管理者ユーザーは全ユーザーの勤怠記録の確認や、修正申請の承認、勤怠記録の修正ができる。

## ER図
<img width="841" height="350" alt="Image" src="https://github.com/user-attachments/assets/271bae01-1a2f-48fb-b106-1f5a6d1553d1" />

## 環境構築手順
1. Laravelプロジェクトの作成 (Laravel 10.x)
以下のDockerコマンドを実行。
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \

2. Laravel Sailのインストール
以下のコマンドを実行し、プロジェクトディレクトリに移動→Laravel Sailをインストール。
cd contact-form-app
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer require laravel/sail --dev
以下のDockerコマンドを実行し、Sailの設定ファイルをパブリッシュ（MySQLを選択）
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    php artisan sail:install --with=mysql
※M1/M2/M3 Mac（Apple Silicon）をお使いの方
Apple Silicon搭載のMacでは、`sail up -d`実行時に以下のエラーが発生することがありますが、解決方法の詳述は、割愛。

3. .env ファイルの設定
.env ファイルを開き、データベース接続情報が以下と一致していることを確認します。
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
重要: DB_HOST は localhost や 127.0.0.1 ではなく、Dockerコンテナ名である mysql を指定します。

4. フロントエンドのセットアップ (Vite & Tailwind CSS)
本プロジェクトでは、フロントエンドのスタイリングにTailwind CSSを使用します。
Sailを起動する。
./vendor/bin/sail up -d

NPM依存パッケージをインストール。
sail npm install

Tailwind CSSのインストール
sail npm install -D tailwindcss@^3.4.0 postcss autoprefixer
sail npm install alpinejs

設定ファイルの生成
sail npx tailwindcss init -p

Tailwind CSSのテンプレートパス設定
プロジェクトフォルダ直下のtailwind.config.js を開き、以下のように設定。
/** @type {import("tailwindcss").Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

提供リポジトリのresourcesディレクトリと入れ替え
以下のリポジトリをクローンし、resourcesディレクトリを丸ごと入れ替える。
https://github.com/coachtech-prepared-file/Preparedblade-mockcase-Attendance

入れ替え手順:
①プロジェクトとは別のフォルダ（ダウンロード等）でクローンコマンドを実行
② Finderでプロジェクトフォルダを開きます。
open .
③ プロジェクト内の resources フォルダを削除します。
④クローンしたリポジトリ内の resources フォルダをプロジェクト直下にコピーします。

6. Vite開発サーバーの起動
sail npm run dev
注意: sail npm run dev は実行したままにしておく必要があるため、別のターミナルで実行。

5. phpMyAdminの追加
compose.yaml を開き、mysql サービスの後に以下の設定を挿入する。
※mysqlと同じ階層に挿入しないと正常動作しないため、注意する事。
compose.yaml に追加する内容:

    phpmyadmin:
        image: 'phpmyadmin:latest'
        ports:
            - '${FORWARD_PHPMYADMIN_PORT:-8080}:80'
        environment:
            PMA_HOST: mysql
            PMA_USER: '${DB_USERNAME}'
            PMA_PASSWORD: '${DB_PASSWORD}'
        networks:
            - sail
        depends_on:
            - mysql

6. Sailの起動とエイリアス設定
Sailをバックグラウンドで起動
./vendor/bin/sail up -d

エイリアスを設定して 'sail' だけでコマンドを実行できるようにする
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.zshrc

シェルを再起動するか、新しいターミナルを開いてエイリアスを有効にする
exec $SHELL

7. アプリケーションキーの生成
ルートで以下のコマンドを実行する
sail artisan key:generate

8. データベースのマイグレーションと初期データ投入
以下のコマンドでテーブルを作成し、初期データを投入します。
sail artisan migrate --seed

## 使用技術
- PHP 8.x
- Laravel 10.x
- Laravel Fortify（認証）
- phpMyAdmin
- Eloquent / Factory / Seeder
- PHPUnit / Orchestra Testbench
- Docker / Laravel Sail
- Tailwind CSS
- Laravel Form Request
- StreamedResponse / CSV Output
- mailpit

## APIエンドポイント一覧
メソッド	  パス	                                          概要
GET	         /api/v1/attendance-records	                     勤怠一覧を取得する
GET	         /api/v1/attendance-records/{attendanceRecord}	勤怠詳細を取得する
POST	     /api/v1/attendance-records	                    勤怠を新規登録する
PUT	         /api/v1/attendance-records/{attendanceRecord}	勤怠を更新する
DELETE	     /api/v1/attendance-records/{attendanceRecord}	勤怠を削除する


## 開発環境URL
http://localhost
phpMyAdmin:http://localhost:8080

## 作成者
甲田　太志

