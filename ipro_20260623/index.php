<?php
/**
 * 共通ルーティングコントローラー（フロントコントローラーパターン）
 * * すべてのリクエストはまずこの「index.php」で受け取ります。
 * 適切なコントローラーに振る分ける役割を担います。
 */

// セッションを開始して、各画面でセッションを利用可能にします。
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_set_cookie_params([
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// 各種コントローラーを読み込む
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/PostController.php';

// クエリパラメータ「action」から実行アクションを取得
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

$userController = new UserController();
$postController = new PostController();

// 【ディレクトリトラバーサルや不正なアクセス制御 (A01:2021) 対策】
// ホワイトリスト方式により、指定外のアクションや不正なパスが実行されるのを完全に防ぎます。
switch ($action) {
    // ユーザー認証関係
    case 'register':
        $userController->register();
        break;

    case 'login':
        $userController->login();
        break;

    case 'logout':
        $userController->logout();
        break;

    case 'mypage':
        require_once __DIR__ . '/views/mypage.php';
        break;

    // 掲示板・投稿関係
    case 'post_list':
        $postController->index();
        break;

    case 'post_create':
        $postController->create();
        break;

    case 'post_edit':
        $postController->edit();
        break;

    case 'post_delete':
        $postController->delete();
        break;

    // いいね機能 (非同期 Ajax 処理)
    case 'post_like':
        $postController->toggleLike();
        break;

    default:
        // ホワイトリストにない不正なアクションは一律ログイン画面または投稿一覧にリダイレクト
        header("Location: index.php?action=post_list");
        exit;
}