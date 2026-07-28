<?php
// public/index.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

$pdo = get_db_connection();

// Level 2: 自身でSQLを組み立ててデータを取得しましょう
$stmt = $pdo->query('SELECT * FROM situations ORDER BY id ASC');
$situations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>シチュエーションを選んでおみくじを引く</h1>
    <form action="result.php" method="GET">
        <select name="situation_id">
            <?php foreach ($situations as $sit): ?>
                <!-- h()関数を使って安全に出力 -->
                <option value="<?= ($sit['id']) ?>"><?= ($sit['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">おみくじを引く</button>
    </form>
    
    <a href="history.php">前に引いた内容を見る</a> | 
    <a href="post.php">新しい話題を投稿する</a>
</body>
</html>