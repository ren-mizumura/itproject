<?php
// セッションを開始
session_start();

// UserModelクラスを読み込み
require_once 'userModel.php';

// 既にログインしている場合はマイページへリダイレクト
if (isset($_SESSION['user'])) {
    header('Location: mypage.php');
    exit;
}

$error = '';
$success = '';

// フォームが送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // UserModelを使用して登録を試みる
    $result = UserModel::register($username, $email, $password);

    if (is_array($result)) {
        // 登録成功！そのまま自動ログインさせてマイページに飛ばす、またはメッセージ表示
        $_SESSION['user'] = $result; // パスワード抜きのユーザー情報が入っている
        $success = 'ユーザー登録が完了しました！マイページに移動します。';
        header('Refresh: 2; url=mypage.php'); // 2秒後にリダイレクト
    } else {
        // 登録失敗（エラーメッセージが返ってきた場合）
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規ユーザー登録</title>
    <!-- Tailwind CSS を使用して現代的なUIを構築 -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-md border border-slate-100">
        <h2 class="text-2xl font-bold text-center text-slate-800 mb-6">新規アカウント登録</h2>

        <!-- エラーメッセージ -->
        <?php if ($error): ?>
            <div class="bg-rose-50 text-rose-600 p-3 rounded-lg text-sm mb-4 border border-rose-100">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- 成功メッセージ -->
        <?php if ($success): ?>
            <div class="bg-emerald-50 text-emerald-600 p-3 rounded-lg text-sm mb-4 border border-emerald-100">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="username">ユーザー名</label>
                <input type="text" name="username" id="username" required
                       class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="タロウ">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="email">メールアドレス</label>
                <input type="email" name="email" id="email" required
                       class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="example@example.com">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="password">パスワード</label>
                <input type="password" name="password" id="password" required minlength="6"
                       class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                       placeholder="6文字以上のパスワード">
            </div>

            <button type="submit"
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition shadow-sm">
                アカウントを作成する
            </button>
        </form>

        <div class="mt-6 text-center border-t border-slate-100 pt-4">
            <p class="text-sm text-slate-600">すでにアカウントをお持ちですか？</p>
            <a href="login.php" class="inline-block mt-2 text-sm font-semibold text-blue-600 hover:text-blue-500 transition">
                ログインはこちら &rarr;
            </a>
        </div>
    </div>
</body>
</html>