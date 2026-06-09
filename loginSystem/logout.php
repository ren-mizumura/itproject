<?php
// セッションを開始
session_start();

// 1. セッション変数を全てクリアする
$_SESSION = [];

// 2. ブラウザのセッションクッキーも破棄する (セキュリティ上の推奨設定)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. セッションファイルを破棄
session_destroy();

// 4. ログイン画面に遷移
header('Location: login.php');
exit;