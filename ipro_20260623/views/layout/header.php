<?php
/**
 * 共通ヘッダー
 * * ナビゲーションに「掲示板」へのリンクを追加しています。
 */

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

// セキュリティヘッダー
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// CSRFトークン自動生成
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// XSS (A03:2021) 対策：HTMLエスケープ関数
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セキュア掲示板システム</title>
    <!-- Tailwind CSS による美しいUI -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <header class="bg-indigo-600 text-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-wider">
                <a href="index.php?action=post_list">SecureBoard</a>
            </h1>
            <nav class="flex space-x-4 items-center">
                <!-- 誰でも見られる掲示板へのリンク -->
                <a href="index.php?action=post_list" class="hover:underline py-1">掲示板</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-sm bg-indigo-700 px-3 py-1.5 rounded-md">
                        👤 <?php echo h($_SESSION['user_email']); ?>
                    </span>
                    <a href="index.php?action=mypage" class="hover:underline py-1">マイページ</a>
                    <a href="index.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-md text-sm font-semibold transition-colors">ログアウト</a>
                <?php else: ?>
                    <a href="index.php?action=login" class="hover:underline py-1">ログイン</a>
                    <a href="index.php?action=register" class="bg-indigo-500 hover:bg-indigo-400 px-3 py-1.5 rounded-md text-sm font-semibold transition-colors">新規登録</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8 max-w-4xl">