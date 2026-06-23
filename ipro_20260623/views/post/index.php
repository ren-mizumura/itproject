<?php
/**
 * 投稿一覧画面ビュー (views/post/index.php)
 * * 画像表示機能、およびいいね機能非同期通信JSを組み込んでいます。
 */
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <h2 class="text-3xl font-bold text-gray-800">掲示板 投稿一覧</h2>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="index.php?action=post_create" 
           class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-5 rounded-md shadow transition-colors">
            ＋ 新規投稿する
        </a>
    <?php else: ?>
        <p class="text-sm text-gray-500">
            <a href="index.php?action=login" class="text-indigo-600 hover:underline font-medium">ログイン</a> すると投稿できます。
        </p>
    <?php endif; ?>
</div>

<!-- フラッシュメッセージ表示 -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded text-sm shadow-sm" role="alert">
        <?php 
            echo h($_SESSION['flash_message']); 
            unset($_SESSION['flash_message']);
        ?>
    </div>
<?php endif; ?>

<!-- 投稿一覧 -->
<?php if (empty($posts)): ?>
    <div class="bg-white p-12 text-center rounded-lg border border-gray-100 shadow-sm">
        <p class="text-gray-400 text-lg">まだ投稿はありません。最初の投稿をしてみましょう！</p>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($posts as $post): ?>
            <div class="bg-white p-6 rounded-lg border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                
                <!-- 投稿ヘッダー -->
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo h($post['title']); ?></h3>
                        <p class="text-xs text-gray-400">
                            投稿者: <span class="text-gray-600 font-medium"><?php echo h($post['user_email']); ?></span> | 
                            投稿日: <?php echo h($post['created_at']); ?>
                            <?php if ($post['created_at'] !== $post['updated_at']): ?>
                                (編集済: <?php echo h($post['updated_at']); ?>)
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- 自分の投稿である場合、操作メニュー（編集・削除）を表示（認可チェック） -->
                    <?php if (isset($_SESSION['user_id']) && (int)$post['user_id'] === (int)$_SESSION['user_id']): ?>
                        <div class="flex space-x-2">
                            <a href="index.php?action=post_edit&id=<?php echo (int)$post['id']; ?>" 
                               class="text-xs bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 text-gray-600 px-2.5 py-1.5 rounded transition-colors border">
                                編集
                            </a>
                            
                            <form action="index.php?action=post_delete" method="POST" 
                                  onsubmit="return confirm('本当にこの投稿を削除してもよろしいですか？（論理削除されます）');" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
                                <button type="submit" 
                                        class="text-xs bg-red-50 hover:bg-red-100 text-red-600 px-2.5 py-1.5 rounded transition-colors border border-red-200">
                                    削除
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 投稿に画像が登録されている場合のみ表示 -->
                <?php if (!empty($post['image_path'])): ?>
                    <div class="my-4 overflow-hidden rounded-lg bg-gray-100 border max-h-[350px] flex items-center justify-center">
                        <!-- パスインジェクションおよびXSSを防止するため、パス情報をエスケープして展開します -->
                        <img src="postImages/<?php echo (int)$post['user_id']; ?>/<?php echo h($post['image_path']); ?>" 
                             alt="); ?>]"
                             class="object-contain max-h-[350px] w-full"
                             onerror="this.style.display='none';">
                    </div>
                <?php endif; ?>

                <!-- 投稿本文 -->
                <p class="text-gray-600 leading-relaxed text-sm mb-4 whitespace-pre-wrap"><?php echo h($post['body']); ?></p>

                <!-- いいねエリア (非同期) -->
                <div class="flex items-center space-x-2 pt-2 border-t border-gray-50">
                    <button 
                        class="like-btn flex items-center space-x-1 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all focus:outline-none"
                        data-post-id="<?php echo (int)$post['id']; ?>"
                        data-liked="<?php echo $post['is_liked'] ? 'true' : 'false'; ?>"
                        style="<?php echo !isset($_SESSION['user_id']) ? 'cursor: not-allowed; opacity: 0.6;' : ''; ?>"
                    >
                        <span class="heart-icon text-base">
                            <?php echo $post['is_liked'] ? '❤️' : '🤍'; ?>
                        </span>
                        <span>いいね！</span>
                        <span class="like-count bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full text-[10px]">
                            <?php echo (int)$post['like_count']; ?>
                        </span>
                    </button>
                    <span class="like-message text-[10px] text-gray-400"></span>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = '<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>';
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            if (!isLoggedIn) {
                const messageSpan = btn.parentElement.querySelector('.like-message');
                messageSpan.textContent = '※ログインするといいねできます';
                messageSpan.classList.add('text-red-500');
                setTimeout(() => messageSpan.textContent = '', 3000);
                return;
            }

            const postId = btn.getAttribute('data-post-id');
            const heartIcon = btn.querySelector('.heart-icon');
            const countSpan = btn.querySelector('.like-count');

            try {
                const response = await fetch('index.php?action=post_like', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: parseInt(postId, 10),
                        csrf_token: csrfToken
                    })
                });

                if (!response.ok) throw new Error('通信エラーが発生しました。');

                const data = await response.json();

                if (data.success) {
                    btn.setAttribute('data-liked', data.liked ? 'true' : 'false');
                    heartIcon.textContent = data.liked ? '❤️' : '🤍';
                    countSpan.textContent = data.like_count;
                    
                    if (data.liked) {
                        btn.classList.add('border-pink-300', 'bg-pink-50', 'text-pink-600');
                    } else {
                        btn.classList.remove('border-pink-300', 'bg-pink-50', 'text-pink-600');
                    }
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error(error);
                alert('いいね処理中にエラーが発生しました。再度お試しください。');
            }
        });

        if (btn.getAttribute('data-liked') === 'true') {
            btn.classList.add('border-pink-300', 'bg-pink-50', 'text-pink-600');
        }
    });
});
</script>

</main>
<footer class="bg-gray-100 text-center py-4 text-xs text-gray-500 border-t mt-8">
    &copy; 2026 Secure Board System. All rights reserved.
</footer>
</body>
</html>