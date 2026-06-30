<?php
// ヘッダー読み込み
require_once 'views/layout/header.php';

// メイン用のデータ
$tab = $_GET['tab'] ?? 'feed';
$lang_filter = $_GET['lang_filter'] ?? 'all';
$author_filter = $_GET['author_filter'] ?? 'all';

// 全体のタイムラインを取得
$timeline = $this->postModel->getTimeline($user_id, $role, $lang_filter, $author_filter);

// 先生用: 受講している全生徒
$all_students = $this->userModel->getAllStudents();
?>

<!-- 共通コンテンツレイアウト -->
<section class="flex-1 flex flex-col bg-[#0d1117] overflow-hidden">
    
    <!-- タブヘッダー -->
    <div class="bg-[#161b22] border-b border-[#30363d] px-6 flex items-center justify-between">
        <nav class="flex gap-4 text-sm font-medium" aria-label="Tabs">
            <a href="/20260630/?tab=feed" class="border-b-2 <?= $tab === 'feed' ? 'border-[#58a6ff] text-white' : 'border-transparent text-[#8b949e] hover:text-white' ?> py-4 px-1 flex items-center gap-2 transition font-semibold">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                <span>進捗・質問掲示板</span>
            </a>
            <a href="/20260630/?tab=analytics" class="border-b-2 <?= $tab === 'analytics' ? 'border-[#58a6ff] text-white' : 'border-transparent text-[#8b949e] hover:text-white' ?> py-4 px-1 flex items-center gap-2 transition">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                <span>学習進捗・可視化</span>
            </a>
            <?php if ($role === 'teacher'): ?>
                <a href="/20260630/?tab=curriculum-editor" class="border-b-2 <?= $tab === 'curriculum-editor' ? 'border-[#58a6ff] text-white' : 'border-transparent text-[#8b949e] hover:text-white' ?> py-4 px-1 flex items-center gap-2 transition">
                    <i data-lucide="settings" class="w-4 h-4"></i>
                    <span>カリキュラム構成 (先生限定)</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- スクロール可能なメインビュー -->
    <div class="flex-1 overflow-y-auto p-6" id="tab-content-container">

        <!-- ==========================================
             1. 掲示板フィードタブ
             ========================================== -->
        <?php if ($tab === 'feed'): ?>
            <div class="space-y-6 max-w-4xl mx-auto">
                
                <!-- 投稿フォーム (生徒、先生両方可能) -->
                <?php if (!empty($study_languages) || $role === 'teacher'): ?>
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg shadow-sm">
                    <div class="border-b border-[#30363d] bg-[#161b22]/50 px-4 py-3 flex items-center justify-between">
                        <span class="text-xs font-semibold text-white flex items-center gap-1.5">
                            <i data-lucide="edit-3" class="w-4 h-4 text-[#58a6ff]"></i>
                            <span>新しい学習進捗 / 質問を投稿する</span>
                        </span>
                        <span class="text-xs text-[#8b949e]">投稿者: <?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    
                    <form id="post-form" action="/20260630/?action=create_post" method="POST" enctype="multipart/form-data" class="p-4 space-y-4">
                        
                        <!-- カリキュラム（対象言語・タスク）のマスター連動 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">学習言語の選択 <span class="text-github-danger">*</span></label>
                                <select id="post-language" name="language" required onchange="updatePostTaskDropdown(this.value)" class="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-[#58a6ff]">
                                    <option value="">-- 選択してください --</option>
                                    <?php 
                                    $form_langs = ($role === 'teacher') ? $all_languages : $study_languages;
                                    foreach ($form_langs as $lang): ?>
                                        <option value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">詳細タスク（カリキュラム） <span class="text-github-danger">*</span></label>
                                <select id="post-task" name="task" required class="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-[#58a6ff]">
                                    <option value="">-- 先に言語を選択してください --</option>
                                </select>
                            </div>
                        </div>

                        <!-- 進捗内容自由記述 -->
                        <div>
                            <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">進捗内容・質問本文 <span class="text-github-danger">*</span></label>
                            <textarea name="body" required rows="4" placeholder="今日学んだこと、躓いていること、実行結果などを入力してください..." class="w-full bg-[#0d1117] border border-[#30363d] rounded p-3 text-white text-xs focus:outline-none focus:border-[#58a6ff] placeholder-gray-600"></textarea>
                        </div>

                        <!-- ソースコード専用欄 -->
                        <div class="border border-[#30363d] rounded overflow-hidden">
                            <div class="bg-[#21262d] px-3 py-1.5 border-b border-[#30363d] flex items-center justify-between">
                                <span class="text-xs text-[#8b949e] font-mono flex items-center gap-1.5">
                                    <i data-lucide="code" class="w-3.5 h-3.5 text-[#58a6ff]"></i>
                                    <span>プログラムコード（任意）</span>
                                </span>
                                <span class="text-[10px] text-[#8b949e]">monospace 記述欄</span>
                            </div>
                            <textarea name="code" rows="6" placeholder="// ここにコードを書き込めます（自動でシンタックス・等幅フォントとしてタイムラインに並びます）" class="w-full bg-[#0d1117] font-mono text-xs text-[#e6edf3] p-3 focus:outline-none resize-y leading-[1.5rem] code-editor-textarea"></textarea>
                        </div>

                        <!-- 参考URL ＆ ファイル添付 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">参考URL（任意）</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="link" class="w-3.5 h-3.5 text-[#8b949e]"></i>
                                    </div>
                                    <input type="url" name="url" placeholder="https://example.com" class="w-full bg-[#0d1117] border border-[#30363d] rounded pl-9 pr-3 py-2 text-white text-xs focus:outline-none focus:border-[#58a6ff]">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">学習ファイルの添付（最大5MB: 画像/txt/pdf）</label>
                                <input type="file" name="attached_file" class="block w-full text-xs text-github-muted file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#21262d] file:text-white hover:file:bg-[#30363d] cursor-pointer">
                            </div>
                        </div>

                        <!-- 公開範囲設定 -->
                        <div>
                            <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">公開範囲（プライバシー設定） <span class="text-github-danger">*</span></label>
                            <div class="flex items-center gap-4 bg-[#0d1117] p-3 border border-[#30363d] rounded">
                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-white">
                                    <input type="radio" name="visibility" value="all" checked onchange="toggleFormTagSelector(false)" class="text-[#58a6ff] focus:ring-[#58a6ff] bg-[#0d1117] border-[#30363d]">
                                    <span>全体公開</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-white">
                                    <input type="radio" name="visibility" value="restricted" onchange="toggleFormTagSelector(true)" class="text-[#58a6ff] focus:ring-[#58a6ff] bg-[#0d1117] border-[#30363d]">
                                    <span>属性指定公開（プライベート）</span>
                                </label>
                            </div>
                            <div id="visibility-tag-selector-container" class="hidden mt-2.5">
                                <label class="block text-xs font-semibold text-[#8b949e] mb-1.5">開示する属性タグを選択してください</label>
                                <select name="target_tag" class="bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-white text-xs focus:outline-none focus:border-[#58a6ff]">
                                    <?php if (empty($unique_tags)): ?>
                                        <option value="">-- ※設定されている属性タグがありません --</option>
                                    <?php else: ?>
                                        <?php foreach ($unique_tags as $utag): ?>
                                            <option value="<?= htmlspecialchars($utag, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($utag, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <p class="text-[10px] text-github-muted mt-1">※この属性をあなたが設定しているユーザーにのみ公開されます（設定属性名や宛先情報は他ユーザーには一切見えません）</p>
                            </div>
                        </div>

                        <!-- ボタン -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-[#2ea44f] hover:bg-[#238636] text-white text-xs font-semibold px-4 py-2 rounded shadow-sm flex items-center gap-1.5 transition">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>タイムラインに投稿</span>
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="bg-[#161b22] border border-github-attention/30 rounded-lg p-6 text-center text-xs">
                    <i data-lucide="alert-circle" class="w-8 h-8 mx-auto text-github-attention mb-2"></i>
                    <p class="text-white font-bold mb-1">学習言語プロフィールが空です</p>
                    <p class="text-github-muted mb-3">左サイドバーの「学習プログラム」の「変更」から、受講したい学習言語を設定すると投稿が可能になります。</p>
                </div>
                <?php endif; ?>

                <!-- フィルターバー -->
                <div class="flex flex-col sm:flex-row gap-2.5 justify-between items-stretch sm:items-center bg-[#161b22] p-3 border border-[#30363d] rounded-lg">
                    <div class="flex items-center gap-2">
                        <i data-lucide="filter" class="w-4 h-4 text-[#8b949e]"></i>
                        <span class="text-xs font-semibold text-white">タイムラインの絞り込み:</span>
                    </div>
                    <form action="/20260630/" method="GET" class="flex flex-wrap gap-2">
                        <input type="hidden" name="tab" value="feed">
                        <select name="lang_filter" onchange="this.form.submit()" class="bg-[#0d1117] border border-[#30363d] text-xs rounded px-2.5 py-1.5 text-white">
                            <option value="all" <?= $lang_filter === 'all' ? 'selected' : '' ?>>すべての言語</option>
                            <?php foreach ($all_languages as $l): ?>
                                <option value="<?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?>" <?= $lang_filter === $l ? 'selected' : '' ?>><?= htmlspecialchars($l, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="author_filter" onchange="this.form.submit()" class="bg-[#0d1117] border border-[#30363d] text-xs rounded px-2.5 py-1.5 text-white">
                            <option value="all" <?= $author_filter === 'all' ? 'selected' : '' ?>>すべての投稿者</option>
                            <?php foreach ($all_students as $student): ?>
                                <option value="<?= htmlspecialchars($student['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $author_filter === $student['id'] ? 'selected' : '' ?>><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <!-- 掲示板フィード -->
                <div class="space-y-6">
                    <?php if (empty($timeline)): ?>
                        <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-8 text-center text-github-muted">
                            <i data-lucide="info" class="w-8 h-8 mx-auto text-[#8b949e] mb-2"></i>
                            <p class="text-sm">該当する学習進捗投稿はありません。</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($timeline as $post): 
                            $is_author = $post['author_id'] === $user_id;
                            $is_teacher = $role === 'teacher';
                            // 投稿後1時間制限
                            $elapsed_seconds = time() - strtotime($post['created_at']);
                            $can_edit_delete = $is_teacher || ($is_author && $elapsed_seconds <= 3600);
                        ?>
                        <div class="bg-[#161b22] border border-[#30363d] rounded-lg shadow-sm">
                            <div class="p-4 border-b border-[#30363d]">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-9 h-9 rounded-full bg-[#58a6ff]/10 border border-[#58a6ff]/30 flex items-center justify-center font-bold text-[#58a6ff] text-sm">
                                            <?= htmlspecialchars(mb_substr($post['author_name'], 0, 1), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-white text-xs"><?= htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="text-[10px] text-[#8b949e] font-mono">@<?= htmlspecialchars($post['author_id'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#58a6ff]/10 text-[#58a6ff] font-bold font-mono">
                                                    <?= htmlspecialchars($post['language'], ENT_QUOTES, 'UTF-8') ?> ＞ <?= htmlspecialchars($post['task'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[9px] text-[#8b949e] font-mono"><?= date('Y/m/d H:i', strtotime($post['created_at'])) ?></span>
                                                <?php if ($post['visibility'] === 'restricted'): ?>
                                                    <span class="bg-[#bc8cff]/10 text-[#bc8cff] border border-[#bc8cff]/30 text-[10px] px-2 py-0.5 rounded flex items-center gap-1">
                                                        <i data-lucide="lock" class="w-3 h-3"></i>
                                                        <span><?= $is_author ? htmlspecialchars($post['target_tag'], ENT_QUOTES, 'UTF-8') . " 限定" : "プライベート" ?></span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-[#30363d] text-github-muted text-[10px] px-2 py-0.5 rounded flex items-center gap-1">
                                                        <i data-lucide="globe" class="w-3 h-3"></i><span>全体公開</span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ライフサイクル編集・削除 -->
                                    <div class="flex gap-2">
                                        <?php if ($can_edit_delete): ?>
                                            <button onclick="openEditPostDialog(<?= $post['id'] ?>, '<?= htmlspecialchars(json_encode($post['body']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(json_encode($post['code']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($post['url'], ENT_QUOTES, 'UTF-8') ?>')" class="bg-[#21262d] border border-[#30363d] hover:bg-[#30363d] text-[10px] text-white px-2.5 py-1 rounded">編集</button>
                                            <form action="/20260630/?action=delete_post" method="POST" onsubmit="return confirm('投稿を削除しますか？');">
                                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                <button type="submit" class="bg-github-border hover:bg-github-danger text-github-danger hover:text-white border border-github-border hover:border-github-danger text-[10px] px-2.5 py-1 rounded transition">削除</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-[9px] text-github-muted">編集不可(1時間経過済)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- 本文 -->
                                <div class="text-xs text-white leading-relaxed whitespace-pre-wrap mt-2"><?= htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8') ?></div>

                                <!-- 添付ファイル -->
                                <?php if (!empty($post['file_path'])): 
                                    $file_ext = strtolower(pathinfo($post['file_path'], PATHINFO_EXTENSION));
                                ?>
                                    <div class="mt-3 bg-[#0d1117] border border-[#30363d] p-3 rounded-lg max-w-sm">
                                        <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <!-- 画像ファイルの場合 -->
                                            <img src="/20260630/<?= htmlspecialchars($post['file_path'], ENT_QUOTES, 'UTF-8') ?>" class="max-h-48 rounded mb-2 w-auto object-contain bg-[#161b22]" alt="添付画像">
                                        <?php endif; ?>
                                        <div class="flex items-center justify-between text-xs">
                                            <div class="flex items-center gap-2 text-white truncate">
                                                <i data-lucide="file-text" class="w-4 h-4 text-[#58a6ff]"></i>
                                                <span class="truncate"><?= htmlspecialchars($post['file_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <a href="/20260630/<?= htmlspecialchars($post['file_path'], ENT_QUOTES, 'UTF-8') ?>" download class="bg-[#21262d] hover:bg-[#30363d] text-white text-[10px] px-2 py-1 rounded flex items-center gap-1">
                                                <i data-lucide="download" class="w-3 h-3"></i><span>DL</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- 参考URL -->
                                <?php if (!empty($post['url'])): ?>
                                    <div class="mt-2.5 text-xs flex items-center gap-1.5 text-[#58a6ff]">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        <a href="<?= htmlspecialchars($post['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="hover:underline truncate max-w-md"><?= htmlspecialchars($post['url'], ENT_QUOTES, 'UTF-8') ?></a>
                                    </div>
                                <?php endif; ?>

                                <!-- プログラムコード -->
                                <?php if (!empty($post['code'])): ?>
                                    <div class="mt-3 border border-[#30363d] rounded overflow-hidden">
                                        <div class="bg-[#161b22] px-3 py-1 border-b border-[#30363d] text-[10px] text-[#8b949e] font-mono flex items-center justify-between">
                                            <span>CODE VIEW</span>
                                            <button onclick="copyCode(this)" class="hover:text-white flex items-center gap-1"><i data-lucide="copy" class="w-3 h-3"></i>コピー</button>
                                        </div>
                                        <pre class="bg-[#0d1117] p-3 text-xs text-[#e6edf3] font-mono overflow-x-auto whitespace-pre leading-relaxed"><code><?= htmlspecialchars($post['code'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- リプライセクション -->
                            <div class="bg-[#161b22]/30">
                                <?php foreach ($post['replies'] as $reply): 
                                    $is_reply_author = $reply['author_id'] === $user_id;
                                    $reply_elapsed = time() - strtotime($reply['created_at']);
                                    $can_edit_delete_reply = $is_teacher || ($is_reply_author && $reply_elapsed <= 3600);
                                ?>
                                    <div class="border-t border-[#30363d] p-3.5 text-xs">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-[#161b22] border border-[#30363d] flex items-center justify-center font-bold text-[10px] text-github-success">
                                                    <?= ($reply['author_role'] === 'teacher') ? 'T' : htmlspecialchars(mb_substr($reply['author_name'], 0, 1), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <span class="font-bold text-white"><?= htmlspecialchars($reply['author_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if ($reply['author_role'] === 'teacher'): ?>
                                                    <span class="text-[8px] bg-[#2ea44f]/10 border border-[#2ea44f]/30 text-[#2ea44f] px-1 py-0.5 rounded">指導員/管理者</span>
                                                <?php endif; ?>
                                                <span class="text-[9px] text-[#8b949e] font-mono"><?= date('m/d H:i', strtotime($reply['created_at'])) ?></span>
                                            </div>

                                            <?php if ($can_edit_delete_reply): ?>
                                                <form action="/20260630/?action=delete_reply" method="POST" onsubmit="return confirm('コメントを削除しますか？');">
                                                    <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                                    <button type="submit" class="text-github-danger hover:underline text-[10px]">削除</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-[#c9d1d9] whitespace-pre-wrap leading-relaxed pl-8"><?= htmlspecialchars($reply['body'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- リプライ入力フォーム -->
                            <div class="p-3 bg-[#161b22]/50 border-t border-[#30363d]">
                                <form action="/20260630/?action=create_reply" method="POST" class="flex gap-2">
                                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                    <input type="text" name="body" placeholder="<?= $is_teacher ? '先生としてアドバイスを指導返信する...' : '進捗にコメント・質問する...' ?>" required class="flex-1 bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs text-white placeholder-gray-500 focus:outline-none focus:border-[#58a6ff]">
                                    <button type="submit" class="bg-[#21262d] border border-[#30363d] hover:bg-[#30363d] text-white px-4 py-2 rounded text-xs font-semibold flex items-center gap-1.5 shrink-0 transition">
                                        <i data-lucide="corner-down-left" class="w-3.5 h-3.5"></i><span>送信</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

        <!-- ==========================================
             2. 学習進捗・可視化タブ
             ========================================== -->
        <?php if ($tab === 'analytics'): ?>
            <div class="space-y-8 max-w-4xl mx-auto">
                
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-[#30363d] pb-4 mb-6">
                        <div>
                            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                <i data-lucide="trending-up" class="w-5 h-5 text-[#58a6ff]"></i>
                                <span><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?> さんの学習進捗ポートフォリオ</span>
                            </h2>
                            <p class="text-xs text-[#8b949e] mt-1">先生からの評価進捗度と、GitHubスタイルの活動カレンダー</p>
                        </div>
                        
                        <?php if ($role === 'student'): ?>
                            <div class="mt-4 md:mt-0 flex gap-2">
                                <div class="bg-[#0d1117] px-4 py-2 border border-[#30363d] rounded text-center">
                                    <span class="text-[10px] text-github-muted block uppercase font-semibold">総合習熟度</span>
                                    <span class="text-xl font-bold font-mono text-[#58a6ff]"><?= $overall_average ?>%</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2-1. コントリビューション風カレンダー (学習進捗の「草」) -->
                    <div class="mb-8">
                        <h3 class="text-xs font-semibold text-white mb-3 flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-4 h-4 text-[#2ea44f]"></i>
                            <span>学習アクティビティ（過去10週間の投稿頻度）</span>
                        </h3>
                        <div class="bg-[#0d1117] border border-[#30363d] p-4 rounded-lg overflow-x-auto">
                            <div class="flex gap-2 items-center justify-between text-[11px] text-[#8b949e] mb-3 font-mono">
                                <span>← 過去の日付</span>
                                <div class="flex items-center gap-1.5">
                                    <span>Less</span>
                                    <div class="w-2.5 h-2.5 bg-[#161b22] border border-[#30363d] rounded-[2px]"></div>
                                    <div class="w-2.5 h-2.5 bg-[#0e4429] rounded-[2px]"></div>
                                    <div class="w-2.5 h-2.5 bg-[#006d32] rounded-[2px]"></div>
                                    <div class="w-2.5 h-2.5 bg-[#26a641] rounded-[2px]"></div>
                                    <div class="w-2.5 h-2.5 bg-[#39d353] rounded-[2px]"></div>
                                    <span>More</span>
                                </div>
                            </div>
                            
                            <!-- 草カレンダー生成 (PHPによる過去70日の活動集計から動的配列をJSへ展開) -->
                            <?php 
                            $raw_grass = $this->curriculumModel->getContributionData($user_id);
                            // 過去70日分の日付配列を作成してPHPからJSへバインド
                            $grass_data = [];
                            $now = time();
                            for ($i = 69; $i >= 0; $i--) {
                                $date_key = date('Y-m-d', $now - ($i * 24 * 60 * 60));
                                $grass_data[$date_key] = isset($raw_grass[$date_key]) ? $raw_grass[$date_key] : 0;
                            }
                            ?>
                            <div id="contribution-grid" class="grid grid-flow-col grid-rows-7 gap-1 justify-start">
                                <!-- JavaScriptで grass_data に応じてマスの色を生成してレンダリング -->
                            </div>
                            <script>
                                const grassData = <?= json_encode($grass_data) ?>;
                                const gridContainer = document.getElementById('contribution-grid');
                                gridContainer.innerHTML = '';
                                
                                Object.keys(grassData).forEach(date => {
                                    const count = grassData[date];
                                    let bgClass = 'bg-[#161b22] border border-[#30363d]';
                                    if (count >= 5) bgClass = 'bg-[#39d353]';
                                    else if (count >= 3) bgClass = 'bg-[#26a641]';
                                    else if (count >= 2) bgClass = 'bg-[#006d32]';
                                    else if (count >= 1) bgClass = 'bg-[#0e4429]';
                                    
                                    const block = document.createElement('div');
                                    block.className = `w-3.5 h-3.5 rounded-[2px] transition-colors ${bgClass}`;
                                    block.title = `${date} : 活動数 ${count}回`;
                                    gridContainer.appendChild(block);
                                });
                            </script>
                            <p class="text-[10px] text-[#8b949e] mt-3 text-right">※掲示板の進捗報告・質問リプライなどシステム内での活動実績が反映されます。</p>
                        </div>
                    </div>

                    <!-- 2-2. 分野別習熟度評価 (生徒のみ) -->
                    <?php if ($role === 'student'): ?>
                    <div>
                        <h3 class="text-xs font-semibold text-white mb-4 flex items-center gap-1.5">
                            <i data-lucide="target" class="w-4 h-4 text-[#bc8cff]"></i>
                            <span>カリキュラム分野別・習熟度評価（先生査定）</span>
                        </h3>
                        <div class="space-y-4">
                            <?php if (empty($study_languages)): ?>
                                <p class="text-xs text-github-muted italic text-center py-4">学習中のカリキュラムがありません。</p>
                            <?php else: ?>
                                <?php foreach ($study_languages as $lang): 
                                    $tasks = $this->curriculumModel->getTasksByLanguage($lang);
                                    $student_prog = $this->curriculumModel->getStudentProgress($user_id);
                                ?>
                                <div class="bg-[#0d1117] border border-[#30363d] p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between items-center border-b border-[#30363d] pb-2 mb-2">
                                        <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                            <i data-lucide="folder-code" class="w-4 h-4 text-[#58a6ff]"></i><span><?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <?php foreach ($tasks as $task): 
                                            $prog_val = isset($student_prog[$lang][$task]) ? $student_prog[$lang][$task] : 0;
                                            
                                            // カラー分岐
                                            $bar_color = 'bg-[#58a6ff]';
                                            if ($prog_val === 100) $bar_color = 'bg-[#2ea44f]';
                                            elseif ($prog_val >= 50) $bar_color = 'bg-github-attention';
                                            elseif ($prog_val > 0) $bar_color = 'bg-orange-500';
                                        ?>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-center text-[11px]">
                                                    <span class="text-white font-mono"><?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="font-bold text-white font-mono"><?= $prog_val ?>%</span>
                                                </div>
                                                <div class="w-full bg-[#161b22] rounded-full h-2 overflow-hidden border border-[#30363d]">
                                                    <div class="<?= $bar_color ?> h-full rounded-full transition-all duration-500" style="width: <?= $prog_val ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 2-3. 先生専用: 生徒全体の進捗一覧と％習熟度評価フォーム -->
                <?php if ($role === 'teacher'): ?>
                    <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-6">
                        <div class="border-b border-[#30363d] pb-3 mb-4">
                            <h3 class="text-base font-bold text-white flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-5 h-5 text-github-success"></i>
                                <span>生徒進捗一覧 & 習熟度評価入力（先生パネル）</span>
                            </h3>
                            <p class="text-xs text-github-muted mt-1">生徒全員のカリキュラム進捗を一覧確認し、詳細タスク毎の習熟度（%）を更新・通知できます。</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-[#30363d] bg-[#0d1117] text-[#8b949e]">
                                        <th class="p-3">生徒ID</th>
                                        <th class="p-3">生徒名</th>
                                        <th class="p-3">学習中言語</th>
                                        <th class="p-3">カリキュラム詳細評価（%）</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#30363d]">
                                    <?php foreach ($all_students as $student): 
                                        $s_langs = $this->curriculumModel->getStudentLanguages($student['id']);
                                        $s_progress = $this->curriculumModel->getStudentProgress($student['id']);
                                    ?>
                                        <tr class="hover:bg-[#161b22]/50 transition">
                                            <td class="p-3 font-mono font-bold text-[#58a6ff]"><?= htmlspecialchars($student['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="p-3 text-white font-semibold"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="p-3">
                                                <?php foreach ($s_langs as $slang): ?>
                                                    <span class="inline-block bg-[#58a6ff]/10 border border-[#58a6ff]/20 text-[#58a6ff] text-[10px] px-1.5 py-0.5 rounded font-bold font-mono mr-1 mb-1"><?= htmlspecialchars($slang, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="p-3 space-y-2">
                                                <?php foreach ($s_langs as $slang): 
                                                    $tasks = $this->curriculumModel->getTasksByLanguage($slang);
                                                ?>
                                                    <div class="bg-[#0d1117] p-2 border border-[#30363d] rounded max-w-md">
                                                        <div class="font-bold text-[#8b949e] mb-1">● <?= htmlspecialchars($slang, ENT_QUOTES, 'UTF-8') ?></div>
                                                        <div class="space-y-1.5">
                                                            <?php foreach ($tasks as $task): 
                                                                $curr_val = isset($s_progress[$slang][$task]) ? $s_progress[$slang][$task] : 0;
                                                            ?>
                                                                <form action="/20260630/?action=update_progress" method="POST" class="flex items-center justify-between gap-2 text-[11px]">
                                                                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                                    <input type="hidden" name="language" value="<?= htmlspecialchars($slang, ENT_QUOTES, 'UTF-8') ?>">
                                                                    <input type="hidden" name="task" value="<?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?>">
                                                                    <span class="text-white font-mono truncate max-w-[150px]"><?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?>:</span>
                                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                                        <input type="number" name="percent" min="0" max="100" step="10" value="<?= $curr_val ?>" class="w-12 bg-[#161b22] border border-[#30363d] text-white text-center rounded px-1 py-0.5 font-mono focus:outline-none focus:border-github-success">
                                                                        <span class="text-github-muted">%</span>
                                                                        <button type="submit" class="bg-[#21262d] hover:bg-github-success hover:text-github-dark border border-[#30363d] hover:border-github-success px-2 py-0.5 rounded transition text-[10px]">更新</button>
                                                                    </div>
                                                                </form>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <!-- ==========================================
             3. カリキュラム構成 (先生専用)
             ========================================== -->
        <?php if ($tab === 'curriculum-editor' && $role === 'teacher'): ?>
            <div class="space-y-6 max-w-4xl mx-auto">
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-6">
                    <div class="border-b border-[#30363d] pb-3 mb-6">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <i data-lucide="layout" class="w-5 h-5 text-[#58a6ff]"></i>
                            <span>カリキュラム構成マスター管理 (先生専用)</span>
                        </h2>
                        <p class="text-xs text-[#8b949e] mt-1">生徒たちが選択可能なプログラミング言語と、カリキュラムタスクを構成設計します。新規タスク追加時は受講中の生徒全員に通知されます。</p>
                    </div>

                    <!-- 追加フォーム -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-[#0d1117] border border-[#30363d] p-4 rounded-lg">
                            <h3 class="text-xs font-semibold text-white mb-3">① 言語の新規追加</h3>
                            <form action="/20260630/?action=add_language" method="POST" class="space-y-3">
                                <div>
                                    <label class="block text-[11px] text-github-muted mb-1">言語名（例: PHP, React, Python）</label>
                                    <input type="text" name="language" required placeholder="Python" class="w-full bg-[#161b22] border border-[#30363d] rounded px-3 py-1.5 text-white text-xs focus:outline-none focus:border-[#58a6ff]">
                                </div>
                                <button type="submit" class="w-full bg-github-success hover:bg-github-successBg text-white text-xs font-semibold py-1.5 rounded transition flex items-center justify-center gap-1">
                                    <i data-lucide="plus" class="w-4 h-4"></i><span>言語を追加</span>
                                </button>
                            </form>
                        </div>

                        <div class="bg-[#0d1117] border border-[#30363d] p-4 rounded-lg">
                            <h3 class="text-xs font-semibold text-white mb-3">② タスクの新規追加</h3>
                            <form action="/20260630/?action=add_task" method="POST" class="space-y-3">
                                <div>
                                    <label class="block text-[11px] text-github-muted mb-1">対象言語</label>
                                    <select name="language" required class="w-full bg-[#161b22] border border-[#30363d] rounded px-3 py-1.5 text-white text-xs focus:outline-none">
                                        <?php foreach ($all_languages as $lang): ?>
                                            <option value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] text-github-muted mb-1">タスク名（例: 条件分岐とループ）</label>
                                    <input type="text" name="task" required placeholder="オブジェクト指向" class="w-full bg-[#161b22] border border-[#30363d] rounded px-3 py-1.5 text-white text-xs focus:outline-none focus:border-[#58a6ff]">
                                </div>
                                <button type="submit" class="w-full bg-[#58a6ff] hover:bg-sky-600 text-[#0d1117] text-xs font-semibold py-1.5 rounded transition flex items-center justify-center gap-1">
                                    <i data-lucide="plus" class="w-4 h-4"></i><span>タスクを追加</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- 現在のカリキュラムツリー一覧 -->
                    <div>
                        <h3 class="text-xs font-semibold text-white mb-3">カリキュラムマスター構成ツリー</h3>
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            <?php foreach ($all_languages as $lang): 
                                $tasks = $this->curriculumModel->getTasksByLanguage($lang);
                            ?>
                                <div class="bg-[#0d1117] border border-[#30363d] rounded-lg p-4 space-y-2">
                                    <div class="flex justify-between items-center border-b border-[#30363d] pb-2">
                                        <span class="text-xs font-bold text-white flex items-center gap-1.5"><i data-lucide="package" class="w-4 h-4 text-[#58a6ff]"></i><span><?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?></span></span>
                                        <form action="/20260630/?action=delete_language" method="POST" onsubmit="return confirm('学習分野、紐づく生徒の進捗評価データを完全に一括削除しますか？');">
                                            <input type="hidden" name="language" value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="bg-github-danger hover:bg-red-600 text-white text-[10px] px-2 py-0.5 rounded transition">言語一括削除</button>
                                        </form>
                                    </div>
                                    <div class="space-y-1.5 pt-1">
                                        <?php if (empty($tasks)): ?>
                                            <p class="text-[10px] text-github-muted italic">タスクが定義されていません。</p>
                                        <?php else: ?>
                                            <?php foreach ($tasks as $idx => $task): ?>
                                                <div class="flex items-center justify-between p-2 bg-[#161b22] border border-[#30363d] rounded text-xs font-mono">
                                                    <span class="text-white"><?= $idx+1 ?>. <?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <form action="/20260630/?action=delete_task" method="POST" onsubmit="return confirm('このタスクを削除しますか？');">
                                                        <input type="hidden" name="language" value="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="task" value="<?= htmlspecialchars($task, ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="text-github-danger hover:text-red-400 font-bold text-[10px]">削除</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ==========================================
     クライアント側 ダイアログ(JSモーダル制御)
     ========================================== -->
<script>
    const modal = document.getElementById('common-modal');
    const modalContent = document.getElementById('common-modal-content');

    function openAddFriendDialog() {
        modal.classList.remove('hidden');
        modalContent.innerHTML = `
            <div class="flex items-center justify-between border-b border-[#30363d] pb-3 mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                    <i data-lucide="user-plus" class="w-4 h-4 text-[#58a6ff]"></i><span>生徒を検索してつながりに追加</span>
                </h3>
                <button onclick="closeModal()" class="text-github-muted hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form action="/20260630/?action=add_friend" method="POST" class="space-y-4 text-xs">
                <div>
                    <label class="block text-[#8b949e] mb-1 font-semibold">追加する生徒のユーザーIDを入力してください</label>
                    <input type="text" name="target_id" required placeholder="student_bob など" class="w-full bg-[#0d1117] border border-[#30363d] rounded p-2 text-white focus:outline-none focus:border-[#58a6ff] font-mono">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal()" class="bg-[#21262d] border border-[#30363d] text-white px-4 py-2 rounded">キャンセル</button>
                    <button type="submit" class="bg-[#2ea44f] hover:bg-[#238636] text-white px-4 py-2 rounded font-semibold">友達追加する</button>
                </div>
            </form>
        `;
        lucide.createIcons();
    }

    function openEditTagDialog(targetId, currentTag) {
        modal.classList.remove('hidden');
        modalContent.innerHTML = `
            <div class="flex items-center justify-between border-b border-[#30363d] pb-3 mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                    <i data-lucide="tag" class="w-4 h-4 text-[#bc8cff]"></i><span>属性タグの設定</span>
                </h3>
                <button onclick="closeModal()" class="text-github-muted hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form action="/20260630/?action=change_tag" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="target_id" value="${targetId}">
                <div>
                    <p class="text-github-muted mb-2 text-[11px]">「${targetId}」さんに対して独自の属性を設定できます (例: 友達, グループA, 1班など)。投稿時にこの属性をキーに公開範囲を限定できます。</p>
                    <label class="block text-[#8b949e] mb-1 font-semibold">属性タグ名</label>
                    <input type="text" name="tag" value="${currentTag}" required placeholder="友達" class="w-full bg-[#0d1117] border border-[#30363d] rounded p-2 text-white focus:outline-none focus:border-[#58a6ff]">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal()" class="bg-[#21262d] border border-[#30363d] text-white px-4 py-2 rounded">キャンセル</button>
                    <button type="submit" class="bg-github-success hover:bg-github-successBg text-white px-4 py-2 rounded font-semibold">タグ変更を保存</button>
                </div>
            </form>
        `;
        lucide.createIcons();
    }

    function openSelectLanguageDialog() {
        modal.classList.remove('hidden');
        modalContent.innerHTML = `
            <div class="flex items-center justify-between border-b border-[#30363d] pb-3 mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                    <i data-lucide="book-open" class="w-4 h-4 text-[#58a6ff]"></i><span>受講言語プロフィールの変更</span>
                </h3>
                <button onclick="closeModal()" class="text-github-muted hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form action="/20260630/?action=update_student_languages" method="POST" class="space-y-4 text-xs">
                <p class="text-github-muted text-[11px]">あなたが学習するプログラミング言語を選択してください。選択した言語のタスクが自動登録されます。</p>
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <?php foreach ($all_languages as $alang): 
                        $is_checked = in_array($alang, $study_languages) ? 'checked' : '';
                    ?>
                        <label class="flex items-center justify-between p-2.5 bg-[#0d1117] hover:bg-[#161b22] border border-[#30363d] rounded cursor-pointer transition">
                            <span class="text-xs text-white font-bold"><?= htmlspecialchars($alang, ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="checkbox" name="languages[]" value="<?= htmlspecialchars($alang, ENT_QUOTES, 'UTF-8') ?>" <?= $is_checked ?> class="text-github-accent focus:ring-github-accent bg-[#161b22] border-[#30363d] rounded">
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal()" class="bg-[#21262d] border border-[#30363d] text-white px-4 py-2 rounded">キャンセル</button>
                    <button type="submit" class="bg-[#2ea44f] hover:bg-[#238636] text-white px-4 py-2 rounded font-semibold">学習登録を決定</button>
                </div>
            </form>
        `;
        lucide.createIcons();
    }

    function openEditPostDialog(postId, rawBody, rawCode, url) {
        // 文字列のエスケープ・補正
        const body = JSON.parse(rawBody);
        const code = JSON.parse(rawCode);

        modal.classList.remove('hidden');
        modalContent.innerHTML = `
            <div class="flex items-center justify-between border-b border-[#30363d] pb-3 mb-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
                    <i data-lucide="edit" class="w-4 h-4 text-[#58a6ff]"></i><span>学習投稿の編集</span>
                </h3>
                <button onclick="closeModal()" class="text-github-muted hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <form action="/20260630/?action=update_post" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="post_id" value="${postId}">
                <div>
                    <label class="block text-[#8b949e] mb-1 font-semibold">進捗・質問本文</label>
                    <textarea name="body" required rows="4" class="w-full bg-[#0d1117] border border-[#30363d] rounded p-2 text-white focus:outline-none focus:border-[#58a6ff]">${body}</textarea>
                </div>
                <div>
                    <label class="block text-[#8b949e] mb-1 font-semibold font-mono">プログラムコード</label>
                    <textarea name="code" rows="6" class="w-full bg-[#0d1117] border border-[#30363d] rounded p-2 text-white font-mono focus:outline-none focus:border-[#58a6ff]">${code || ''}</textarea>
                </div>
                <div>
                    <label class="block text-[#8b949e] mb-1 font-semibold">参考URL</label>
                    <input type="url" name="url" value="${url || ''}" class="w-full bg-[#0d1117] border border-[#30363d] rounded p-2 text-white focus:outline-none focus:border-[#58a6ff]">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal()" class="bg-[#21262d] border border-[#30363d] text-white px-4 py-2 rounded">キャンセル</button>
                    <button type="submit" class="bg-github-success hover:bg-github-successBg text-white px-4 py-2 rounded font-semibold">変更を保存</button>
                </div>
            </form>
        `;
        lucide.createIcons();
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // 投稿時のタスクドロップダウンの動的書き換え（JavaScript連携）
    const curriculums = {
        <?php foreach ($all_languages as $lang): 
            $tasks = $this->curriculumModel->getTasksByLanguage($lang);
        ?>
            "<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>": <?= json_encode($tasks) ?>,
        <?php endforeach; ?>
    };

    function updatePostTaskDropdown(lang) {
        const selectTask = document.getElementById('post-task');
        selectTask.innerHTML = '';

        if (!lang || !curriculums[lang]) {
            selectTask.innerHTML = '<option value="">-- 先に言語を選択してください --</option>';
            return;
        }

        const tasks = curriculums[lang];
        if (tasks.length === 0) {
            selectTask.innerHTML = '<option value="">-- タスクが未定義です --</option>';
            return;
        }

        tasks.forEach(task => {
            const opt = document.createElement('option');
            opt.value = task;
            opt.textContent = task;
            selectTask.appendChild(opt);
        });
    }

    function toggleFormTagSelector(show) {
        const container = document.getElementById('visibility-tag-selector-container');
        if (show) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    function copyInviteURL(text) {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);

        const banner = document.getElementById('invite-copied-modal');
        banner.classList.remove('hidden');
        setTimeout(() => banner.classList.add('hidden'), 3000);
    }

    function copyCode(btn) {
        const pre = btn.closest('.border').querySelector('pre code');
        const el = document.createElement('textarea');
        el.value = pre.textContent;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        
        btn.innerHTML = '<i data-lucide="check" class="w-3 h-3"></i>コピーしました！';
        lucide.createIcons();
        setTimeout(() => {
            btn.innerHTML = '<i data-lucide="copy" class="w-3 h-3"></i>コピー';
            lucide.createIcons();
        }, 2000);
    }
</script>

<script>
    lucide.createIcons();
</script>
</body>
</html>