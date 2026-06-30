<?php
class User {
    private $db;

    public function __construct($db) {
        $db->exec("SET NAMES utf8mb4");
        $this->db = $db;
    }

    // ユーザー作成 (サインアップ)
    public function create($id, $name, $password, $role = 'student') {
        $sql = "INSERT INTO users (id, name, password_hash, role) VALUES (:id, :name, :password_hash, :role)";
        $stmt = $this->db->prepare($sql);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':password_hash' => $hashed_password,
            ':role' => $role
        ]);
    }

    // ユーザーIDの存在確認
    public function exists($id) {
        $sql = "SELECT COUNT(*) FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    // IDによるユーザー取得
    public function getById($id) {
        $sql = "SELECT id, name, role FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ログイン認証
    public function authenticate($id, $password) {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    // 全ての生徒ユーザーのリスト取得
    public function getAllStudents() {
        $sql = "SELECT id, name FROM users WHERE role = 'student' ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------------
    // つながり (友達・属性タグ) の処理
    // -------------------------------------------------------------

    // 友達一覧の取得
    public function getFriends($user_id) {
        $sql = "SELECT f.to_user_id as id, u.name, f.tag 
                FROM friendships f
                JOIN users u ON f.to_user_id = u.id
                WHERE f.from_user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    // 友達関係の登録 (相互に初期値 '友達' で登録)
    public function addFriendship($from, $to, $tag = '友達') {
        // すでに存在するか確認
        $sql = "SELECT COUNT(*) FROM friendships WHERE from_user_id = :from AND to_user_id = :to";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':from' => $from, ':to' => $to]);
        if ($stmt->fetchColumn() > 0) {
            return true; // 登録済み
        }

        // 相互登録
        $sqlInsert = "INSERT INTO friendships (from_user_id, to_user_id, tag) VALUES (:from, :to, :tag)";
        $stmtInsert = $this->db->prepare($sqlInsert);
        $stmtInsert->execute([':from' => $from, ':to' => $to, ':tag' => $tag]);

        // 相手側からも登録
        $sqlReverse = "INSERT IGNORE INTO friendships (from_user_id, to_user_id, tag) VALUES (:from, :to, :tag)";
        $stmtReverse = $this->db->prepare($sqlReverse);
        $stmtReverse->execute([':from' => $to, ':to' => $from, ':tag' => '友達']);

        return true;
    }

    // 友達属性タグの更新
    public function updateFriendTag($from, $to, $new_tag) {
        $sql = "UPDATE friendships SET tag = :tag WHERE from_user_id = :from AND to_user_id = :to";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':tag' => $new_tag, ':from' => $from, ':to' => $to]);
    }

    // つながり削除
    public function removeFriendship($from, $to) {
        $sql = "DELETE FROM friendships WHERE (from_user_id = :from AND to_user_id = :to) OR (from_user_id = :to AND to_user_id = :from)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':from' => $from, ':to' => $to]);
    }

    // 自分が他ユーザーに設定している全属性タグの一覧（重複排除）
    public function getMyUniqueTags($user_id) {
        $sql = "SELECT DISTINCT tag FROM friendships WHERE from_user_id = :user_id ORDER BY tag ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}