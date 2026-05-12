<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>高機能掲示板</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background-color: #f0f2f5; color: #1c1e21; line-height: 1.5; padding: 20px; }
        .container { max-width: 800px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { text-align: center; color: #1877f2; margin-top: 0; }
        
        /* 検索・表示件数バー */
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .search-box { display: flex; gap: 5px; }
        .search-box input { padding: 8px; border: 1px solid #ddd; border-radius: 20px; outline: none; padding-left: 15px; }
        .limit-box select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }

        form { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .field { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        textarea { height: 100px; resize: vertical; }
        .submit-btn { background: #1877f2; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-size: 1em; font-weight: bold; }
        .submit-btn:hover { background: #166fe5; }

        /* 引用作成時の表示 */
        .quote-preview { background: #f7f8fa; border: 1px solid #ddd; border-left: 4px solid #1877f2; padding: 10px; border-radius: 4px; margin-bottom: 15px; position: relative; }
        .quote-preview .close-quote { position: absolute; right: 10px; top: 5px; text-decoration: none; color: #888; font-weight: bold; }

        /* 投稿カード */
        .post { background: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; position: relative; }
        .post-header { display: flex; justify-content: space-between; margin-bottom: 10px; align-items: flex-start; }
        .user-info { display: flex; flex-direction: column; }
        .nickname { color: #1c1e21; font-weight: bold; font-size: 1.1em; }
        .date { font-size: 0.8em; color: #65676b; }
        
        .post-actions { display: flex; gap: 10px; }
        
        /* 削除ボタンと引用ボタンの共通スタイル */
        .delete-form button, .quote-link { 
            border: none; 
            padding: 5px 10px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 0.8em; 
            font-weight: bold;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .quote { margin-bottom: 20px; padding-bottom: 20px; }

        .delete-form button { background: #f02849; color: white; }
        .delete-form button:hover { background: #d6223f; }

        .quote-link { background: #e4e6eb; color: #050505; }
        .quote-link:hover { background: #d8dadf; }

        .message { white-space: pre-wrap; margin-bottom: 10px; }
        .media-content { margin-top: 10px; border-radius: 8px; overflow: hidden; background: transparent; display: flex; justify-content: center; }
        .media-content img, .media-content video { max-width: 100%; max-height: 400px; display: block; }

        /* 引用投稿の表示（投稿内） */
        .quoted-post { margin-top: 10px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px; background: #f9f9f9; font-size: 0.9em; }
        .quoted-nickname { font-weight: bold; color: #555; }
        .quoted-message { color: #666; font-size: 0.95em; margin-top: 5px; }

        /* いいねセクション */
        .likes-section { margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; display: flex; align-items: center; gap: 10px; }
        .like-btn { background: #f0f2f5; border: none; color: #65676b; padding: 6px 12px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
        .like-btn:hover { background: #e4e6eb; color: #1877f2; }
        .like-count { font-weight: bold; color: #1877f2; }

        /* ページネーション */
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #1877f2; }
        .pagination .active { background: #1877f2; color: white; border-color: #1877f2; }

        .error-msg { color: #f02849; background: #ffebe9; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9em; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h1>シンプル掲示板</h1>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
            <p class="error-msg">ニックネームと本文を入力してください。</p>
        <?php endif; ?>

        <!-- 投稿フォーム -->
        <form action="index.php?action=store" method="POST" enctype="multipart/form-data">
            <!-- 引用プレビュー -->
            <?php if ($quotePost): ?>
                <div class="quote-preview">
                    <a href="index.php" class="close-quote">×</a>
                    <div style="font-size:0.8em; color:#65676b;">引用元: <strong><?php echo htmlspecialchars($quotePost['nickname'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div style="font-size:0.85em; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php echo htmlspecialchars($quotePost['message'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <input type="hidden" name="parent_id" value="<?php echo $quotePost['id']; ?>">
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="nickname">ニックネーム</label>
                <input type="text" name="nickname" id="nickname" maxlength="50" required placeholder="お名前">
            </div>
            <div class="field">
                <label for="message">本文</label>
                <textarea name="message" id="message" required placeholder="<?php echo $quotePost ? '引用についてコメントする...' : 'いまどうしてる？'; ?>"></textarea>
            </div>
            <div class="field">
                <label for="media">画像・動画を追加</label>
                <input type="file" name="media" id="media" accept="image/*,video/*">
            </div>
            <button type="submit" class="submit-btn"><?php echo $quotePost ? '引用投稿する' : '投稿する'; ?></button>
        </form>

        <!-- ツールバー（検索・表示件数） -->
        <div class="toolbar">
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="キーワード検索..." value="<?php echo htmlspecialchars($keyword ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                <button type="submit" style="display:none;">検索</button>
            </form>

            <div class="limit-box">
                <select onchange="location.href='index.php?search=<?php echo urlencode($keyword ?? ''); ?>&limit=' + this.value">
                    <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5件</option>
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10件</option>
                    <option value="15" <?php echo $limit == 15 ? 'selected' : ''; ?>>15件</option>
                </select>
            </div>
        </div>

        <!-- 投稿一覧 -->
        <div class="posts-list">
            <?php if (empty($posts)): ?>
                <p style="text-align:center; color:#888;">投稿が見つかりませんでした。</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-header">
                            <div class="user-info">
                                <span class="nickname"><?php echo htmlspecialchars($post['nickname'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="date"><?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="post-actions">
                                <div class="quote">
                                    <a href="index.php?quote_id=<?php echo $post['id']; ?>#nickname" class="quote-link">引用</a>
                                </div>
                                <form action="index.php?action=delete" method="POST" class="delete-form" onsubmit="return confirm('本当に削除しますか？');">
                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                    <button type="submit">削除</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="message"><?php echo htmlspecialchars($post['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                        
                        <!-- 引用された投稿の表示 -->
                        <?php if ($post['parent_id']): ?>
                            <div class="quoted-post">
                                <div class="quoted-nickname">@<?php echo htmlspecialchars($post['parent_nickname'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="quoted-message"><?php echo htmlspecialchars($post['parent_message'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php if ($post['parent_media_path']): ?>
                                    <div class="media-content" style="background:#eee; margin-top:5px;">
                                        <?php if ($post['parent_media_type'] === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($post['parent_media_path'], ENT_QUOTES, 'UTF-8'); ?>" style="max-height:150px;">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 自投稿のメディア -->
                        <?php if ($post['media_path']): ?>
                            <div class="media-content">
                                <?php if ($post['media_type'] === 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($post['media_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Post image">
                                <?php elseif ($post['media_type'] === 'video'): ?>
                                    <video src="<?php echo htmlspecialchars($post['media_path'], ENT_QUOTES, 'UTF-8'); ?>" controls></video>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- いいねボタン -->
                        <div class="likes-section">
                            <form action="index.php?action=like" method="POST">
                                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="like-btn">
                                    <span>👍 いいね</span>
                                    <?php if ($post['likes_count'] > 0): ?>
                                        <span class="like-count"><?php echo $post['likes_count']; ?></span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ページネーション -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="index.php?page=<?php echo $i; ?>&search=<?php echo urlencode($keyword ?? ''); ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>