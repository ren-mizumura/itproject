<?php
/**
 * logout.php
 * アクティブなセッションを安全に破棄し、ログイン画面へと戻します。
 * クライアントのクッキーまで含めて完全な破棄を施すことで、なりすまし等の再利用リスクを遮断します。
 */

// 共通関数ファイルを読み込んで、現在のセッション状態（session_start）を引き継ぎます
require_once 'functions.php';

// 1. セッション変数のクリア
// サーバー側メモリにあるセッション配列を、空の配列にリセットします
$_SESSION = [];

// 2. クライアント側のセッションクッキーの完全削除
// ブラウザに格納されているセッションID（クッキー名: PHPSESSIDなど）の有効期限を過去（現在時刻より前）に設定することで、
// ブラウザからセッション用のクッキーを強制的に削除・無効化させます。
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),                 // クッキー名を取得（通常は PHPSESSID）
        '',                             // 中身を空にする
        time() - 42000,                 // 有効期限を過去（約11時間半前）に設定
        $params["path"],                // クッキーの有効パス
        $params["domain"],              // クッキーの有効ドメイン
        $params["secure"],              // https通信限定か
        $params["httponly"]             // JavaScriptからのアクセス禁止設定（XSS対策に重要）
    );
}

// 3. サーバー側のセッション情報の完全破棄
// これにより、サーバーに保存されていたセッションデータ自体のファイルを完全に削除します
session_destroy();

// 4. ログアウト完了後、速やかにログイン画面（login.php）へ強制遷移
header('Location: login.php');

// 【重要】未ログイン時の require_login() と同様に、
// リダイレクトの送信が終わったら、それ以上の処理やデータの出力を完全停止させるために exit させます
exit;