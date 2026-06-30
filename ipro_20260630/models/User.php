<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * ユーザー名からユーザー情報を取得
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    /**
     * IDからユーザー情報を取得
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * 新規生徒ユーザー登録
     */
    public function register($username, $display_name, $password, $role = 'student') {
        // パスワードのハッシュ化 (セキュリティ要件)
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // ユニークな招待トークンを作成
        $invite_token = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare("INSERT INTO users (username, display_name, password_hash, role, invite_token) VALUES (:username, :display_name, :password_hash, :role, :invite_token)");
        $result = $stmt->execute([
            ':username' => $username,
            ':display_name' => $display_name,
            ':password_hash' => $password_hash,
            ':role' => $role,
            ':invite_token' => $invite_token
        ]);

        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * トークンからユーザー情報を取得
     */
    public function findByInviteToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE invite_token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    /**
     * ユーザー名または表示名による生徒の検索
     * 【修正】PDOエラー回避のためプレースホルダー名を :query1 と :query2 に分割
     */
    public function searchStudents($query, $current_user_id) {
        $stmt = $this->db->prepare("
            SELECT id, username, display_name, role 
            FROM users 
            WHERE (username LIKE :query1 OR display_name LIKE :query2) 
              AND id != :current_user_id 
              AND role = 'student' 
            LIMIT 20
        ");
        
        $stmt->execute([
            ':query1' => '%' . $query . '%',
            ':query2' => '%' . $query . '%',
            ':current_user_id' => $current_user_id
        ]);
        return $stmt->fetchAll();
    }

    /**
     * 友達（属性）一覧の取得
     */
    public function getFriendsWithAttributes($user_id) {
        $stmt = $this->db->prepare("
            SELECT f.id as friendship_id, u.id as friend_id, u.username, u.display_name, f.attribute_tag, f.created_at
            FROM friendships f
            JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = :user_id
            ORDER BY u.display_name ASC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * 相手との友達（属性）設定を取得
     */
    public function getFriendship($user_id, $friend_id) {
        $stmt = $this->db->prepare("SELECT * FROM friendships WHERE user_id = :user_id AND friend_id = :friend_id LIMIT 1");
        $stmt->execute([':user_id' => $user_id, ':friend_id' => $friend_id]);
        return $stmt->fetch();
    }

    /**
     * 友達・属性登録または更新
     */
    public function saveOrUpdateFriendship($user_id, $friend_id, $tag = '友達') {
        if ($user_id === $friend_id) return false;

        $existing = $this->getFriendship($user_id, $friend_id);
        if ($existing) {
            // 更新
            $stmt = $this->db->prepare("UPDATE friendships SET attribute_tag = :tag WHERE user_id = :user_id AND friend_id = :friend_id");
            return $stmt->execute([
                ':tag' => $tag,
                ':user_id' => $user_id,
                ':friend_id' => $friend_id
            ]);
        } else {
            // 新規
            $stmt = $this->db->prepare("INSERT INTO friendships (user_id, friend_id, attribute_tag) VALUES (:user_id, :friend_id, :tag)");
            return $stmt->execute([
                ':user_id' => $user_id,
                ':friend_id' => $friend_id,
                ':tag' => $tag
            ]);
        }
    }

    /**
     * 友達削除
     */
    public function removeFriendship($user_id, $friend_id) {
        $stmt = $this->db->prepare("DELETE FROM friendships WHERE user_id = :user_id AND friend_id = :friend_id");
        return $stmt->execute([
            ':user_id' => $user_id,
            ':friend_id' => $friend_id
        ]);
    }

    /**
     * 自分の友達属性リストを一意に取得（投稿範囲選択UI用、例: 「友達」「グループA」など）
     */
    public function getDistinctAttributeTags($user_id) {
        $stmt = $this->db->prepare("SELECT DISTINCT attribute_tag FROM friendships WHERE user_id = :user_id ORDER BY attribute_tag ASC");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 特定の属性を持つ友達のID一覧を取得
     */
    public function getFriendIdsByAttribute($user_id, $attribute_tag) {
        $stmt = $this->db->prepare("SELECT friend_id FROM friendships WHERE user_id = :user_id AND attribute_tag = :tag");
        $stmt->execute([':user_id' => $user_id, ':tag' => $attribute_tag]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 全ての生徒一覧の取得（先生用、管理画面等）
     */
    public function getAllStudents() {
        $stmt = $this->db->prepare("SELECT id, username, display_name, created_at FROM users WHERE role = 'student' ORDER BY display_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}