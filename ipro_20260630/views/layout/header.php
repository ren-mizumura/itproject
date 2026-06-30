<?php
// ヘッダーおよびサイドバーのグローバルなデータ読み込み・アタッチ
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$role = $_SESSION['role'];

// つながり・友達の取得
$friends = $this->userModel->getFriends($user_id);
$friends_count = count($friends);

// 独自の属性タグ一覧を取得（投稿時用）
$unique_tags = $this->userModel->getMyUniqueTags($user_id);

// 受講カリキュラムの取得
$study_languages = $this->curriculumModel->getStudentLanguages($user_id);
$all_languages = $this->curriculumModel->getLanguages();

// 習熟度プログレス全体の算出（生徒の場合のみ）
$overall_average = 0;
if ($role === 'student') {
    $my_progress = $this->curriculumModel->getStudentProgress($user_id);
    $total_percent = 0;
    $total_tasks = 0;
    foreach ($study_languages as $lang) {
        $tasks = $this->curriculumModel->getTasksByLanguage($lang);
        foreach ($tasks as $task) {
            $total_tasks++;
            $total_percent += isset($my_progress[$lang][$task]) ? $my_progress[$lang][$task] : 0;
        }
    }
    $overall_average = $total_tasks > 0 ? round($total_percent / $total_tasks) : 0;
}

// 通知の一覧取得
$notifications = $this->postModel->getNotifications($user_id);
$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}
?>
<!DOCTYPE html>
<html lang="ja" class="h-full bg-[#0d1117] text-[#c9d1d9]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevLMS - 学習進捗管理掲示板</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        github: {
                            dark: '#0d1117',
                            canvas: '#161b22',
                            border: '#30363d',
                            text: '#c9d1d9',
                            muted: '#8b949e',
                            accent: '#58a6ff',
                            success: '#2ea44f',
                            successBg: '#238636',
                            danger: '#f85149',
                            attention: '#d29922',
                        }
                    },
                    fontFamily: {
                        mono: ['SFMono-Regular', 'Consolas', 'Liberation Mono', 'Menlo', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #161b22; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #8b949e; }
        .code-editor-textarea { resize: vertical; line-height: 1.5; }
    </style>
</head>
<body class="h-full flex flex-col font-sans antialiased selection:bg-github-accent/30 selection:text-white">

    <!-- システムメッセージトースト -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="toast-success" class="fixed top-4 right-4 z-50 p-4 rounded-lg border bg-[#1f2c22] border-github-success/30 text-github-success flex items-center gap-3 text-xs shadow-2xl">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <div><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></div>
            <button onclick="this.parentElement.remove()" class="text-github-success/80 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div id="toast-error" class="fixed top-4 right-4 z-50 p-4 rounded-lg border bg-[#2d1f1f] border-github-danger/30 text-[#f85149] flex items-center gap-3 text-xs shadow-2xl">
            <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
            <div><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></div>
            <button onclick="this.parentElement.remove()" class="text-[#f85149]/80 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- 招待URLコピー用のセーフティエリア -->
    <div id="invite-copied-modal" class="hidden fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 bg-github-successBg text-white rounded-md text-xs shadow-lg flex items-center gap-2">
        <i data-lucide="check" class="w-4 h-4"></i>
        <span>招待URLをクリップボードにコピーしました！</span>
    </div>

    <!-- モーダル（共通コンテナ） -->
    <div id="common-modal" class="fixed inset-0 z-40 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div id="common-modal-content" class="bg-github-canvas border border-github-border rounded-lg max-w-md w-full p-6 shadow-2xl">
            <!-- 呼び出し箇所で中身を書き換えて表示 -->
        </div>
    </div>

    <!-- ヘッダー -->
    <header class="bg-github-canvas border-b border-github-border px-6 py-3 flex items-center justify-between sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <div class="bg-github-accent text-github-dark p-2 rounded-md font-bold flex items-center justify-center">
                <i data-lucide="terminal" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-white flex items-center gap-2">
                    DevLMS <span class="text-xs px-2 py-0.5 rounded bg-github-border text-github-muted font-mono">XAMPP/MySQL</span>
                </h1>
                <p class="text-xs text-github-muted">プログラミング学習進捗管理システム</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 bg-[#0d1117] border border-github-border rounded-lg px-3 py-1.5">
                <span class="text-xs text-github-muted">ログイン中:</span>
                <span class="text-xs font-semibold text-white"><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded-full <?= ($role === 'teacher') ? 'bg-github-success/20 text-github-success border border-github-success/40' : 'bg-github-accent/20 text-github-accent border border-github-accent/40' ?>">
                    <?= ($role === 'teacher') ? '先生' : '生徒' ?>
                </span>
            </div>
            <a href="/20260630/?action=logout" class="text-xs border border-github-border hover:bg-github-danger hover:text-white text-github-muted px-3 py-1.5 rounded transition font-semibold flex items-center gap-1">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                <span>ログアウト</span>
            </a>
        </div>
    </header>

    <!-- メインレイアウト -->
    <main class="flex-1 flex overflow-hidden">
        
        <!-- 左側サイドバー（進捗、通知、友達） -->
        <aside class="w-80 border-r border-github-border bg-github-canvas flex flex-col overflow-y-auto">
            
            <!-- セクション1: プロフィール ＆ 進捗サマリー (生徒のみ) -->
            <div class="p-4 border-b border-github-border bg-[#0d1117]/50">
                <?php if ($role === 'teacher'): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-github-successBg text-white font-bold flex items-center justify-center text-base border-2 border-github-border">T</div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-white truncate"><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-[10px] text-github-success font-mono">Teacher Mode</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-github-muted mt-2">生徒の投稿への指導、カリキュラムや進捗の評価管理権限を保有しています。</p>
                <?php else: ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-github-accent/20 text-github-accent font-bold flex items-center justify-center text-base border-2 border-github-accent/40">
                            <?= htmlspecialchars(mb_substr($user_name, 0, 1), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-white truncate"><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-[10px] text-github-muted font-mono">@<?= htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <div class="flex justify-between items-center text-[10px] text-github-muted">
                            <span>学習総合習熟度</span>
                            <span class="font-bold text-github-accent font-mono"><?= $overall_average ?>%</span>
                        </div>
                        <div class="w-full bg-github-border rounded-full h-1.5 overflow-hidden">
                            <div class="bg-github-accent h-full rounded-full transition-all duration-500" style="width: <?= $overall_average ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- セクション2: システム内通知センター -->
            <div class="p-4 border-b border-github-border">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-1.5">
                        <i data-lucide="bell" class="w-4 h-4 text-github-attention"></i>
                        <span>システム通知</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="bg-github-danger text-white text-[9px] px-1.5 py-0.5 rounded-full font-bold font-mono"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </h3>
                    <form action="/20260630/?action=clear_notifications" method="post">
                        <button type="submit" class="text-[10px] text-github-accent hover:underline flex items-center gap-0.5">既読にする</button>
                    </form>
                </div>
                <div class="space-y-2 max-h-40 overflow-y-auto pr-1 text-xs text-github-muted">
                    <?php if (empty($notifications)): ?>
                        <p class="text-[11px] text-github-muted italic text-center py-2">新着通知はありません</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="p-2 rounded border transition text-[11px] <?= $n['is_read'] ? 'bg-transparent border-github-border/40 opacity-75' : 'bg-github-accent/5 border-github-accent/30' ?>">
                                <div class="flex justify-between items-start gap-1">
                                    <span class="text-white leading-tight font-medium"><?= htmlspecialchars($n['text'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-[8px] text-[#8b949e] shrink-0 font-mono"><?= date('m/d H:i', strtotime($n['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- セクション3: 友達・属性（つながり）管理 -->
            <div class="p-4 border-b border-github-border">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-1.5">
                        <i data-lucide="users" class="w-4 h-4 text-[#bc8cff]"></i>
                        <span>つながり・属性管理</span>
                    </h3>
                    <button onclick="openAddFriendDialog()" class="text-[11px] text-github-accent hover:underline flex items-center gap-0.5">
                        <i data-lucide="plus" class="w-3 h-3"></i>検索/追加
                    </button>
                </div>

                <!-- 生徒のみ: 専用招待リンク -->
                <?php if ($role === 'student'): ?>
                <div class="mb-3 bg-[#0d1117] p-2.5 rounded border border-github-border text-xs">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-[10px] text-github-muted">あなたの専用招待リンク</span>
                        <button onclick="copyInviteURL('http://localhost:8080/20260630/?invite_from=<?= $user_id ?>')" class="text-github-accent hover:underline text-[10px] flex items-center gap-0.5">
                            <i data-lucide="copy" class="w-2.5 h-2.5"></i>コピー
                        </button>
                    </div>
                    <div class="font-mono text-[9px] truncate text-github-muted select-all">
                        http://localhost:8080/20260630/?invite_from=<?= $user_id ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <?php if (empty($friends)): ?>
                        <p class="text-xs text-github-muted italic">つながり情報がありません</p>
                    <?php else: ?>
                        <?php foreach ($friends as $friend): ?>
                            <div class="flex items-center justify-between p-2 bg-[#0d1117] border border-github-border rounded text-xs">
                                <div class="min-w-0">
                                    <div class="font-bold text-white truncate"><?= htmlspecialchars($friend['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-[10px] text-github-muted font-mono truncate">@<?= htmlspecialchars($friend['id'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button onclick="openEditTagDialog('<?= htmlspecialchars($friend['id'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($friend['tag'], ENT_QUOTES, 'UTF-8') ?>')" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-github-accent/10 text-github-accent border border-github-accent/20 hover:bg-github-accent/20 transition">
                                        <?= htmlspecialchars($friend['tag'], ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                    <form action="/20260630/?action=remove_friend" method="post" onsubmit="return confirm('つながりを解除しますか？');">
                                        <input type="hidden" name="target_id" value="<?= htmlspecialchars($friend['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="text-github-danger hover:text-red-400 p-0.5"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- セクション4: 学習カリキュラム登録（生徒は選択追加、先生は管理画面へ） -->
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-1.5">
                        <i data-lucide="book-open" class="w-4 h-4 text-github-accent"></i>
                        <span><?= ($role === 'teacher') ? 'カリキュラム一覧' : '学習プログラム' ?></span>
                    </h3>
                    <?php if ($role === 'teacher'): ?>
                        <a href="/20260630/?tab=curriculum-editor" class="text-[11px] text-github-accent hover:underline flex items-center gap-0.5">
                            <i data-lucide="edit" class="w-3 h-3"></i>編集
                        </a>
                    <?php else: ?>
                        <button onclick="openSelectLanguageDialog()" class="text-[11px] text-github-accent hover:underline flex items-center gap-0.5">
                            <i data-lucide="plus" class="w-3 h-3"></i>変更
                        </button>
                    <?php endif; ?>
                </div>
                <div class="space-y-2 text-xs">
                    <?php if ($role === 'teacher'): ?>
                        <?php foreach ($all_languages as $lang): 
                            $tasks = $this->curriculumModel->getTasksByLanguage($lang); ?>
                            <div class="p-2 bg-[#0d1117] rounded border border-github-border flex justify-between items-center">
                                <span class="font-bold text-white"><?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-[10px] bg-github-border text-github-muted px-1.5 rounded font-mono"><?= count($tasks) ?> タスク</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php if (empty($study_languages)): ?>
                            <p class="text-github-muted italic text-[11px]">現在選択している言語はありません。「変更」からカリキュラムを登録してください。</p>
                        <?php else: ?>
                            <?php foreach ($study_languages as $lang): 
                                $tasks = $this->curriculumModel->getTasksByLanguage($lang);
                                $lang_percent = 0;
                                if (!empty($tasks)) {
                                    $sum = 0;
                                    foreach ($tasks as $task) {
                                        $sum += isset($my_progress[$lang][$task]) ? $my_progress[$lang][$task] : 0;
                                    }
                                    $lang_percent = round($sum / count($tasks));
                                }
                            ?>
                            <div class="p-2 bg-[#0d1117] rounded border border-github-border space-y-1.5">
                                <div class="flex justify-between items-center text-xs font-bold text-white">
                                    <span><?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-github-accent font-mono text-[10px]"><?= $lang_percent ?>%</span>
                                </div>
                                <div class="w-full bg-github-border rounded-full h-1 overflow-hidden">
                                    <div class="bg-github-accent h-full rounded-full" style="width: <?= $lang_percent ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>