<?php
// ヘッダー共通処理
$current_tab = $_GET['tab'] ?? 'feed';
$invitation_url = BASE_URL . "invite/" . ($userInfo['invite_token'] ?? '');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitProgress - 学習進捗管理システム</title>
    <!-- Tailwind CSS (CDN経由でスタイリング) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            background-color: #f6f8fa;
            color: #24292f;
        }
        .code-font {
            font-family: 'Fira Code', 'Courier New', Courier, monospace;
        }
        /* Custom GitHub scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- GitHub風 ナビゲーションバー -->
    <header class="bg-[#24292f] text-white py-3 px-6 flex items-center justify-between shadow-sm sticky top-0 z-50">
        <div class="flex items-center space-x-4">
            <a href="<?php echo BASE_URL; ?>dashboard" class="flex items-center space-x-2 text-white font-semibold text-lg hover:opacity-90">
                <i data-lucide="git-branch" class="text-[#3fb950] w-6 h-6"></i>
                <span class="tracking-tight">GitProgress</span>
            </a>
            <span class="bg-[#30363d] text-xs font-semibold px-2 py-0.5 rounded-full border border-[#444c56] text-[#c9d1d9]">
                <?php echo $_SESSION['role'] === 'teacher' ? '先生（管理者）' : '生徒'; ?>
            </span>
        </div>

        <div class="flex items-center space-x-6">
            <div class="text-sm font-medium text-[#c9d1d9] flex items-center space-x-1">
                <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                <span><?php echo htmlspecialchars($userInfo['display_name']); ?> (<?php echo htmlspecialchars($userInfo['username']); ?>)</span>
            </div>
            <a href="<?php echo BASE_URL; ?>logout" class="text-sm font-medium text-red-400 hover:text-red-300 flex items-center space-x-1 border border-red-900/30 px-3 py-1 rounded bg-red-950/10 hover:bg-red-950/20 transition">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>ログアウト</span>
            </a>
        </div>
    </header>

    <!-- メインコンテンツ配置エリア（PC特化 1200px） -->
    <div class="max-w-[1300px] w-full mx-auto flex-grow flex p-6 gap-6">
        
        <!-- 左サイドバー（基本プロフィール、友達機能、学習言語、招待リンク、通知一覧） -->
        <aside class="w-1/4 flex flex-col space-y-6 flex-shrink-0">
            
            <!-- プロフィール & 招待URLカード -->
            <div class="bg-white border border-[#d0d7de] rounded-lg p-5 shadow-sm">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-[#f3f4f6] border border-[#d0d7de] flex items-center justify-center text-[#24292f] font-bold text-lg">
                        <?php echo mb_substr($userInfo['display_name'], 0, 1); ?>
                    </div>
                    <div>
                        <h3 class="font-bold text-base leading-tight text-[#24292f]"><?php echo htmlspecialchars($userInfo['display_name']); ?></h3>
                        <p class="text-xs text-gray-500">@<?php echo htmlspecialchars($userInfo['username']); ?></p>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'student'): ?>
                <!-- 生徒専用招待URLコピー機能 -->
                <div class="border-t border-gray-100 pt-3 mt-3">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">あなたの友達招待URL</label>
                    <div class="flex space-x-1">
                        <input type="text" readonly id="invite-url-input" value="<?php echo htmlspecialchars($invitation_url); ?>" 
                               class="text-xs bg-gray-50 border border-gray-300 text-gray-600 px-2 py-1.5 rounded w-full focus:outline-none overflow-hidden text-ellipsis whitespace-nowrap">
                        <button onclick="copyInviteURL()" class="bg-[#24292f] hover:bg-[#1f2328] text-white p-1.5 rounded flex items-center justify-center text-xs transition" title="クリップボードにコピー">
                            <i id="copy-icon" data-lucide="copy" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <span id="copy-message" class="text-[10px] text-green-600 font-semibold mt-1 hidden flex items-center space-x-1">
                        <i data-lucide="check" class="w-3 h-3"></i> <span>コピーしました！</span>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- 通知（リアルタイム風一覧） -->
            <div class="bg-white border border-[#d0d7de] rounded-lg shadow-sm">
                <div class="px-4 py-3 border-b border-[#d0d7de] flex items-center justify-between bg-gray-50 rounded-t-lg">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="bell" class="w-4 h-4 text-gray-600"></i>
                        <span class="font-bold text-sm text-gray-700">システム内通知</span>
                    </div>
                    <span class="bg-[#afb8c1]/30 text-gray-700 text-xs px-2 py-0.5 rounded-full font-semibold">
                        <?php echo count($unreadNotifications); ?>
                    </span>
                </div>
                <div class="p-2 max-h-[220px] overflow-y-auto divide-y divide-gray-100">
                    <?php if (empty($unreadNotifications)): ?>
                        <p class="text-xs text-gray-400 py-4 text-center">新着通知はありません</p>
                    <?php else: ?>
                        <?php foreach ($unreadNotifications as $notif): ?>
                            <div class="p-3 text-xs text-gray-700 hover:bg-blue-50/50 rounded-lg transition mb-1 border-l-4 border-blue-500 bg-blue-50/20">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <span class="text-[10px] text-gray-400 block mt-1"><?php echo date('m/d H:i', strtotime($notif['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 生徒専用：登録している学習プロフィール（言語選択） -->
            <?php if ($_SESSION['role'] === 'student'): ?>
            <div class="bg-white border border-[#d0d7de] rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                    <h3 class="font-bold text-sm text-[#24292f] flex items-center space-x-1">
                        <i data-lucide="book-open" class="w-4 h-4 text-[#3fb950]"></i>
                        <span>学習プロフィール</span>
                    </h3>
                    <a href="?tab=curriculum" class="text-xs text-blue-600 hover:underline flex items-center">
                        <i data-lucide="plus" class="w-3 h-3"></i>登録追加
                    </a>
                </div>
                <?php if (empty($studentCurriculums)): ?>
                    <p class="text-xs text-gray-400 py-2 text-center">学習言語が未登録です。<br>「登録追加」から選択してください。</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($studentCurriculums as $sc): ?>
                            <div>
                                <div class="flex items-center justify-between text-xs font-semibold mb-1">
                                    <span class="text-gray-700"><?php echo htmlspecialchars($sc['name']); ?></span>
                                    <span class="text-[#3fb950]"><?php echo $sc['average_proficiency']; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-150 h-2 rounded-full overflow-hidden border border-gray-200">
                                    <div class="bg-[#3fb950] h-full rounded-full transition-all duration-300" style="width: <?php echo $sc['average_proficiency']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </aside>

        <!-- 右メイン領域 -->
        <main class="flex-grow w-3/4 flex flex-col space-y-6">