<?php
/**
 * index.php
 * すべてのリクエスト（URLアクセス）を最初に受け取り、解析して
 * 適切なコントローラーとアクションに処理を振り分ける「フロントコントローラー」です。
 * * MVCにおける「交通整理（ルーティング）」の役割を担います。
 */

// セッションの開始（セッションハイジャックや固定攻撃対策の前提となる状態を維持）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// データベース接続クラスと、必要なModel、Controllerファイルを一括でロードします。
// （本来はオートローダーを作成しますが、今回は学習用として分かりやすく明示的に読み込みます）
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Todo.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/TodoController.php';

// 共通エスケープ関数 (XSS対策)
// ViewでHTML出力する際に、意図しないJavaScriptの実行を防ぐためのヘルパー関数です。
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// URLパラメータから、実行するアクション（機能）を取得します。
// 例: index.php?action=login や index.php?action=todo_add など
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// インスタンスの生成準備
$db = Database::getConnection();
$userController = new UserController($db);
$todoController = new TodoController($db);

// ルーティング（リクエストの解析と処理の振り分け）
// actionパラメータの値に基づいて、呼び出すコントローラーのメソッドを分岐させます。
switch ($action) {
    // === ユーザー管理系のアクション ===
    case 'register':
        // 新規登録画面の表示および登録処理の受付
        $userController->register();
        break;

    case 'login':
        // ログイン画面の表示および認証処理の受付
        $userController->login();
        break;

    case 'logout':
        // ログアウト処理の実行
        $userController->logout();
        break;

    // === TODO（タスク）管理系のアクション (Step 2) ===
    case 'todo':
        // TODOマイページの表示
        $todoController->index();
        break;

    case 'todo_add':
        // タスクの新規追加
        $todoController->add();
        break;

    case 'todo_toggle':
        // タスクの完了・未完了ステータスの反転
        $todoController->toggle();
        break;

    case 'todo_delete':
        // タスクの削除
        $todoController->delete();
        break;

    default:
        // 定義されていない不正なアクションが指定された場合は、ログイン画面にリダイレクト
        header('Location: index.php?action=login');
        exit;
}