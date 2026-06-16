<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セキュアなMVCログイン＆TODO管理システム</title>
    <!-- Tailwind CSS を CDN 経由で読み込んでモダンなデザインを適用 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

<!-- ナビゲーションバー -->
<nav class="bg-white border-b border-slate-200 py-4 px-6 shadow-sm z-50">
    <div class="max-w-6xl mx-auto flex items-center justify-between">
        <!-- ロゴエリア -->
        <a href="index.php?action=todo" class="flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-black text-lg">T</span>
            <span class="font-extrabold text-slate-800 tracking-tight">MVC TODO</span>
        </a>

        <!-- 右側のメニュー -->
        <div class="flex items-center gap-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-xs text-slate-500 hidden sm:inline-block font-medium">
                    ログイン中: <strong class="text-slate-800 font-semibold"><?php echo h($_SESSION['user_email']); ?></strong>
                </span>
                <a href="index.php?action=logout" 
                   class="bg-rose-500 hover:bg-rose-600 text-white text-xs font-bold py-2 px-4 rounded-lg transition-all shadow-sm flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    ログアウト
                </a>
            <?php else: ?>
                <a href="index.php?action=login" class="text-slate-600 hover:text-slate-900 text-sm font-semibold transition-colors">
                    ログイン
                </a>
                <a href="index.php?action=register" 
                   class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded-lg transition-all shadow-sm">
                    新規登録
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- 
  ヘッダーでの中央寄せ属性を排除し、シンプルなコンテナ要素とします。
  これにより、各View側がレイアウト（中央揃え、または幅広リスト）を自由にコントロールできるようになります。
-->
<main class="flex-grow">