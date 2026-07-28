<?php
// mypage.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login();

$userId = $_SESSION['user_id'];

// ログインユーザー情報の取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Figmaが連携済みかどうかを判定
$isFigmaLinked = !empty($user['figma_access_token']);

// 連携成功フラグの確認
$figmaSuccess = isset($_GET['figma_success']) && $_GET['figma_success'] == 1;

try {
    $statsStmt = $pdo->prepare('SELECT COUNT(*) AS total_count, AVG(total_score) AS avg_score FROM submissions WHERE user_id = :user_id');
    $statsStmt->execute([':user_id' => $userId]);
    $stats = $statsStmt->fetch();

    $listStmt = $pdo->prepare('SELECT * FROM submissions WHERE user_id = :user_id ORDER BY created_at DESC');
    $listStmt->execute([':user_id' => $userId]);
    $submissions = $listStmt->fetchAll();

} catch (PDOException $e) {
    error_log('Mypage Error: ' . $e->getMessage());
    die('データの読み込み中にエラーが発生しました。');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ - デザトレ</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="app-header">
        <div>ようこそ、<strong><?php echo h($_SESSION['display_name']); ?></strong> さん！</div>
        <nav>
            <a href="index.php">ホーム</a>
            <a href="mypage.php">マイページ</a>
            <a href="gallery.php">ギャラリー</a>
            <a href="logout.php">ログアウト</a>
        </nav>
    </header>

    <div class="container">
        <h1>マイページ</h1>
        
        <!-- Figma連携エリア -->
        <div class="figma-connect-box" style="margin: 20px 0; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; background: rgba(255,255,255,0.02);">
            <h3>Figma アカウント連携</h3>

            <?php if ($figmaSuccess): ?>
                <p style="color: #4CAF50; font-weight: bold;">✔ Figmaアカウントとの連携が完了しました！</p>
            <?php endif; ?>

            <?php if ($isFigmaLinked): ?>
                <p style="color: #2196F3; font-weight: bold;">連携中 (Figma ID: <?php echo h($user['figma_id'] ?? '連携済み'); ?>)</p>
                <a href="figma_redirect.php" class="btn" style="display:inline-block; padding:8px 16px; background:#666; color:#fff; text-decoration:none; border-radius:4px;">再連携する</a>
            <?php else: ?>
                <p>課題のサムネイルを自動取得するにはFigmaアカウントとの連携が必要です。</p>
                <a href="figma_redirect.php" class="btn" style="display:inline-block; padding:10px 20px; background:#0ACF83; color:#fff; text-decoration:none; font-weight:bold; border-radius:4px;">Figmaアカウントと連携する</a>
            <?php endif; ?>
        </div>

        <h2>学習スタッツ</h2>
        <div style="display: flex; gap: 20px; margin-bottom: 30px;">
            <div class="card" style="flex: 1; text-align: center;">
                <p style="color: var(--text-muted); margin: 0; font-size: 14px;">総提出回数</p>
                <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo h($stats['total_count'] ?? 0); ?> <span style="font-size: 16px;">回</span></p>
            </div>
            <div class="card" style="flex: 1; text-align: center;">
                <p style="color: var(--text-muted); margin: 0; font-size: 14px;">平均スコア</p>
                <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0; color: var(--primary-color);"><?php echo h(round($stats['avg_score'] ?? 0, 1)); ?> <span style="font-size: 16px;">点</span></p>
            </div>
        </div>

        <h2>提出履歴</h2>
        <?php if (empty($submissions)): ?>
            <p style="color: var(--text-muted);">まだ提出した課題はありません。</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th style="width: 130px;">プレビュー</th>
                        <th>日時</th>
                        <th>Figma リンク</th>
                        <th>AIスコア</th>
                        <th>フィードバック</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td>
                                <?php if (!empty($sub['thumbnail_url'])): ?>
                                    <!-- 🔍 タップ・クリックで拡大表示されるサムネイル -->
                                    <details class="img-modal">
                                        <summary style="list-style: none; cursor: pointer;">
                                            <img src="<?php echo h($sub['thumbnail_url']); ?>" alt="Figma Thumbnail" style="width: 120px; height: 75px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                            <span style="display: block; font-size: 11px; color: var(--primary-color); text-align: center; margin-top: 3px; font-weight: bold;">🔍 拡大表示</span>
                                        </summary>
                                        
                                        <!-- ポップアップで表示される巨大画像 -->
                                        <div style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); display: flex; align-items: center; justify-content: center; z-index: 9999;" onclick="this.parentElement.removeAttribute('open');">
                                            <div style="max-width: 90%; max-height: 90%; text-align: center;">
                                                <img src="<?php echo h($sub['thumbnail_url']); ?>" style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                                                <p style="color: #fff; margin-top: 12px; font-size: 14px; background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; display: inline-block;">画面のどこかをタップで閉じる ✖</p>
                                            </div>
                                        </div>
                                    </details>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--text-muted);">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;"><?php echo h($sub['created_at']); ?></td>
                            <td><a href="<?php echo h($sub['figma_url']); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--primary-color);">Figmaで開く</a></td>
                            <td><span class="score-badge"><?php echo h($sub['total_score']); ?> 点</span></td>
                            <td style="font-size: 14px;"><?php echo nl2br(h($sub['ai_feedback'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>