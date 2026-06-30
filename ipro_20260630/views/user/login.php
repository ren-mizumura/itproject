<!DOCTYPE html>
<html lang="ja" class="h-full bg-[#0d1117] text-[#c9d1d9]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - DevLMS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full flex items-center justify-center bg-[#0d1117] px-4 py-12 sm:px-6 lg:px-8">

    <div class="max-w-md w-full space-y-8 bg-[#161b22] p-8 border border-[#30363d] rounded-lg shadow-xl">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 rounded-md bg-[#58a6ff] text-[#0d1117] flex items-center justify-center font-bold">
                <i data-lucide="terminal" class="w-8 h-8"></i>
            </div>
            <h2 class="mt-4 text-center text-xl font-extrabold text-white">学習進捗管理システム</h2>
            <p class="mt-2 text-center text-xs text-[#8b949e]">DevLMS にログインして進捗を記録しましょう</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-[#2d1f1f] border border-[#f85149]/30 text-[#f85149] px-4 py-3 rounded text-xs">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-[#1f2c22] border border-[#2ea44f]/30 text-[#2ea44f] px-4 py-3 rounded text-xs">
                <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form class="mt-8 space-y-4 text-xs" action="/20260630/?action=login" method="POST">
            <div>
                <label for="id" class="block text-[#8b949e] font-semibold mb-1">ユーザーID</label>
                <input id="id" name="id" type="text" required class="appearance-none rounded w-full px-3 py-2.5 bg-[#0d1117] border border-[#30363d] text-white focus:outline-none focus:border-[#58a6ff] placeholder-gray-500 font-mono" placeholder="student_alice など">
            </div>
            <div>
                <label for="password" class="block text-[#8b949e] font-semibold mb-1">パスワード</label>
                <input id="password" name="password" type="password" required class="appearance-none rounded w-full px-3 py-2.5 bg-[#0d1117] border border-[#30363d] text-white focus:outline-none focus:border-[#58a6ff] placeholder-gray-500" placeholder="••••••••">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded text-sm font-semibold text-white bg-[#2ea44f] hover:bg-[#238636] focus:outline-none shadow-md transition duration-200">
                    ログイン
                </button>
            </div>
        </form>

        <div class="mt-4 text-center text-xs">
            <span class="text-[#8b949e]">初めてご利用の方はこちら：</span>
            <a href="/20260630/?action=register_form" class="font-bold text-[#58a6ff] hover:underline">生徒の自己登録へ</a>
        </div>

        <div class="mt-6 border-t border-[#30363d] pt-4 text-[11px] text-[#8b949e] bg-[#0d1117]/40 p-3 rounded">
            <p class="font-semibold mb-1 text-white">🔑 テスト用初期アカウント一覧:</p>
            <ul class="space-y-1 font-mono">
                <li>・先生 ID: <span class="text-white">teacher_admin</span> (pw: password)</li>
                <li>・生徒 ID: <span class="text-white">student_alice</span> (pw: password)</li>
                <li>・生徒 ID: <span class="text-white">student_bob</span> (pw: password)</li>
            </ul>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>