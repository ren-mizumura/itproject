<?php
class Curriculum {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // 定義されている言語一覧を取得
    public function getLanguages() {
        $sql = "SELECT DISTINCT language FROM curriculums ORDER BY language ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 特定の言語の詳細タスク一覧を取得
    public function getTasksByLanguage($language) {
        $sql = "SELECT task FROM curriculums WHERE language = :language ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':language' => $language]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 言語をカリキュラムマスターに新規追加
    public function addLanguage($language) {
        // 詳細のない空のダミー、もしくはプレースホルダタスクで言語の枠を作成
        $sql = "INSERT IGNORE INTO curriculums (language, task) VALUES (:language, 'イントロダクション')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':language' => $language]);
    }

    // 詳細タスクをカリキュラムマスターに新規追加
    public function addTask($language, $task) {
        // もしすでにデフォルトの「イントロダクション」のみがあり、それを上書きまたは共存
        $sql = "INSERT INTO curriculums (language, task) VALUES (:language, :task)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':language' => $language, ':task' => $task]);

        // 新規タスク追加時に、その言語をすでに学習登録している全生徒の進捗に 0% を自動初期登録する
        $sqlStudents = "SELECT student_id FROM student_languages WHERE language = :language";
        $stmtStudents = $this->db->prepare($sqlStudents);
        $stmtStudents->execute([':language' => $language]);
        $students = $stmtStudents->fetchAll(PDO::FETCH_COLUMN);

        if ($students) {
            $sqlProgress = "INSERT IGNORE INTO progress (student_id, language, task, percent) VALUES (:student_id, :language, :task, 0)";
            $stmtProgress = $this->db->prepare($sqlProgress);
            foreach ($students as $student_id) {
                $stmtProgress->execute([
                    ':student_id' => $student_id,
                    ':language' => $language,
                    ':task' => $task
                ]);
            }
        }
        return true;
    }

    // 言語マスターの削除（紐づく生徒の進捗、タスクを全て削除）
    public function deleteLanguage($language) {
        $sql = "DELETE FROM curriculums WHERE language = :language";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':language' => $language]);
    }

    // タスクマスターの個別削除
    public function deleteTask($language, $task) {
        $sql = "DELETE FROM curriculums WHERE language = :language AND task = :task";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':language' => $language, ':task' => $task]);
    }

    // -------------------------------------------------------------
    // 生徒ごとの選択プロフィール ＆ 進捗率
    // -------------------------------------------------------------

    // 生徒が現在選択している学習言語の取得
    public function getStudentLanguages($student_id) {
        $sql = "SELECT language FROM student_languages WHERE student_id = :student_id ORDER BY language ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $student_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 生徒の受講言語プロフィール更新
    public function saveStudentLanguages($student_id, $languages) {
        $this->db->beginTransaction();
        try {
            // 一旦全件削除
            $sqlDelete = "DELETE FROM student_languages WHERE student_id = :student_id";
            $stmtDelete = $this->db->prepare($sqlDelete);
            $stmtDelete->execute([':student_id' => $student_id]);

            // 新たにインサート
            if (!empty($languages)) {
                $sqlInsert = "INSERT INTO student_languages (student_id, language) VALUES (:student_id, :language)";
                $stmtInsert = $this->db->prepare($sqlInsert);

                $sqlInitProgress = "INSERT IGNORE INTO progress (student_id, language, task, percent) VALUES (:student_id, :language, :task, 0)";
                $stmtInitProg = $this->db->prepare($sqlInitProgress);

                foreach ($languages as $lang) {
                    $stmtInsert->execute([':student_id' => $student_id, ':language' => $lang]);

                    // 新規追加した言語のタスクの進捗レコードを0%で自動初期化
                    $tasks = $this->getTasksByLanguage($lang);
                    foreach ($tasks as $task) {
                        $stmtInitProg->execute([
                            ':student_id' => $student_id,
                            ':language' => $lang,
                            ':task' => $task
                        ]);
                    }
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // 生徒の特定の言語・タスク毎の進捗率を取得
    public function getStudentProgress($student_id) {
        $sql = "SELECT language, task, percent FROM progress WHERE student_id = :student_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $student_id]);
        
        $progress = [];
        while ($row = $stmt->fetch()) {
            $progress[$row['language']][$row['task']] = $row['percent'];
        }
        return $progress;
    }

    // 先生による進捗習熟度(%)の更新
    public function updateProgressPercent($student_id, $language, $task, $percent) {
        $sql = "INSERT INTO progress (student_id, language, task, percent) 
                VALUES (:student_id, :language, :task, :percent) 
                ON DUPLICATE KEY UPDATE percent = :percent";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':student_id' => $student_id,
            ':language' => $language,
            ':task' => $task,
            ':percent' => $percent
        ]);
    }

    // GitHub「草」カレンダー用のデータを集計（過去70日分の該当生徒の活動実績）
    public function getContributionData($student_id) {
        // 投稿とリプライの総数を日付ごとにグルーピングして取得
        $sql = "
            SELECT DATE(created_at) as act_date, COUNT(*) as act_count FROM (
                SELECT created_at FROM posts WHERE author_id = :user_id1
                UNION ALL
                SELECT created_at FROM replies WHERE author_id = :user_id2
            ) combined
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 70 DAY)
            GROUP BY DATE(created_at)
            ORDER BY act_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id1' => $student_id, ':user_id2' => $student_id]);
        
        $activities = [];
        while ($row = $stmt->fetch()) {
            $activities[$row['act_date']] = (int)$row['act_count'];
        }
        return $activities;
    }
}