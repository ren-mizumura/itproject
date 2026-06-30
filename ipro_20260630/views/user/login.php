<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitProgress にログインする</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            background-color: #f6f8fa;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-6">

    <div class="w-[340px] flex flex-col items-center">
        <!-- ロゴ -->
        <div class="mb-6 flex flex-col items-center">
            <i data-lucide="git-branch" class="text-[#3fb950] w-12 h-12 mb-3"></i>
            <h1 class="text-2xl font-light text-[#24292f] tracking-tight">GitProgress にサインイン</h1>
        </div>

        <!-- 友達招待からアクセスされた場合のリマインダー -->
        <?php if (isset($_GET['invited_by'])): ?>
            <div class="w-full mb-4 bg-blue-50 border border-blue-300 text-blue-800 text-xs rounded-md p-3 flex items-start space-x-2">
                <i data-lucide="info" class="w-4 h-4 flex-shrink-0 text-blue-500 mt-0.5"></i>
                <p>
                    <strong><?php echo htmlspecialchars($_GET['invited_by']); ?></strong>さんから招待されました！アカウント登録またはログインすると自動的に友達登録が紐付きます。
                </p>
            </div>
        <?php endif; ?>

        <!-- エラーメッセージ -->
        <?php if ($error): ?>
            <div class="w-full mb-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-md p-3 flex items-center space-x-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0"></i>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- ログインフォーム -->
        <div class="w-full bg-white border border-[#d0d7de] rounded-md p-5 shadow-sm">
            <form action="<?php echo BASE_URL; ?>login" method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-[#24292f] mb-1.5">ユーザーID</label>
                    <input type="text" name="username" id="username" required autofocus placeholder="例: student_alice"
                           class="w-full border border-[#d0d7de] rounded px-3 py-1.5 text-sm focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none bg-[#f6f8fa] focus:bg-white transition">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-sm font-medium text-[#24292f]">パスワード</label>
                    </div>
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                           class="w-full border border-[#d0d7de] rounded px-3 py-1.5 text-sm focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none bg-[#f6f8fa] focus:bg-white transition">
                </div>

                <button type="submit" class="w-full bg-[#2c974b] hover:bg-[#2c8543] text-white font-semibold py-1.5 px-4 rounded text-sm transition shadow-sm border border-[#217c3b]">
                    サインインする
                </button>
            </form>
        </div>

        <!-- 登録への案内 -->
        <div class="w-full mt-4 bg-transparent border border-[#d0d7de] rounded-md p-4 text-center text-xs text-[#24292f]">
            新しく学習を始めますか？ 
            <a href="<?php echo BASE_URL; ?>register" class="text-[#0969da] font-medium hover:underline">アカウントを作成する</a>
        </div>

        <!-- デフォルトログイン案内（テスト用） -->
        <div class="w-full mt-6 bg-gray-100 border border-gray-200 rounded p-3 text-[11px] text-gray-500">
            <p class="font-bold mb-1">【検証用テストアカウント】</p>
            <ul class="list-disc list-inside space-y-0.5">
                <li>先生: <code class="bg-gray-200 px-1 rounded">teacher_admin</code> / <code class="bg-gray-200 px-1 rounded">password123</code></li>
                <li>生徒: <code class="bg-gray-200 px-1 rounded">student_alice</code> / <code class="bg-gray-200 px-1 rounded">password123</code></li>
                <li>生徒: <code class="bg-gray-200 px-1 rounded">student_bob</code> / <code class="bg-gray-200 px-1 rounded">password123</code></li>
            </ul>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>