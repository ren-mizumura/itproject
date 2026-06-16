<?php
/**
 * controllers/UserController.php
 * 新規会員登録、ログイン認証、ログアウトといったユーザー管理フロー全体を制御するコントローラークラスです。
 */

class UserController {
    // 使用するUserモデルインスタンスを保持するプロパティ
    private $userModel;

    /**
     * コンストラクタ
     * データベースオブジェクトを受け取り、Userモデルをインスタンス化します。
     * * @param PDO $db
     */
    public function __construct($db) {
        $this->userModel = new User($db);
    }

    /**
     * 1. 会員登録（register）アクション
     * GETリクエスト時は登録フォームを表示し、POSTリクエスト時はデータベース登録を実行します。
     */
    public function register() {
        // すでにログインしている場合はマイページ（TODO一覧画面）へ自動でリダイレクト
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=todo');
            exit; // 確実にそれ以降の出力を停止
        }

        $error = '';
        $email = '';

        // フォームが送信（POST）された場合
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            // バリデーション（入力値検証）
            if ($email === '' || $password === '') {
                $error = 'すべての項目を入力してください。';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'メールアドレスの形式が正しくありません。';
            } elseif (mb_strlen($password) < 6) {
                $error = 'パスワードは6文字以上で入力してください。';
            } else {
                // すでに登録されているメールアドレスかチェック
                $existingUser = $this->userModel->findByEmail($email);
                if ($existingUser) {
                    $error = 'このメールアドレスは既に登録されています。';
                } else {
                    // モデルを呼び出して登録処理を実行
                    if ($this->userModel->register($email, $password)) {
                        // 登録完了時、ログイン画面に「登録成功」を伝えつつ安全にリダイレクト
                        header('Location: index.php?action=login&signup=success');
                        exit;
                    } else {
                        $error = '登録処理中にエラーが発生しました。';
                    }
                }
            }
        }

        // View（画面）の読み込み
        // コントローラー内のローカル変数（$error, $email）は、読み込まれたViewファイル内でそのまま参照可能です。
        require_once __DIR__ . '/../views/user/register.php';
    }

    /**
     * 2. ログイン（login）アクション
     * ログイン画面の表示と、認証手続きの実行、セッション管理を行います。
     */
    public function login() {
        // すでにログインしている場合は、マイページへリダイレクト
        if (isset($_SESSION['user_id'])) {
            header('Location: index.php?action=todo');
            exit;
        }

        $error = '';
        $success_message = '';
        $email = '';

        // 新規登録からの遷移時の完了メッセージを検知
        if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
            $success_message = 'ユーザー登録が完了しました！ログインしてください。';
        }

        // ログインフォームが送信された場合
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($email === '' || $password === '') {
                $error = 'メールアドレスとパスワードを入力してください。';
            } else {
                // Userモデルを使った認証
                $user = $this->userModel->authenticate($email, $password);

                if ($user) {
                    // 【セキュリティ：セッション固定攻撃対策】
                    // ログイン成功時に古いセッションIDを完全に破棄して新しく再生成します。
                    session_regenerate_id(true);

                    // サーバー側のセッション変数にユーザー情報を保存
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];

                    // マイページ（TODO一覧）へ安全に遷移
                    header('Location: index.php?action=todo');
                    exit;
                } else {
                    // 【セキュリティ】詳細すぎるエラー原因を攻撃者に明かさないよう統一メッセージにします
                    $error = 'メールアドレスまたはパスワードが正しくありません。';
                }
            }
        }

        // ログインView（画面）を読み込む
        require_once __DIR__ . '/../views/user/login.php';
    }

    /**
     * 3. ログアウト（logout）アクション
     * セッションおよびブラウザ上のセッションクッキーを安全かつ完全に破棄します。
     */
    public function logout() {
        // セッション変数の初期化
        $_SESSION = [];

        // ブラウザのセッションID用クッキーのクリア
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // セッションの破棄
        session_destroy();

        // ログイン画面に強制遷移して終了
        header('Location: index.php?action=login');
        exit;
    }
}