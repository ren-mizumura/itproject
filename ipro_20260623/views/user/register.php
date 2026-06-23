<?php
/**
 * 新規登録画面（View）
 */
require_once __DIR__ . '/../layout/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">新規ユーザー登録</h2>

    <!-- エラーメッセージの表示（XSS対策として関数 h() を用いたエスケープを徹底） -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="index.php?action=register" method="POST" class="space-y-5">
        
        <!-- 【CSRF対策：ワンタイムトークンの埋め込み】
             POST送信時に必ずサーバー側で生成したcsrf_tokenを hidden 項目として送信します。 -->
        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-600 mb-1">メールアドレス</label>
            <input type="email" name="email" id="email" required 
                   placeholder="example@secure.com"
                   class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700"
                   value="<?php echo isset($_POST['email']) ? h($_POST['email']) : ''; ?>">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">パスワード <span class="text-xs text-gray-400">(8文字以上)</span></label>
            <input type="password" name="password" id="password" required
                   placeholder="••••••••"
                   class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700">
        </div>

        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-600 mb-1">パスワード（確認用）</label>
            <input type="password" name="password_confirm" id="password_confirm" required
                   placeholder="••••••••"
                   class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700">
        </div>

        <button type="submit" 
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
            アカウントを作成する
        </button>
    </form>

    <div class="mt-6 text-center text-sm">
        <span class="text-gray-500">すでにアカウントをお持ちですか？</span>
        <a href="index.php?action=login" class="text-indigo-600 hover:underline font-medium ml-1">ログインはこちら</a>
    </div>
</div>

</main>
<footer class="bg-gray-100 text-center py-4 text-xs text-gray-500 border-t mt-8">
    &copy; 2026 Secure Login System. All rights reserved.
</footer>
</body>
</html>