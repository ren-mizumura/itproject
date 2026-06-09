<?php
/**
 * UserModel クラス (userModel.php)
 * * このクラスは、ユーザーデータの管理（JSONファイルの読み書き）と、
 * 新規登録（バリデーション、ハッシュ化、重複チェック）、
 * ログイン認証（パスワード照合）のビジネスロジックをカプセル化した「Model（モデル）」です。
 * 静的メソッド（static）を使用して、インスタンス化せずに呼び出せる設計にしています。
 */

class UserModel {
    // 擬似データベースとなるJSONファイルのパス
    private static $jsonFile = 'user.json';

    /**
     * JSONファイルからユーザーデータを読み込む（非公開メソッド）
     * * @return array ユーザーデータの連想配列
     */
    private static function loadUsers() {
        // ファイルが存在しない場合は、空の配列を返す
        if (!file_exists(self::$jsonFile)) {
            return [];
        }

        $jsonStr = file_get_contents(self::$jsonFile);
        $users = json_decode($jsonStr, true);

        // JSONデコードに失敗した場合は空配列を返す
        return is_array($users) ? $users : [];
    }

    /**
     * JSONファイルにユーザーデータを保存する（非公開メソッド）
     * * @param array $users ユーザーデータの連想配列
     * @return bool 保存成否
     */
    private static function saveUsers($users) {
        // JSON_PRETTY_PRINTを適用して、人間が見ても読みやすい綺麗なフォーマットで保存します
        $jsonStr = json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return file_put_contents(self::$jsonFile, $jsonStr) !== false;
    }

    /**
     * メールアドレスからユーザーを検索する
     * * @param string $email メールアドレス
     * @return array|null 見つかったユーザーデータ（存在しない場合はnull）
     */
    public static function findByEmail($email) {
        $users = self::loadUsers();
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user; // 見つかったらユーザーデータを返す
            }
        }
        return null; // 見つからなかった場合
    }

    /**
     * 新規ユーザーを登録する
     * * @param string $username ユーザー名
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return array|string 成功した場合は新規ユーザーの情報を配列で返し、失敗した場合はエラーメッセージ（文字列）を返す
     */
    public static function register($username, $email, $password) {
        // 1. 簡易バリデーション（入力値チェック）
        $username = trim($username);
        $email = trim($email);

        if (empty($username) || empty($email) || empty($password)) {
            return 'すべての項目を入力してください。';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '正しいメールアドレスの形式で入力してください。';
        }

        if (strlen($password) < 6) {
            return 'パスワードは6文字以上で入力してください。';
        }

        // 2. 重複チェック（すでにメールアドレスが使われていないか）
        if (self::findByEmail($email) !== null) {
            return 'このメールアドレスは既に登録されています。';
        }

        // 3. パスワードのハッシュ化（安全な暗号化）
        // password_hash() は、最新の強力なアルゴリズム（現在はBCRYPT）を使って安全なソルトを含んだハッシュを作成します。
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 4. 新規ユーザーデータの作成
        $newUser = [
            'id' => uniqid('user_', true), // ユニークなIDを自動生成
            'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'), // XSS対策
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 5. 保存処理
        $users = self::loadUsers();
        $users[] = $newUser;
        
        if (self::saveUsers($users)) {
            // パスワードを返却値から除外して、呼び出し元に安全なユーザー情報を返す
            unset($newUser['password']);
            return $newUser; 
        }

        return 'データの保存に失敗しました。';
    }

    /**
     * ユーザー認証（ログインチェック）を行う
     * * @param string $email メールアドレス
     * @param string $password パスワード
     * @return array|string 成功した場合はパスワードを除いたユーザー情報を返し、失敗した場合はエラーメッセージを返す
     */
    public static function authenticate($email, $password) {
        $email = trim($email);

        if (empty($email) || empty($password)) {
            return 'メールアドレスとパスワードを入力してください。';
        }

        // 1. メールアドレスでユーザーを検索
        $user = self::findByEmail($email);

        if (!$user) {
            return 'メールアドレスまたはパスワードが正しくありません。';
        }

        // 2. パスワードの照合
        // password_verify() は、生のパスワードと、DB（JSON）に保存されたハッシュ化パスワードが一致するかを検証します。
        if (password_verify($password, $user['password'])) {
            // 認証成功！
            // セキュリティのため、セッション等に格納されるデータからパスワードを削除します
            unset($user['password']);
            return $user;
        }

        // パスワードが一致しなかった場合
        return 'メールアドレスまたはパスワードが正しくありません。';
    }
}