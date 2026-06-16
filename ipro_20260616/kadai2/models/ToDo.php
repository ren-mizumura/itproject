<?php
/**
 * models/Todo.php
 * tasks テーブルとのデータ操作（取得・追加・状態反転・削除）を担当するモデルクラスです。
 * すべての処理において「ログイン中のユーザーのタスクのみ」に制限し、他人のデータ操作をシャットアウトするよう設計します。
 */

class Todo {
    private $db;

    /**
     * コンストラクタ
     * @param PDO $db
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 指定ユーザーのタスク一覧を取得（最新順）
     * * @param int $user_id
     * @return array タスクリスト（連想配列の配列）
     */
    public function getAllByUserId($user_id) {
        // 不正なデータの混入・他人のデータの取得を絶対に防ぐため、WHERE条件に user_id を明記します
        $sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    /**
     * 新規タスクの追加
     * * @param int $user_id
     * @param string $title
     * @param string $body
     * @return bool
     */
    public function create($user_id, $title, $body) {
        // プレースホルダを用いてSQLインジェクションを防御。complete（完了フラグ）はデフォルト「0: 未完了」で登録
        $sql = "INSERT INTO tasks (user_id, title, body, complete) VALUES (:user_id, :title, :body, 0)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':title'   => $title,
            ':body'    => $body
        ]);
    }

    /**
     * タスクの完了状態（complete）のトグル切り替え（0 ⇄ 1）
     * * @param int $task_id
     * @param int $user_id 不正防止用の操作者ユーザーID
     * @return bool
     */
    public function toggle($task_id, $user_id) {
        // 1. まず操作しようとしているタスクが、本当にログイン中ユーザーが所有しているものかを検証
        $sqlSelect = "SELECT complete FROM tasks WHERE id = :id AND user_id = :user_id";
        $stmtSelect = $this->db->prepare($sqlSelect);
        $stmtSelect->execute([
            ':id'      => $task_id,
            ':user_id' => $user_id
        ]);
        $task = $stmtSelect->fetch();

        if (!$task) {
            return false; // タスクが存在しない、または所有者が異なる場合は処理を拒否
        }

        // 2. 現在の完了状態を反転
        $new_status = ($task['complete'] == 0) ? 1 : 0;

        // 3. 状態を更新（UPDATE）
        $sqlUpdate = "UPDATE tasks SET complete = :complete WHERE id = :id AND user_id = :user_id";
        $stmtUpdate = $this->db->prepare($sqlUpdate);
        return $stmtUpdate->execute([
            ':complete' => $new_status,
            ':id'       => $task_id,
            ':user_id'  => $user_id
        ]);
    }

    /**
     * タスクの削除
     * * @param int $task_id
     * @param int $user_id 不正防止用の操作者ユーザーID
     * @return bool
     */
    public function delete($task_id, $user_id) {
        // 【セキュリティ】WHEREに id だけでなく user_id も含めることで、
        // URL等で他人のタスクIDを勝手に指定して削除する攻撃（IDOR）を完全に遮断します。
        $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'      => $task_id,
            ':user_id' => $user_id
        ]);
    }
}