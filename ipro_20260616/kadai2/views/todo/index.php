<?php
/**
 * views/todo/index.php
 * ログイン中ユーザー専用のマイページです。
 * ヘッダーのレイアウト変更に伴い、パディングと配置バランスを最適化しました。
 */

// 共通ヘッダーを読み込みます
require_once __DIR__ . '/../layout/header.php';
?>

<!-- 
  【レイアウト調整ポイント】
  TODOリストなどのダッシュボードは中央に固める必要はないため、
  適度なパディング（py-8 px-4 md:px-6）を持たせて上部から自然に幅広に展開されるようにしています。
-->
<div class="w-full max-w-6xl mx-auto py-8 px-4 md:px-6 space-y-8">
    
    <!-- イントロウェルカム -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800">マイページ ＆ タスク管理</h1>
            <p class="text-slate-500 text-sm mt-1">
                こんにちは、<strong class="text-blue-600 font-semibold"><?php echo h($user_email); ?></strong>さん。タスクを管理して生産性を向上させましょう！
            </p>
        </div>
        <div class="bg-blue-50 border border-blue-100 px-4 py-2.5 rounded-xl shrink-0 self-start md:self-center">
            <span class="text-xs text-blue-600 font-bold block">登録タスク総数</span>
            <span class="text-xl font-black text-blue-800 font-mono"><?php echo count($tasks); ?> 件</span>
        </div>
    </div>

    <!-- コンテンツ 2カラム構造 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- 左：タスク追加フォーム -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-6">
                <h2 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">新規タスク作成</h2>
                
                <form action="index.php?action=todo_add" method="POST" class="space-y-4">
                    <div>
                        <label for="title" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">タイトル <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" id="title" required 
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all text-slate-800"
                               placeholder="例: PHPのMVCを学ぶ">
                    </div>

                    <div>
                        <label for="body" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">詳細・メモ</label>
                        <textarea name="body" id="body" rows="3"
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all resize-none text-slate-800"
                                  placeholder="例: ModelとControllerの結合をテストする"></textarea>
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-3 px-4 rounded-xl transition-all shadow-md shadow-blue-100 flex justify-center items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        タスクを追加する
                    </button>
                </form>
            </div>
        </div>

        <!-- 右：タスク一覧表示 -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-base font-bold text-slate-800 mb-6 pb-2 border-b border-slate-100">タスクリスト</h2>

                <?php if (empty($tasks)): ?>
                    <div class="text-center py-12 px-4">
                        <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-slate-700 font-bold mb-1">未登録です</h3>
                        <p class="text-slate-400 text-xs">左のフォームから新しいタスクを作成してみましょう！</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($tasks as $task): ?>
                            <div class="p-4 rounded-xl border transition-all flex items-start gap-4 <?php echo $task['complete'] ? 'bg-slate-50/70 border-slate-100' : 'bg-white border-slate-150 hover:border-slate-300 hover:shadow-sm'; ?>">
                                
                                <form action="index.php?action=todo_toggle" method="POST" class="mt-0.5">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="focus:outline-none" title="状態を切り替える">
                                        <?php if ($task['complete']): ?>
                                            <div class="w-5 h-5 bg-emerald-500 text-white rounded-full flex items-center justify-center border border-emerald-500">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-5 h-5 rounded-full border-2 border-slate-300 hover:border-blue-500 transition-all bg-white"></div>
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <div class="flex-grow min-w-0">
                                    <h4 class="text-sm font-bold text-slate-800 break-words <?php echo $task['complete'] ? 'line-through text-slate-400 font-medium' : ''; ?>">
                                        <?php echo h($task['title']); ?>
                                    </h4>
                                    
                                    <?php if (!empty($task['body'])): ?>
                                        <p class="text-xs text-slate-500 mt-1 break-words leading-relaxed whitespace-pre-wrap <?php echo $task['complete'] ? 'text-slate-400' : ''; ?>">
                                            <?php echo h($task['body']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <span class="text-[10px] text-slate-400 mt-2 block font-medium">
                                        作成日時: <?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?>
                                    </span>
                                </div>

                                <form action="index.php?action=todo_delete" method="POST" class="shrink-0" onsubmit="return confirm('このタスクを完全に削除しますか？');">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="text-slate-400 hover:text-rose-500 p-1.5 rounded-lg hover:bg-rose-50 transition-all" title="タスクを削除する">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</main>
</body>
</html>