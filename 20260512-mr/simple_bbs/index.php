<?php
/**
 * フロントコントローラー（ルーティング）
 */
require_once 'controllers/PostController.php';

$controller = new PostController();

// 簡単なルーティング
$action = $_GET['action'] ?? 'index';

if ($action === 'store') {
    $controller->store();
} else {
    $controller->index();
}