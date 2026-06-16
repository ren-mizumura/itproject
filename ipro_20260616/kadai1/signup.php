<?php
/**
 * signup.php
 * 新規ユーザー登録画面（HTML）と登録の実行処理を担当します。
 */

// 共通関数ファイルを読み込む
require_once 'functions.php';

// すでにログインしている場合は、わざわざ新規登録する必要がないため、マイページへ自動リダイレクトします
if (is_logged_in()) {
    header('Location: mypage.php');
    exit; // リダイレクト後のスクリプト実行を確実に防止
}

// エラーメッセージと入力保持用変数の初期化
$error = '';
$email = '';

// フォームがPOST送信された場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームから送られてきたデータを受け取る（余分な空白を除去する trim() を使用）
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // バリデーション（入力値の検証）
    if ($email === '' || $password === '') {
        $error = 'すべての項目を入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // FILTER_VALIDATE_EMAIL で、メールアドレスとしての正しい形式（@やドメインの有無など）をチェック
        $error = 'メールアドレスの形式が正しくありません。';
    } elseif (mb_strlen($password) < 6) {
        // パスワードの最低文字数を検証（セキュリティのため6文字以上を要件とします）
        $error = 'パスワードは6文字以上で入力してください。';
    } else {
        // バリデーション成功：登録処理の呼び出し
        if (signup_user($email, $password)) {
            // 登録成功時：login.php へリダイレクト（パラメータを付与して完了メッセージを出す工夫）
            header('Location: login.php?signup=success');
            exit; // 確実に処理を止める
        } else {
            // 登録失敗時（メールアドレスが既にテーブルに存在していた場合など）
            $error = 'このメールアドレスは既に登録されています。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規ユーザー登録</title>
    <!-- Tailwind CSS を使用して現代的で美しく、レスポンシブなUIを構築 -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 border border-slate-100">
        <h2 class="text-3xl font-extrabold text-slate-800 text-center mb-2">新規登録</h2>
        <p class="text-sm text-slate-500 text-center mb-8">アカウントを作成してサービスを始めましょう</p>

        <!-- エラーメッセージがある場合は安全にエスケープして表示 -->
        <?php if ($error !== ''): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo h($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- 登録フォーム -->
        <form action="signup.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">メールアドレス</label>
                <!-- value属性に既存入力を入れ、登録失敗時の再入力を省きます。当然、XSS対策としてh()で囲みます -->
                <input type="email" name="email" id="email" required 
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                       placeholder="example@email.com" value="<?php echo h($email); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">パスワード (6文字以上)</label>
                <input type="password" name="password" id="password" required 
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                       placeholder="••••••••">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-lg shadow-blue-100 flex justify-center items-center">
                登録する
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <p class="text-sm text-slate-600">
                すでにアカウントをお持ちですか？ 
                <a href="login.php" class="text-blue-600 hover:underline font-semibold ml-1">ログインはこちら</a>
            </p>
        </div>
    </div>
</body>
</html>