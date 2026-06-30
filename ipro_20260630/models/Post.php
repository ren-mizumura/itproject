<?php
class Post {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 掲示板への新規投稿作成
    public function create($author_id, $language, $task, $body, $code, $file_name, $file_path, $url, $visibility, $target_tag) {
        $sql = "INSERT INTO posts (author_id, language, task, body, code, file_name, file_path, url, visibility, target_tag) 
                VALUES (:author_id, :language, :task, :body, :code, :file_name, :file_path, :url, :visibility, :target_tag)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':author_id' => $author_id,
            ':language' => $language,
            ':task' => $task,
            ':body' => $body,
            ':code' => !empty($code) ? $code : null,
            ':file_name' => !empty($file_name) ? $file_name : null,
            ':file_path' => !empty($file_path) ? $file_path : null,
            ':url' => !empty($url) ? $url : null,
            ':visibility' => $visibility,
            ':target_tag' => ($visibility === 'restricted') ? $target_tag : null
        ]);
    }

    // 投稿情報の個別取得
    public function getById($id) {
        $sql = "SELECT * FROM posts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // 投稿の更新 (編集)
    public function update($id, $body, $code, $url) {
        $sql = "UPDATE posts SET body = :body, code = :code, url = :url WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':body' => $body,
            ':code' => !empty($code) ? $code : null,
            ':url' => !empty($url) ? $url : null
        ]);
    }

    // 投稿の削除
    public function delete($id) {
        // アップロードファイルの物理削除対応
        $post = $this->getById($id);
        if ($post && !empty($post['file_path']) && file_exists($post['file_path'])) {
            unlink($post['file_path']);
        }

        $sql = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // タイムライン投稿の取得（閲覧権限チェック、絞り込み、およびリプライ一括取得）
    public function getTimeline($user_id, $role, $lang_filter = 'all', $author_filter = 'all') {
        $params = [];
        
        // 基本クエリ
        $sql = "SELECT p.*, u.name as author_name 
                FROM posts p
                JOIN users u ON p.author_id = u.id";
        
        // 絞り込み条件
        $conditions = [];
        
        if ($lang_filter !== 'all') {
            $conditions[] = "p.language = :lang_filter";
            $params[':lang_filter'] = $lang_filter;
        }
        if ($author_filter !== 'all') {
            $conditions[] = "p.author_id = :author_filter";
            $params[':author_filter'] = $author_filter;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $all_posts = $stmt->fetchAll();

        $filtered_posts = [];

        foreach ($all_posts as $post) {
            // 公開範囲（プライバシー）の判定
            if ($post['visibility'] === 'restricted') {
                // 1. 投稿者自身、または先生（管理者）は無条件で閲覧可
                if ($post['author_id'] === $user_id || $role === 'teacher') {
                    $filtered_posts[] = $post;
                    continue;
                }

                // 2. 投稿者が、閲覧中のユーザー(user_id)に対して設定している属性タグが合致するか
                $sqlCheck = "SELECT COUNT(*) FROM friendships 
                             WHERE from_user_id = :author_id 
                             AND to_user_id = :user_id 
                             AND tag = :target_tag";
                $stmtCheck = $this->db->prepare($sqlCheck);
                $stmtCheck->execute([
                    ':author_id' => $post['author_id'],
                    ':user_id' => $user_id,
                    ':target_tag' => $post['target_tag']
                ]);
                
                if ($stmtCheck->fetchColumn() > 0) {
                    $filtered_posts[] = $post;
                }
            } else {
                // 全体公開
                $filtered_posts[] = $post;
            }
        }

        // 各投稿に紐づくリプライ一覧を動的アタッチ
        foreach ($filtered_posts as &$post) {
            $post['replies'] = $this->getRepliesByPostId($post['id']);
        }

        return $filtered_posts;
    }

    // -------------------------------------------------------------
    // リプライ (返信・指導) の処理
    // -------------------------------------------------------------
    public function getRepliesByPostId($post_id) {
        $sql = "SELECT r.*, u.name as author_name, u.role as author_role 
                FROM replies r
                JOIN users u ON r.author_id = u.id
                WHERE r.post_id = :post_id
                ORDER BY r.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':post_id' => $post_id]);
        return $stmt->fetchAll();
    }

    public function createReply($post_id, $author_id, $body) {
        $sql = "INSERT INTO replies (post_id, author_id, body) VALUES (:post_id, :author_id, :body)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':post_id' => $post_id,
            ':author_id' => $author_id,
            ':body' => $body
        ]);
    }

    public function getReplyById($id) {
        $sql = "SELECT * FROM replies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function updateReply($id, $body) {
        $sql = "UPDATE replies SET body = :body WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id, ':body' => $body]);
    }

    public function deleteReply($id) {
        $sql = "DELETE FROM replies WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // -------------------------------------------------------------
    // 通知センターの処理
    // -------------------------------------------------------------
    public function addNotification($for_user_id, $text, $type) {
        $sql = "INSERT INTO notifications (for_user_id, text, type) VALUES (:for_user_id, :text, :type)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':for_user_id' => $for_user_id,
            ':text' => $text,
            ':type' => $type
        ]);
    }

    public function getNotifications($user_id) {
        $sql = "SELECT * FROM notifications WHERE for_user_id = :user_id ORDER BY created_at DESC LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    public function markAllAsRead($user_id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE for_user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $user_id]);
    }
}