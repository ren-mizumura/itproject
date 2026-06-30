<?php
require_once __DIR__ . '/../config/database.php';

class Post {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * 新規投稿の作成（添付ファイル、参考URL、エディタ風コード、公開範囲に対応）
     */
    public function createPost($user_id, $curriculum_id, $task_id, $content, $code_content = null, $file_path = null, $file_name = null, $reference_url = null, $allowed_friend_ids = []) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO posts (user_id, curriculum_id, task_id, content, code_content, file_path, file_name, reference_url)
                VALUES (:user_id, :curriculum_id, :task_id, :content, :code_content, :file_path, :file_name, :reference_url)
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':curriculum_id' => $curriculum_id,
                ':task_id' => $task_id,
                ':content' => $content,
                ':code_content' => $code_content,
                ':file_path' => $file_path,
                ':file_name' => $file_name,
                ':reference_url' => $reference_url
            ]);
            $post_id = $this->db->lastInsertId();

            // 公開範囲（特定の属性を持つ友達に限定）を設定した場合
            if (!empty($allowed_friend_ids)) {
                $stmt_vis = $this->db->prepare("INSERT INTO post_visibilities (post_id, allowed_user_id) VALUES (:post_id, :allowed_user_id)");
                foreach ($allowed_friend_ids as $friend_id) {
                    $stmt_vis->execute([
                        ':post_id' => $post_id,
                        ':allowed_user_id' => $friend_id
                    ]);

                    // 通知: 公開範囲に自分が含まれる投稿があった時
                    $this->createNotification(
                        $friend_id,
                        $user_id,
                        'custom_visibility',
                        $post_id,
                        "友達があなたを公開範囲に指定して学習進捗（または質問）を投稿しました。"
                    );
                }
            }

            // 先生全員への通知（生徒が新規投稿した時）
            $stmt_teachers = $this->db->prepare("SELECT id FROM users WHERE role = 'teacher'");
            $stmt_teachers->execute();
            $teachers = $stmt_teachers->fetchAll(PDO::FETCH_COLUMN);

            $stmt_sender = $this->db->prepare("SELECT display_name FROM users WHERE id = :user_id");
            $stmt_sender->execute([':user_id' => $user_id]);
            $sender_name = $stmt_sender->fetchColumn();

            foreach ($teachers as $teacher_id) {
                $this->createNotification(
                    $teacher_id,
                    $user_id,
                    'new_post',
                    $post_id,
                    "生徒「{$sender_name}」さんが学習掲示板に新規投稿を行いました。"
                );
            }

            $this->db->commit();
            return $post_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Post Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 投稿の取得（単一）
     */
    public function getPost($post_id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.username, u.display_name, u.role as user_role, c.name as curriculum_name, ct.task_name
            FROM posts p
            JOIN users u ON p.user_id = u.id
            JOIN curriculums c ON p.curriculum_id = c.id
            JOIN curriculum_tasks ct ON p.task_id = ct.id
            WHERE p.id = :post_id
            LIMIT 1
        ");
        $stmt->execute([':post_id' => $post_id]);
        return $stmt->fetch();
    }

    /**
     * 投稿が特定のユーザーから閲覧可能か判定
     */
    public function isVisibleToUser($post_id, $viewer_id, $viewer_role) {
        $post = $this->getPost($post_id);
        if (!$post) return false;

        // 先生または投稿者本人であれば無条件に閲覧可能
        if ($viewer_role === 'teacher' || $post['user_id'] == $viewer_id) {
            return true;
        }

        // 投稿の公開制限が設定されているか確認
        $stmt_vis_check = $this->db->prepare("SELECT COUNT(*) FROM post_visibilities WHERE post_id = :post_id");
        $stmt_vis_check->execute([':post_id' => $post_id]);
        $has_restriction = $stmt_vis_check->fetchColumn() > 0;

        if (!$has_restriction) {
            // 全体公開
            return true;
        }

        // 制限対象に含まれているか確認
        $stmt_allowed = $this->db->prepare("SELECT COUNT(*) FROM post_visibilities WHERE post_id = :post_id AND allowed_user_id = :viewer_id");
        $stmt_allowed->execute([
            ':post_id' => $post_id,
            ':viewer_id' => $viewer_id
        ]);
        return $stmt_allowed->fetchColumn() > 0;
    }

    /**
     * 投稿一覧（フィード）の取得（公開範囲制御を適用）
     */
    public function getFeed($user_id, $role) {
        if ($role === 'teacher') {
            // 先生は全ての投稿を制限なく閲覧可能
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.display_name, u.role as user_role, c.name as curriculum_name, ct.task_name,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) as reply_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                JOIN curriculums c ON p.curriculum_id = c.id
                JOIN curriculum_tasks ct ON p.task_id = ct.id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
        } else {
            // 生徒は「全体公開のもの」「自分が作成したもの」「自分が公開範囲に含まれている他者のもの」のみ閲覧可能
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.display_name, u.role as user_role, c.name as curriculum_name, ct.task_name,
                       (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.id) as reply_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                JOIN curriculums c ON p.curriculum_id = c.id
                JOIN curriculum_tasks ct ON p.task_id = ct.id
                WHERE p.user_id = :user_id 
                   OR NOT EXISTS (SELECT 1 FROM post_visibilities pv WHERE pv.post_id = p.id)
                   OR p.id IN (SELECT pv2.post_id FROM post_visibilities pv2 WHERE pv2.allowed_user_id = :user_id2)
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':user_id2' => $user_id
            ]);
        }
        return $stmt->fetchAll();
    }

    /**
     * 1時間以内の編集・削除制限判定
     */
    public function canEditOrDelete($post_created_at, $post_user_id, $current_user_id, $current_user_role) {
        if ($current_user_role === 'teacher') {
            return true; // 先生は無制限
        }
        if ($post_user_id != $current_user_id) {
            return false; // 他人の投稿は不可
        }
        // 作成時刻から1時間経過しているか
        $created_time = strtotime($post_created_at);
        $current_time = time();
        return ($current_time - $created_time) <= 3600; // 3600秒 = 1時間
    }

    /**
     * 投稿の編集
     */
    public function updatePost($post_id, $content, $code_content = null, $reference_url = null) {
        $stmt = $this->db->prepare("
            UPDATE posts 
            SET content = :content, code_content = :code_content, reference_url = :reference_url
            WHERE id = :post_id
        ");
        return $stmt->execute([
            ':content' => $content,
            ':code_content' => $code_content,
            ':reference_url' => $reference_url,
            ':post_id' => $post_id
        ]);
    }

    /**
     * 投稿の削除
     */
    public function deletePost($post_id) {
        $stmt = $this->db->prepare("DELETE FROM posts WHERE id = :post_id");
        return $stmt->execute([':post_id' => $post_id]);
    }

    /**
     * 先生リプライ（指導）の作成
     */
    public function createReply($post_id, $user_id, $content) {
        $stmt = $this->db->prepare("INSERT INTO replies (post_id, user_id, content) VALUES (:post_id, :user_id, :content)");
        $result = $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id,
            ':content' => $content
        ]);

        if ($result) {
            $reply_id = $this->db->lastInsertId();
            
            // 投稿元の生徒情報を取得
            $post = $this->getPost($post_id);
            if ($post && $post['user_id'] != $user_id) {
                // 返信者が先生で、投稿者が別の生徒である場合、生徒に通知
                $stmt_sender = $this->db->prepare("SELECT display_name FROM users WHERE id = :user_id");
                $stmt_sender->execute([':user_id' => $user_id]);
                $sender_name = $stmt_sender->fetchColumn();

                $this->createNotification(
                    $post['user_id'],
                    $user_id,
                    'reply',
                    $post_id,
                    "{$sender_name}さんがあなたの投稿「{$post['curriculum_name']} ＞ {$post['task_name']}」に指導リプライを行いました。"
                );
            }
            return $reply_id;
        }
        return false;
    }

    /**
     * リプライ一覧の取得
     */
    public function getRepliesForPost($post_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.username, u.display_name, u.role as user_role
            FROM replies r
            JOIN users u ON r.user_id = u.id
            WHERE r.post_id = :post_id
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([':post_id' => $post_id]);
        return $stmt->fetchAll();
    }

    /**
     * 単一のリプライを取得
     */
    public function getReply($reply_id) {
        $stmt = $this->db->prepare("SELECT * FROM replies WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $reply_id]);
        return $stmt->fetch();
    }

    /**
     * リプライの編集
     */
    public function updateReply($reply_id, $content) {
        $stmt = $this->db->prepare("UPDATE replies SET content = :content WHERE id = :reply_id");
        return $stmt->execute([
            ':content' => $content,
            ':reply_id' => $reply_id
        ]);
    }

    /**
     * リプライの削除
     */
    public function deleteReply($reply_id) {
        $stmt = $this->db->prepare("DELETE FROM replies WHERE id = :reply_id");
        return $stmt->execute([':reply_id' => $reply_id]);
    }

    /**
     * システム内通知の作成
     */
    public function createNotification($user_id, $sender_id, $type, $target_id, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, sender_id, type, target_id, message)
            VALUES (:user_id, :sender_id, :type, :target_id, :message)
        ");
        return $stmt->execute([
            ':user_id' => $user_id,
            ':sender_id' => $sender_id,
            ':type' => $type,
            ':target_id' => $target_id,
            ':message' => $message
        ]);
    }

    /**
     * 特定ユーザーの未読通知一覧を取得
     */
    public function getUnreadNotifications($user_id) {
        $stmt = $this->db->prepare("
            SELECT n.*, u.display_name as sender_name
            FROM notifications n
            JOIN users u ON n.sender_id = u.id
            WHERE n.user_id = :user_id AND n.is_read = 0
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * 通知をすべて既読に更新
     */
    public function markAllAsRead($user_id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $user_id]);
    }
}