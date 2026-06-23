<?php
/**
 * 投稿編集画面ビュー (views/post/edit.php)
 * * 現在の登録画像のプレビュー表示と、新しい画像の選択差し替えに対応しています。
 */
require_once __DIR__ . '/../layout/header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <div class="mb-6">
        <a href="index.php?action=post_list" class="text-sm text-indigo-600 hover:underline">&larr; キャンセルして戻る</a>
    </div>

    <h2 class="text-2xl font-bold mb-6 text-gray-700">投稿を編集する</h2>

    <!-- エラー表示 (XSS対策) -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm" role="alert">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 【画像を編集可能にするため enctype="multipart/form-data" を必須追加】 -->
    <form action="index.php?action=post_edit&id=<?php echo (int)$post['id']; ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
        
        <!-- 【CSRF対策：トークンの埋め込み】 -->
        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

        <div>
            <label for="title" class="block text-sm font-medium text-gray-600 mb-1">タイトル <span class="text-red-500">*</span></label>
            <input type="text" name="title" id="title" required 
                   placeholder="投稿のタイトルを入力"
                   maxlength="255"
                   class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700"
                   value="<?php echo isset($_POST['title']) ? h($_POST['title']) : h($post['title']); ?>">
        </div>

        <div>
            <label for="body" class="block text-sm font-medium text-gray-600 mb-1">本文 <span class="text-red-500">*</span></label>
            <textarea name="body" id="body" required rows="6"
                      placeholder="本文をこちらに入力してください..."
                      class="w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 focus:outline-none text-gray-700"
            ><?php echo isset($_POST['body']) ? h($_POST['body']) : h($post['body']); ?></textarea>
        </div>

        <!-- 現在アップロードされている画像がある場合のプレビュー表示 -->
        <?php if (!empty($post['image_path'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">現在設定されている画像</label>
                <div class="mb-2 max-w-[200px] rounded border overflow-hidden bg-gray-50 p-1">
                    <img src="postImages/<?php echo (int)$post['user_id']; ?>/<?php echo h($post['image_path']); ?>" 
                         alt="); ?>]"
                         class="object-contain w-full h-auto">
                </div>
                <p class="text-xs text-gray-400">※新しい画像をアップロードすると、現在の画像は差し替えられます。変更しない場合は空のままにしてください。</p>
            </div>
        <?php endif; ?>

        <!-- 新しい画像への更新（任意） -->
        <div>
            <label for="image" class="block text-sm font-medium text-gray-600 mb-1">
                画像を変更する <span class="text-xs text-gray-400">(変更する場合のみ選択・最大5MB・JPG, PNG, GIFのみ)</span>
            </label>
            <input type="file" name="image" id="image" accept="image/jpeg, image/png, image/gif"
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
        </div>

        <button type="submit" 
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
            変更内容を保存する
        </button>
    </form>
</div>

</main>
<footer class="bg-gray-100 text-center py-4 text-xs text-gray-500 border-t mt-8">
    &copy; 2026 Secure Board System. All rights reserved.
</footer>
</body>
</html>