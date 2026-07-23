<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login();

try {
    $sql = 'SELECT s.*, u.display_name, t.title AS topic_title 
            FROM submissions s
            JOIN users u ON s.user_id = u.user_id
            JOIN topics t ON s.topic_id = t.topic_id
            ORDER BY s.created_at DESC';
    $stmt = $pdo->query($sql);
    $submissions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Gallery Error: ' . $e->getMessage());
    $submissions = [];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ギャラリー - デザトレ</title>
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
        <h1>みんなの提出作品（ギャラリー）</h1>
        <p>他のユーザーの作品やAIフィードバックを参考にしてみましょう。</p>

        <?php if (empty($submissions)): ?>
            <p>まだ提出された作品はありません。</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($submissions as $sub): ?>
                    <div class="card">
                        <h3><?php echo h($sub['topic_title']); ?></h3>
                        <p style="color: var(--text-muted); font-size: 14px;">制作者: <?php echo h($sub['display_name']); ?></p>
                        <p><span class="score-badge">AIスコア: <?php echo h($sub['total_score']); ?>点</span></p>
                        <p style="font-size: 14px; margin: 15px 0;"><?php echo nl2br(h($sub['ai_feedback'])); ?></p>
                        <p>
                            <a href="<?php echo h($sub['figma_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn" style="padding: 8px 16px; font-size: 14px;">
                                Figmaで見る
                            </a>
                        </p>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 0;"><?php echo h($sub['created_at']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>