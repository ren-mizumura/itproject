<?php
// セッション管理開始
session_start();

// 1. 共通データベース接続
require_once 'config/database.php';
$db = Database::getConnection();

// 2. 共通コントローラー読み込み
require_once 'controllers/UserController.php';
require_once 'controllers/BoardController.php';

$userController = new UserController($db);
$boardController = new BoardController($db);

// 3. ルーティングパラメータの解析
$action = $_GET['action'] ?? 'dashboard';

// --- セッションに招待用クエリパラメータの保持
if (isset($_GET['invite_from'])) {
    $_SESSION['invite_from'] = $_GET['invite_from'];
}

// 4. ゲスト（非ログイン状態）閲覧制限ガード
$auth_actions = ['login_form', 'login', 'register_form', 'register'];
if (!isset($_SESSION['user_id']) && !in_array($action, $auth_actions)) {
    // 招待URLを踏んでログインもしていない状態なら登録画面に優先的に誘導
    if (isset($_SESSION['invite_from'])) {
        header('Location: /20260630/?action=register_form');
    } else {
        header('Location: /20260630/?action=login_form');
    }
    exit;
}

// 5. アクションハンドリング・ルーティング分岐
switch ($action) {
    // --- 認証機能 ---
    case 'login_form':
        include 'views/user/login.php';
        break;
        
    case 'login':
        $userController->login();
        break;
        
    case 'register_form':
        include 'views/user/register.php';
        break;
        
    case 'register':
        $userController->register();
        break;
        
    case 'logout':
        $userController->logout();
        break;

    // --- つながり（友達・属性）機能 ---
    case 'add_friend':
        $userController->addFriend();
        break;

    case 'change_tag':
        $userController->changeFriendTag();
        break;

    case 'remove_friend':
        $userController->removeFriend();
        break;

    // --- カリキュラム・掲示板・通知機能 ---
    case 'create_post':
        $boardController->createPost();
        break;

    case 'update_post':
        $boardController->updatePost();
        break;

    case 'delete_post':
        $boardController->deletePost();
        break;

    case 'create_reply':
        $boardController->createReply();
        break;

    case 'delete_reply':
        $boardController->deleteReply();
        break;

    case 'add_language':
        $boardController->addLanguage();
        break;

    case 'add_task':
        $boardController->addTask();
        break;

    case 'delete_language':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'teacher') {
            $language = $_POST['language'] ?? '';
            $boardController->curriculumModel->deleteLanguage($language);
            $_SESSION['success'] = "学習言語「{$language}」と関連タスク、生徒進捗評価を全て削除しました。";
        }
        header('Location: /20260630/?tab=curriculum-editor');
        break;

    case 'delete_task':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'teacher') {
            $language = $_POST['language'] ?? '';
            $task = $_POST['task'] ?? '';
            $boardController->curriculumModel->deleteTask($language, $task);
            $_SESSION['success'] = "タスク「{$task}」を削除しました。";
        }
        header('Location: /20260630/?tab=curriculum-editor');
        break;

    case 'update_progress':
        $boardController->updateProgress();
        break;

    case 'update_student_languages':
        $boardController->updateStudentLanguages();
        break;

    case 'clear_notifications':
        $boardController->clearNotifications();
        break;

    // --- ダッシュボード（メインポータル） ---
    case 'dashboard':
    default:
        // header.php 内で自動ローディング
        include 'views/dashboard.php';
        break;
}