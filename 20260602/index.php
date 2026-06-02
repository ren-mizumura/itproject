<?php
// 1. データベース接続設定
$host = 'localhost';
$username = 'root'; // 適宜書き換えてください
$password = ''; // 適宜書き換えてください
$dbname = 'it_test20260602_v2'; // 適宜書き換えてください


$mysqli = new mysqli($host, $username, $password, $dbname);


if ($mysqli->connect_error) {
    die("接続失敗: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");


// 2. データの取得 (JOIN使用)

// $sql = "SELECT *
//         FROM posts
//         ";

// $sql = "SELECT posts.*, users.name
//         FROM posts
//         JOIN users ON posts.user_id = users.id
//         ORDER BY posts.create_at DESC";

$sql = "SELECT * FROM `posts` 
        WHERE`body` LIKE '%今日%'
        ORDER BY `create_at` DESC;";



$result = $mysqli->query($sql);
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイブログ</title>
</head>
<body>
    <h1>投稿一覧</h1>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($post = $result->fetch_assoc()): ?>
            <article style="border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;">
                <?php if(isset($post['title'])): ?>
                    <h2><?= htmlspecialchars($post['title'], ENT_QUOTES) ?></h2>
                <?php endif; ?>


                <p><small>
                    <?php if(isset($post['name'])): ?>
                        投稿者: <?= htmlspecialchars($post['name'], ENT_QUOTES) ?> |
                    <?php endif; ?>
                    <?php if(isset($post['create_at'])): ?>
                        投稿日: <?= htmlspecialchars($post['create_at'], ENT_QUOTES) ?>
                    <?php endif; ?>
                </small></p>


                <?php if(isset($post['body'])): ?>
                    <p><?= nl2br(htmlspecialchars($post['body'], ENT_QUOTES)) ?></p>
                <?php endif; ?>
               
                <?php if(isset($post['post_id'])): ?>
                    <p style="color: #888; font-size: 0.8em;">ID: <?= $post['post_id'] ?></p>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <p>投稿はありません。</p>
    <?php endif; ?>


    <?php $mysqli->close(); ?>
</body>
</html>