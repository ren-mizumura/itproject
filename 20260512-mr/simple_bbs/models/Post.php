<?php
/**
 * 投稿データを扱うモデルクラス（いいね・引用対応）
 */
class Post {
    private $conn;
    private $table_name = "posts";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function countAll($keyword = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        if ($keyword !== '') {
            $query .= " WHERE nickname LIKE :keyword OR message LIKE :keyword";
        }
        $stmt = $this->conn->prepare($query);
        if ($keyword !== '') {
            $search = "%{$keyword}%";
            $stmt->bindParam(":keyword", $search);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row['total'];
    }

    /**
     * 一覧取得（引用元の情報もJOINで取得）
     */
    public function getList($offset, $limit, $keyword = '') {
        $query = "SELECT p.*, 
                         parent.nickname as parent_nickname, 
                         parent.message as parent_message,
                         parent.media_path as parent_media_path,
                         parent.media_type as parent_media_type
                  FROM " . $this->table_name . " p 
                  LEFT JOIN " . $this->table_name . " parent ON p.parent_id = parent.id";
        
        if ($keyword !== '') {
            $query .= " WHERE p.nickname LIKE :keyword OR p.message LIKE :keyword";
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :offset, :limit";
        
        $stmt = $this->conn->prepare($query);
        if ($keyword !== '') {
            $search = "%{$keyword}%";
            $stmt->bindParam(":keyword", $search);
        }
        $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * いいねを1増やす
     */
    public function addLike($id) {
        $query = "UPDATE " . $this->table_name . " SET likes_count = likes_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function create($nickname, $message, $media_path = null, $media_type = null, $parent_id = null) {
        $query = "INSERT INTO " . $this->table_name . " (nickname, message, media_path, media_type, parent_id) 
                  VALUES (:nickname, :message, :media_path, :media_type, :parent_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nickname", $nickname);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":media_path", $media_path);
        $stmt->bindParam(":media_type", $media_type);
        $stmt->bindValue(":parent_id", $parent_id ? $parent_id : null, $parent_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}