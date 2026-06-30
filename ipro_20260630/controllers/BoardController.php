<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Curriculum.php';
require_once __DIR__ . '/../models/Post.php';

class BoardController {
    private $userModel;
    private $curriculumModel;
    private $postModel;

    public function __construct() {
        $this->userModel = new User();
        $this->curriculumModel = new Curriculum();
        $this->postModel = new Post();
    }

    /**
     * メインダッシュボード画面
     */
    public function dashboard() {
        $this->checkAuth();
        
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        // 各モデルから必要データをロード
        $userInfo = $this->userModel->findById($user_id);
        $curriculumsWithTasks = $this->curriculumModel->getAllCurriculumsWithTasks();
        $studentCurriculums = $this->curriculumModel->getStudentCurriculums($user_id);
        $feed = $this->postModel->getFeed($user_id, $role);
        $friends = $this->userModel->getFriendsWithAttributes($user_id);
        $distinctTags = $this->userModel->getDistinctAttributeTags($user_id);
        $unreadNotifications = $this->postModel->getUnreadNotifications($user_id);

        // 先生用の生徒全体進捗確認用データ
        $allStudents = [];
        if ($role === 'teacher') {
            $allStudents = $this->userModel->getAllStudents();
            foreach ($allStudents as &$student) {
                $student['curriculums'] = $this->curriculumModel->getStudentCurriculums($student['id']);
            }
        }

        // GitHub風草データの取得 (生徒自身、または先生が見る対象)
        $selected_student_id = $user_id;
        if ($role === 'teacher' && isset($_GET['view_student_id'])) {
            $selected_student_id = intval($_GET['view_student_id']);
        }
        $grassCalendar = $this->curriculumModel->getContributionGrassData($selected_student_id);
        $viewingStudentName = ($selected_student_id === $user_id) ? "あなた" : ($this->userModel->findById($selected_student_id)['display_name'] ?? '生徒');

        // ポスト一覧に各投稿の編集削除判定フラグと、紐づくリプライ一覧を結合
        foreach ($feed as &$post) {
            $post['can_edit_delete'] = $this->postModel->canEditOrDelete($post['created_at'], $post['user_id'], $user_id, $role);
            $post['replies'] = $this->postModel->getRepliesForPost($post['id']);
            foreach ($post['replies'] as &$rep) {
                // リプライの編集削除判定
                $rep['can_edit_delete'] = ($role === 'teacher' || $rep['user_id'] == $user_id);
            }
        }

        // 検索パラメータ
        $searchQuery = trim($_GET['search_query'] ?? '');
        $searchResults = [];
        if (!empty($searchQuery)) {
            $searchResults = $this->userModel->searchStudents($searchQuery, $user_id);
        }

        // 通知全既読化処理 (ダッシュボード表示時)
        if (!empty($unreadNotifications)) {
            $this->postModel->markAllAsRead($user_id);
        }

        include __DIR__ . '/../views/dashboard.php';
    }

    /**
     * 新規進捗投稿
     */
    public function createPost() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "dashboard");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
        $task_id = intval($_POST['task_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $code_content = trim($_POST['code_content'] ?? '');
        $reference_url = trim($_POST['reference_url'] ?? '');
        $visibility_type = $_POST['visibility_type'] ?? 'public'; // public or attribute_tag

        $allowed_friend_ids = [];
        if ($visibility_type !== 'public' && !empty($visibility_type)) {
            // 指定された属性タグの友達のみを取得
            $allowed_friend_ids = $this->userModel->getFriendIdsByAttribute($user_id, $visibility_type);
        }

        // ファイルアップロード処理 (5MB制限)
        $file_path = null;
        $file_name = null;

        if (isset($_FILES['attached_file']) && $_FILES['attached_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attached_file'];
            $max_size = 5 * 1024 * 1024; // 5MB
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'pdf'];
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['size'] > $max_size) {
                $_SESSION['post_error'] = "ファイルサイズは5MB以下にしてください。";
                header("Location: " . BASE_URL . "dashboard");
                exit;
            } elseif (!in_array($ext, $allowed_extensions)) {
                $_SESSION['post_error'] = "許可されていないファイル形式です。(.jpg, .jpeg, .png, .gif, .txt, .pdf のみ)";
                header("Location: " . BASE_URL . "dashboard");
                exit;
            } else {
                // XAMPP環境で保存用の「uploads」ディレクトリを作成
                $upload_dir = __DIR__ . '/../public/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $unique_name = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $file_path = 'public/uploads/' . $unique_name;
                    $file_name = $file['name'];
                }
            }
        }

        if ($curriculum_id > 0 && $task_id > 0 && !empty($content)) {
            $this->postModel->createPost($user_id, $curriculum_id, $task_id, $content, $code_content, $file_path, $file_name, $reference_url, $allowed_friend_ids);
        }

        header("Location: " . BASE_URL . "dashboard");
        exit;
    }

    /**
     * 投稿の編集
     */
    public function editPost() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $post_id = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $code_content = trim($_POST['code_content'] ?? '');
        $reference_url = trim($_POST['reference_url'] ?? '');

        $post = $this->postModel->getPost($post_id);
        if ($post && $this->postModel->canEditOrDelete($post['created_at'], $post['user_id'], $_SESSION['user_id'], $_SESSION['role'])) {
            $this->postModel->updatePost($post_id, $content, $code_content, $reference_url);
        }
        header("Location: " . BASE_URL . "dashboard");
        exit;
    }

    /**
     * 投稿の削除
     */
    public function deletePost() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = $this->postModel->getPost($post_id);
        if ($post && $this->postModel->canEditOrDelete($post['created_at'], $post['user_id'], $_SESSION['user_id'], $_SESSION['role'])) {
            // ファイルの物理削除
            if (!empty($post['file_path'])) {
                $physical_path = __DIR__ . '/../' . $post['file_path'];
                if (file_exists($physical_path)) {
                    unlink($physical_path);
                }
            }
            $this->postModel->deletePost($post_id);
        }
        header("Location: " . BASE_URL . "dashboard");
        exit;
    }

    /**
     * リプライ（先生指導）の作成・編集・削除
     */
    public function handleReply() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $post_id = intval($_POST['post_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if ($post_id > 0 && !empty($content)) {
                $this->postModel->createReply($post_id, $_SESSION['user_id'], $content);
            }
        } elseif ($action === 'edit') {
            $reply_id = intval($_POST['reply_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $reply = $this->postModel->getReply($reply_id);
            if ($reply && ($_SESSION['role'] === 'teacher' || $reply['user_id'] == $_SESSION['user_id'])) {
                $this->postModel->updateReply($reply_id, $content);
            }
        } elseif ($action === 'delete') {
            $reply_id = intval($_POST['reply_id'] ?? 0);
            $reply = $this->postModel->getReply($reply_id);
            if ($reply && ($_SESSION['role'] === 'teacher' || $reply['user_id'] == $_SESSION['user_id'])) {
                $this->postModel->deleteReply($reply_id);
            }
        }

        header("Location: " . BASE_URL . "dashboard");
        exit;
    }

    /**
     * 生徒による学習カリキュラム選択追加
     */
    public function selectCurriculum() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
            if ($curriculum_id > 0 && $_SESSION['role'] === 'student') {
                $this->curriculumModel->selectStudentCurriculum($_SESSION['user_id'], $curriculum_id);
            }
        }
        header("Location: " . BASE_URL . "dashboard?tab=curriculum");
        exit;
    }

    /**
     * 言語マスタの追加（先生のみ。追加時に対象生徒へ通知）
     */
    public function masterAddCurriculum() {
        $this->checkAuth();
        if ($_SESSION['role'] !== 'teacher') exit;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            if (!empty($name)) {
                $this->curriculumModel->addCurriculum($name);
            }
        }
        header("Location: " . BASE_URL . "dashboard?tab=teacher_config");
        exit;
    }

    /**
     * 言語タスクの追加（先生のみ。追加時、該当言語を学習登録している全生徒へ通知）
     */
    public function masterAddTask() {
        $this->checkAuth();
        if ($_SESSION['role'] !== 'teacher') exit;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $curriculum_id = intval($_POST['curriculum_id'] ?? 0);
            $task_name = trim($_POST['task_name'] ?? '');

            if ($curriculum_id > 0 && !empty($task_name)) {
                $task_id = $this->curriculumModel->addTask($curriculum_id, $task_name);
                if ($task_id) {
                    // 全生徒の進捗テーブル初期化
                    $this->curriculumModel->initProgressForNewTask($curriculum_id, $task_id);

                    // 登録している生徒全員に通知を送信
                    $stmt_students = Database::getConnection()->prepare("SELECT student_id FROM student_curriculums WHERE curriculum_id = :curriculum_id");
                    $stmt_students->execute([':curriculum_id' => $curriculum_id]);
                    $students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

                    $curr = $this->curriculumModel->getCurriculum($curriculum_id);

                    foreach ($students as $student_id) {
                        $this->postModel->createNotification(
                            $student_id,
                            $_SESSION['user_id'],
                            'new_task',
                            $curriculum_id,
                            "あなたが学習中の言語「{$curr['name']}」に、新しいカリキュラムタスク『{$task_name}』が先生により追加されました！"
                        );
                    }
                }
            }
        }
        header("Location: " . BASE_URL . "dashboard?tab=teacher_config");
        exit;
    }

    /**
     * 習熟度（パーセント評価）の入力・更新（先生のみ。更新時、対象生徒へ通知）
     */
    public function updateStudentProficiency() {
        $this->checkAuth();
        if ($_SESSION['role'] !== 'teacher') exit;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $student_id = intval($_POST['student_id'] ?? 0);
            $task_id = intval($_POST['task_id'] ?? 0);
            $percent = intval($_POST['proficiency'] ?? 0);

            if ($student_id > 0 && $task_id > 0) {
                $this->curriculumModel->updateProficiency($student_id, $task_id, $percent);

                // タスク名と言語名を取得して生徒に通知
                $db = Database::getConnection();
                $stmt = $db->prepare("
                    SELECT ct.task_name, c.name as curr_name
                    FROM curriculum_tasks ct
                    JOIN curriculums c ON ct.curriculum_id = c.id
                    WHERE ct.id = :task_id
                ");
                $stmt->execute([':task_id' => $task_id]);
                $task_info = $stmt->fetch();

                if ($task_info) {
                    $this->postModel->createNotification(
                        $student_id,
                        $_SESSION['user_id'],
                        'proficiency',
                        $task_id,
                        "先生があなたのカリキュラム「{$task_info['curr_name']} ＞ {$task_info['task_name']}」の習熟度を [ {$percent}% ] に更新しました！"
                    );
                }
            }
        }
        // 表示していた生徒のコンテキストに戻る
        header("Location: " . BASE_URL . "dashboard?tab=teacher_students&view_student_id=" . $student_id);
        exit;
    }

    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "login");
            exit;
        }
    }
}