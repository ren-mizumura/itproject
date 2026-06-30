<!DOCTYPE html>
<html lang="ja" class="h-full bg-[#0d1117] text-[#c9d1d9]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>生徒登録 - DevLMS</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full flex items-center justify-center bg-[#0d1117] px-4 py-12 sm:px-6 lg:px-8">

    <div class="max-w-md w-full space-y-8 bg-[#161b22] p-8 border border-[#30363d] rounded-lg shadow-xl">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 rounded-md bg-[#2ea44f] text-white flex items-center justify-center font-bold">
                <i data-lucide="user-plus" class="w-8 h-8"></i>
            </div>
            <h2 class="mt-4 text-center text-xl font-extrabold text-white">生徒の自己登録</h2>
            <p class="mt-2 text-center text-xs text-[#8b949e]">DevLMSのアカウントを自己登録して勉強を始めましょう</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-[#2d1f1f] border border-[#f85149]/30 text-[#f85149] px-4 py-3 rounded text-xs">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- 招待URLからのアクセスの場合のメッセージ -->
        <?php if (!empty($_SESSION['invite_from'])): ?>
            <div class="bg-[#1f242c] border border-[#58a6ff]/30 text-[#58a6ff] px-4 py-3 rounded text-xs flex items-center gap-2">
                <i data-lucide="link" class="w-4 h-4 shrink-0"></i>
                <span>招待を受けました！アカウント作成完了後、自動的に <b><?= htmlspecialchars($_SESSION['invite_from'], ENT_QUOTES, 'UTF-8') ?></b> さんと友達登録されます。</span>
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4 text-xs" action="/20260630/?action=register" method="POST">
            <div>
                <label for="id" class="block text-[#8b949e] font-semibold mb-1">ユーザーID <span class="text-[#f85149]">*</span></label>
                <input id="id" name="id" type="text" required class="appearance-none rounded w-full px-3 py-2.5 bg-[#0d1117] border border-[#30363d] text-white focus:outline-none focus:border-[#58a6ff] placeholder-gray-500 font-mono" placeholder="任意の半角英数字（例: tanaka_stud）">
            </div>
            <div>
                <label for="name" class="block text-[#8b949e] font-semibold mb-1">表示名（生徒氏名など） <span class="text-[#f85149]">*</span></label>
                <input id="name" name="name" type="text" required class="appearance-none rounded w-full px-3 py-2.5 bg-[#0d1117] border border-[#30363d] text-white focus:outline-none focus:border-[#58a6ff] placeholder-gray-500" placeholder="田中 太郎">
            </div>
            <div>
                <label for="password" class="block text-[#8b949e] font-semibold mb-1">パスワード <span class="text-[#f85149]">*</span></label>
                <input id="password" name="password" type="password" required class="appearance-none rounded w-full px-3 py-2.5 bg-[#0d1117] border border-[#30363d] text-white focus:outline-none focus:border-[#58a6ff] placeholder-gray-500" placeholder="••••••••">
                <p class="text-[10px] text-[#8b949e] mt-1">※ハッシュ化（PHP password_hash）により安全に保管されます</p>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded text-sm font-semibold text-white bg-[#2ea44f] hover:bg-[#238636] focus:outline-none shadow-md transition duration-200">
                    アカウントを作成してログイン
                </button>
            </div>
        </form>

        <div class="mt-4 text-center text-xs">
            <span class="text-[#8b949e]">既に登録済みですか？</span>
            <a href="/20260630/?action=login_form" class="font-bold text-[#58a6ff] hover:underline">ログイン画面へ</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>