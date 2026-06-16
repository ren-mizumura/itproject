<?php
/**
 * views/user/register.php
 * 新規会員登録画面用のビューテンプレートファイルです。
 * 上下左右の完全中央揃えにレイアウトを修正しました。
 */

// 共通ヘッダーの読み込み
require_once __DIR__ . '/../layout/header.php';
?>

<!-- 
  【レイアウト修正ポイント】
  min-h-[calc(100vh-73px)]：画面の高さからヘッダーの高さ（約73px）を引いた高さの最小値を確保します。
  flex items-center justify-center：これで中のコンテンツ（新規登録カード）が完全に画面の「上下左右中央」に配置されます。
-->
<div class="min-h-[calc(100vh-73px)] w-full flex items-center justify-center p-4">
    
    <!-- 新規登録カード -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 border border-slate-100 my-auto">
        <h2 class="text-3xl font-extrabold text-slate-800 text-center mb-2">新規登録</h2>
        <p class="text-sm text-slate-500 text-center mb-8">MVCシステムへようこそ。アカウントを作成しましょう。</p>

        <!-- コントローラーから渡されたエラーメッセージの表示 -->
        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo h($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- 登録フォーム -->
        <form action="index.php?action=register" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">メールアドレス</label>
                <input type="email" name="email" id="email" required 
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-slate-800 text-sm"
                       placeholder="example@email.com" value="<?php echo h($email); ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">パスワード (6文字以上)</label>
                <input type="password" name="password" id="password" required 
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-slate-800 text-sm"
                       placeholder="••••••••">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl transition-all shadow-lg shadow-blue-100 flex justify-center items-center">
                アカウントを作成する
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <p class="text-sm text-slate-600">
                すでにアカウントをお持ちですか？ 
                <a href="index.php?action=login" class="text-blue-600 hover:underline font-semibold ml-1">ログインはこちら</a>
            </p>
        </div>
    </div>

</div>

</main>
</body>
</html>