-- データベースの作成（存在しない場合）
CREATE DATABASE IF NOT EXISTS `devlms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `devlms`;

-- 1. ユーザーテーブル
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'teacher') NOT NULL DEFAULT 'student',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. カリキュラム（言語と詳細タスク）テーブル
CREATE TABLE IF NOT EXISTS `curriculums` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `language` VARCHAR(100) NOT NULL,
    `task` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_lang_task` (`language`, `task`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. 生徒が受講する学習言語の紐付けテーブル
CREATE TABLE IF NOT EXISTS `student_languages` (
    `student_id` VARCHAR(50) NOT NULL,
    `language` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`student_id`, `language`),
    FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. 生徒のカリキュラム進捗・習熟度評価テーブル
CREATE TABLE IF NOT EXISTS `progress` (
    `student_id` VARCHAR(50) NOT NULL,
    `language` VARCHAR(100) NOT NULL,
    `task` VARCHAR(100) NOT NULL,
    `percent` INT NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`student_id`, `language`, `task`),
    FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. つながり（友達・属性）テーブル
CREATE TABLE IF NOT EXISTS `friendships` (
    `from_user_id` VARCHAR(50) NOT NULL,
    `to_user_id` VARCHAR(50) NOT NULL,
    `tag` VARCHAR(50) NOT NULL DEFAULT '友達',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`from_user_id`, `to_user_id`),
    FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. 掲示板投稿テーブル
CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `author_id` VARCHAR(50) NOT NULL,
    `language` VARCHAR(100) NOT NULL,
    `task` VARCHAR(100) NOT NULL,
    `body` TEXT NOT NULL,
    `code` TEXT NULL,
    `file_name` VARCHAR(255) NULL,
    `file_path` VARCHAR(255) NULL,
    `url` VARCHAR(500) NULL,
    `visibility` ENUM('all', 'restricted') NOT NULL DEFAULT 'all',
    `target_tag` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. リプライ（返信・指導）テーブル
CREATE TABLE IF NOT EXISTS `replies` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `post_id` INT NOT NULL,
    `author_id` VARCHAR(50) NOT NULL,
    `body` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. システム内通知テーブル
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `for_user_id` VARCHAR(50) NOT NULL,
    `text` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL, -- 'reply', 'progress_update', 'private_post', 'curriculum_added', 'new_post'
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`for_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- テスト用初期ダミーデータの挿入
-- ==========================================

-- パスワードハッシュの用意
-- 'admin123' -> $2y$10$6RzZby6XkX8l7/B4X7R1eu3V5O0D9Z0N8V8Zf8a8s8d8f8g8h8j8k (シミュレートハッシュ)
-- 実運用では password_hash() を使用。ダミー用としてpassword_hash('password', PASSWORD_DEFAULT)に相当する値を入れておきます。
-- ここでは一律 password_hash('password', PASSWORD_DEFAULT) のハッシュ値 '$2y$10$mC3GgQY7C2/t6eOq2GbyDe39R3S1i4fO4C6p5v5lA0lO5F6X0vB1G' を利用。

INSERT INTO `users` (`id`, `name`, `password_hash`, `role`) VALUES
('teacher_admin', '先生（管理者）', '$2y$10$mC3GgQY7C2/t6eOq2GbyDe39R3S1i4fO4C6p5v5lA0lO5F6X0vB1G', 'teacher'),
('student_alice', 'アリス', '$2y$10$mC3GgQY7C2/t6eOq2GbyDe39R3S1i4fO4C6p5v5lA0lO5F6X0vB1G', 'student'),
('student_bob', 'ボブ', '$2y$10$mC3GgQY7C2/t6eOq2GbyDe39R3S1i4fO4C6p5v5lA0lO5F6X0vB1G', 'student'),
('student_charlie', 'チャーリー', '$2y$10$mC3GgQY7C2/t6eOq2GbyDe39R3S1i4fO4C6p5v5lA0lO5F6X0vB1G', 'student')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- カリキュラムマスター
INSERT INTO `curriculums` (`language`, `task`) VALUES
('HTML/CSS', 'タグの基礎'),
('HTML/CSS', 'Flexboxレイアウト'),
('HTML/CSS', 'レスポンシブ設計'),
('PHP', '変数と演算'),
('PHP', '条件分岐とループ'),
('PHP', '関数'),
('PHP', 'MySQL連携')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 生徒の学習登録
INSERT INTO `student_languages` (`student_id`, `language`) VALUES
('student_alice', 'HTML/CSS'),
('student_alice', 'PHP'),
('student_bob', 'PHP')
ON DUPLICATE KEY UPDATE `student_id`=`student_id`;

-- 習熟度初期評価
INSERT INTO `progress` (`student_id`, `language`, `task`, `percent`) VALUES
('student_alice', 'HTML/CSS', 'タグの基礎', 100),
('student_alice', 'HTML/CSS', 'Flexboxレイアウト', 80),
('student_alice', 'HTML/CSS', 'レスポンシブ設計', 40),
('student_alice', 'PHP', '変数と演算', 90),
('student_alice', 'PHP', '条件分岐とループ', 60),
('student_alice', 'PHP', '関数', 30),
('student_alice', 'PHP', 'MySQL連携', 0),
('student_bob', 'PHP', '変数と演算', 50),
('student_bob', 'PHP', '条件分岐とループ', 10),
('student_bob', 'PHP', '関数', 0),
('student_bob', 'PHP', 'MySQL連携', 0)
ON DUPLICATE KEY UPDATE `percent`=`percent`;

-- 友達登録（アリスがボブを「友達」と登録）
INSERT INTO `friendships` (`from_user_id`, `to_user_id`, `tag`) VALUES
('student_alice', 'student_bob', '友達'),
('student_bob', 'student_alice', '友達') -- 双方向
ON DUPLICATE KEY UPDATE `tag`=`tag`;

-- 初期質問投稿
INSERT INTO `posts` (`id`, `author_id`, `language`, `task`, `body`, `code`, `file_name`, `file_path`, `url`, `visibility`) VALUES
(1, 'student_alice', 'PHP', '関数', 'PHPの関数で、引数の渡し方（値渡しと参照渡し）に躓いてしまいました。以下の関数を実行すると、元の変数の値が書き変わってしまいます。どうしてでしょうか？', '<?php\nfunction modifyValue(&$num) {\n    $num += 10;\n}\n\n$val = 5;\nmodifyValue($val);\necho $val; // なぜ 15 になる？\n?>', 'php_scope_error.png', 'uploads/php_scope_error.png', 'https://www.php.net/manual/ja/functions.arguments.php', 'all')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 先生からの初期回答リプライ
INSERT INTO `replies` (`id`, `post_id`, `author_id`, `body`) VALUES
(1, 1, 'teacher_admin', 'アリスさん、良い質問ですね！引数の前についている `&`（アンパサンド）がポイントです。\nこれは「参照渡し（リファレンス）」を指定する記号です。これがあると、関数内での変更が関数外の元の変数にも直接影響を与えます。\nもし元の値を変えたくない場合は、`function modifyValue($num)` のように `&` を外して「値渡し」にしてみてください。')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 初期通知
INSERT INTO `notifications` (`for_user_id`, `text`, `type`, `is_read`) VALUES
('student_alice', '先生（管理者）があなたの投稿にリプライを投稿しました。', 'reply', 0)
ON DUPLICATE KEY UPDATE `id`=`id`;