<?php
/**
 * 投稿コントローラー (PostController)
 * * 画像のバリデーション（ファイルサイズ、拡張子、MIMEタイプ等）を厳格に行い、
 * セキュアな画像アップロード（postImages/{user_id}/）を実行します。
 */

require_once __DIR__ . '/../models/Post.php';

class PostController {
    private $postModel;

    public function __construct() {
        $this->postModel = new Post();
    }

    /**
     * 1. 投稿一覧の表示
     */
    public function index() {
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $posts = $this->postModel->getAllActive($current_user_id);
        require_once __DIR__ . '/../views/post/index.php';
    }

    /**
     * 2. 新規投稿の作成 (画像アップロード対応)
     */
    public function create() {
        // 未ログイン時はログイン画面に強制遷移（アクセス制御）
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = '投稿するにはログインが必要です。';
            header("Location: index.php?action=login");
            exit;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF検証
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die("不正なリクエストです。(CSRF検証失敗)");
            }

            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $body = isset($_POST['body']) ? trim($_POST['body']) : '';
            $image_path = null;

            // バリデーション
            if (empty($title)) {
                $errors[] = 'タイトルを入力してください。';
            } elseif (strlen($title) > 255) {
                $errors[] = 'タイトルは255文字以内で入力してください。';
            }

            if (empty($body)) {
                $errors[] = '本文を入力してください。';
            }

            // 【画像アップロードバリデーション＆処理】
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_error = $_FILES['image']['error'];
                
                if ($upload_error !== UPLOAD_ERR_OK) {
                    $errors[] = $this->getUploadErrorMessage($upload_error);
                } else {
                    // 画像の処理とパスの取得
                    $processed_filename = $this->handleImageUpload($_SESSION['user_id'], $_FILES['image'], $errors);
                    if ($processed_filename) {
                        $image_path = $processed_filename;
                    }
                }
            }

            // エラーがない場合はデータベースに保存
            if (empty($errors)) {
                $user_id = $_SESSION['user_id'];
                if ($this->postModel->create($user_id, $title, $body, $image_path)) {
                    $_SESSION['flash_message'] = '投稿が完了しました。';
                    header("Location: index.php?action=post_list");
                    exit;
                } else {
                    $errors[] = '投稿の作成中にエラーが発生しました。';
                }
            }
        }

        require_once __DIR__ . '/../views/post/create.php';
    }

    /**
     * 3. 投稿の編集 (画像差し替え対応)
     */
    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'ログインが必要です。';
            header("Location: index.php?action=login");
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $post = $this->postModel->findById($id);

        if (!$post) {
            $_SESSION['flash_message'] = '指定された投稿が見つかりません。';
            header("Location: index.php?action=post_list");
            exit;
        }

        // 所有者認証（認可制御）
        if ((int)$post['user_id'] !== (int)$_SESSION['user_id']) {
            $_SESSION['flash_message'] = '他人の投稿を編集することはできません。';
            header("Location: index.php?action=post_list");
            exit;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF検証
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                die("不正なリクエストです。(CSRF検証失敗)");
            }

            $title = isset($_POST['title']) ? trim($_POST['title']) : '';
            $body = isset($_POST['body']) ? trim($_POST['body']) : '';
            $image_path = null; // nullの場合は既存の画像をそのまま維持する

            if (empty($title)) {
                $errors[] = 'タイトルを入力してください。';
            } elseif (strlen($title) > 255) {
                $errors[] = 'タイトルは255文字以内で入力してください。';
            }

            if (empty($body)) {
                $errors[] = '本文を入力してください。';
            }

            // 差し替え画像が指定された場合の処理
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload_error = $_FILES['image']['error'];
                if ($upload_error !== UPLOAD_ERR_OK) {
                    $errors[] = $this->getUploadErrorMessage($upload_error);
                } else {
                    $processed_filename = $this->handleImageUpload($_SESSION['user_id'], $_FILES['image'], $errors);
                    if ($processed_filename) {
                        $image_path = $processed_filename;
                    }
                }
            }

            if (empty($errors)) {
                if ($this->postModel->update($id, $title, $body, $image_path)) {
                    $_SESSION['flash_message'] = '投稿を更新しました。';
                    header("Location: index.php?action=post_list");
                    exit;
                } else {
                    $errors[] = '更新処理中にエラーが発生しました。';
                }
            }
        }

        require_once __DIR__ . '/../views/post/edit.php';
    }

    /**
     * 4. 投稿の論理削除
     */
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'ログインが必要です。';
            header("Location: index.php?action=login");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die("無効なリクエストメソッドです。");
        }

        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die("不正なリクエストです。(CSRF検証失敗)");
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $post = $this->postModel->findById($id);

        if (!$post) {
            $_SESSION['flash_message'] = '指定された投稿が見つかりません。';
            header("Location: index.php?action=post_list");
            exit;
        }

        if ((int)$post['user_id'] !== (int)$_SESSION['user_id']) {
            die("削除権限がありません。");
        }

        if ($this->postModel->delete($id)) {
            $_SESSION['flash_message'] = '投稿を削除しました。';
        } else {
            $_SESSION['flash_message'] = '削除処理に失敗しました。';
        }

        header("Location: index.php?action=post_list");
        exit;
    }

    /**
     * 5. いいね状態のトグル
     */
    public function toggleLike() {
        header('Content-Type: application/json; charset=UTF-8');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'いいねするにはログインが必要です。']);
            exit;
        }

        $postData = json_decode(file_get_contents('php://input'), true);
        $csrf_token = isset($postData['csrf_token']) ? $postData['csrf_token'] : '';

        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'CSRF検証に失敗しました。']);
            exit;
        }

        $post_id = isset($postData['post_id']) ? (int)$postData['post_id'] : 0;
        $user_id = $_SESSION['user_id'];

        if ($post_id <= 0) {
            echo json_encode(['success' => false, 'message' => '無効な投稿IDです。']);
            exit;
        }

        $is_liked = $this->postModel->isLiked($user_id, $post_id);
        
        if ($is_liked) {
            $this->postModel->removeLike($user_id, $post_id);
            $liked = false;
        } else {
            $this->postModel->addLike($user_id, $post_id);
            $liked = true;
        }

        $like_count = $this->postModel->countLikes($post_id);

        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'like_count' => $like_count
        ]);
        exit;
    }

    /* =========================================================================
     * ヘルパーメソッド（セキュアなアップロード処理）
     * ========================================================================= */

    /**
     * セキュアに画像を検証・保存する
     * * @param int $user_id
     * @param array $file_info $_FILES['image']
     * @param array &$errors エラー配列参照
     * @return string|false 成功時は一意なファイル名、失敗時はfalse
     */
    private function handleImageUpload($user_id, $file_info, &$errors) {
        // 1. 【サイズ制限：5MB (5 * 1024 * 1024 バイト)】
        $max_size = 5 * 1024 * 1024;
        if ($file_info['size'] > $max_size) {
            $errors[] = '画像ファイルのサイズは5MB以下にしてください。';
            return false;
        }

        // 2. 【拡張子・MIMEタイプの多重厳格検証 (バイパス攻撃防止)】
        // クライアント側から送られる $_FILES['image']['type'] は改ざん可能なため信用せず、
        // PHP標準の「finfo_file」でファイルの実態（バイナリ構造）からMIMEタイプを厳密に判別します。
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file_info['tmp_name']);

        $allowed_mime_types = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png'  => ['png'],
            'image/gif'  => ['gif']
        ];

        if (!array_key_exists($mime_type, $allowed_mime_types)) {
            $errors[] = '許可されていないファイル形式です。JPG, PNG, GIF画像のみアップロード可能です。';
            return false;
        }

        // 拡張子のチェック
        $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $extension = strtolower($extension); // 小文字に統一

        if (!in_array($extension, $allowed_mime_types[$mime_type])) {
            $errors[] = 'ファイルの拡張子がファイル内容と一致しません。';
            return false;
        }

        // 3. 【保存先ディレクトリの自動生成：postImages/{user_id}/】
        $target_dir = __DIR__ . '/../postImages/' . $user_id . '/';
        if (!is_dir($target_dir)) {
            // umaskの影響を避けるため確実にパーミッションを設定（0755: 所有者は書き込み可、他者は読み書き実行のみ）
            if (!mkdir($target_dir, 0755, true)) {
                $errors[] = '画像保存ディレクトリの作成に失敗しました。';
                return false;
            }
        }

        // 4. 【ファイル名の衝突・上書き、及び日本語ファイル名インジェクションの防御】
        // ユーザーが送信したファイル名はそのまま使用せず、ランダムで一意な名前（バイナリベースのハッシュなど）に強制変換します。
        // これにより、ファイル名によるパスインジェクションやシェル起動（RCE）を確実に回避します。
        $new_filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target_path = $target_dir . $new_filename;

        // 一時フォルダから正規の格納ディレクトリにファイルを安全に移動
        if (move_uploaded_file($file_info['tmp_name'], $target_path)) {
            return $new_filename; // 保存成功したファイル名をDB保存用に返す
        } else {
            $errors[] = '画像のアップロード中に致命的なエラーが発生しました。';
            return false;
        }
    }

    /**
     * アップロードエラー定数を人間が読みやすい文字列に変換
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'アップロードされたファイルが大きすぎます（5MBまで）。';
            case UPLOAD_ERR_PARTIAL:
                return 'ファイルの一部のみしかアップロードされませんでした。';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'サーバーのテンポラリフォルダが見つかりません。';
            case UPLOAD_ERR_CANT_WRITE:
                return 'ディスクへの書き込みに失敗しました。';
            default:
                return 'ファイルのアップロードに失敗しました。';
        }
    }
}