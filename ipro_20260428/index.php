<?php
// GachaManagerクラスなどの定義を読み込む
require_once 'GachaManager.php';

// 保存するJSONファイルの名前
$dataFile = 'collection.json';

// マネージャーのインスタンスを作成
$manager = new GachaManager($dataFile);

// 今回引いたカードを入れる変数（最初は空っぽ）
$drawnCard = null;

// POST通信で「draw」という名前のデータが送られてきたか確認（ガチャボタンが押されたか）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['draw'])) {
    // 1. ガチャを引いてカードを決定する
    $drawnCard = $manager->draw();
    // 2. 決定したカードをファイルに保存する
    $manager->saveCard($drawnCard);
}

// 画面下部に表示するため、最新のコレクション（これまでの履歴）を取得
$collection = $manager->getCollection();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡易カードガチャアプリ</title>
    <!-- CSSファイルを読み込む -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>簡易カードガチャアプリ</h1>
        
        <!-- ガチャ実行ボタン (押すと自分自身にPOST送信する) -->
        <form method="post" class="gacha-form">
            <button type="submit" name="draw" class="btn-draw">ガチャを引く！</button>
        </form>

        <!-- 今回引いたカードがある場合だけ結果を表示する -->
        <?php if ($drawnCard !== null): ?>
            <div class="result-area">
                <h2>結果</h2>
                <!-- レアリティごとのクラス名をつけて、CSSで色を変えられるようにする -->
                <div class="card result-card rarity-<?= htmlspecialchars($drawnCard->rarity) ?>">
                    <img src="<?= htmlspecialchars($drawnCard->imageUrl) ?>" alt="Card Image">
                    <p class="rarity"><?= htmlspecialchars($drawnCard->rarity) ?></p>
                    <p class="name"><?= htmlspecialchars($drawnCard->name) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- コレクション（これまでの履歴）の表示 -->
        <div class="collection-area">
            <h2>コレクション (全 <?= count($collection) ?> 枚)</h2>
            
            <?php if (count($collection) > 0): ?>
                <div class="card-grid">
                    <?php 
                    // 配列を逆順にして、新しく引いたものが上に表示されるようにする
                    foreach (array_reverse($collection) as $cardData): 
                    ?>
                        <div class="card rarity-<?= htmlspecialchars($cardData['rarity']) ?>">
                            <img src="<?= htmlspecialchars($cardData['imageUrl']) ?>" alt="Card Image">
                            <p class="rarity"><?= htmlspecialchars($cardData['rarity']) ?></p>
                            <p class="name"><?= htmlspecialchars($cardData['name']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>まだカードがありません。ガチャを引いてみましょう！</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
