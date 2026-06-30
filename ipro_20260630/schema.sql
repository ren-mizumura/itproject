-- ==========================================
-- ユーザ管理機能付き掲示板 データベース設計 (MySQL)
-- ==========================================

-- データベースの作成 (存在しない場合)
CREATE DATABASE IF NOT EXISTS `ipro_20260630` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ipro_20260630`;

-- テーブル削除 (初期化用)
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `replies`;
DROP TABLE IF EXISTS `post_visibilities`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `student_progress`;
DROP TABLE IF EXISTS `student_curriculums`;
DROP TABLE IF EXISTS `curriculum_tasks`;
DROP TABLE IF EXISTS `curriculums`;
DROP TABLE IF EXISTS `friendships`;
DROP TABLE IF EXISTS `users`;

-- 1. ユーザーテーブル
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'ユーザーID（ログイン用）',
  `display_name` VARCHAR(100) NOT NULL COMMENT '表示名',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'ハッシュ化されたパスワード',
  `role` ENUM('student', 'teacher') NOT NULL DEFAULT 'student' COMMENT '権限（生徒/先生）',
  `invite_token` VARCHAR(64) UNIQUE NULL COMMENT '専用招待URL用トークン',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 友達・属性紐付けテーブル
CREATE TABLE `friendships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT '設定を行ったユーザーID',
  `friend_id` INT NOT NULL COMMENT '対象のユーザーID',
  `attribute_tag` VARCHAR(50) NOT NULL DEFAULT '友達' COMMENT '属性タグ（「友達」「グループA」など）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`friend_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_friend_relation` (`user_id`, `friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. カリキュラム（プログラミング言語マスター）
CREATE TABLE `curriculums` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT '言語名（例: PHP）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. カリキュラム詳細タスク
CREATE TABLE `curriculum_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `curriculum_id` INT NOT NULL COMMENT 'カリキュラム言語ID',
  `task_name` VARCHAR(150) NOT NULL COMMENT '詳細学習タスク名（例: 関数）',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_curriculum_task` (`curriculum_id`, `task_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. 生徒学習対象登録テーブル
CREATE TABLE `student_curriculums` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL COMMENT '生徒ユーザーID',
  `curriculum_id` INT NOT NULL COMMENT '選択した言語ID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_student_curriculum` (`student_id`, `curriculum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. 生徒進捗・習熟度テーブル（カリキュラムタスクごとの習熟度パーセント）
CREATE TABLE `student_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL COMMENT '生徒ユーザーID',
  `task_id` INT NOT NULL COMMENT '学習タスクID',
  `proficiency` INT NOT NULL DEFAULT 0 COMMENT '習熟度パーセント(0〜100)',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`task_id`) REFERENCES `curriculum_tasks`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_student_task_progress` (`student_id`, `task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. 掲示板投稿テーブル
CREATE TABLE `posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT '投稿者ユーザーID',
  `curriculum_id` INT NOT NULL COMMENT '対象の学習言語ID',
  `task_id` INT NOT NULL COMMENT '詳細な学習内容（タスク）ID',
  `content` TEXT NOT NULL COMMENT '本文・進捗内容',
  `code_content` TEXT NULL COMMENT 'ソースコード記述欄',
  `file_path` VARCHAR(255) NULL COMMENT '添付ファイル保存パス',
  `file_name` VARCHAR(255) NULL COMMENT '添付ファイルオリジナル名',
  `reference_url` VARCHAR(500) NULL COMMENT '参考URL',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`task_id`) REFERENCES `curriculum_tasks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. 投稿公開範囲制限（特定の属性タグを持つ友達にのみ公開する中間テーブル）
-- 誰に送信されたか・どのような属性宛かは投稿者本人のみ分かり、他者からは完全に隠蔽される
CREATE TABLE `post_visibilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL COMMENT '対象投稿ID',
  `allowed_user_id` INT NOT NULL COMMENT '閲覧が許可されたユーザーID',
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`allowed_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. 先生リプライ・指導テーブル
CREATE TABLE `replies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL COMMENT '対象の生徒投稿ID',
  `user_id` INT NOT NULL COMMENT '先生または自身のユーザーID',
  `content` TEXT NOT NULL COMMENT '指導・返信内容',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. システム内通知テーブル
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL COMMENT '通知を受け取るユーザーID',
  `sender_id` INT NOT NULL COMMENT '通知を引き起こしたユーザーID',
  `type` VARCHAR(50) NOT NULL COMMENT '通知タイプ(reply, proficiency, custom_visibility, new_task, new_post)',
  `target_id` INT NOT NULL COMMENT '関連する対象ID（post_id や curriculum_id 等）',
  `is_read` TINYINT(1) DEFAULT 0 COMMENT '既読フラグ(0:未読, 1:既読)',
  `message` VARCHAR(255) NOT NULL COMMENT '通知表示テキスト',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- テスト用初期ダミーデータのインポート
-- ==========================================

-- パスワードはすべて「123456」をハッシュ化
-- ハッシュ：$2y$10$ErPIIJhCMFX2xp2QpNRo7.4l27pq9KFluJLUXv4AsYWcP8kLZoKXa
SET @hashed_password = '$2y$10$ErPIIJhCMFX2xp2QpNRo7.4l27pq9KFluJLUXv4AsYWcP8kLZoKXa';

-- 1. ユーザーアカウントの登録
-- 先生 (1名)
INSERT INTO `users` (`id`, `username`, `display_name`, `password_hash`, `role`, `invite_token`) VALUES
(1, 'teacher_admin', '山田先生（管理者）', @hashed_password, 'teacher', 'token_teacher_admin');

-- 生徒 (3名)
INSERT INTO `users` (`id`, `username`, `display_name`, `password_hash`, `role`, `invite_token`) VALUES
(2, 'student_alice', 'アリス', @hashed_password, 'student', 'token_student_alice'),
(3, 'student_bob', 'ボブ', @hashed_password, 'student', 'token_student_bob'),
(4, 'student_charlie', 'チャーリー', @hashed_password, 'student', 'token_student_charlie');

-- 2. カリキュラムマスター（先生設定済み）
INSERT INTO `curriculums` (`id`, `name`) VALUES
(1, 'HTML/CSS'),
(2, 'PHP');

-- 3. 詳細カリキュラムタスクの登録
-- HTML/CSS
INSERT INTO `curriculum_tasks` (`id`, `curriculum_id`, `task_name`) VALUES
(1, 1, 'タグの基礎'),
(2, 1, 'Flexboxレイアウト'),
(3, 1, 'レスポンシブ設計');

-- PHP
INSERT INTO `curriculum_tasks` (`id`, `curriculum_id`, `task_name`) VALUES
(4, 2, '変数と演算'),
(5, 2, '条件分岐とループ'),
(6, 2, '関数'),
(7, 2, 'MySQL連携');

-- 4. 学習プロフィール（生徒選択済み）
-- Alice: PHP、HTML/CSS
INSERT INTO `student_curriculums` (`student_id`, `curriculum_id`) VALUES
(2, 1),
(2, 2);
-- Bob: PHP
INSERT INTO `student_curriculums` (`student_id`, `curriculum_id`) VALUES
(3, 2);

-- 5. 習熟度初期データ設定 (Alice, Bob)
-- Alice (HTML/CSS)
INSERT INTO `student_progress` (`student_id`, `task_id`, `proficiency`) VALUES
(2, 1, 90),
(2, 2, 80),
(2, 3, 40);
-- Alice (PHP)
INSERT INTO `student_progress` (`student_id`, `task_id`, `proficiency`) VALUES
(2, 4, 95),
(2, 5, 70),
(2, 6, 20), -- 関数
(2, 7, 0);
-- Bob (PHP)
INSERT INTO `student_progress` (`student_id`, `task_id`, `proficiency`) VALUES
(3, 4, 80),
(3, 5, 50),
(3, 6, 0),
(3, 7, 0);

-- 6. 友達登録（アリスからボブへの「友達」属性登録）
INSERT INTO `friendships` (`user_id`, `friend_id`, `attribute_tag`) VALUES
(2, 3, '友達'), -- AliceがBobを「友達」に設定
(3, 2, 'クラスメイト'); -- BobがAliceを「クラスメイト」に設定

-- 7. 初期投稿
-- Aliceの質問：「PHPの関数で引数の渡し方に躓いた」
INSERT INTO `posts` (`id`, `user_id`, `curriculum_id`, `task_id`, `content`, `code_content`, `file_path`, `file_name`, `reference_url`, `created_at`) VALUES
(1, 2, 2, 6, 
 'PHPの関数を学習中ですが、参照渡し（&）と値渡しの違いが直感的に理解できません。どのようなシチュエーションで使い分けるべきでしょうか？簡単な例を教えていただけると助かります！', 
 '<?php\nfunction modifyValue($val) {\n    $val = $val + 10;\n}\n\n$num = 5;\nmodifyValue($num);\necho $num; // ここが15にならない理由が分かりません\n?>', 
 NULL, NULL, 'https://www.php.net/manual/ja/functions.arguments.php', 
 DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- 8. 先生からのリプライ（指導）
INSERT INTO `replies` (`id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 1, 
 'アリスさん、良い質問ですね！\nPHPではデフォルトが「値渡し」になるため、関数内で引数の値を変更しても元の変数 `$num` には影響しません。元の変数を直接書き換えたい場合は、引数に `&` を付与して「参照渡し」にします。\n\n```php\nfunction modifyValue(&$val) { // & を付ける\n    $val = $val + 10;\n}\n```\nこれで `$num` は `15` になります。基本的には値渡しを使用し、メモリ節約や直接破壊的変更を行いたい特殊な場合のみ参照渡しと覚えておきましょう！', 
 DATE_SUB(NOW(), INTERVAL 90 MINUTE));

-- 9. 初期通知の登録
INSERT INTO `notifications` (`id`, `user_id`, `sender_id`, `type`, `target_id`, `is_read`, `message`, `created_at`) VALUES
(1, 2, 1, 'reply', 1, 0, '山田先生（管理者）があなたの投稿に指導リプライを行いました。', DATE_SUB(NOW(), INTERVAL 90 MINUTE));