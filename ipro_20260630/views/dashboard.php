<?php
// ヘッダー・サイドバーを含む共通レイアウトのインクルード
include __DIR__ . '/layout/header.php';
?>

<!-- 上部タブ切り替えナビゲーション（GitHubのレポジトリ内タブを模倣） -->
<div class="border-b border-[#d0d7de] pb-px flex space-x-6 mb-6">
    <a href="?tab=feed" class="pb-3 text-sm font-semibold flex items-center space-x-2 border-b-2 <?php echo $current_tab === 'feed' ? 'border-[#fd8c73] text-[#24292f]' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
        <i data-lucide="message-square" class="w-4 h-4"></i>
        <span>学習掲示板フィード</span>
    </a>

    <?php if ($_SESSION['role'] === 'student'): ?>
    <a href="?tab=curriculum" class="pb-3 text-sm font-semibold flex items-center space-x-2 border-b-2 <?php echo $current_tab === 'curriculum' ? 'border-[#fd8c73] text-[#24292f]' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
        <i data-lucide="book-open" class="w-4 h-4"></i>
        <span>カリキュラム学習登録</span>
    </a>
    <?php endif; ?>

    <a href="?tab=friends" class="pb-3 text-sm font-semibold flex items-center space-x-2 border-b-2 <?php echo $current_tab === 'friends' ? 'border-[#fd8c73] text-[#24292f]' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
        <i data-lucide="users" class="w-4 h-4"></i>
        <span>友達・属性登録</span>
    </a>

    <?php if ($_SESSION['role'] === 'teacher'): ?>
    <a href="?tab=teacher_students" class="pb-3 text-sm font-semibold flex items-center space-x-2 border-b-2 <?php echo $current_tab === 'teacher_students' ? 'border-[#fd8c73] text-[#24292f]' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
        <span>生徒進捗一覧・評価管理</span>
    </a>
    <a href="?tab=teacher_config" class="pb-3 text-sm font-semibold flex items-center space-x-2 border-b-2 <?php echo $current_tab === 'teacher_config' ? 'border-[#fd8c73] text-[#24292f]' : 'border-transparent text-gray-500 hover:text-gray-800' ?>">
        <i data-lucide="settings" class="w-4 h-4"></i>
        <span>マスタデータ設定</span>
    </a>
    <?php endif; ?>
</div>

<!-- エラー等トーストメッセージ表示 -->
<?php if (isset($_SESSION['post_error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 text-xs rounded p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i data-lucide="alert-circle" class="text-red-500 w-4 h-4"></i>
            <span><?php echo htmlspecialchars($_SESSION['post_error']); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">&times;</button>
    </div>
    <?php unset($_SESSION['post_error']); ?>
<?php endif; ?>

<!-- ==========================================
     タブ1: 学習掲示板フィード & 進捗カレンダー
     ========================================== -->
<?php if ($current_tab === 'feed'): ?>
    
    <!-- 1. GitHubスタイル草カレンダー（学習アクティビティ） -->
    <div class="bg-white border border-[#d0d7de] rounded-lg p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-bold text-gray-800 flex items-center space-x-2">
                <i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i>
                <span>学習コントリビューション（過去90日間の進捗報告数） : <span class="text-blue-600"><?php echo htmlspecialchars($viewingStudentName); ?></span></span>
            </h4>
            <div class="flex items-center space-x-2 text-[10px] text-gray-400">
                <span>Less</span>
                <span class="w-2.5 h-2.5 bg-[#ebedf0] border border-gray-200 rounded-sm"></span>
                <span class="w-2.5 h-2.5 bg-[#9be9a8] border border-gray-200 rounded-sm"></span>
                <span class="w-2.5 h-2.5 bg-[#40c463] border border-gray-200 rounded-sm"></span>
                <span class="w-2.5 h-2.5 bg-[#30a14e] border border-gray-200 rounded-sm"></span>
                <span class="w-2.5 h-2.5 bg-[#216e39] border border-gray-200 rounded-sm"></span>
                <span>More</span>
            </div>
        </div>

        <!-- コントリビューション草グリッド -->
        <div class="flex flex-wrap gap-[3px] p-2 bg-[#f8f9fa] rounded-md border border-gray-150 overflow-x-auto">
            <?php foreach ($grassCalendar as $day): ?>
                <div class="w-3.5 h-3.5 rounded-sm border-[0.5px] border-black/5 transition relative group
                    <?php 
                        if ($day['level'] === 0) echo 'bg-[#ebedf0]';
                        elseif ($day['level'] === 1) echo 'bg-[#9be9a8]';
                        elseif ($day['level'] === 2) echo 'bg-[#40c463]';
                        elseif ($day['level'] === 3) echo 'bg-[#30a14e]';
                        elseif ($day['level'] === 4) echo 'bg-[#216e39]';
                    ?>"
                    title="<?php echo htmlspecialchars($day['date']); ?> : <?php echo $day['count']; ?> 回の報告">
                    <!-- Tooltip -->
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 bg-[#24292f] text-white text-[10px] rounded px-2 py-1 hidden group-hover:block whitespace-nowrap mb-1.5 shadow-md z-10">
                        <?php echo htmlspecialchars($day['date']); ?> : <?php echo $day['count']; ?>回報告
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 2. 進捗投稿エリア（生徒専用） -->
    <?php if ($_SESSION['role'] === 'student'): ?>
        <?php if (empty($studentCurriculums)): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5 text-center text-yellow-800 text-sm shadow-sm mb-6">
                <i data-lucide="alert-triangle" class="w-8 h-8 text-yellow-500 mx-auto mb-2"></i>
                <p class="font-bold">掲示板に投稿する前に、学習する言語を登録しましょう！</p>
                <p class="text-xs text-yellow-600 mt-1">「カリキュラム学習登録」タブから先生の設定したプログラミング言語を追加してください。</p>
            </div>
        <?php else: ?>
            <div class="bg-white border border-[#d0d7de] rounded-lg shadow-sm p-5 mb-6">
                <h4 class="text-sm font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center space-x-1">
                    <i data-lucide="edit-3" class="w-4 h-4 text-gray-500"></i>
                    <span>本日の学習進捗・質問を報告する</span>
                </h4>
                <form action="<?php echo BASE_URL; ?>post/create" method="POST" enctype="multipart/form-data" class="space-y-4">
                    
                    <!-- セレクトボックス連携（言語 ＞ タスク） -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">学習言語 <span class="text-red-500">*</span></label>
                            <select name="curriculum_id" id="post-curriculum-select" required onchange="updatePostTasks()"
                                    class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none transition bg-white">
                                <option value="">選択してください</option>
                                <?php foreach ($studentCurriculums as $sc): ?>
                                    <option value="<?php echo $sc['id']; ?>"><?php echo htmlspecialchars($sc['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">学習内容（タスク） <span class="text-red-500">*</span></label>
                            <select name="task_id" id="post-task-select" required disabled
                                    class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none transition bg-white">
                                <option value="">まず言語を選択してください</option>
                            </select>
                        </div>
                    </div>

                    <!-- 本文 -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">進捗詳細・質問内容 <span class="text-red-500">*</span></label>
                        <textarea name="content" required rows="4" placeholder="（例）カリキュラムの関数部分に到達しました。引数の考え方にまだ少し不安があります。"
                                  class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none transition"></textarea>
                    </div>

                    <!-- ソースコード（エディタライク） -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">ソースコード（オプション：等幅フォント & シンタックス風表示用）</label>
                        <textarea name="code_content" rows="6" placeholder="<?php echo htmlspecialchars("<?php\n// ここにプログラムを記述できます\n?>"); ?>"
                                  class="w-full border border-gray-300 rounded p-2 text-xs code-font bg-gray-50 focus:bg-white focus:border-[#0969da] focus:ring-1 focus:ring-[#0969da] focus:outline-none transition"></textarea>
                    </div>

                    <!-- ファイル添付 & 参考URL & 公開範囲 -->
                    <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">画像・ファイルの添付 (最大5MB)</label>
                            <input type="file" name="attached_file" accept=".jpg,.jpeg,.png,.gif,.txt,.pdf"
                                   class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">参考URL</label>
                            <input type="url" name="reference_url" placeholder="https://..."
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:border-[#0969da] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">開示公開範囲（属性別制御）</label>
                            <select name="visibility_type"
                                    class="w-full border border-gray-300 rounded px-2 py-1 text-xs focus:border-[#0969da] focus:outline-none bg-white">
                                <option value="public">🌍 全体公開</option>
                                <?php foreach ($distinctTags as $tag): ?>
                                    <option value="<?php echo htmlspecialchars($tag); ?>">🔒 【属性宛】<?php echo htmlspecialchars($tag); ?>のみ開示</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-[#2da44e] hover:bg-[#2c974b] text-white text-xs font-semibold px-4 py-2 rounded shadow-sm transition">
                            報告・質問を投稿する
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- 3. フィード表示 -->
    <div class="space-y-4">
        <h3 class="font-bold text-base text-gray-800 flex items-center space-x-2">
            <i data-lucide="activity" class="w-5 h-5 text-gray-600"></i>
            <span>最新進捗タイムライン</span>
        </h3>

        <?php if (empty($feed)): ?>
            <div class="bg-white border border-[#d0d7de] rounded-lg p-10 text-center text-gray-400 text-sm shadow-sm">
                <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                <p>表示可能な投稿はありません。</p>
            </div>
        <?php else: ?>
            <?php foreach ($feed as $post): ?>
                <div class="bg-white border border-[#d0d7de] rounded-lg shadow-sm overflow-hidden">
                    <!-- 投稿ヘッダー -->
                    <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between bg-gray-50/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center font-bold text-gray-700">
                                <?php echo mb_substr($post['display_name'], 0, 1); ?>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-sm text-[#24292f]"><?php echo htmlspecialchars($post['display_name']); ?></span>
                                    <span class="text-xs text-gray-500">@<?php echo htmlspecialchars($post['username']); ?></span>
                                </div>
                                <div class="flex items-center space-x-2 mt-1">
                                    <!-- 進捗タグ -->
                                    <span class="bg-[#ddf4ff] text-[#0969da] border border-[#b4e1fc] text-[10px] font-semibold px-1.5 py-0.5 rounded">
                                        <?php echo htmlspecialchars($post['curriculum_name']); ?> ＞ <?php echo htmlspecialchars($post['task_name']); ?>
                                    </span>
                                    <span class="text-[10px] text-gray-400"><?php echo date('Y/m/d H:i', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- 編集・削除ボタン (投稿後1時間以内に限定) -->
                        <?php if ($post['can_edit_delete']): ?>
                            <div class="flex space-x-1">
                                <button onclick="openEditPostModal(<?php echo htmlspecialchars(json_encode($post)); ?>)" class="text-gray-400 hover:text-blue-600 p-1 rounded hover:bg-gray-100 transition">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <form action="<?php echo BASE_URL; ?>post/delete" method="POST" onsubmit="return confirm('この投稿を完全に削除しますか？添付ファイルも削除されます。');" class="inline">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="text-gray-400 hover:text-red-600 p-1 rounded hover:bg-gray-100 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 投稿本文 -->
                    <div class="p-5">
                        <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap mb-4"><?php echo htmlspecialchars($post['content']); ?></p>

                        <!-- コードブロック（行番号と等幅フォントを模倣） -->
                        <?php if (!empty($post['code_content'])): ?>
                            <div class="border border-[#d0d7de] rounded-lg overflow-hidden mb-4 shadow-inner">
                                <div class="bg-gray-50 border-b border-[#d0d7de] px-4 py-1.5 text-xs text-gray-500 flex justify-between items-center">
                                    <span class="font-semibold code-font">Code Segment</span>
                                    <button onclick="copyCode(this)" class="hover:text-gray-800 flex items-center space-x-1">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i><span>Copy</span>
                                    </button>
                                </div>
                                <div class="bg-[#f6f8fa] p-4 font-mono text-xs overflow-x-auto text-[#24292f] leading-5 flex">
                                    <!-- 行番号シミュレーション -->
                                    <div class="select-none text-gray-400 text-right pr-4 border-r border-[#d0d7de] mr-4 min-w-[20px]">
                                        <?php 
                                        $lines = explode("\n", $post['code_content']);
                                        for ($i = 1; $i <= count($lines); $i++) {
                                            echo $i . "<br>";
                                        }
                                        ?>
                                    </div>
                                    <!-- コード本体 -->
                                    <pre class="flex-grow whitespace-pre"><code class="language-php"><?php echo htmlspecialchars($post['code_content']); ?></code></pre>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 添付ファイル表示 -->
                        <?php if (!empty($post['file_path'])): ?>
                            <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-between">
                                <div class="flex items-center space-x-2 text-xs text-gray-700">
                                    <i data-lucide="paperclip" class="w-4 h-4 text-gray-500"></i>
                                    <span class="font-semibold"><?php echo htmlspecialchars($post['file_name']); ?></span>
                                </div>
                                <?php 
                                $ext = strtolower(pathinfo($post['file_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                ?>
                                    <a href="<?php echo BASE_URL . $post['file_path']; ?>" target="_blank" class="block max-w-[250px] border border-gray-200 rounded hover:opacity-90">
                                        <img src="<?php echo BASE_URL . $post['file_path']; ?>" alt="添付画像" class="max-h-[120px] rounded object-cover">
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL . $post['file_path']; ?>" download class="text-xs text-blue-600 hover:underline flex items-center space-x-1 font-semibold">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i><span>ダウンロード</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 参考URLリンク -->
                        <?php if (!empty($post['reference_url'])): ?>
                            <div class="mt-3 text-xs flex items-center space-x-1 text-[#0969da] hover:underline">
                                <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                <a href="<?php echo htmlspecialchars($post['reference_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars($post['reference_url']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- リプライ（指導・返信）セクション -->
                    <div class="bg-gray-50/50 border-t border-gray-100 px-5 py-4">
                        <h5 class="text-xs font-bold text-gray-600 mb-3 flex items-center space-x-1">
                            <i data-lucide="messages-square" class="w-4 h-4"></i>
                            <span>指導リプライ・コメント (<?php echo count($post['replies']); ?>)</span>
                        </h5>

                        <div class="space-y-3 mb-4">
                            <?php foreach ($post['replies'] as $reply): ?>
                                <div class="bg-white border border-[#d0d7de]/60 rounded-lg p-3 text-xs relative group">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-bold text-[#24292f]"><?php echo htmlspecialchars($reply['display_name']); ?></span>
                                            <?php if ($reply['user_role'] === 'teacher'): ?>
                                                <span class="bg-[#dafbe1] text-[#1a7f37] text-[9px] font-bold px-1 rounded">指導官</span>
                                            <?php endif; ?>
                                            <span class="text-gray-400 text-[10px]"><?php echo date('m/d H:i', strtotime($reply['created_at'])); ?></span>
                                        </div>
                                        
                                        <!-- 返信削除 -->
                                        <?php if ($reply['can_edit_delete']): ?>
                                            <div class="opacity-0 group-hover:opacity-100 transition flex space-x-1">
                                                <form action="<?php echo BASE_URL; ?>reply" method="POST" onsubmit="return confirm('この返信を削除しますか？');" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                                    <button type="submit" class="text-gray-400 hover:text-red-600">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($reply['content']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- 指導・返信入力フォーム (先生、または自身の返信用) -->
                        <form action="<?php echo BASE_URL; ?>reply" method="POST" class="mt-2">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <div class="flex items-end space-x-2">
                                <textarea name="content" required rows="1" placeholder="指導・フィードバックを記入..."
                                          class="flex-grow border border-gray-300 rounded-md p-2 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white transition resize-y"></textarea>
                                <button type="submit" class="bg-[#24292f] hover:bg-[#1f2328] text-white text-xs font-semibold px-3 py-2 rounded transition flex items-center space-x-1 h-fit">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i><span>送信</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<!-- ==========================================
     タブ2: カリキュラム学習登録 (生徒専用)
     ========================================== -->
<?php elseif ($current_tab === 'curriculum' && $_SESSION['role'] === 'student'): ?>
    
    <div class="bg-white border border-[#d0d7de] rounded-lg p-5 shadow-sm">
        <h3 class="font-bold text-base text-[#24292f] pb-3 border-b border-gray-100 flex items-center space-x-2 mb-4">
            <i data-lucide="book-open" class="w-5 h-5 text-[#3fb950]"></i>
            <span>学習言語の選択・カリキュラム登録</span>
        </h3>
        <p class="text-xs text-gray-500 mb-6 leading-relaxed">
            先生が事前に登録したマスタデータから、自分が現在学習を進めている言語を選択してプロフィールに登録します。<br>
            登録すると、言語内の詳細タスク進捗状況と先生からの評価（％）が可視化されるようになります。
        </p>

        <div class="grid grid-cols-2 gap-6">
            <!-- 登録可能マスタリスト -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                <h4 class="font-bold text-xs text-gray-700 mb-3 flex items-center space-x-1">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i><span>新しく言語を登録する</span>
                </h4>
                
                <form action="<?php echo BASE_URL; ?>curriculum/select" method="POST" class="space-y-3">
                    <select name="curriculum_id" required class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:outline-none focus:border-blue-500">
                        <option value="">言語マスタを選択...</option>
                        <?php 
                        // すでに学習済みの言語を除外
                        $my_curr_ids = array_column($studentCurriculums, 'id');
                        foreach ($curriculumsWithTasks as $c): 
                            if (in_array($c['id'], $my_curr_ids)) continue;
                        ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (タスク数: <?php echo count($c['tasks']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full bg-[#2da44e] hover:bg-[#2c974b] text-white text-xs font-semibold py-2 rounded transition">
                        学習対象として登録追加する
                    </button>
                </form>
            </div>

            <!-- 現在登録中の言語進捗状況 -->
            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="font-bold text-xs text-gray-700 mb-3 flex items-center space-x-1">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-green-600"></i><span>登録中のカリキュラムと指導評価</span>
                </h4>

                <?php if (empty($studentCurriculums)): ?>
                    <p class="text-xs text-gray-400 py-6 text-center">登録中の言語はありません</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($studentCurriculums as $sc): ?>
                            <div class="border border-gray-150 rounded p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-bold text-xs text-[#24292f]"><?php echo htmlspecialchars($sc['name']); ?></span>
                                    <span class="text-xs bg-[#dafbe1] text-[#1a7f37] font-semibold px-2 py-0.5 rounded-full">全体進捗: <?php echo $sc['average_proficiency']; ?>%</span>
                                </div>
                                
                                <!-- 詳細タスクアコーディオン表示 -->
                                <div class="space-y-2 mt-2 border-t border-gray-100 pt-2">
                                    <?php foreach ($sc['tasks'] as $task): ?>
                                        <div class="flex items-center justify-between text-[11px] text-gray-600">
                                            <span>├ <?php echo htmlspecialchars($task['task_name']); ?></span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-20 bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                                    <div class="bg-[#3fb950] h-full" style="width: <?php echo $task['proficiency']; ?>%"></div>
                                                </div>
                                                <span class="font-bold text-gray-800"><?php echo $task['proficiency']; ?>%</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- ==========================================
     タブ3: 友達・属性登録
     ========================================== -->
<?php elseif ($current_tab === 'friends'): ?>
    
    <div class="bg-white border border-[#d0d7de] rounded-lg p-5 shadow-sm">
        <h3 class="font-bold text-base text-[#24292f] pb-3 border-b border-gray-100 flex items-center space-x-2 mb-4">
            <i data-lucide="users" class="w-5 h-5 text-gray-600"></i>
            <span>友達と繋がる & 属性タグ設定</span>
        </h3>
        <p class="text-xs text-gray-500 mb-6 leading-relaxed">
            友達を「ユーザー検索」または「招待URL」から登録し、独自の属性（「友達」「グループA」など）を設定できます。<br>
            <strong>プライバシー設計：</strong>設定した属性情報や送信先の詳細は自分以外には一切開示されません。
        </p>

        <div class="grid grid-cols-2 gap-6">
            <!-- ユーザー検索登録 -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                <h4 class="font-bold text-xs text-gray-700 mb-3 flex items-center space-x-1">
                    <i data-lucide="search" class="w-4 h-4"></i><span>生徒ユーザーを探す</span>
                </h4>
                
                <form action="?tab=friends" method="GET" class="flex space-x-2 mb-4">
                    <input type="hidden" name="tab" value="friends">
                    <input type="text" name="search_query" required value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="ID、表示名で検索..."
                           class="flex-grow border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:outline-none">
                    <button type="submit" class="bg-[#24292f] hover:bg-[#1f2328] text-white text-xs font-semibold px-4 py-1.5 rounded transition">
                        検索
                    </button>
                </form>

                <!-- 検索結果 -->
                <?php if (!empty($searchQuery)): ?>
                    <div class="space-y-2 max-h-[250px] overflow-y-auto">
                        <p class="text-[10px] text-gray-500">「<?php echo htmlspecialchars($searchQuery); ?>」の検索結果 (<?php echo count($searchResults); ?>件)</p>
                        <?php if (empty($searchResults)): ?>
                            <p class="text-xs text-gray-400 py-4 text-center">該当するユーザーは見つかりませんでした</p>
                        <?php else: ?>
                            <?php foreach ($searchResults as $row): ?>
                                <div class="bg-white border border-gray-200 rounded p-2.5 flex items-center justify-between text-xs">
                                    <div>
                                        <p class="font-bold"><?php echo htmlspecialchars($row['display_name']); ?></p>
                                        <p class="text-[10px] text-gray-400">@<?php echo htmlspecialchars($row['username']); ?></p>
                                    </div>
                                    <form action="<?php echo BASE_URL; ?>friends/manage" method="POST" class="flex items-center space-x-1">
                                        <input type="hidden" name="action" value="add_or_update">
                                        <input type="hidden" name="friend_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="attribute_tag" value="友達" required placeholder="属性を設定" 
                                               class="border border-gray-300 rounded px-2 py-1 text-[11px] w-20 text-center">
                                        <button type="submit" class="bg-[#2da44e] hover:bg-[#2c974b] text-white font-semibold px-2 py-1 rounded text-[11px]">
                                            追加
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 登録友達一覧と属性変更 -->
            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="font-bold text-xs text-gray-700 mb-3 flex items-center space-x-1">
                    <i data-lucide="contact" class="w-4 h-4 text-blue-600"></i><span>友達リスト（登録・属性タグ）</span>
                </h4>

                <?php if (empty($friends)): ?>
                    <p class="text-xs text-gray-400 py-10 text-center">登録されている友達はいません。</p>
                <?php else: ?>
                    <div class="space-y-3 max-h-[350px] overflow-y-auto">
                        <?php foreach ($friends as $friend): ?>
                            <div class="border border-gray-150 rounded p-3 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold"><?php echo htmlspecialchars($friend['display_name']); ?></p>
                                    <p class="text-[10px] text-gray-400">@<?php echo htmlspecialchars($friend['username']); ?></p>
                                    <span class="inline-block bg-blue-50 text-blue-700 border border-blue-100 text-[10px] font-semibold px-1.5 py-0.2 rounded mt-1.5">
                                        🏷️ <?php echo htmlspecialchars($friend['attribute_tag']); ?>
                                    </span>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <!-- 属性編集フォーム -->
                                    <form action="<?php echo BASE_URL; ?>friends/manage" method="POST" class="flex items-center space-x-1">
                                        <input type="hidden" name="action" value="add_or_update">
                                        <input type="hidden" name="friend_id" value="<?php echo $friend['friend_id']; ?>">
                                        <input type="text" name="attribute_tag" value="<?php echo htmlspecialchars($friend['attribute_tag']); ?>" required 
                                               class="border border-gray-300 rounded px-1.5 py-0.5 text-[11px] w-20 text-center focus:border-blue-500">
                                        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-2 py-0.5 rounded text-[10px] border border-gray-300">
                                            変更
                                        </button>
                                    </form>

                                    <!-- 削除 -->
                                    <form action="<?php echo BASE_URL; ?>friends/manage" method="POST" onsubmit="return confirm('この友達登録を削除しますか？');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="friend_id" value="<?php echo $friend['friend_id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 p-1">
                                            <i data-lucide="user-x" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- ==========================================
     タブ4: 生徒進捗一覧・評価管理 (先生専用)
     ========================================== -->
<?php elseif ($current_tab === 'teacher_students' && $_SESSION['role'] === 'teacher'): ?>
    
    <div class="bg-white border border-[#d0d7de] rounded-lg p-5 shadow-sm">
        <h3 class="font-bold text-base text-[#24292f] pb-3 border-b border-gray-100 flex items-center space-x-2 mb-4">
            <i data-lucide="graduation-cap" class="w-5 h-5 text-gray-700"></i>
            <span>生徒全体の進捗確認＆習熟度（％）評価更新</span>
        </h3>

        <div class="grid grid-cols-3 gap-6">
            <!-- 生徒一覧選択サイドバー -->
            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                <h4 class="font-bold text-xs text-gray-700 mb-3">生徒リスト</h4>
                <div class="space-y-2">
                    <?php foreach ($allStudents as $student): ?>
                        <a href="?tab=teacher_students&view_student_id=<?php echo $student['id']; ?>"
                           class="block p-3 rounded border text-xs transition <?php echo (isset($_GET['view_student_id']) && $_GET['view_student_id'] == $student['id']) ? 'bg-blue-50 border-blue-300 text-blue-900 font-bold' : 'bg-white border-gray-200 hover:bg-gray-50 text-gray-700' ?>">
                            <div class="flex justify-between items-center">
                                <span><?php echo htmlspecialchars($student['display_name']); ?></span>
                                <span class="text-[10px] text-gray-400">@<?php echo htmlspecialchars($student['username']); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 選択された生徒の詳細カリキュラム進捗 & 評価更新フォーム -->
            <div class="col-span-2 border border-gray-200 rounded-lg p-4 bg-white">
                <?php 
                $v_student_id = intval($_GET['view_student_id'] ?? 0);
                if ($v_student_id === 0 && !empty($allStudents)) {
                    $v_student_id = $allStudents[0]['id'];
                }

                $selected_student = null;
                foreach ($allStudents as $s) {
                    if ($s['id'] == $v_student_id) {
                        $selected_student = $s;
                        break;
                    }
                }

                if (!$selected_student):
                    echo "<p class='text-xs text-gray-400 text-center py-10'>表示する生徒が選択されていません</p>";
                else:
                ?>
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                        <div>
                            <h4 class="font-bold text-sm text-[#24292f]"><?php echo htmlspecialchars($selected_student['display_name']); ?> さんの学習状況</h4>
                            <p class="text-xs text-gray-400">ユーザーID: @<?php echo htmlspecialchars($selected_student['username']); ?></p>
                        </div>
                        <a href="?tab=feed&view_student_id=<?php echo $selected_student['id']; ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded border border-gray-300 transition flex items-center space-x-1">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i><span>この生徒の進捗草を表示</span>
                        </a>
                    </div>

                    <?php if (empty($selected_student['curriculums'])): ?>
                        <p class="text-xs text-gray-400 text-center py-8">この生徒はまだ学習言語を登録していません。</p>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($selected_student['curriculums'] as $curr): ?>
                                <div class="border border-gray-150 rounded p-4 bg-gray-50/20">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="font-bold text-xs text-[#0969da]"><?php echo htmlspecialchars($curr['name']); ?></span>
                                        <span class="text-xs font-semibold text-green-700 bg-green-50 px-2.5 py-0.5 rounded-full">全体平均: <?php echo $curr['average_proficiency']; ?>%</span>
                                    </div>

                                    <!-- タスク別 習熟度数値更新 -->
                                    <div class="space-y-3">
                                        <?php foreach ($curr['tasks'] as $task): ?>
                                            <div class="bg-white border border-gray-150 rounded p-3 flex items-center justify-between">
                                                <div class="w-1/2">
                                                    <p class="text-xs font-semibold text-gray-700"><?php echo htmlspecialchars($task['task_name']); ?></p>
                                                    <div class="w-full bg-gray-100 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                                        <div class="bg-blue-500 h-full" style="width: <?php echo $task['proficiency']; ?>%"></div>
                                                    </div>
                                                </div>

                                                <!-- 評価入力フォーム -->
                                                <form action="<?php echo BASE_URL; ?>curriculum/update_proficiency" method="POST" class="flex items-center space-x-2">
                                                    <input type="hidden" name="student_id" value="<?php echo $selected_student['id']; ?>">
                                                    <input type="hidden" name="task_id" value="<?php echo $task['task_id']; ?>">
                                                    <div class="flex items-center space-x-1">
                                                        <input type="number" name="proficiency" min="0" max="100" value="<?php echo $task['proficiency']; ?>" required
                                                               class="border border-gray-300 rounded px-2 py-1 text-xs w-16 text-center focus:ring-1 focus:ring-blue-500">
                                                        <span class="text-xs text-gray-500">%</span>
                                                    </div>
                                                    <button type="submit" class="bg-[#24292f] hover:bg-[#1f2328] text-white font-semibold px-3 py-1 rounded text-[11px] transition">
                                                        評定更新
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- ==========================================
     タブ5: マスタデータ設定 (先生専用)
     ========================================== -->
<?php elseif ($current_tab === 'teacher_config' && $_SESSION['role'] === 'teacher'): ?>
    
    <div class="bg-white border border-[#d0d7de] rounded-lg p-5 shadow-sm">
        <h3 class="font-bold text-base text-[#24292f] pb-3 border-b border-gray-100 flex items-center space-x-2 mb-4">
            <i data-lucide="settings" class="w-5 h-5 text-gray-700"></i>
            <span>指導用プログラミング言語・タスク（マスタ設定）</span>
        </h3>
        <p class="text-xs text-gray-500 mb-6 leading-relaxed">
            生徒たちが学習選択や進捗投稿に利用する「指導用カリキュラム言語」と、その中に含まれる「詳細な学習タスク」を定義・編集します。<br>
            <strong>新タスク追加：</strong>登録すると、該当言語を学習登録している全生徒へ自動通知が飛び、自動的に0%進捗レコードが生成されます。
        </p>

        <div class="grid grid-cols-2 gap-6">
            <!-- 1. 新規プログラミング言語の登録 -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                <h4 class="font-bold text-xs text-gray-700 mb-3 flex items-center space-x-1">
                    <i data-lucide="plus-square" class="w-4 h-4"></i><span>新規プログラミング言語を追加</span>
                </h4>
                <form action="<?php echo BASE_URL; ?>master/add_curriculum" method="POST" class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">プログラミング言語名</label>
                        <input type="text" name="name" required placeholder="例: Ruby / Go / React"
                               class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:outline-none">
                    </div>
                    <button type="submit" class="w-full bg-[#2da44e] hover:bg-[#2c974b] text-white text-xs font-semibold py-2 rounded transition">
                        言語マスタとして追加する
                    </button>
                </form>
            </div>

            <!-- 2. 詳細タスクの割り当て -->
            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50">
                <h4 class="font-bold text-xs text-gray-700 mb-3 flex items-center space-x-1">
                    <i data-lucide="list-plus" class="w-4 h-4"></i><span>カリキュラム学習タスクを割り当て</span>
                </h4>
                <form action="<?php echo BASE_URL; ?>master/add_task" method="POST" class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">対象の親言語</label>
                        <select name="curriculum_id" required class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:outline-none">
                            <option value="">言語を選択してください...</option>
                            <?php foreach ($curriculumsWithTasks as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">詳細カリキュラムタスク名</label>
                        <input type="text" name="task_name" required placeholder="例: データベースCRUD処理"
                               class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs bg-white focus:outline-none">
                    </div>
                    <button type="submit" class="w-full bg-[#24292f] hover:bg-[#1f2328] text-white text-xs font-semibold py-2 rounded transition">
                        新規課題タスクとして追加
                    </button>
                </form>
            </div>

            <!-- マスタデータ構造ツリーのプレビュー表示 -->
            <div class="col-span-2 border border-gray-200 rounded-lg p-4 bg-white">
                <h4 class="font-bold text-xs text-gray-700 mb-3">現在のマスターデータ構成ツリー</h4>
                <div class="grid grid-cols-2 gap-4">
                    <?php foreach ($curriculumsWithTasks as $c): ?>
                        <div class="border border-gray-150 rounded p-3 text-xs">
                            <p class="font-bold text-sm text-[#24292f] border-b border-gray-100 pb-1.5 flex items-center justify-between">
                                <span>🚀 <?php echo htmlspecialchars($c['name']); ?></span>
                                <span class="text-[10px] text-gray-400">タスク数: <?php echo count($c['tasks']); ?></span>
                            </p>
                            <ul class="mt-2 space-y-1.5 text-gray-600 pl-2">
                                <?php if (empty($c['tasks'])): ?>
                                    <li class="text-gray-400 text-[11px] italic">タスクはまだ登録されていません</li>
                                <?php else: ?>
                                    <?php foreach ($c['tasks'] as $t): ?>
                                        <li class="flex items-center space-x-1">
                                            <span class="text-gray-400">├</span>
                                            <span><?php echo htmlspecialchars($t['task_name']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- ==========================================
     投稿編集用 モーダルダイアログ
     ========================================== -->
<div id="edit-post-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
    <div class="bg-white border border-gray-300 rounded-lg max-w-lg w-full p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150">
        <h4 class="font-bold text-base text-gray-800 mb-4 flex items-center space-x-2">
            <i data-lucide="edit" class="w-5 h-5 text-gray-600"></i>
            <span>進捗投稿の編集 (投稿後1時間制限)</span>
        </h4>

        <form action="<?php echo BASE_URL; ?>post/edit" method="POST" class="space-y-4">
            <input type="hidden" name="post_id" id="edit-post-id">
            
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">本文・内容</label>
                <textarea name="content" id="edit-post-content" required rows="4" 
                          class="w-full border border-gray-300 rounded p-2 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">ソースコード</label>
                <textarea name="code_content" id="edit-post-code" rows="5" 
                          class="w-full border border-gray-300 rounded p-2 text-xs code-font bg-gray-50 focus:bg-white focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">参考URL</label>
                <input type="url" name="reference_url" id="edit-post-url"
                       class="w-full border border-gray-300 rounded p-2 text-xs focus:outline-none">
            </div>

            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="closeEditPostModal()" class="bg-gray-150 hover:bg-gray-200 text-gray-700 text-xs font-semibold px-4 py-2 rounded transition">
                    キャンセル
                </button>
                <button type="submit" class="bg-[#2da44e] hover:bg-[#2c974b] text-white text-xs font-semibold px-4 py-2 rounded transition">
                    変更を保存する
                </button>
            </div>
        </form>
    </div>
</div>


<!-- フッターを含むレイアウトの終了部分 -->
</main>
</div>

<!-- クライアントサイド動的制御用 JavaScript -->
<script>
    // Lucideアイコンの即時レンダリング
    lucide.createIcons();

    // 友達招待URLをクリップボードにコピー
    function copyInviteURL() {
        const input = document.getElementById('invite-url-input');
        input.select();
        document.execCommand('copy');

        const msg = document.getElementById('copy-message');
        msg.classList.remove('hidden');

        // コピーアイコンをチェックマークに変更
        const icon = document.getElementById('copy-icon');
        icon.setAttribute('data-lucide', 'check');
        lucide.createIcons();

        setTimeout(() => {
            msg.classList.add('hidden');
            icon.setAttribute('data-lucide', 'copy');
            lucide.createIcons();
        }, 3000);
    }

    // 投稿コードクリップボードコピー
    function copyCode(button) {
        const pre = button.parentElement.nextElementSibling.querySelector('pre');
        const range = document.createRange();
        range.selectNode(pre);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();

        const label = button.querySelector('span');
        label.innerText = 'Copied!';
        setTimeout(() => {
            label.innerText = 'Copy';
        }, 2000);
    }

    // 生徒用：選択された言語に基づいて、投稿可能な詳細タスク一覧を動的に更新（二重セレクト連動）
    const myCurriculums = <?php echo json_encode($studentCurriculums ?? []); ?>;
    
    function updatePostTasks() {
        const currSelect = document.getElementById('post-curriculum-select');
        const taskSelect = document.getElementById('post-task-select');
        const selectedId = parseInt(currSelect.value);

        taskSelect.innerHTML = '';

        if (!selectedId) {
            taskSelect.innerHTML = '<option value="">まず言語を選択してください</option>';
            taskSelect.disabled = true;
            return;
        }

        const match = myCurriculums.find(c => c.id === selectedId);
        if (match && match.tasks && match.tasks.length > 0) {
            match.tasks.forEach(task => {
                const opt = document.createElement('option');
                opt.value = task.task_id;
                opt.textContent = `${task.task_name} (現在習熟度: ${task.proficiency}%)`;
                taskSelect.appendChild(opt);
            });
            taskSelect.disabled = false;
        } else {
            taskSelect.innerHTML = '<option value="">タスクが登録されていません</option>';
            taskSelect.disabled = true;
        }
    }

    // 投稿編集用モーダルの表示制御
    function openEditPostModal(post) {
        document.getElementById('edit-post-id').value = post.id;
        document.getElementById('edit-post-content').value = post.content;
        document.getElementById('edit-post-code').value = post.code_content || '';
        document.getElementById('edit-post-url').value = post.reference_url || '';
        document.getElementById('edit-post-modal').classList.remove('hidden');
    }

    function closeEditPostModal() {
        document.getElementById('edit-post-modal').classList.add('hidden');
    }
</script>
</body>
</html>