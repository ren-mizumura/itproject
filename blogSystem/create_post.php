<?php
session_start();
require_once 'articleModel.php';

// ログインガード
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];

$editId = $_GET['edit_id'] ?? '';
$isEditMode = !empty($editId);

$title = '';
$content = '';
$error = '';
$success = '';

// 編集モードの場合、既存の記事情報を取得して初期表示
if ($isEditMode) {
    $post = ArticleModel::findById($editId);
    if (!$post) {
        header('Location: mypage.php');
        exit;
    }
    // 本人以外は編集できないようにガード
    if ($post['user_id'] !== $currentUser['id']) {
        header('Location: mypage.php');
        exit;
    }
    $title = $post['title'];
    $content = $post['content'];
}

// フォーム送信時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if ($isEditMode) {
        // 更新処理
        $result = ArticleModel::update($editId, $currentUser['id'], $title, $content);
        if ($result === true) {
            $success = '記事を更新しました！';
            header('Refresh: 1.5; url=mypage.php');
        } else {
            $error = $result;
        }
    } else {
        // 新規作成処理
        $result = ArticleModel::create($currentUser['id'], $currentUser['username'], $title, $content);
        if (is_array($result)) {
            $success = '記事を投稿しました！';
            header('Refresh: 1.5; url=mypage.php');
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? '記事の編集' : '新しい投稿' ?> | NoteLike</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen text-slate-800">

    <!-- ヘッダー -->
    <header class="border-b border-slate-100 py-4 px-6 flex justify-between items-center max-w-5xl mx-auto">
        <a href="mypage.php" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition">
            &larr; キャンセルして戻る
        </a>
        <h1 class="text-md font-bold text-slate-800"><?= $isEditMode ? '記事の編集' : 'ノートを作成' ?></h1>
        <div></div> <!-- バランス用の余白 -->
    </header>

    <!-- 記事作成フォーム (執筆に集中できるノートライクな極小UI) -->
    <main class="max-w-3xl mx-auto px-4 py-8">
        
        <!-- エラーメッセージ -->
        <?php if ($error): ?>
            <div class="bg-rose-50 text-rose-600 p-4 rounded-xl text-sm mb-6 border border-rose-100">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- 成功メッセージ -->
        <?php if ($success): ?>
            <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl text-sm mb-6 border border-emerald-100">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="create_post.php<?= $isEditMode ? '?edit_id=' . urlencode($editId) : '' ?>" method="POST" class="space-y-6">
            
            <!-- タイトル入力 -->
            <div>
                <input type="text" name="title" id="title" required
                       class="w-full text-3xl md:text-4xl font-extrabold placeholder-slate-300 border-none outline-none focus:ring-0 py-2"
                       placeholder="タイトル" 
                       value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- 本文入力 -->
            <div>
                <textarea name="content" id="content" required rows="15"
                          class="w-full text-lg placeholder-slate-300 border-none outline-none focus:ring-0 py-2 resize-y font-serif leading-relaxed"
                          placeholder="あなたの言葉を、ここからはじめましょう..."><?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- フッターアクション -->
            <div class="pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit"
                        class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-full transition shadow-sm">
                    <?= $isEditMode ? '更新する' : '公開する' ?>
                </button>
            </div>
        </form>
    </main>

</body>
</html>