<?php
/**
 * controllers/TodoController.php
 * ログインユーザー向けマイページの表示、およびTODOタスク（追加・トグル・削除）を制御するコントローラークラスです。
 */

class TodoController {
    // Todoモデルインスタンスを保持するプロパティ
    private $todoModel;

    /**
     * コンストラクタ
     * @param PDO $db
     */
    public function __construct($db) {
        $this->todoModel = new Todo($db);
    }

    /**
     * アクセス制限（ガード機能）
     * ログインが確認できない場合は処理を即中断し、ログイン画面へと追い返すための共通プライベートメソッドです。
     */
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit; // 確実にそれ以降のコントローラーコードが動くのを防ぎます
        }
    }

    /**
     * 1. TODOマイページメイン表示（index）アクション
     */
    public function index() {
        // アクセス制限のチェック
        $this->checkAuth();

        // セッションに保存されているユーザーIDを取得
        $user_id = $_SESSION['user_id'];
        $user_email = $_SESSION['user_email'];

        // Todoモデルを使って、ログイン中のユーザーが持つタスクリストのみを取得
        $tasks = $this->todoModel->getAllByUserId($user_id);

        // ビューファイルで共通して参照できるよう変数展開し、TODO用 index.php 画面を表示
        require_once __DIR__ . '/../views/todo/index.php';
    }

    /**
     * 2. タスク新規追加（add）アクション
     */
    public function add() {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $body = isset($_POST['body']) ? trim($_POST['body']) : '';

            // 必須チェック（サーバーサイドバリデーション）
            if ($title !== '') {
                // Todoモデルを使用してタスクを登録
                $this->todoModel->create($user_id, $title, $body);
            }
        }

        // 登録が完了した、またはタイトルが空などの後でも、マイページにリダイレクトさせてPOST情報を消去（二重送信防止）
        header('Location: index.php?action=todo');
        exit;
    }

    /**
     * 3. タスク完了・未完了反転（toggle）アクション
     */
    public function toggle() {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;

            if ($task_id > 0) {
                // 認証済みのuser_idと一緒にモデルを呼び出して、他人のデータの改ざんを防ぎます
                $this->todoModel->toggle($task_id, $user_id);
            }
        }

        header('Location: index.php?action=todo');
        exit;
    }

    /**
     * 4. タスク削除（delete）アクション
     */
    public function delete() {
        $this->checkAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;

            if ($task_id > 0) {
                // 認証済みのuser_idと一緒にモデルを呼び出して、他人のデータの勝手な削除を防ぎます
                $this->todoModel->delete($task_id, $user_id);
            }
        }

        header('Location: index.php?action=todo');
        exit;
    }
}