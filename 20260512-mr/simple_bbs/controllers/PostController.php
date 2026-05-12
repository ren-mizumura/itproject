<?php
require_once 'config/database.php';
require_once 'models/Post.php';

class PostController {
    private $db;
    private $postModel;
    private $uploadDir = 'uploads/';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->postModel = new Post($this->db);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function index() {
        $keyword = $_GET['search'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;

        $offset = ($page - 1) * $limit;

        $totalPosts = $this->postModel->countAll($keyword);
        $totalPages = ceil($totalPosts / $limit);
        $posts = $this->postModel->getList($offset, $limit, $keyword);

        // 引用用の情報を取得（引用モードの場合）
        $quotePost = null;
        if (isset($_GET['quote_id'])) {
            $quotePost = $this->postModel->getById($_GET['quote_id']);
        }

        include 'views/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nickname = trim($_POST['nickname'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $parentId = $_POST['parent_id'] ?? null;
            
            if ($nickname === '' || $message === '') {
                header("Location: index.php?error=empty");
                exit;
            }

            $mediaPath = null;
            $mediaType = null;

            if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['media']['tmp_name'];
                $fileName = basename($_FILES['media']['name']);
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $mimeType = mime_content_type($tmpName);
                $newFileName = uniqid() . '.' . $extension;
                $targetPath = $this->uploadDir . $newFileName;

                if (str_starts_with($mimeType, 'image/')) {
                    $mediaType = 'image';
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $mediaType = 'video';
                }

                if ($mediaType !== null) {
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $mediaPath = $targetPath;
                    }
                }
            }

            if ($this->postModel->create($nickname, $message, $mediaPath, $mediaType, $parentId)) {
                header("Location: index.php");
                exit;
            }
        }
    }

    /**
     * いいね処理
     */
    public function like() {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->postModel->addLike($id);
        }
        // 元のページに戻る（検索やページを維持するためリファラを使用するか、単純にリダイレクト）
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    public function destroy() {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $post = $this->postModel->getById($id);
            if ($post && $post['media_path'] && file_exists($post['media_path'])) {
                unlink($post['media_path']);
            }
            $this->postModel->delete($id);
        }
        header("Location: index.php");
        exit;
    }
}