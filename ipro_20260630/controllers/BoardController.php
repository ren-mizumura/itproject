<?php
class BoardController {
    private $postModel;
    private $curriculumModel;
    private $userModel;

    public function __construct($db) {
        require_once 'models/Post.php';
        require_once 'models/Curriculum.php';
        require_once 'models/User.php';
        
        $this->postModel = new Post($db);
        $this->curriculumModel = new Curriculum($db);
        $this->userModel = new User($db);
    }

    // 進捗・質問の新規投稿 (ファイルアップロード処理込み)
    public function createPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $author_id = $_SESSION['user_id'];
            $language = $_POST['language'] ?? '';
            $task = $_POST['task'] ?? '';
            $body = trim($_POST['body'] ?? '');
            $code = $_POST['code'] ?? null;
            $url = trim($_POST['url'] ?? null);
            $visibility = $_POST['visibility'] ?? 'all';
            $target_tag = $_POST['target_tag'] ?? null;

            if (empty($language) || empty($task) || empty($body)) {
                $_SESSION['error'] = '対象言語・タスク・本文は入力必須項目です。';
                header('Location: /20260630/');
                exit;
            }

            // ファイルアップロード処理 (5MB制限、拡張子制限)
            $file_name = null;
            $file_path = null;

            if (isset($_FILES['attached_file']) && $_FILES['attached_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attached_file'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if ($file['size'] > $max_size) {
                    $_SESSION['error'] = '添付可能なファイルは最大5MBまでです。';
                    header('Location: /20260630/');
                    exit;
                }

                // 拡張子判定
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'pdf'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_extensions)) {
                    $_SESSION['error'] = '許可されていない拡張子です（JPG, PNG, GIF, TXT, PDFのみ添付可能）。';
                    header('Location: /20260630/');
                    exit;
                }

                // アップロード用ディレクトリ作成
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_file_name = uniqid('file_', true) . '.' . $file_ext;
                $target_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $file_name = $file['name'];
                    $file_path = $target_path;
                }
            }

            // 投稿保存
            $result = $this->postModel->create(
                $author_id, $language, $task, $body, $code, $file_name, $file_path, $url, $visibility, $target_tag
            );

            if ($result) {
                // 先生(管理者)への通知（生徒が新規投稿した際）
                if ($_SESSION['role'] === 'student') {
                    $user_name = $_SESSION['user_name'];
                    $this->postModel->addNotification(
                        'teacher_admin', 
                        "生徒「{$user_name}」が新しい進捗投稿 \"{$language} > {$task}\" を行いました。", 
                        'new_post'
                    );
                }

                // 属性指定公開時の対象者へのシステム内通知
                if ($visibility === 'restricted' && !empty($target_tag)) {
                    $friends = $this->userModel->getFriends($author_id);
                    foreach ($friends as $friend) {
                        if ($friend['tag'] === $target_tag) {
                            $user_name = $_SESSION['user_name'];
                            $this->postModel->addNotification(
                                $friend['id'],
                                "「{$user_name}」さんからあなた限定（属性: {$target_tag}）の進捗投稿が届きました。",
                                'private_post'
                            );
                        }
                    }
                }

                $_SESSION['success'] = '学習進捗をタイムラインに投稿しました！';
            } else {
                $_SESSION['error'] = '投稿処理中にエラーが発生しました。';
            }

            header('Location: /20260630/');
            exit;
        }
    }

    // 投稿の編集 (作成後1時間制限、または先生権限)
    public function updatePost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_id = $_POST['post_id'] ?? '';
            $body = trim($_POST['body'] ?? '');
            $code = $_POST['code'] ?? null;
            $url = trim($_POST['url'] ?? null);

            $post = $this->postModel->getById($post_id);
            if (!$post) {
                $_SESSION['error'] = '投稿が見つかりませんでした。';
                header('Location: /20260630/');
                exit;
            }

            // 編集削除のライフサイクル保護
            $is_author = $post['author_id'] === $_SESSION['user_id'];
            $is_teacher = $_SESSION['role'] === 'teacher';
            $elapsed_seconds = time() - strtotime($post['created_at']);
            $is_within_one_hour = $elapsed_seconds <= 3600;

            if ($is_teacher || ($is_author && $is_within_one_hour)) {
                $this->postModel->update($post_id, $body, $code, $url);
                $_SESSION['success'] = '投稿内容を更新しました。';
            } else {
                $_SESSION['error'] = '投稿後1時間を経過したため、編集できません。';
            }

            header('Location: /20260630/');
            exit;
        }
    }

    // 投稿の削除
    public function deletePost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_id = $_POST['post_id'] ?? '';
            $post = $this->postModel->getById($post_id);

            if (!$post) {
                $_SESSION['error'] = '投稿が見つかりませんでした。';
                header('Location: /20260630/');
                exit;
            }

            $is_author = $post['author_id'] === $_SESSION['user_id'];
            $is_teacher = $_SESSION['role'] === 'teacher';
            $elapsed_seconds = time() - strtotime($post['created_at']);
            $is_within_one_hour = $elapsed_seconds <= 3600;

            if ($is_teacher || ($is_author && $is_within_one_hour)) {
                $this->postModel->delete($post_id);
                $_SESSION['success'] = '学習進捗の投稿を削除しました。';
            } else {
                $_SESSION['error'] = '削除する権限がないか、投稿後1時間を超過しています。';
            }

            header('Location: /20260630/');
            exit;
        }
    }

    // リプライ投稿 (指導・返信)
    public function createReply() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post_id = $_POST['post_id'] ?? '';
            $author_id = $_SESSION['user_id'];
            $body = trim($_POST['body'] ?? '');

            if (empty($body)) {
                $_SESSION['error'] = 'コメント内容を入力してください。';
                header('Location: /20260630/');
                exit;
            }

            $this->postModel->createReply($post_id, $author_id, $body);

            // 通知処理（先生から生徒への指導リプライの場合）
            $post = $this->postModel->getById($post_id);
            if ($_SESSION['role'] === 'teacher' && $post && $post['author_id'] !== $author_id) {
                $this->postModel->addNotification(
                    $post['author_id'],
                    "先生があなたの投稿 \"{$post['language']} > {$post['task']}\" にアドバイスを投稿しました。",
                    'reply'
                );
            }

            $_SESSION['success'] = 'リプライを投稿しました。';
            header('Location: /20260630/');
            exit;
        }
    }

    // リプライの削除
    public function deleteReply() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reply_id = $_POST['reply_id'] ?? '';
            $reply = $this->postModel->getReplyById($reply_id);

            if (!$reply) {
                $_SESSION['error'] = 'コメントが見つかりません。';
                header('Location: /20260630/');
                exit;
            }

            $is_author = $reply['author_id'] === $_SESSION['user_id'];
            $is_teacher = $_SESSION['role'] === 'teacher';
            $elapsed_seconds = time() - strtotime($reply['created_at']);
            $is_within_one_hour = $elapsed_seconds <= 3600;

            if ($is_teacher || ($is_author && $is_within_one_hour)) {
                $this->postModel->deleteReply($reply_id);
                $_SESSION['success'] = 'コメントを削除しました。';
            } else {
                $_SESSION['error'] = '削除する権限がありません。';
            }

            header('Location: /20260630/');
            exit;
        }
    }

    // -------------------------------------------------------------
    // カリキュラム ＆ 習熟度（%）管理（先生権限）
    // -------------------------------------------------------------

    // 言語の追加
    public function addLanguage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'teacher') {
            $language = trim($_POST['language'] ?? '');
            if (!empty($language)) {
                $this->curriculumModel->addLanguage($language);
                $_SESSION['success'] = "新しい学習分野「{$language}」を追加しました。";
            }
            header('Location: /20260630/?tab=curriculum-editor');
            exit;
        }
    }

    // タスクの追加
    public function addTask() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'teacher') {
            $language = $_POST['language'] ?? '';
            $task = trim($_POST['task'] ?? '');

            if (!empty($language) && !empty($task)) {
                $this->curriculumModel->addTask($language, $task);
                $_SESSION['success'] = "「{$language}」に新タスク「{$task}」を追加しました。関連生徒に自動通知が送られました。";
            }
            header('Location: /20260630/?tab=curriculum-editor');
            exit;
        }
    }

    // 習熟度（%）の査定・更新
    public function updateProgress() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'teacher') {
            $student_id = $_POST['student_id'] ?? '';
            $language = $_POST['language'] ?? '';
            $task = $_POST['task'] ?? '';
            $percent = intval($_POST['percent'] ?? 0);

            if ($percent < 0) $percent = 0;
            if ($percent > 100) $percent = 100;

            $this->curriculumModel->updateProgressPercent($student_id, $language, $task, $percent);

            // 更新時に生徒へシステム内通知
            $this->postModel->addNotification(
                $student_id,
                "先生があなたのカリキュラム \"{$language} > {$task}\" の習熟度（現在の評価：{$percent}%）を更新しました。",
                'progress_update'
            );

            $_SESSION['success'] = "{$student_id} さんの習熟度を {$percent}% に更新し通知しました。";
            header('Location: /20260630/?tab=analytics');
            exit;
        }
    }

    // 生徒自身の受講言語プロフィールの更新
    public function updateStudentLanguages() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $student_id = $_SESSION['user_id'];
            $selected_languages = $_POST['languages'] ?? [];

            $this->curriculumModel->saveStudentLanguages($student_id, $selected_languages);
            $_SESSION['success'] = '学習プロフィールを更新しました。カリキュラム初期習熟度(0%)を構成しました。';
            header('Location: /20260630/');
            exit;
        }
    }

    // 通知の全件既読
    public function clearNotifications() {
        $user_id = $_SESSION['user_id'];
        $this->postModel->markAllAsRead($user_id);
        $_SESSION['success'] = '通知をすべて既読にしました。';
        header('Location: /20260630/');
        exit;
    }
}