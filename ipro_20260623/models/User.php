<?php
/**
 * ユーザーモデル（User Model）
 * * データベースへの直接のやり取りを担うクラス（M:Model）です。
 * 直接SQLに引数を展開せず、プレースホルダを使用してSQLインジェクション（A03:2021）を防止します。
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * メールアドレスからユーザー情報を取得する
     * * @param string $email
     * @return array|false ユーザーが存在すればそのレコード、いなければfalse
     */
    public function findByEmail($email) {
        // 【SQLインジェクション対策】
        // プレースホルダ「:email」を用いてSQLを用意します。SQL文の中に直接変数を結合（$email）してはいけません。
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        
        // プレースホルダに値を厳密にバインド（型や安全な文字列処理が施されてクエリに代入される）
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    /**
     * 新規ユーザーを登録する
     * * @param string $email
     * @param string $password
     * @return bool 登録成功したかどうか
     */
    public function create($email, $password) {
        // 【不適切な認証・セッション管理 (A07:2021-Identification and Authentication Failures)】
        // パスワードは「絶対に生テキストで保存してはいけません」。
        // 万が一DBの中身が流出した場合、全てのユーザーのアカウントが不正アクセスされます。
        // PHPの「password_hash()」を使用し、強力な一方向ハッシュ関数（デフォルトで暗号化強度の高いbcryptなど）でハッシュ化します。
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 【SQLインジェクション対策】
        $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
}