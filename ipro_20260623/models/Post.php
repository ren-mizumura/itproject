<?php
/**
 * 投稿・いいねモデル（Post Model：画像対応）
 * * SQLインジェクションを防ぐため、すべてのクエリでプリペアドステートメントを使用します。
 * 新規カラム `image_path` の保存・更新に対応しています。
 */

require_once __DIR__ . '/../config/database.php';

class Post {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * 全ての有効な投稿を取得する（論理削除されたものは除く）
     * 各投稿のいいね数、現在ログインしているユーザーがいいねしているかのフラグ、
     * および画像ファイル名（image_path）も含めて取得します。
     * * @param int|null $current_user_id
     * @return array
     */
    public function getAllActive($current_user_id = null) {
        // delete_flag = 0 のもののみ取得します（論理削除対策）。
        $sql = "SELECT 
                    p.*, 
                    u.email AS user_email,
                    COUNT(l.post_id) AS like_count,
                    MAX(CASE WHEN l.user_id = :current_user_id THEN 1 ELSE 0 END) AS is_liked
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN likes l ON p.id = l.post_id
                WHERE p.delete_flag = 0
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':current_user_id', $current_user_id, $current_user_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * 特定の投稿を取得する（編集・削除時の所有権チェックに利用）
     * * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT * FROM posts WHERE id = :id AND delete_flag = 0 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * 新規投稿を作成する（画像対応）
     * * @param int $user_id
     * @param string $title
     * @param string $body
     * @param string|null $image_path 保存された一意な画像ファイル名
     * @return bool
     */
    public function create($user_id, $title, $body, $image_path = null) {
        $sql = "INSERT INTO posts (user_id, title, body, image_path) VALUES (:user_id, :title, :body, :image_path)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':body', $body, PDO::PARAM_STR);
        $stmt->bindValue(':image_path', $image_path, $image_path !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        return $stmt->execute();
    }

    /**
     * 投稿を編集する（画像対応）
     * * @param int $id
     * @param string $title
     * @param string $body
     * @param string|null $image_path nullの場合は画像を更新しない（元の画像を維持する）
     * @return bool
     */
    public function update($id, $title, $body, $image_path = null) {
        if ($image_path !== null) {
            // 新しい画像がある場合は画像パスも更新
            $sql = "UPDATE posts SET title = :title, body = :body, image_path = :image_path, updated_at = NOW() WHERE id = :id";
        } else {
            // 画像の変更がない場合はそのまま維持
            $sql = "UPDATE posts SET title = :title, body = :body, updated_at = NOW() WHERE id = :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':body', $body, PDO::PARAM_STR);
        if ($image_path !== null) {
            $stmt->bindValue(':image_path', $image_path, PDO::PARAM_STR);
        }
        return $stmt->execute();
    }

    /**
     * 投稿を論理削除する (delete_flag = 1)
     * データの復元や管理者の確認を考慮し、サーバー上の画像ファイル自体は削除しません。
     * * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "UPDATE posts SET delete_flag = 1, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /* =========================================================================
     * いいね機能 (Likes) 関連の処理
     * ========================================================================= */

    public function isLiked($user_id, $post_id) {
        $sql = "SELECT 1 FROM likes WHERE user_id = :user_id AND post_id = :post_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetch();
    }

    public function addLike($user_id, $post_id) {
        $sql = "INSERT IGNORE INTO likes (user_id, post_id) VALUES (:user_id, :post_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function removeLike($user_id, $post_id) {
        $sql = "DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function countLikes($post_id) {
        $sql = "SELECT COUNT(*) AS count FROM likes WHERE post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
}