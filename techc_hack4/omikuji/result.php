<?php
// public/result.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

$situation_id = filter_input(INPUT_GET, 'situation_id', FILTER_VALIDATE_INT);
if (!$situation_id) {
    exit('無効なシチュエーションです。');
}

$pdo = get_db_connection();

// 1. 指定されたシチュエーションかつ承認済みの話題をランダムに1件取得（プリペアドステートメント利用）
$stmt = $pdo->prepare('SELECT * FROM topics WHERE situation_id = :situation_id AND is_approved = 1 ORDER BY RAND() LIMIT 1');
$stmt->bindValue(':situation_id', $situation_id, PDO::PARAM_INT);
$stmt->execute();
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if ($topic) {
    // 2. 引かれた回数（draw_count）をインクリメント
    $update_stmt = $pdo->prepare('UPDATE topics SET draw_count = draw_count + 1 WHERE id = :id');
    $update_stmt->bindValue(':id', $topic['id'], PDO::PARAM_INT);
    $update_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>おみくじ結果 - 気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>おみくじ結果</h1>
    <?php if ($topic): ?>
        <div class="card">
            <p><strong>話題:</strong> <span id="topic-content"><?= h($topic['content']) ?></span></p>
            <p><small>投稿者: <span id="topic-contributor"><?= h($topic['contributor_name']) ?></span></small></p>
        </div>

        <!-- 端末内履歴（LocalStorage）に保存するためのJavaScript処理 -->
        <script>
            // PHPから出力されたデータをJavaScriptの変数に渡す
            const topicContent = document.getElementById('topic-content').textContent;
            const topicContributor = document.getElementById('topic-contributor').textContent;
            const drawnAt = new Date().toLocaleString();

            // 保存する履歴オブジェクト
            const historyItem = {
                content: topicContent,
                contributor: topicContributor,
                date: drawnAt
            };

            // ローカルストレージから既存の履歴を取得（なければ空配列）
            let historyList = JSON.parse(localStorage.getItem('omikuji_history')) || [];

            // 新しい履歴を先頭に追加（最大20件まで保持）
            historyList.unshift(historyItem);
            if (historyList.length > 20) {
                historyList.pop();
            }

            // ローカルストレージに保存し直す
            localStorage.setItem('omikuji_history', JSON.stringify(historyList));
        </script>
    <?php else: ?>
        <p>現在、このシチュエーションに該当する承認済みの話題はありません。新しい話題を投稿してみましょう！</p>
    <?php endif; ?>

    <!-- ナビゲーションリンクエリア -->
    <p style="margin-top: 20px;"> 
        <a href="index.php">🏠 トップに戻る</a> | 
    </p>
    <p style="margin-top: 20px;"> 
      <a href="history.php">📜 端末内履歴を見る</a>
    </p>
    <p>
        <a href="post.php">✏️ 新しい話題を投稿する</a>
    </p>

    <!-- 端末内履歴（LocalStorage）に保存するためのJavaScript処理（※そのまま残してください） -->
    <?php if ($topic): ?>
        <script>
            // PHPから出力されたデータをJavaScriptの変数に渡す
            const topicContent = document.getElementById('topic-content').textContent;
            const topicContributor = document.getElementById('topic-contributor').textContent;
            const drawnAt = new Date().toLocaleString();

            // 保存する履歴オブジェクト
            const historyItem = {
                content: topicContent,
                contributor: topicContributor,
                date: drawnAt
            };

            // ローカルストレージから既存の履歴を取得（なければ空配列）
            let historyList = JSON.parse(localStorage.getItem('omikuji_history')) || [];

            // 新しい履歴を先頭に追加（最大20件まで保持）
            historyList.unshift(historyItem);
            if (historyList.length > 20) {
                historyList.pop();
            }

            // ローカルストレージに保存し直す
            localStorage.setItem('omikuji_history', JSON.stringify(historyList));
        </script>
    <?php endif; ?>
</body>
</html>