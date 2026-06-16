<?php
/**
 * login.php
 * ログイン画面（HTML）と、ユーザー情報の認証処理を担当します。
 */

// 共通関数ファイルを読み込む
require_once 'functions.php';

// すでにログインしている場合は、ログイン画面をスキップしてマイページへ自動リダイレクトします
if (is_logged_in()) {
    header('Location: mypage.php');
    exit; // 確実に処理を終了
}

// エラー・メッセージ用変数の初期化
$error = '';
$success_message = '';
$email = '';

// signup.php から登録成功パラメータ（?signup=success）が渡されてきた場合の処理
if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $success_message = 'ユーザー登録が完了しました！ログインしてください。';
}

// ログインフォームがPOST送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // バリデーション
    if ($email === '' || $password === '') {
        $error = 'すべての項目を入力してください。';
    } else {
        // functions.php に定義した login_user() 関数で認証を実行
        if (login_user($email, $password)) {
            // 認証成功：マイページへ安全に遷移
            header('Location: mypage.php');
            exit; // 確実にスクリプトを終了
        } else {
            // 【セキュリティ対策】詳細なエラー原因を相手に教えない
            // 「パスワードが間違っています」のように詳細を明かすと、悪意ある第三者がアカウントの存在チェック（列挙攻撃）を容易に行えてしまいます。
            // 「メールアドレスまたはパスワードが正しくありません」と共通のエラーを返すのがWebセキュリティの原則です。
            $error = 'メールアドレスまたはパスワードが正しくありません。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
        <h2 class="text-3xl font-extrabold text-slate-800 text-center mb-2">ログイン</h2>
        <p class="text-sm text-slate-500 text-center mb-8">登録した情報を入力してログインしてください</p>

        <!-- 登録成功メッセージの表示（安全にh()でエスケープ） -->
        <?php if ($success_message !== ''): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo h($success_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- ログイン失敗エラーメッセージの表示 -->
        <?php if ($error !== ''): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo h($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- ログインフォーム -->
        <form action="login.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">メールアドレス</label>
                <input type="email" name="email" id="email" required 
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                       placeholder="example@email.com" value="<?php echo h($email); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">パスワード</label>
                <input type="password" name="password" id="password" required 
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                       placeholder="••••••••">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-blue-100 flex justify-center items-center">
                ログインする
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <p class="text-sm text-slate-600">
                アカウントをまだお持ちでないですか？ 
                <a href="signup.php" class="text-blue-600 hover:underline font-semibold ml-1">新規登録はこちら</a>
            </p>
        </div>
    </div>
</body>
</html>