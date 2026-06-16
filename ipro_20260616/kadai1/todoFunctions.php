<?php
/**
 * todoFunctions.php
 * タスク（TODO）に関するデータベース操作（CRUD処理）を関数として共通化するファイルです。
 * 表示（HTML）からデータ操作ロジックを分離することで、コードの保守性と安全性を高めます。
 */

/**
 * 1. get_tasks($pdo, $user_id)
 * 指定されたユーザーIDに紐づくタスク一覧を最新順で取得する関数
 *
 * @param PDO $pdo データベース接続オブジェクト
 * @param int $user_id ログイン中のユーザーID
 * @return array タスクの連想配列リスト
 */
function get_tasks($pdo, $user_id) {
    // 他人のタスクが混ざらないよう、必ず `WHERE user_id = :user_id` で絞り込みを行います（マルチテナントセキュリティ）
    // 作成日時（created_at）の降順（DESC）で並べることで、新しいタスクが上に来るようにします
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
    
    // プリペアドステートメントを作成し、SQLインジェクションを完全に防止します
    $stmt = $pdo->prepare($sql);
    
    // プレースホルダに実際のユーザーIDを安全にバインドして実行します
    $stmt->execute([':user_id' => $user_id]);
    
    // 該当するすべてのレコードを連想配列として取得して返します
    return $stmt->fetchAll();
}

/**
 * 2. add_task($pdo, $user_id, $title, $body)
 * 新しいタスクをデータベースに安全に登録する関数
 *
 * @param PDO $pdo データベース接続オブジェクト
 * @param int $user_id ログイン中のユーザーID
 * @param string $title タスクのタイトル
 * @param string $body タスクの詳細説明
 * @return bool 登録に成功した場合はtrue、失敗した場合はfalse
 */
function add_task($pdo, $user_id, $title, $body) {
    // プレースホルダを用いたインサート文
    $sql = "INSERT INTO tasks (user_id, title, body, complete) VALUES (:user_id, :title, :body, 0)";
    
    $stmt = $pdo->prepare($sql);
    
    // executeの中に配列でパラメータを渡すことで、安全に値を割り当てて実行します
    return $stmt->execute([
        ':user_id' => $user_id,
        ':title'   => $title,
        ':body'    => $body
    ]);
}

/**
 * 3. toggle_task($pdo, $task_id, $user_id)
 * タスクの完了（1）と未完了（0）の状態を反転（トグル）させる関数
 *
 * @param PDO $pdo データベース接続オブジェクト
 * @param int $task_id 切り替える対象のタスクID
 * @param int $user_id ログイン中のユーザーID
 * @return bool 更新に成功した場合はtrue、失敗した場合はfalse
 */
function toggle_task($pdo, $task_id, $user_id) {
    // 【セキュリティ対策】
    // `WHERE id = :id AND user_id = :user_id` とすることで、
    // ログイン中のユーザー本人が所有しているタスクのみを更新対象に制限します。
    // これにより、他人のタスクIDを悪意を持ってリクエストされた場合でも、更新を完全にシャットアウトできます。
    
    // 1. 対象タスクの現在の完了状態（complete）を取得する
    $select_sql = "SELECT complete FROM tasks WHERE id = :id AND user_id = :user_id";
    $select_stmt = $pdo->prepare($select_sql);
    $select_stmt->execute([
        ':id'      => $task_id,
        ':user_id' => $user_id
    ]);
    $task = $select_stmt->fetch();
    
    // 対象タスクが存在しない（他人のタスク、または削除済み）場合は何もしない
    if (!$task) {
        return false;
    }
    
    // 現在の状態が 0（未完了）なら 1（完了）に、1 なら 0 に反転させます
    $new_status = ($task['complete'] == 0) ? 1 : 0;
    
    // 2. 状態をデータベースに反映（UPDATE）する
    $update_sql = "UPDATE tasks SET complete = :complete WHERE id = :id AND user_id = :user_id";
    $update_stmt = $pdo->prepare($update_sql);
    
    return $update_stmt->execute([
        ':complete' => $new_status,
        ':id'       => $task_id,
        ':user_id'  => $user_id
    ]);
}

/**
 * 4. delete_task($pdo, $task_id, $user_id)
 * 指定されたタスクをデータベースから物理削除する関数
 *
 * @param PDO $pdo データベース接続オブジェクト
 * @param int $task_id 削除対象のタスクID
 * @param int $user_id ログイン中のユーザーID
 * @return bool 削除に成功した場合はtrue、失敗した場合はfalse
 */
function delete_task($pdo, $task_id, $user_id) {
    // 【セキュリティ対策】
    // 削除処理でも同様に `user_id` を条件に含めることで、
    // URLやフォームから他人のタスクIDが送られてきても削除できないように防御（なりすまし・改ざん防御）します。
    $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
    
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute([
        ':id'      => $task_id,
        ':user_id' => $user_id
    ]);
}