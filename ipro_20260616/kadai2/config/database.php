<?php
/**
 * config/database.php
 * データベース接続を担当するクラスです。
 * データベースの接続情報をカプセル化し、PDO（PHP Data Objects）を用いた
 * セキュアな接続インスタンスをシングルトンパターンのように一元管理します。
 */

class Database {
    // データベース接続設定（定数またはプライベートプロパティとして定義）
    private static $host = 'localhost';
    private static $db_name = 'it_20260616_db'; // 先ほど作成したデータベース名
    private static $username = 'root';          // 環境に応じて変更してください
    private static $password = '';              // 環境に応じて変更してください
    private static $charset = 'utf8mb4';
    private static $conn = null;                // 接続を保持するスタティック変数

    /**
     * データベース接続インスタンスを取得する静的メソッド
     * 毎回新しい接続を作らず、1回作成した接続（$conn）を再利用することで
     * サーバーのリソース消費を抑えます。
     * * @return PDO|null
     */
    public static function getConnection() {
        // すでに接続が存在する場合は、既存の接続オブジェクトをそのまま返す
        if (self::$conn !== null) {
            return self::$conn;
        }

        // DSN (Data Source Name) の構築
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;

        try {
            // 安全なオプション構成
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // エラー発生時に例外をスロー（安全なエラーハンドリングのため）
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // 取得データを連想配列形式に統一
                PDO::ATTR_EMULATE_PREPARES => false,               // SQLインジェクションを防ぐため、リアルプリペアドステートメントを強制
            ];

            // PDOインスタンスの作成
            self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            
        } catch (PDOException $exception) {
            // 万が一の接続エラー時、生のエラー（パスワードなどを含む可能性あり）が
            // 画面に露出しないよう、安全なエラーメッセージに差し替えて処理を停止します。
            error_log("Database Connection Error: " . $exception->getMessage()); // サーバーのログには記録
            die("システムエラーが発生しました。時間を置いて再度お試しください。");
        }

        return self::$conn;
    }
}