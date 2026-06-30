<?php
/**
 * =======================================================
 * ユーザ管理機能付き掲示板（学習進捗管理システム）
 * フロントコントローラー & カスタムルーター (index.php)
 * =======================================================
 */

// セッションの開始
session_start();

// 技術要件: XAMPP環境（PHP/MySQL想定）に基づくポートおよびルートパス設定
define('BASE_PORT', '8080');
define('BASE_PATH', '/ipro_20260630/');
define('BASE_URL', 'http://localhost:' . BASE_PORT . BASE_PATH);

// セキュリティレスポンスヘッダーの設定（X-XSS-Protection、X-Frame-Options、X-Content-Type-Optionsなど）
header('X-XSS-Protection: 1; mode=block');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

// リクエストURIのパース処理（クエリ文字列の切り離し）
$request_uri = $_SERVER['REQUEST_URI'];
$base_path_escaped = preg_quote(BASE_PATH, '/');
// URLパスを正規化
$path = preg_replace('/^' . $base_path_escaped . '/', '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = rtrim($path, '/');

// インポートコントローラー群
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/BoardController.php';

$userController = new UserController();
$boardController = new BoardController();

// -------------------------------------------------------
// 高機能URLルーティングのシミュレーション
// -------------------------------------------------------

if ($path === '' || $path === 'login') {
    // ログイン処理
    $userController->login();
} elseif ($path === 'register') {
    // 自己登録
    $userController->register();
} elseif ($path === 'logout') {
    // ログアウト
    $userController->logout();
} elseif ($path === 'dashboard') {
    // メインダッシュボード画面
    $boardController->dashboard();
} elseif ($path === 'post/create') {
    // 投稿作成
    $boardController->createPost();
} elseif ($path === 'post/edit') {
    // 投稿編集
    $boardController->editPost();
} elseif ($path === 'post/delete') {
    // 投稿削除
    $boardController->deletePost();
} elseif ($path === 'reply') {
    // 指導リプライ処理 (作成/編集/削除)
    $boardController->handleReply();
} elseif ($path === 'curriculum/select') {
    // 生徒が学習カリキュラムをプロフィールに追加登録
    $boardController->selectCurriculum();
} elseif ($path === 'friends/manage') {
    // 友達検索・登録・属性タグの変更/削除
    $userController->manageFriends();
} elseif (preg_match('/^invite\/([a-f0-9]+)$/', $path, $matches)) {
    // 専用招待URL（トークン付き）アクセス時
    $token = $matches[1];
    $userController->handleInvite($token);
} elseif ($path === 'master/add_curriculum') {
    // 先生：言語マスタ追加
    $boardController->masterAddCurriculum();
} elseif ($path === 'master/add_task') {
    // 先生：詳細タスク追加
    $boardController->masterAddTask();
} elseif ($path === 'curriculum/update_proficiency') {
    // 先生：生徒のタスク習熟度評価(%)を更新
    $boardController->updateStudentProficiency();
} else {
    // URL不整合時、ログイン状態に合わせてリダイレクト
    if (isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "dashboard");
    } else {
        header("Location: " . BASE_URL . "login");
    }
    exit;
}