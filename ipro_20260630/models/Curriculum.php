<?php
require_once __DIR__ . '/../config/database.php';

class Curriculum {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * 言語マスタの新規追加（先生のみ）
     */
    public function addCurriculum($name) {
        $stmt = $this->db->prepare("INSERT INTO curriculums (name) VALUES (:name)");
        try {
            $stmt->execute([':name' => $name]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false; // 重複時など
        }
    }

    /**
     * タスクの追加（先生のみ）
     */
    public function addTask($curriculum_id, $task_name) {
        $stmt = $this->db->prepare("INSERT INTO curriculum_tasks (curriculum_id, task_name) VALUES (:curriculum_id, :task_name)");
        try {
            $stmt->execute([
                ':curriculum_id' => $curriculum_id,
                ':task_name' => $task_name
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * 全言語と紐づくタスク一覧を取得
     */
    public function getAllCurriculumsWithTasks() {
        $stmt = $this->db->prepare("SELECT id, name FROM curriculums ORDER BY id ASC");
        $stmt->execute();
        $curriculums = $stmt->fetchAll();

        foreach ($curriculums as &$curr) {
            $stmt_tasks = $this->db->prepare("SELECT id, task_name FROM curriculum_tasks WHERE curriculum_id = :id ORDER BY id ASC");
            $stmt_tasks->execute([':id' => $curr['id']]);
            $curr['tasks'] = $stmt_tasks->fetchAll();
        }
        return $curriculums;
    }

    /**
     * 特定の言語とタスク一覧の取得
     */
    public function getCurriculum($id) {
        $stmt = $this->db->prepare("SELECT * FROM curriculums WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $curr = $stmt->fetch();
        if ($curr) {
            $stmt_tasks = $this->db->prepare("SELECT id, task_name FROM curriculum_tasks WHERE curriculum_id = :id ORDER BY id ASC");
            $stmt_tasks->execute([':id' => $id]);
            $curr['tasks'] = $stmt_tasks->fetchAll();
        }
        return $curr;
    }

    /**
     * 生徒が学習言語を選択・追加する
     */
    public function selectStudentCurriculum($student_id, $curriculum_id) {
        $stmt = $this->db->prepare("INSERT IGNORE INTO student_curriculums (student_id, curriculum_id) VALUES (:student_id, :curriculum_id)");
        $stmt->execute([
            ':student_id' => $student_id,
            ':curriculum_id' => $curriculum_id
        ]);

        // 追加されたカリキュラム配下の既存タスクについて、進捗度0%で初期化挿入する
        $stmt_tasks = $this->db->prepare("SELECT id FROM curriculum_tasks WHERE curriculum_id = :curriculum_id");
        $stmt_tasks->execute([':curriculum_id' => $curriculum_id]);
        $tasks = $stmt_tasks->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tasks as $task_id) {
            $stmt_init = $this->db->prepare("INSERT IGNORE INTO student_progress (student_id, task_id, proficiency) VALUES (:student_id, :task_id, 0)");
            $stmt_init->execute([
                ':student_id' => $student_id,
                ':task_id' => $task_id
            ]);
        }
        return true;
    }

    /**
     * 新しいタスクが追加された時に登録生徒全員の進捗レコードを0%で初期化
     */
    public function initProgressForNewTask($curriculum_id, $task_id) {
        $stmt_students = $this->db->prepare("SELECT student_id FROM student_curriculums WHERE curriculum_id = :curriculum_id");
        $stmt_students->execute([':curriculum_id' => $curriculum_id]);
        $students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

        foreach ($students as $student_id) {
            $stmt_init = $this->db->prepare("INSERT IGNORE INTO student_progress (student_id, task_id, proficiency) VALUES (:student_id, :task_id, 0)");
            $stmt_init->execute([
                ':student_id' => $student_id,
                ':task_id' => $task_id
            ]);
        }
    }

    /**
     * 生徒が学習中の言語一覧（およびタスク、進捗状況）を取得
     */
    public function getStudentCurriculums($student_id) {
        $stmt = $this->db->prepare("
            SELECT c.id, c.name
            FROM student_curriculums sc
            JOIN curriculums c ON sc.curriculum_id = c.id
            WHERE sc.student_id = :student_id
            ORDER BY c.id ASC
        ");
        $stmt->execute([':student_id' => $student_id]);
        $curriculums = $stmt->fetchAll();

        foreach ($curriculums as &$curr) {
            $stmt_progress = $this->db->prepare("
                SELECT ct.id as task_id, ct.task_name, COALESCE(sp.proficiency, 0) as proficiency
                FROM curriculum_tasks ct
                LEFT JOIN student_progress sp ON ct.id = sp.task_id AND sp.student_id = :student_id
                WHERE ct.curriculum_id = :curr_id
                ORDER BY ct.id ASC
            ");
            $stmt_progress->execute([
                ':student_id' => $student_id,
                ':curr_id' => $curr['id']
            ]);
            $curr['tasks'] = $stmt_progress->fetchAll();

            // 言語全体の平均習熟度を算出
            $total = 0;
            $count = count($curr['tasks']);
            foreach ($curr['tasks'] as $task) {
                $total += $task['proficiency'];
            }
            $curr['average_proficiency'] = $count > 0 ? round($total / $count) : 0;
        }

        return $curriculums;
    }

    /**
     * 習熟度の更新（先生のみ）
     */
    public function updateProficiency($student_id, $task_id, $percent) {
        $percent = max(0, min(100, intval($percent))); // 0~100範囲を担保

        $stmt = $this->db->prepare("
            INSERT INTO student_progress (student_id, task_id, proficiency)
            VALUES (:student_id, :task_id, :percent)
            ON DUPLICATE KEY UPDATE proficiency = :percent_update
        ");
        return $stmt->execute([
            ':student_id' => $student_id,
            ':task_id' => $task_id,
            ':percent' => $percent,
            ':percent_update' => $percent
        ]);
    }

    /**
     * GitHubスタイル草カレンダーデータの算出 (過去90日間の生徒の日別投稿数)
     */
    public function getContributionGrassData($student_id) {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) as post_date, COUNT(*) as post_count
            FROM posts
            WHERE user_id = :student_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            GROUP BY DATE(created_at)
        ");
        $stmt->execute([':student_id' => $student_id]);
        $db_data = $stmt->fetchAll();

        $contributions = [];
        foreach ($db_data as $row) {
            $contributions[$row['post_date']] = intval($row['post_count']);
        }

        // 過去90日間のカレンダー用日付配列を構築
        $calendar = [];
        for ($i = 90; $i >= 0; $i--) {
            $date_str = date('Y-m-d', strtotime("-$i days"));
            $count = isset($contributions[$date_str]) ? $contributions[$date_str] : 0;
            // 投稿数によるコントリビューションレベルの定義
            $level = 0;
            if ($count >= 5) $level = 4;
            elseif ($count >= 3) $level = 3;
            elseif ($count >= 2) $level = 2;
            elseif ($count >= 1) $level = 1;

            $calendar[] = [
                'date' => $date_str,
                'count' => $count,
                'level' => $level
            ];
        }
        return $calendar;
    }
}