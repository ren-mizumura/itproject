<?php
/**
 * ユーザーコントローラー
 * * 画面の制御、ビジネスロジック、CSRF検証、ログイン判定、バリデーション等（C:Controller）を行います。
 */

require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * 新規ユーザー登録のアクション
     */
    public function register() {
        $errors = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 【CSRF (A05:2021) 対策：トークンの検証】
            // 送信されたトークンが、セッションに保存されたトークンと一致するか厳密に比較します。
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                // hash_equals()を使用するのは、タイミング攻撃（応答時間の差から文字を推測する攻撃）を防ぐため。
                die("不正なリクエストです。(CSRF検証失敗)");
            }

            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

            // 1. バリデーション：メールアドレスの形式チェック
            if (empty($email)) {
                $errors[] = 'メールアドレスを入力してください。';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // filter_varによりメールアドレスの妥当性をチェック（形式不正のインジェクション防御も兼ねる）
                $errors[] = '有効なメールアドレスの形式で入力してください。';
            } elseif (strlen($email) > 255) {
                $errors[] = 'メールアドレスは255文字以内で入力してください。';
            }

            // 2. バリデーション：パスワードの強度チェック
            // パスワード辞書攻撃やブルートフォースに対抗するため、最低文字数などの制約を設けます。
            if (empty($password)) {
                $errors[] = 'パスワードを入力してください。';
            } elseif (strlen($password) < 8) {
                $errors[] = 'パスワードは8文字以上で設定してください。';
            }

            if ($password !== $password_confirm) {
                $errors[] = 'パスワード（確認用）が一致しません。';
            }

            // 3. 重複登録チェック
            if (empty($errors)) {
                $existingUser = $this->userModel->findByEmail($email);
                if ($existingUser) {
                    $errors[] = 'このメールアドレスは既に登録されています。';
                }
            }

            // 4. 新規作成処理
            if (empty($errors)) {
                if ($this->userModel->create($email, $password)) {
                    $success = true;
                    // セッションにメッセージを格納して、ログイン画面にリダイレクト
                    $_SESSION['flash_message'] = '登録が完了しました。ログインしてください。';
                    
                    // 【セキュリティ上超重要：リダイレクト後のexit/die】
                    // header("Location: ...") はブラウザにリダイレクトを「要請」するだけで、PHPの処理自体は止まりません。
                    // 後続の処理が実行されて情報が漏洩するのを完全に防ぐため、リダイレクトの直後は必ず「exit;」を記述します。
                    header("Location: index.php?action=login");
                    exit;
                } else {
                    $errors[] = '登録処理中にエラーが発生しました。再度お試しください。';
                }
            }
        }

        // 画面のレンダリング
        require_once __DIR__ . '/../views/user/register.php';
    }

    /**
     * ログインアクション
     */
    public function login() {
        // すでにログインしている場合はマイページへ
        if (isset($_SESSION['user_id'])) {
            header("Location: index.php?action=mypage");
            exit;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF検証
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die("不正なリクエストです。(CSRF検証失敗)");
            }

            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($email) || empty($password)) {
                $errors[] = 'メールアドレスとパスワードを入力してください。';
            } else {
                // メールアドレスからユーザーを検索
                $user = $this->userModel->findByEmail($email);

                // 【セキュリティ上重要な認証ロジック】
                // 1. パスワードの照合は「password_verify()」を使用して、ハッシュ値と安全に比較します。
                // 2. 「ユーザーが存在しなかった場合」と「パスワードが違った場合」でエラーメッセージを変えてはいけません。
                // 　「メールアドレスが存在しません」と出力してしまうと、登録済みのアカウント情報（メールアドレス）を
                //    第三者に特定される（アカウントの列挙攻撃）リスクが発生します。
                if ($user && password_verify($password, $user['password'])) {
                    
                    // 【不適切な認証・セッション管理対策 (A07:2021)：「セッション固定攻撃」の防止】
                    // 認証（ログイン）が成立した「直後」に、必ずセッションIDを新しく作り直します。
                    // 攻撃者が事前に用意したセッションIDを使ってログイン状態を奪取するのを防ぐ必須対策です。
                    session_regenerate_id(true);

                    // セッションに安全にユーザーIDを格納（サーバー側管理）
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['last_activity'] = time(); // セッションタイムアウト用

                    header("Location: index.php?action=mypage");
                    exit;
                } else {
                    // あえて曖昧な表現にし、メールかパスワードどちらが間違っているか推測させないようにします。
                    $errors[] = 'メールアドレスまたはパスワードが正しくありません。';
                }
            }
        }

        require_once __DIR__ . '/../views/user/login.php';
    }

    /**
     * ログアウトアクション
     */
    public function logout() {
        // ログアウトもCSRFを考慮する（GETでのログアウトは意図しないログアウト攻撃を招くため推奨されませんが、今回は簡易的にセッションをクリアします）
        $_SESSION = array(); // 全てのセッション変数をクリア

        // セッションクッキーも安全に削除
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 最後にサーバー側のセッションデータを完全に破壊します。
        session_destroy();

        // ログイン画面へ安全にリダイレクト
        header("Location: index.php?action=login");
        exit;
    }
}