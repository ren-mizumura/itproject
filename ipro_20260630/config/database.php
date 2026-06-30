<?php
/**
 * データベース接続管理クラス
 */
class Database {
    private static $host = 'localhost';
    private static $db_name = 'ipro_20260630';
    private static $username = 'root';
    private static $password = ''; // XAMPPデフォルトは空文字
    private static $port = '3306';
    private static $conn = null;

    /**
     * データベース接続を取得する (シングルトンパターン)
     * @return PDO|null
     */
    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                self::$conn = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $exception) {
                // セキュリティ保護のため詳細なDB情報を画面に出さないよう配慮
                error_log("Database Connection Error: " . $exception->getMessage());
                die("<div style='font-family:sans-serif;padding:20px;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:6px;max-width:600px;margin:50px auto;'>
                        <h4 style='margin-top:0;'>データベース接続エラー</h4>
                        <p>MySQLが起動しているか、またはデータベース名・接続設定を確認してください。</p>
                        <p style='font-size:12px;color:#555;'>詳細: " . htmlspecialchars($exception->getMessage()) . "</p>
                     </div>");
            }
        }
        return self::$conn;
    }
}