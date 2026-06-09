<?php
// セッションを開始
session_start();

// ログイン確認のガード処理
// セッションに 'user' キーが存在しない場合はログインしていないとみなします
if (!isset($_SESSION['user'])) {
    // login.php にリダイレクト
    header('Location: login.php');
    exit; // リダイレクト後の処理が実行されないように必ず exit を呼び出します
}

// ログインしているユーザーの情報を取得
$currentUser = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-6">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        
        <!-- ヘッダーエリア -->
        <div class="bg-slate-900 p-6 text-white flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">マイページ</h1>
                <p class="text-slate-400 text-sm mt-1">ログイン中のユーザー情報</p>
            </div>
            <a href="logout.php" 
               class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-sm font-medium rounded-lg transition">
                ログアウト
            </a>
        </div>

        <!-- ユーザー詳細コンテンツ -->
        <div class="p-8">
            <div class="flex items-center space-x-4 mb-6">
                <!-- ダミーアバターアイコン -->
                <div class="w-16 h-16 bg-blue-100 text-blue-600 font-bold text-2xl flex items-center justify-center rounded-full">
                    <?= mb_substr($currentUser['username'], 0, 1) ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?> さん</h2>
                    <p class="text-sm text-slate-500">アカウントID: <?= htmlspecialchars($currentUser['id'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6 space-y-4">
                <div class="grid grid-cols-3 py-2 border-b border-slate-50">
                    <span class="text-slate-500 text-sm font-medium">メールアドレス</span>
                    <span class="col-span-2 text-slate-800 font-medium"><?= htmlspecialchars($currentUser['email'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-50">
                    <span class="text-slate-500 text-sm font-medium">アカウント作成日時</span>
                    <span class="col-span-2 text-slate-800 font-medium"><?= htmlspecialchars($currentUser['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>

            <!-- セキュリティ上のポイントを解説するカード -->
            <div class="mt-8 p-4 bg-blue-50 border border-blue-100 rounded-xl text-blue-800 text-sm space-y-2">
                <h3 class="font-bold">🔒 セキュリティ学習メモ:</h3>
                <p>現在セッションに保存されているデータには、パスワード情報（暗号化ハッシュも含めて）は一切含まれていません。<code>UserModel</code> 内で事前に削除（unset）されているため、セッション乗っ取りなどの漏洩リスクが抑えられています。</p>
            </div>
        </div>
    </div>
</body>
</html>