<?php
class UserController {
    private $userModel;

    public function __construct($db) {
        require_once 'models/User.php';
        $this->userModel = new User($db);
    }

    // ログイン処理
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = trim($_POST['id'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($id) || empty($password)) {
                $_SESSION['error'] = 'ユーザーIDとパスワードを入力してください。';
                header('Location: /20260630/?action=login_form');
                exit;
            }

            $user = $this->userModel->authenticate($id, $password);
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['success'] = 'ログインしました。';
                header('Location: /20260630/');
                exit;
            } else {
                $_SESSION['error'] = 'ユーザーIDまたはパスワードが正しくありません。';
                header('Location: /20260630/?action=login_form');
                exit;
            }
        }
    }

    // 自己登録 (サインアップ)
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = trim($_POST['id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($id) || empty($name) || empty($password)) {
                $_SESSION['error'] = 'すべての項目を入力してください。';
                header('Location: /20260630/?action=register_form');
                exit;
            }

            if ($this->userModel->exists($id)) {
                $_SESSION['error'] = 'このユーザーIDは既に使用されています。';
                header('Location: /20260630/?action=register_form');
                exit;
            }

            // 登録実行
            if ($this->userModel->create($id, $name, $password, 'student')) {
                // 自動ログイン
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = 'student';
                $_SESSION['success'] = '自己登録が完了し、ログインしました！';

                // もしセッションに招待リンクによるホスト情報が保持されていれば、つながり登録
                if (!empty($_SESSION['invite_from'])) {
                    $host = $_SESSION['invite_from'];
                    if ($this->userModel->exists($host) && $host !== $id) {
                        $this->userModel->addFriendship($id, $host, '友達');
                        $_SESSION['success'] .= " 招待元「" . $host . "」さんとつながりました。";
                        unset($_SESSION['invite_from']);
                    }
                }

                header('Location: /20260630/');
                exit;
            } else {
                $_SESSION['error'] = '登録処理中にエラーが発生しました。';
                header('Location: /20260630/?action=register_form');
                exit;
            }
        }
    }

    // ログアウト処理
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        session_start();
        $_SESSION['success'] = 'ログアウトしました。';
        header('Location: /20260630/?action=login_form');
        exit;
    }

    // 友達の手動追加
    public function addFriend() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $my_id = $_SESSION['user_id'];
            $target_id = trim($_POST['target_id'] ?? '');

            if ($my_id === $target_id) {
                $_SESSION['error'] = '自分自身を友達登録することはできません。';
                header('Location: /20260630/');
                exit;
            }

            if (!$this->userModel->exists($target_id)) {
                $_SESSION['error'] = '該当するユーザーIDが存在しません。';
                header('Location: /20260630/');
                exit;
            }

            $this->userModel->addFriendship($my_id, $target_id, '友達');
            $_SESSION['success'] = 'つながり登録が完了しました。属性：「友達」が設定されました。';
            header('Location: /20260630/');
            exit;
        }
    }

    // 友達の属性（タグ）の変更
    public function changeFriendTag() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $my_id = $_SESSION['user_id'];
            $target_id = $_POST['target_id'] ?? '';
            $new_tag = trim($_POST['tag'] ?? '友達');

            if (empty($new_tag)) {
                $new_tag = '友達';
            }

            $this->userModel->updateFriendTag($my_id, $target_id, $new_tag);
            $_SESSION['success'] = "属性を変更しました。";
            header('Location: /20260630/');
            exit;
        }
    }

    // つながりの削除
    public function removeFriend() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $my_id = $_SESSION['user_id'];
            $target_id = $_POST['target_id'] ?? '';

            $this->userModel->removeFriendship($my_id, $target_id);
            $_SESSION['success'] = 'つながり情報を解除しました。';
            header('Location: /20260630/');
            exit;
        }
    }
}