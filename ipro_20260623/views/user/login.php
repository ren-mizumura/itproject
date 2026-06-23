<?php
/**
 * ログイン画面（View）
 */
require_once __DIR__ . '/../layout/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">ログイン</h2>

    <!-- 新規登録直後などの成功メッセージ表示 -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded text-sm" role="alert">
            <?php 
                echo h($_SESSION['flash_message']); 
                unset($_SESSION['flash_message']); // 表示後、セッションから即時削除（1回限りの通知に）
            ?>
        </div>
    <?php endif; ?>

    <!-- エラーメッセージの表示 -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm" role="alert">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="index.php?action=login" method="POST" class="space-y-5">
        
        <!-- 【CSRF対策】 -->
        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-600 mb-1">メールアドレス</label>
            <input type="email" name="email" id="email" required 
                   placeholder="example@secure.com"
                   class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700"
                   value="<?php echo isset($_POST['email']) ? h($_POST['email']) : ''; ?>">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-600 mb-1">パスワード</label>
            <input type="password" name="password" id="password" required
                   placeholder="••••••••"
                   class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700">
        </div>

        <button type="submit" 
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
            ログイン
        </button>
    </form>

    <div class="mt-6 text-center text-sm">
        <span class="text-gray-500">アカウントをお持ちではないですか？</span>
        <a href="index.php?action=register" class="text-indigo-600 hover:underline font-medium ml-1">新規登録はこちら</a>
    </div>
</div>

</main>
<footer class="bg-gray-100 text-center py-4 text-xs text-gray-500 border-t mt-8">
    &copy; 2026 Secure Login System. All rights reserved.
</footer>
</body>
</html>