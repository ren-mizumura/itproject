<?php
/**
 * ArticleModel クラス (articleModel.php)
 * * 記事データのCRUD（作成・読み込み・更新・削除）処理とバリデーションを担当します。
 * 各記事は作成者のユーザーID（user_id）と紐付けられ、マルチユーザーブログを実現します。
 */

class ArticleModel {
    // 記事データを保存するJSONファイル
    private static $jsonFile = 'articles.json';

    /**
     * JSONファイルから記事データを読み込む（非公開）
     */
    private static function loadArticles() {
        if (!file_exists(self::$jsonFile)) {
            return [];
        }
        $jsonStr = file_get_contents(self::$jsonFile);
        $articles = json_decode($jsonStr, true);
        return is_array($articles) ? $articles : [];
    }

    /**
     * JSONファイルに記事データを保存する（非公開）
     */
    private static function saveArticles($articles) {
        // 新しい順（降順）に並び替えるために、保存時はそのまま、取得時にソートする設計にします。
        $jsonStr = json_encode($articles, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return file_put_contents(self::$jsonFile, $jsonStr) !== false;
    }

    /**
     * 全ての記事を取得する（最新順）
     * @return array 記事の配列
     */
    public static function findAll() {
        $articles = self::loadArticles();
        // 作成日時（created_at）で降順にソート（新しい記事が上）
        usort($articles, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        return $articles;
    }

    /**
     * 特定のユーザーが書いた記事のみを取得する（最新順）
     * @param string $userId ユーザーID
     * @return array 記事の配列
     */
    public static function findByUserId($userId) {
        $all = self::findAll();
        $userArticles = [];
        foreach ($all as $article) {
            if ($article['user_id'] === $userId) {
                $userArticles[] = $article;
            }
        }
        return $userArticles;
    }

    /**
     * IDで特定の記事を1件取得する
     * @param string $id 記事ID
     * @return array|null 記事データ、またはnull
     */
    public static function findById($id) {
        $articles = self::loadArticles();
        foreach ($articles as $article) {
            if ($article['id'] === $id) {
                return $article;
            }
        }
        return null;
    }

    /**
     * 新規記事を投稿する
     * @param string $userId 投稿者のユーザーID
     * @param string $username 投稿者のユーザー名
     * @param string $title タイトル
     * @param string $content 本文
     * @return array|string 成功時は記事データ、失敗時はエラーメッセージ
     */
    public static function create($userId, $username, $title, $content) {
        $title = trim($title);
        $content = trim($content);

        if (empty($title)) {
            return 'タイトルを入力してください。';
        }
        if (empty($content)) {
            return '本文を入力してください。';
        }

        $newArticle = [
            'id' => uniqid('post_', true),
            'user_id' => $userId,
            'username' => $username,
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $articles = self::loadArticles();
        $articles[] = $newArticle;

        if (self::saveArticles($articles)) {
            return $newArticle;
        }
        return '記事の保存に失敗しました。';
    }

    /**
     * 記事を更新（編集）する
     * @param string $id 記事ID
     * @param string $userId 編集を試みているユーザーID（本人確認用）
     * @param string $title 新しいタイトル
     * @param string $content 新しい本文
     * @return bool|string 成功時はtrue、失敗時はエラーメッセージ
     */
    public static function update($id, $userId, $title, $content) {
        $title = trim($title);
        $content = trim($content);

        if (empty($title)) {
            return 'タイトルを入力してください。';
        }
        if (empty($content)) {
            return '本文を入力してください。';
        }

        $articles = self::loadArticles();
        $found = false;

        foreach ($articles as &$article) {
            if ($article['id'] === $id) {
                // セキュリティチェック: 投稿者本人か確認
                if ($article['user_id'] !== $userId) {
                    return '他のユーザーの記事を編集する権限がありません。';
                }
                
                $article['title'] = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $article['content'] = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                $article['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }

        if (!$found) {
            return '指定された記事が見つかりません。';
        }

        if (self::saveArticles($articles)) {
            return true;
        }
        return '記事の更新に失敗しました。';
    }

    /**
     * 記事を削除する
     * @param string $id 記事ID
     * @param string $userId 削除を試みているユーザーID（本人確認用）
     * @return bool|string 成功時はtrue、失敗時はエラーメッセージ
     */
    public static function delete($id, $userId) {
        $articles = self::loadArticles();
        $filteredArticles = [];
        $found = false;

        foreach ($articles as $article) {
            if ($article['id'] === $id) {
                // セキュリティチェック: 投稿者本人か確認
                if ($article['user_id'] !== $userId) {
                    return '他のユーザーの記事を削除する権限がありません。';
                }
                $found = true;
                continue; // 削除対象は新しい配列に入れない
            }
            $filteredArticles[] = $article;
        }

        if (!$found) {
            return '指定された記事が見つかりません。';
        }

        if (self::saveArticles($filteredArticles)) {
            return true;
        }
        return '記事の削除に失敗しました。';
    }
}