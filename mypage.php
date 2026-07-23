<?php
// mypage.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login();

$userId = $_SESSION['user_id'];

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
                        <th>日時</th>
                        <th>Figma リンク</th>
                        <th>AIスコア</th>
                        <th>フィードバック</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
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