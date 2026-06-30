<?php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * ログイン処理
     */
    public function login() {
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "dashboard");
            exit;
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = "ユーザーIDとパスワードを入力してください。";
            } else {
                $user = $this->userModel->findByUsername($username);
                if ($user && password_verify($password, $user['password_hash'])) {
                    // セッションへの情報格納
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['display_name'] = $user['display_name'];
                    $_SESSION['role'] = $user['role'];

                    // 友達招待URLを処理した形跡がある場合（クッキーなどに一時保存されていた場合）
                    if (isset($_SESSION['pending_invite_token'])) {
                        $this->processInviteLink($_SESSION['pending_invite_token'], $user['id']);
                        unset($_SESSION['pending_invite_token']);
                    }

                    header("Location: " . BASE_URL . "dashboard");
                    exit;
                } else {
                    $error = "ユーザーIDまたはパスワードが正しくありません。";
                }
            }
        }

        // ビュー読み込み
        include __DIR__ . '/../views/user/login.php';
    }

    /**
     * サインアップ・生徒自己登録
     */
    public function register() {
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "dashboard");
            exit;
        }

        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $display_name = trim($_POST['display_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($username) || empty($display_name) || empty($password)) {
                $error = "全ての項目を入力してください。";
            } elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
                $error = "ユーザーIDには半角英数字、ハイフン、アンダースコアのみ使用できます。";
            } elseif ($password !== $password_confirm) {
                $error = "確認用パスワードが一致しません。";
            } elseif (strlen($password) < 6) {
                $error = "パスワードは6文字以上で入力してください。";
            } else {
                // 重複確認
                $existing = $this->userModel->findByUsername($username);
                if ($existing) {
                    $error = "このユーザーIDは既に登録されています。";
                } else {
                    $new_id = $this->userModel->register($username, $display_name, $password);
                    if ($new_id) {
                        // 登録後自動ログイン
                        $_SESSION['user_id'] = $new_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['display_name'] = $display_name;
                        $_SESSION['role'] = 'student';

                        // 友達招待URLを処理
                        if (isset($_SESSION['pending_invite_token'])) {
                            $this->processInviteLink($_SESSION['pending_invite_token'], $new_id);
                            unset($_SESSION['pending_invite_token']);
                        }

                        header("Location: " . BASE_URL . "dashboard");
                        exit;
                    } else {
                        $error = "登録処理に失敗しました。システム管理者に問い合わせてください。";
                    }
                }
            }
        }

        include __DIR__ . '/../views/user/register.php';
    }

    /**
     * ログアウト
     */
    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: " . BASE_URL . "login");
        exit;
    }

    /**
     * ユーザー検索＆友達属性管理の処理（Ajax or POST）
     */
    public function manageFriends() {
        $this->checkAuth();
        $user_id = $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add_or_update') {
                $friend_id = intval($_POST['friend_id'] ?? 0);
                $tag = trim($_POST['attribute_tag'] ?? '友達');
                if ($friend_id > 0 && $friend_id != $user_id) {
                    $this->userModel->saveOrUpdateFriendship($user_id, $friend_id, $tag);
                }
            } elseif ($action === 'delete') {
                $friend_id = intval($_POST['friend_id'] ?? 0);
                if ($friend_id > 0) {
                    $this->userModel->removeFriendship($user_id, $friend_id);
                }
            }
            header("Location: " . BASE_URL . "dashboard?tab=friends");
            exit;
        }
    }

    /**
     * 招待トークンリンクアクセス時の処理
     */
    public function handleInvite($token) {
        $inviter = $this->userModel->findByInviteToken($token);
        if (!$inviter) {
            die("無効な招待URLです。");
        }

        if (!isset($_SESSION['user_id'])) {
            // ログインしていない場合はセッションに退避させてログイン/登録画面へリダイレクト
            $_SESSION['pending_invite_token'] = $token;
            header("Location: " . BASE_URL . "login?invited_by=" . urlencode($inviter['display_name']));
            exit;
        }

        // ログイン状態であれば自動で相互に友達（デフォルト：友達）として登録
        $this->processInviteLink($token, $_SESSION['user_id']);
        header("Location: " . BASE_URL . "dashboard?tab=friends&invited_success=1");
        exit;
    }

    /**
     * 招待リンクによる実際の紐付け処理
     */
    private function processInviteLink($token, $current_user_id) {
        $inviter = $this->userModel->findByInviteToken($token);
        if ($inviter && $inviter['id'] != $current_user_id) {
            // 招待者側にとって、自分を「友達」登録
            $this->userModel->saveOrUpdateFriendship($inviter['id'], $current_user_id, '友達');
            // 自分側にとって、招待者を「友達」登録 (双方向自動紐付け)
            $this->userModel->saveOrUpdateFriendship($current_user_id, $inviter['id'], '友達');
        }
    }

    /**
     * ログイン認証ガード
     */
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "login");
            exit;
        }
    }
}