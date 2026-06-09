<?php
session_start();
require_once 'articleModel.php';

// クエリパラメータから記事IDを取得
$postId = $_GET['id'] ?? '';
$post = ArticleModel::findById($postId);

// 記事が存在しない場合はトップページへリダイレクト
if (!$post) {
    header('Location: index.php');
    exit;
}

$isLoggedIn = isset($_SESSION['user']);
$isAuthor = $isLoggedIn && ($_SESSION['user']['id'] === $post['user_id']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?> | NoteLike</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- ナビゲーションバー -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-black text-emerald-600 tracking-wider">NoteLike</a>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-slate-600 hover:text-slate-900 text-sm font-semibold">一覧に戻る</a>
                <?php if ($isLoggedIn): ?>
                    <a href="mypage.php" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-full transition">マイページ</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- 記事詳細コンテンツ -->
    <main class="max-w-3xl mx-auto px-4 py-12">
        <article class="bg-white p-8 md:p-12 rounded-3xl border border-slate-100 shadow-sm">
            
            <!-- メタ情報 -->
            <div class="flex items-center space-x-3 text-sm text-slate-500 mb-6">
                <div class="w-10 h-10 bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center rounded-full">
                    <?= mb_substr($post['username'], 0, 1) ?>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-base"><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs"><?= date('Y年m月d日 H:i', strtotime($post['created_at'])) ?></p>
                </div>
            </div>

            <!-- タイトル -->
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-950 leading-tight mb-8">
                <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <!-- 記事本文 -->
            <!-- nl2br で改行を維持して表示します -->
            <div class="prose max-w-none text-slate-800 leading-relaxed text-lg space-y-6 whitespace-pre-wrap font-serif">
                <?= nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')) ?>
            </div>

            <!-- 執筆者本人の場合のアクションボタン -->
            <?php if ($isAuthor): ?>
                <div class="mt-12 pt-6 border-t border-slate-100 flex items-center space-x-3">
                    <a href="create_post.php?edit_id=<?= urlencode($post['id']) ?>" 
                       class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition">
                        この記事を編集する
                    </a>
                </div>
            <?php endif; ?>

        </article>
    </main>

</body>
</html>