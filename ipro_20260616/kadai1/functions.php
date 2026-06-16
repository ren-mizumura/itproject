<?php
/**
 * functions.php
 * 定数の定義、DB接続、登録・認証・状態チェックなどの共通関数を管理するファイルです。
 * 開発における「DRY (Don't Repeat Yourself: 同じ記述を繰り返さない)」原則に基づき、
 * 各ページで必要となる処理をこのファイルに集約させて再利用します。
 */

// 1. データベース接続情報の定数化 (define)
// 定数化することで、不意に書き換えられるリスクを防ぎ、接続情報が一目で管理できるようになります。
define('DB_HOST', 'localhost');                  // データベースサーバーのアドレス
define('DB_NAME', 'it_20260616_db');             // 使用するデータベース名
define('DB_USER', 'root');                       // 接続ユーザー名（環境に合わせて変更してください）
define('DB_PASS', '');                           // 接続パスワード（環境に合わせて変更してください）
define('DB_CHAR', 'utf8mb4');                    // 文字コード（日本語環境で絵文字等も扱えるutf8mb4を推奨）

// 2. セッションの自動開始
// セッションがまだ開始されていない（PHP_SESSION_NONE）場合にのみ、session_start() を実行します。
// これにより、多重で session_start() が呼ばれる警告エラーを防ぎつつ、安全にセッションを開始できます。
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ① db_connect()
 * PDOを用いてDBに安全に接続し、接続オブジェクトを返す関数
 *
 * @return PDO データベース接続オブジェクト
 */
function db_connect() {
    // DSN（Data Source Name）の組み立て
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
    
    try {
        // PDOのインスタンスを作成（接続実行）
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // エラー発生時に「例外(Exception)」を投げる設定（セキュリティ上重要）
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // 取得データを連想配列形式に固定
            PDO::ATTR_EMULATE_PREPARES => false,               // SQLインジェクションを防ぐため、PDO側でのエミュレートをOFFにしてDB側のリアルプレースホルダを使う
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // 例外発生時（接続エラー時など）に、データベースの内部情報（パスワード等）が画面に露出しないよう、
        // 開発者向けにエラーを出力し、処理を安全に停止（exit/die）させます。
        die("データベース接続エラーが発生しました。時間を置いて再度お試しください。");
    }
}

/**
 * ② signup_user($email, $password)
 * パスワードをハッシュ化し、安全にDBにユーザーを登録する関数
 *
 * @param string $email
 * @param string $password
 * @return bool 登録成功でtrue、失敗（重複等）でfalse
 */
function signup_user($email, $password) {
    $pdo = db_connect();

    // 1. パスワードの安全なハッシュ化
    // 元の文字列（平文パスワード）のまま保存することは絶対にNGです。
    // password_hash() は、強力な一方通行暗号（bcryptなど）と自動ソルトを用いてハッシュ値を生成します。
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 2. プレースホルダを用いたSQLの準備 (SQLインジェクション対策)
    // 直接変数を含めたSQL文（"INSERT INTO... '$email'"）は脆弱性の原因になります。
    // `:email` や `:password` のようなプレースホルダを使い、後から安全にバインドします。
    $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        // プレースホルダに実際のデータを安全に割り当てて実行します
        $result = $stmt->execute([
            ':email'    => $email,
            ':password' => $hashed_password
        ]);
        
        return $result;
    } catch (PDOException $e) {
        // メールアドレスの重複（UNIQUE制約エラーなど）が発生した場合に例外をキャッチして false を返します
        return false;
    }
}

/**
 * ③ login_user($email, $password)
 * 入力された情報から認証を行い、成功ならセッションに情報を保持しセッション固定攻撃対策を行う関数
 *
 * @param string $email
 * @param string $password
 * @return bool 認証成功でtrue、失敗でfalse
 */
function login_user($email, $password) {
    $pdo = db_connect();

    // メールアドレスをキーに対象のユーザーデータを1件取得
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // ユーザーが存在し、かつパスワードの検証が成功したかチェック
    // password_verify() は、平文パスワードとハッシュ化されたパスワードが一致するかを安全に検証します。
    if ($user && password_verify($password, $user['password'])) {
        
        // 【重要】セッションIDの再生成（セッション固定攻撃への対策）
        // ログインが成立した瞬間にセッションIDを新しく発行し、既存のIDを破棄(true)します。
        // これにより、悪意ある第三者が事前に用意したセッションIDを奪取してログイン状態になりすます攻撃を防ぎます。
        session_regenerate_id(true);

        // ログイン状態を維持するために、セッション変数にユーザー情報を保存
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        
        return true;
    }
    
    // ユーザーが存在しない、またはパスワードが間違っている場合
    return false;
}

/**
 * ④ is_logged_in()
 * 現在のアクセスがログイン状態（セッションにユーザーIDが存在する）かを判定する関数
 *
 * @return bool ログイン済みならtrue、未ログインならfalse
 */
function is_logged_in() {
    // セッション変数の中に 'user_id' がセットされており、かつ空でないかを判定します
    return !empty($_SESSION['user_id']);
}

/**
 * ⑤ require_login()
 * 未ログインの場合にログイン画面へ強制リダイレクトし、処理を強制終了する関数
 */
function require_login() {
    // ログイン状態でなければ、即座に login.php へリダイレクト
    if (!is_logged_in()) {
        header('Location: login.php');
        
        // 【超重要】リダイレクト後の exit;
        // header('Location: ...') はブラウザに対して「別のページに移動して」と指示を出すだけで、
        // PHP自体のプログラムの実行は後ろに続いてしまいます。
        // exit; を記述しないと、未ログイン状態でもそれ以降のHTMLや機密処理が全て実行され、脆弱性（強制遷移のバイパス）に繋がります。
        exit;
    }
}

/**
 * ⑥ h($str)
 * XSS（クロスサイトスクリプティング）対策用エスケープ関数
 * HTML内でユーザーの入力を出力する際は、ブラウザが意図しないHTMLタグやJavaScriptを実行してしまわないよう、
 * 必ずこの関数を通して安全な文字列に変換します。
 *
 * @param string $str 対象の文字列
 * @return string エスケープされた文字列
 */
function h($str) {
    // htmlspecialchars(対象文字列, シングルクォート等も変換対象にする設定, 使用する文字コード)
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}