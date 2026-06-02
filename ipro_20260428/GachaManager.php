<?php
/**
 * 1枚のカード情報を扱うクラス
 */
class Card {
    public string $name;      // カード名
    public string $rarity;    // レアリティ (SSR, SR, R, N など)
    public string $imageUrl;  // カード画像のURL

    // インスタンス作成時に実行される初期化処理
    public function __construct(string $name, string $rarity, string $imageUrl) {
        $this->name = $name;
        $this->rarity = $rarity;
        $this->imageUrl = $imageUrl;
    }

    // JSON保存のために、カードの情報を配列に変換するメソッド
    public function toArray(): array {
        return [
            'name'     => $this->name,
            'rarity'   => $this->rarity,
            'imageUrl' => $this->imageUrl
        ];
    }
}

/**
 * ガチャの抽選とデータ保存を管理するクラス
 */
class GachaManager {
    private string $dataFilePath; // 保存先JSONファイルのパス
    private array $cardPool;      // 排出されるカードの候補一覧

    // 初期化処理：保存先パスを受け取り、カードのラインナップを準備する
    public function __construct(string $dataFilePath) {
        $this->dataFilePath = $dataFilePath;
        
        // ガチャで排出されるカードの候補（ダミー画像を使用しています）
        $this->cardPool = [
            new Card("勇者アルス", "SSR", "[https://via.placeholder.com/150x200/ffd700/000000?text=SSR+Hero](https://via.placeholder.com/150x200/ffd700/000000?text=SSR+Hero)"),
            new Card("魔法使いエルフ", "SR", "[https://via.placeholder.com/150x200/c0c0c0/000000?text=SR+Wizard](https://via.placeholder.com/150x200/c0c0c0/000000?text=SR+Wizard)"),
            new Card("戦士ゴードン", "R", "[https://via.placeholder.com/150x200/cd7f32/ffffff?text=R+Fighter](https://via.placeholder.com/150x200/cd7f32/ffffff?text=R+Fighter)"),
            new Card("スライム", "N", "[https://via.placeholder.com/150x200/87cefa/000000?text=N+Slime](https://via.placeholder.com/150x200/87cefa/000000?text=N+Slime)"),
            new Card("ゴブリン", "N", "[https://via.placeholder.com/150x200/32cd32/000000?text=N+Goblin](https://via.placeholder.com/150x200/32cd32/000000?text=N+Goblin)")
        ];
    }

    // ガチャを引く（ランダムに1枚選ぶ）メソッド
    public function draw(): Card {
        // cardPoolの中からランダムなインデックス（キー）を1つ取得
        $randomIndex = array_rand($this->cardPool);
        // 選ばれたカードを返す
        return $this->cardPool[$randomIndex];
    }

    // 引いたカードをJSONファイルに追記保存するメソッド
    public function saveCard(Card $card): void {
        // 現在のコレクションを読み込む
        $collection = $this->getCollection();
        
        // 新しいカードを配列の最後に追加する
        $collection[] = $card->toArray();
        
        // 配列をJSON形式の文字列に変換し、ファイルに書き込む（整形して保存）
        file_put_contents(
            $this->dataFilePath, 
            json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    // これまでに引いたカードの一覧を取得するメソッド
    public function getCollection(): array {
        // ファイルが存在しない場合は、まだ誰も引いていないので空の配列を返す
        if (!file_exists($this->dataFilePath)) {
            return [];
        }
        
        // ファイルからJSON文字列を読み込む
        $json = file_get_contents($this->dataFilePath);
        // JSON文字列をPHPの配列に変換する
        $data = json_decode($json, true);
        
        return is_array($data) ? $data : [];
    }
}
