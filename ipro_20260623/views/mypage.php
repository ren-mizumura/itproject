<?php
/**
 * マイページ（ステップ2用）
 * * 【不適切なアクセス制御 (A01:2021-Broken Access Control) 対策】
 * 認証されていないユーザー、またはセッションタイムアウトしたユーザーの立ち入りを阻止します。
 */

// ヘッダーを読み込み（ここでセッションが開始され、h()エスケープ関数が定義されます）
require_once __DIR__ . '/layout/header.php';

// 1. 【アクセス制御の検証】
// ログイン状態を示すセッション変数が存在しない場合、ログイン画面に追い返します。
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = 'ログインが必要です。';
    header("Location: index.php?action=login");
    exit;
}

// 2. 【セッションタイムアウトの管理】
// セッションの有効期限（ここでは例として15分）を管理し、放置されたセッションを自動無効化します。
$timeout_duration = 900; // 15分 (秒数)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    // タイムアウト時間を超えていたら、セッションを破棄してログイン画面に返します。
    session_unset();
    session_destroy();
    
    // 新しいセッションを始めてメッセージを保持します。
    session_start();
    $_SESSION['flash_message'] = 'セッションがタイムアウトしました。セキュリティ保護のため再ログインしてください。';
    header("Location: index.php?action=login");
    exit;
}
// アクティビティ時間を現在時刻に更新
$_SESSION['last_activity'] = time();
?>

<div class="bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <div class="flex items-center space-x-3 mb-6">
        <div class="p-3 bg-green-100 text-green-600 rounded-full">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-700">マイページ (認証成功)</h2>
    </div>

    <!-- ウェルカムメッセージ -->
    <p class="text-gray-600 mb-6 leading-relaxed">
        ログインに成功しました。この領域は、認証に成功したユーザーのみがアクセス可能な安全なマイページです。
    </p>

    <!-- ユーザー詳細情報 -->
    <div class="border-t border-b border-gray-100 py-4 mb-6 space-y-3">
        <div class="flex justify-between">
            <span class="text-gray-500 text-sm">ユーザーID</span>
            <!-- IDは数値ですが、将来的な拡張も考慮して必ずエスケープ(h)して出力します。 -->
            <span class="text-gray-800 font-medium"><?php echo h($_SESSION['user_id']); ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 text-sm">現在のメールアドレス</span>
            <!-- XSS (A03:2021) 対策：メールアドレスのようなユーザー入力値は絶対に直接出力してはいけません。 -->
            <span class="text-gray-800 font-medium"><?php echo h($_SESSION['user_email']); ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500 text-sm">セキュリティ状態</span>
            <span class="text-green-600 font-semibold text-sm flex items-center">
                <span class="h-2 w-2 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                保護されています (SSL/TLS推奨)
            </span>
        </div>
    </div>

    <!-- 安全性に関する説明文 -->
    <div class="bg-indigo-50 p-4 rounded-md border border-indigo-100 text-sm text-indigo-800 space-y-2">
        <p class="font-semibold">🛡️ 施された主なセキュリティ対策：</p>
        <ul class="list-disc pl-5 space-y-1 text-xs">
            <li><strong>セッション固定攻撃対策</strong>：ログイン時にセッションIDが再生成されています。</li>
            <li><strong>セッション固定奪取対策</strong>：Cookieに HttpOnly / SameSite が適用されています。</li>
            <li><strong>アクセス制御</strong>：未ログインでの直接URL（/mypage）直叩きアクセスは完全に防ぎます。</li>
            <li><strong>セッションタイムアウト</strong>：15分放置すると自動でセッションを破棄します。</li>
        </ul>
    </div>

    <div class="mt-8 flex justify-end">
        <a href="index.php?action=logout" 
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-md border text-sm transition-colors">
            ログアウト
        </a>
    </div>
</div>

</main>
<footer class="bg-gray-100 text-center py-4 text-xs text-gray-500 border-t mt-8">
    &copy; 2026 Secure Login System. All rights reserved.
</footer>
</body>
</html>