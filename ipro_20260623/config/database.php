<?php
/**
 * DB接続設定ファイル
 * * 安全なPDO接続インスタンスを管理します。
 * 定数を用いた設定、適切な文字コード（utf8mb4）の指定、
 * およびSQLインジェクション対策としてのエミュレーション無効化を行います。
 */

// データベース接続情報の設定
define('DB_HOST', 'localhost');             // データベースのホスト名
define('DB_NAME', 'it_20260623_db');        // 指定されたデータベース名
define('DB_USER', 'root');                  // データベース接続ユーザー名
define('DB_PASS', '');                      // データベース接続パスワード（環境に合わせて要変更）
define('DB_CHARSET', 'utf8mb4');            // 文字コード

class Database {
    private static $pdo = null;

    /**
     * データベース接続（PDOインスタンス）を取得する静的メソッド
     * * @return PDO
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                // DSN（Data Source Name）の構築。charsetを必ず指定して文字エンコーディングの脆弱性を防ぎます。
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                
                // PDOオプションの設定（セキュリティとエラーハンドリングの強化）
                $options = [
                    // エラー時に例外（Exception）をスローする。不具合の早期発見と、致命的な情報の画面リーク防止のため。
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    
                    // デフォルトのフェッチモードを連想配列に設定。扱いやすく安全。
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    
                    // 【SQLインジェクション対策 (A03:2021-Injection)】
                    // プリペアドステートメントのエミュレーションを「無効 (false)」にする。
                    // これにより、PHP側でのバインドエミュレーションではなく、MySQL本来のプレースホルダ機能を使用し、
                    // 悪意のあるSQLが混入して実行されるのを根本から完全に防ぎます。
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // 【セキュリティ上の重要点】
                // 本番環境ではエラーメッセージ（接続パスワードやシステム内部情報）を画面に出力してはいけません。
                // 攻撃者にサーバー内部のヒントを与えることになるため、一般的なメッセージを表示して安全に終了（exit）します。
                error_log("データベース接続エラー: " . $e->getMessage()); // サーバーのログのみに出力
                die("システムエラーが発生しました。時間を置いて再度お試しください。");
            }
        }
        return self::$pdo;
    }
}