<?php declare(strict_types=1);
    // 1. セッションを開始する（ブラウザから送られたセッションIDを元に、サーバー上のデータを復元）
    session_start();

    

    $user = [
        'userName' => 'test',
        'userEmail' => 'test@test.com',
        'userPassword' => 'test'
    ];


    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $userName = $_POST['userName'];
        $userEmail = $_POST['userEmail'];
        $userPassword = $_POST['userPassword'];


        if(
            $user['userName'] === $userName
            &&
            $user['userEmail'] === $userEmail
            &&
            $user['userPassword'] === $userPassword
        ){
            //ログイン成功
            // 2. アクセス数のカウント処理
            if (isset($_SESSION['count'])) {
                // 2回目以降のアクセス：現在のカウントを1増やす
                $_SESSION['count']++;
            } else {
                // 初めてのアクセス：1をセットする
                $_SESSION['count'] = 1;
            }
        }else{
            echo 'ログイン失敗<br>';
            $_SESSION['count'] = 0;
        }


    }

    $count = (int)$_SESSION['count'];;
?>

<body>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <h1>セッションとクッキーを理解する</h1>
        <?php // $count++; //1回増やす  ?>
        <p>あなたは、<?= $count ?>回目の訪問です</p>
        <form action="" method="post">
            ユーザーネーム： <input type="text" name="userName" id=""><br>
            メールアドレス： <input type="email" name="userEmail" id=""><br>
            パスワード： <input type="password" name="userPassword" id=""><br>
            <button type="submit">送信</button>
        </form>
    </body>
    </html>
</body>