<?php
session_start();
require_once 'articleModel.php';

// 全ての記事を取得
$posts = ArticleModel::findAll();
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoteLike - クリエイターの創作プラットフォーム</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <!-- ナビゲーションバー (note風のシンプルデザイン) -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-black text-emerald-600 tracking-wider">NoteLike</a>
            
            <div class="flex items-center space-x-4">
                <?php if ($isLoggedIn): ?>
                    <a href="create_post.php" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-full transition shadow-sm">
                        投稿する
                    </a>
                    <a href="mypage.php" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-full transition">
                        マイページ
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-slate-600 hover:text-slate-900 text-sm font-semibold">ログイン</a>
                    <a href="signup.php" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-full transition shadow-sm">
                        新規登録
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- メインコンテンツ -->
    <main class="max-w-4xl mx-auto px-4 py-10">
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight sm:text-4xl">コトバと、出会おう。</h1>
            <p class="mt-3 text-lg text-slate-500">NoteLikeは、だれでも自分の言葉を発信できるシンプルなブログプラットフォームです。</p>
        </div>

        <h2 class="text-xl font-bold border-b border-slate-200 pb-3 mb-6">最新のフィード</h2>

        <?php if (empty($posts)): ?>
            <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-200">
                <p class="text-slate-400 text-lg">まだ投稿がありません。最初の記事を書いてみませんか？</p>
                <?php if ($isLoggedIn): ?>
                    <a href="create_post.php" class="inline-block mt-4 px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-full hover:bg-emerald-700 transition">記事を書く</a>
                <?php else: ?>
                    <a href="signup.php" class="inline-block mt-4 px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-full hover:bg-emerald-700 transition font-medium">今すぐ参加する</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($posts as $post): ?>
                    <article class="bg-white p-6 rounded-2xl border border-slate-100 hover:shadow-md transition duration-200 flex flex-col justify-between">
                        <div>
                            <!-- 作成者情報 -->
                            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-3">
                                <span class="font-bold text-slate-700"><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span>•</span>
                                <span><?= date('Y/m/d H:i', strtotime($post['created_at'])) ?></span>
                            </div>

                            <!-- 記事タイトル -->
                            <h3 class="text-xl font-bold text-slate-900 hover:text-emerald-600 transition mb-2">
                                <a href="view_post.php?id=<?= urlencode($post['id']) ?>">
                                    <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h3>

                            <!-- 本文のプレビュー (先頭120文字) -->
                            <p class="text-slate-600 text-sm leading-relaxed mb-4">
                                <?php 
                                    $plainText = strip_tags($post['content']);
                                    echo mb_strlen($plainText) > 120 ? mb_substr($plainText, 0, 120) . '...' : $plainText;
                                ?>
                            </p>
                        </div>
                        
                        <div class="flex justify-between items-center text-xs pt-3 border-t border-slate-50">
                            <a href="view_post.php?id=<?= urlencode($post['id']) ?>" class="text-emerald-600 font-semibold hover:underline">
                                続きを読む &rarr;
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>