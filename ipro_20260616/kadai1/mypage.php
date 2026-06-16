<?php
/**
 * mypage.php
 * ログインユーザー専用のマイページです。
 * 登録されているプロフィール情報の表示に加え、自身が作成したタスク（TODO）の表示・追加・完了切り替え・削除を行えます。
 */

// 1. 共通関数ファイルとTODO専用関数ファイルを読み込む
require_once 'functions.php';
require_once 'todoFunctions.php';

// 2. ログイン状態の厳格なチェック
// 未ログインの場合は、この関数内で自動的に login.php に強制リダイレクトされ、以降の処理は一切実行されません。
require_login();

// 3. データベース接続の確立とセッション情報の取得
$pdo = db_connect();
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// エラー・成功メッセージ用の変数初期化
$error_message = '';
$success_message = '';

// 4. POSTリクエスト（タスク操作アクション）の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // アクションの種類を判別する（add: 追加, toggle: 状態切り替え, delete: 削除）
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add') {
        // --- タスク新規追加処理 ---
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $body = isset($_POST['body']) ? trim($_POST['body']) : '';

        // タイトルは必須入力とするバリデーション
        if ($title === '') {
            $error_message = 'タスクのタイトルを入力してください。';
        } else {
            // 安全な関数を通してデータベースにタスクを登録
            if (add_task($pdo, $user_id, $title, $body)) {
                // 【PRGパターン (Post-Redirect-Get)】
                // 二重送信を防止するため、処理成功後は必ずリダイレクトしてPOST状態をリセットします。
                header('Location: mypage.php');
                exit;
            } else {
                $error_message = 'タスクの登録に失敗しました。';
            }
        }

    } elseif ($action === 'toggle') {
        // --- タスク完了/未完了状態切り替え処理 ---
        $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;

        if ($task_id > 0) {
            // 本人のタスクのみが切り替わるよう、関数内で $user_id を用いた所有権確認が行われます
            if (toggle_task($pdo, $task_id, $user_id)) {
                header('Location: mypage.php');
                exit;
            } else {
                $error_message = 'タスク状態の更新に失敗しました（権限がありません）。';
            }
        }

    } elseif ($action === 'delete') {
        // --- タスク削除処理 ---
        $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;

        if ($task_id > 0) {
            // 本人のタスクのみが削除されるよう、安全な物理削除を行います
            if (delete_task($pdo, $task_id, $user_id)) {
                header('Location: mypage.php');
                exit;
            } else {
                $error_message = 'タスクの削除に失敗しました（権限がありません）。';
            }
        }
    }
}

// 5. 表示するタスク一覧の取得
// 常に最新のタスクデータをDBから取得して画面にレンダリングします
$tasks = get_tasks($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ ＆ TODO</title>
    <!-- Tailwind CSS を使って洗練されたモダンなデザインを提供 -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen py-8 px-4 font-sans">
    <div class="max-w-4xl mx-auto space-y-8">
        
        <!-- ヘッダー・プロフィールエリア -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-8 md:px-8 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="bg-white/20 backdrop-blur-md text-xs px-3 py-1 rounded-full font-medium inline-block mb-2">ログイン認証完了</span>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">マイページ ＆ タスク管理</h1>
                    <p class="text-blue-100 text-sm mt-1">ようこそ、<?php echo h($user_email); ?> さん</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-blue-200 font-mono">User ID: <?php echo h($user_id); ?></span>
                    <a href="logout.php" 
                       class="bg-rose-500 hover:bg-rose-600 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-all shadow-md shadow-rose-900/10 flex items-center gap-2">
                        <!-- ログアウトアイコン -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        ログアウト
                    </a>
                </div>
            </div>
        </div>

        <!-- アプリケーション・コンテンツ（2カラムレイアウト設計） -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- 左カラム：タスク新規追加フォーム -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-8">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">新しいタスクを追加</h2>
                    
                    <!-- フォーム送信に伴うエラーの安全な出力 -->
                    <?php if ($error_message !== ''): ?>
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-3 rounded-xl mb-4 text-xs flex items-start gap-2">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span><?php echo h($error_message); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="mypage.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div>
                            <label for="title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">タイトル <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" id="title" required 
                                   class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                                   placeholder="例：PHPの復習をする">
                        </div>

                        <div>
                            <label for="body" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">詳細（メモ）</label>
                            <textarea name="body" id="body" rows="3"
                                      class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all resize-none"
                                      placeholder="例：functions.phpのセキュリティ設計を書き出す"></textarea>
                        </div>

                        <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-all shadow-lg shadow-blue-100 flex justify-center items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            タスクを追加する
                        </button>
                    </form>
                </div>
            </div>

            <!-- 右カラム：タスク一覧表示エリア -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-6 pb-2 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-800">タスク一覧</h2>
                        <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2.5 py-1 rounded-full">
                            全 <?php echo count($tasks); ?> 件
                        </span>
                    </div>

                    <?php if (empty($tasks)): ?>
                        <!-- タスクが1件もない場合の空画面表示（Empty State） -->
                        <div class="text-center py-12 px-4">
                            <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                            <h3 class="text-slate-700 font-bold mb-1">登録されたタスクはありません</h3>
                            <p class="text-slate-400 text-sm">左のフォームから新しいタスクを作成してみましょう！</p>
                        </div>
                    <?php else: ?>
                        <!-- タスクリストの展開 -->
                        <div class="space-y-4">
                            <?php foreach ($tasks as $task): ?>
                                <!-- 完了状態（completeが1）のタスクは背景をトーンダウンさせ、視覚的に区別します -->
                                <div class="p-4 rounded-xl border transition-all flex items-start gap-3 <?php echo $task['complete'] ? 'bg-slate-50/70 border-slate-100' : 'bg-white border-slate-100 hover:border-slate-200 hover:shadow-sm'; ?>">
                                    
                                    <!-- 1. 状態切り替えチェックボタン用のフォーム -->
                                    <form action="mypage.php" method="POST" class="mt-0.5">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="focus:outline-none" title="状態を切り替える">
                                            <?php if ($task['complete']): ?>
                                                <!-- 完了アイコン（緑） -->
                                                <div class="w-5 h-5 bg-emerald-500 text-white rounded-full flex items-center justify-center border border-emerald-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            <?php else: ?>
                                                <!-- 未完了チェックボックス（ホバーで青枠に変化） -->
                                                <div class="w-5 h-5 rounded-full border-2 border-slate-300 hover:border-blue-500 transition-all bg-white"></div>
                                            <?php endif; ?>
                                        </button>
                                    </form>

                                    <!-- 2. タイトルと詳細メモのテキスト表示領域 -->
                                    <div class="flex-grow min-w-0">
                                        <h4 class="text-sm font-bold text-slate-800 break-words <?php echo $task['complete'] ? 'line-through text-slate-400 font-medium' : ''; ?>">
                                            <?php echo h($task['title']); ?>
                                        </h4>
                                        
                                        <!-- 詳細（body）が入力されている場合のみ表示 -->
                                        <?php if (!empty($task['body'])): ?>
                                            <p class="text-xs text-slate-500 mt-1 break-words leading-relaxed whitespace-pre-wrap <?php echo $task['complete'] ? 'text-slate-400' : ''; ?>"><?php echo h($task['body']); ?></p>
                                        <?php endif; ?>
                                        
                                        <!-- 作成日時の表示 -->
                                        <span class="text-[10px] text-slate-400 mt-2 block font-medium">
                                            作成日: <?php echo date('Y/m/d H:i', strtotime($task['created_at'])); ?>
                                        </span>
                                    </div>

                                    <!-- 3. タスク削除用のボタンフォーム -->
                                    <form action="mypage.php" method="POST" class="shrink-0" onsubmit="return confirm('このタスクを本当に削除しますか？');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button type="submit" class="text-slate-400 hover:text-rose-500 p-1 rounded-lg hover:bg-rose-50 transition-all" title="タスクを削除する">
                                            <!-- ゴミ箱アイコン -->
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
</body>
</html>