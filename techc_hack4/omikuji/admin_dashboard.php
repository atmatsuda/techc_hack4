<?php
// public/admin_dashboard.php
require_once '../includes/auth.php';
require_admin_login(); // アクセス制限チェック

require_once '../config/db.php';
require_once '../includes/functions.php';

$pdo = get_db_connection();

// 1. ステータス変更処理（POST送信時）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['topic_id'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        exit('トークンが無効です。');
    }
    $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($topic_id) {
        $is_approved = ($action === 'approve') ? 1 : 0;
        $update_stmt = $pdo->prepare('UPDATE topics SET is_approved = :is_approved WHERE id = :id');
        $update_stmt->bindValue(':is_approved', $is_approved, PDO::PARAM_INT);
        $update_stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);
        $update_stmt->execute();
    }
    header('Location: admin_dashboard.php?key=secret123');
    exit;
}

$csrf_token = generate_csrf_token();

// 2. 投稿一覧の取得
$stmt = $pdo->query('SELECT topics.*, situations.name AS situation_name FROM topics JOIN situations ON topics.situation_id = situations.id ORDER BY topics.id DESC');
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. インサイト集計の取得（SQL集計テキスト表示）
$insight_stmt = $pdo->query('SELECT situations.name, COUNT(topics.id) AS topic_count, SUM(topics.draw_count) AS total_draws FROM situations LEFT JOIN topics ON situations.id = topics.situation_id GROUP BY situations.id');
$insights = $insight_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者ダッシュボード</title>
</head>
<body>
    <h1>管理者ダッシュボード</h1>
    <p><a href="index.php">サイトトップへ戻る</a></p>

    <h2>インサイト（利用状況集計）</h2>
    <table border="1">
        <tr>
            <th>シチュエーション</th>
            <th>登録話題数</th>
            <th>累計引かれた回数</th>
        </tr>
        <?php foreach ($insights as $ins): ?>
        <tr>
            <td><?= h($ins['name']) ?></td>
            <td><?= h($ins['topic_count']) ?> 件</td>
            <td><?= h($ins['total_draws'] ?? 0) ?> 回</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>投稿話題の精査管理</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>カテゴリ</th>
            <th>話題</th>
            <th>投稿者</th>
            <th>状態</th>
            <th>操作</th>
        </tr>
        <?php foreach ($topics as $t): ?>
        <tr>
            <td><?= h($t['id']) ?></td>
            <td><?= h($t['situation_name']) ?></td>
            <td><?= h($t['content']) ?></td>
            <td><?= h($t['contributor_name']) ?></td>
            <td><?= $t['is_approved'] == 1 ? '承認済' : '未承認' ?></td>
            <td>
                <form action="admin_dashboard.php?key=secret123" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                    <input type="hidden" name="topic_id" value="<?= h($t['id']) ?>">
                    <?php if ($t['is_approved'] == 1): ?>
                        <button type="submit" name="action" value="hide">非表示にする</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="approve">承認する</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>