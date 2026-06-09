<?php
// セッションを開始
session_start();
require_once 'articleModel.php';

// ログインガード
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];

// ログインユーザー自身が執筆した記事一覧を取得
$myPosts = ArticleModel::findByUserId($currentUser['id']);

// 削除要求の処理
$error = '';
$success = '';
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = $_POST['post_id'] ?? '';
    $result = ArticleModel::delete($deleteId, $currentUser['id']);
    if ($result === true) {
        $success = '記事を削除しました。';
        // 記事一覧を再読み込み
        $myPosts = ArticleModel::findByUserId($currentUser['id']);
    } else {
        $error = $result;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クリエイターダッシュボード | NoteLike</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- ナビゲーションバー -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-black text-emerald-600 tracking-wider">NoteLike</a>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-slate-600 hover:text-slate-900 text-sm font-semibold">タイムライン</a>
                <a href="logout.php" class="text-rose-600 hover:text-rose-900 text-sm font-semibold">ログアウト</a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-10">

        <!-- アラート表示 -->
        <?php if ($success): ?>
            <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl text-sm mb-6 border border-emerald-100">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-rose-50 text-rose-600 p-4 rounded-xl text-sm mb-6 border border-rose-100">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- クリエイターヘッダー -->
        <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-700 font-bold text-3xl flex items-center justify-center rounded-full">
                    <?= mb_substr($currentUser['username'], 0, 1) ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?> さん</h2>
                    <p class="text-sm text-slate-500">アカウントID: <?= htmlspecialchars($currentUser['id'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            
            <a href="create_post.php" class="w-full md:w-auto text-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-full transition shadow-sm">
                新しい記事を書く
            </a>
        </div>

        <!-- 自分の記事一覧セクション -->
        <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-4 mb-6">自分の記事を管理する</h3>

            <?php if (empty($myPosts)): ?>
                <div class="text-center py-12 text-slate-400 text-sm">
                    投稿した記事はまだありません。
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($myPosts as $post): ?>
                        <div class="py-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h4 class="font-bold text-slate-900 text-lg hover:text-emerald-600 transition">
                                    <a href="view_post.php?id=<?= urlencode($post['id']) ?>">
                                        <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </h4>
                                <p class="text-xs text-slate-400 mt-1">投稿日: <?= date('Y/m/d H:i', strtotime($post['created_at'])) ?></p>
                            </div>

                            <div class="flex items-center space-x-2 w-full md:w-auto justify-end">
                                <a href="create_post.php?edit_id=<?= urlencode($post['id']) ?>" 
                                   class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-slate-900 text-sm font-semibold rounded-lg transition text-center w-24">
                                    編集
                                </a>
                                
                                <!-- 削除はPOSTリクエストで安全に行う -->
                                <form action="mypage.php" method="POST" onsubmit="return confirm('本当にこの記事を削除しますか？');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id'], ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" 
                                            class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 text-sm font-semibold rounded-lg transition w-24">
                                        削除
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>