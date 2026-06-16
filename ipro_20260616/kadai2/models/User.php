<?php
/**
 * models/User.php
 * users テーブルとのデータ操作（登録・取得・認証）を担当するモデルクラスです。
 * オブジェクト指向のクラスとしてカプセル化し、DB接続オブジェクト($db)を内部で保持して再利用します。
 */

class User {
    // データベース接続オブジェクトを保持するプライベートプロパティ
    private $db;

    /**
     * コンストラクタ
     * インスタンス生成時にコントローラーからPDO接続オブジェクトを受け取り、保持します（依存性注入：DI）。
     * * @param PDO $db
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 新規ユーザーの登録（新規登録処理）
     * * @param string $email
     * @param string $password
     * @return bool 登録成功時に true, 失敗時に false
     */
    public function register($email, $password) {
        // パスワードの安全なハッシュ化（ハッシュ値は自動的にソルトが付与され安全に一方向暗号化されます）
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // SQLインジェクションを防ぐため、プレースホルダ (:email, :password) を使用
        $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            // バインドパラメータを渡しつつSQLを実行
            return $stmt->execute([
                ':email'    => $email,
                ':password' => $hashed_password
            ]);
        } catch (PDOException $e) {
            // メールアドレスのUNIQUE制約エラーなどが起きた場合は false を返し、コントローラーでエラー処理させます
            return false;
        }
    }

    /**
     * メールアドレスからユーザー情報を1件取得する
     * * @param string $email
     * @return array|false ユーザーが存在すれば連想配列、なければ false
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        // 該当するレコードを1件取得（連想配列形式）
        return $stmt->fetch();
    }

    /**
     * ユーザーIDからユーザー情報を1件取得する
     * * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * ログイン認証処理
     * * @param string $email
     * @param string $password
     * @return array|false 認証成功時にユーザーデータを配列で返し、失敗時は false を返す
     */
    public function authenticate($email, $password) {
        // 1. まず入力されたメールアドレスでDB内を検索
        $user = $this->findByEmail($email);

        if ($user) {
            // 2. ユーザーが存在した場合、入力された平文パスワードとDBに保管されたハッシュ値を照合
            // password_verify は、同じアルゴリズム・ソルトを使って入力値を検証するセキュアな関数です
            if (password_verify($password, $user['password'])) {
                return $user; // 認証成功、ユーザーデータを返す
            }
        }
        
        return false; // 認証失敗
    }
}