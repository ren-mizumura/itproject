<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitProgress アカウント登録</title>
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

    <div class="w-[360px] flex flex-col items-center">
        <!-- ロゴ -->
        <div class="mb-6 flex flex-col items-center">
            <i data-lucide="git-pull-request" class="text-[#3fb950] w-12 h-12 mb-3"></i>
            <h1 class="text-2xl font-light text-[#24292f] tracking-tight">生徒アカウント作成</h1>
            <p class="text-xs text-gray-500 mt-1">学習進捗と課題の管理を始めましょう</p>
        </div>

        <!-- エラーメッセージ -->
        <?php if ($error): ?>
            <div class="w-full mb-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-md p-3 flex items-center space-x-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 flex-shrink-0"></i>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- 登録フォーム -->
        <div class="w-full bg-white border border-[#d0d7de] rounded-md p-5 shadow-sm">
            <form action="<?php echo BASE_URL; ?>register" method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-[#24292f] mb-1.5">ユーザーID (英数字)</label>
                    <input type="text" name="username" id="username" required autofocus placeholder="例: student_kenta"
                           pattern="^[a-zA-Z0-9_\-]+$" title="半角英数字、ハイフン、アンダースコアが利用可能です"
                           class="w-full border border-[#d0d7de] rounded px-3 py-1.5 text-sm focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none bg-[#f6f8fa] focus:bg-white transition">
                </div>

                <div>
                    <label for="display_name" class="block text-sm font-medium text-[#24292f] mb-1.5">表示名 (本名やニックネーム)</label>
                    <input type="text" name="display_name" id="display_name" required placeholder="例: 健太 / Kenta"
                           class="w-full border border-[#d0d7de] rounded px-3 py-1.5 text-sm focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none bg-[#f6f8fa] focus:bg-white transition">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[#24292f] mb-1.5">パスワード (6文字以上)</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••" minlength="6"
                           class="w-full border border-[#d0d7de] rounded px-3 py-1.5 text-sm focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none bg-[#f6f8fa] focus:bg-white transition">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-[#24292f] mb-1.5">パスワード (確認用)</label>
                    <input type="password" name="password_confirm" id="password_confirm" required placeholder="••••••••" minlength="6"
                           class="w-full border border-[#d0d7de] rounded px-3 py-1.5 text-sm focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none bg-[#f6f8fa] focus:bg-white transition">
                </div>

                <button type="submit" class="w-full bg-[#2c974b] hover:bg-[#2c8543] text-white font-semibold py-2 px-4 rounded text-sm transition shadow-sm border border-[#217c3b]">
                    アカウント作成を開始する
                </button>
            </form>
        </div>

        <!-- ログインへの案内 -->
        <div class="w-full mt-4 bg-transparent border border-[#d0d7de] rounded-md p-4 text-center text-xs text-[#24292f]">
            既にアカウントを持っていますか？ 
            <a href="<?php echo BASE_URL; ?>login" class="text-[#0969da] font-medium hover:underline">サインインする</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>