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
$stmt = $pdo->prepare('SELECT * FROM topics WHERE situation_id = :situation_id AND is_approved = 1 ORDER BY RAND() LIMIT 1');
$stmt->bindValue(':situation_id', $situation_id, PDO::PARAM_INT);
$stmt->execute();
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if ($topic) {
    $update_stmt = $pdo->prepare('UPDATE topics SET draw_count = draw_count + 1 WHERE id = :id');
    $update_stmt->bindValue(':id', $topic['id'], PDO::PARAM_INT);
    $update_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>おみくじ結果 - 気まずさ撃退! 即効トークおみくじ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@500;700&display=swap');
        body {
            font-family: 'Shippori Mincho', serif;
        }
    </style>
</head>
<body class="bg-[#f9f6f0] bg-[radial-gradient(#e5decc_1px,transparent_1px)] [background-size:16px_16px] min-h-screen text-stone-800 flex flex-col justify-between">

    <div class="max-w-xl mx-auto px-4 py-12 w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-stone-900 tracking-widest">おみくじ結果</h1>
        </div>

        <?php if ($topic): ?>
            <!-- おみくじの紙（短冊）風カード -->
            <div class="bg-[#fffdf9] shadow-xl rounded-2xl p-8 border-2 border-amber-900/20 relative overflow-hidden">
                <div class="absolute top-0 right-0 bg-red-900 text-amber-100 text-xs px-4 py-1 rounded-bl-xl font-bold tracking-widest">
                    神託の話題
                </div>

                <div class="mb-8 mt-2">
                    <p class="text-xl md:text-2xl font-bold text-stone-900 leading-relaxed" id="topic-content"><?= h($topic['content']) ?></p>
                </div>

                <div class="border-t border-amber-900/10 pt-4 flex justify-between items-center text-sm text-stone-600">
                    <span>詠み人（投稿者）: <strong class="text-stone-800" id="topic-contributor"><?= h($topic['contributor_name']) ?></strong></span>
                </div>
            </div>

            <script>
                const topicContent = document.getElementById('topic-content').textContent;
                const topicContributor = document.getElementById('topic-contributor').textContent;
                const drawnAt = new Date().toLocaleString();
                const historyItem = { content: topicContent, contributor: topicContributor, date: drawnAt };
                
                let historyList = JSON.parse(localStorage.getItem('omikuji_history')) || [];
                historyList.unshift(historyItem);
                if (historyList.length > 20) { historyList.pop(); }
                localStorage.setItem('omikuji_history', JSON.stringify(historyList));
            </script>
        <?php else: ?>
            <div class="bg-white shadow-md rounded-2xl p-6 text-center border border-stone-200">
                <p class="text-stone-600">現在、このシチュエーションに該当する承認済みの話題はありません。</p>
                <a href="post.php" class="text-red-900 font-bold hover:underline mt-2 inline-block">新しい話題を投稿してみましょう！</a>
            </div>
        <?php endif; ?>

        <!-- アクションボタン群 -->
        <div class="mt-8 space-y-3">
            <a href="index.php?situation_id=<?= $situation_id ?>" class="block w-full bg-red-900 hover:bg-red-950 text-amber-50 text-center font-bold py-3.5 px-6 rounded-xl transition duration-200 shadow-md tracking-wider">
                もう一度おみくじを引く
            </a>
            <div class="grid grid-cols-2 gap-3">
                <a href="index.php" class="block bg-white hover:bg-stone-50 text-stone-800 border border-stone-300 text-center font-bold py-3 px-4 rounded-xl transition">
                    社頭（トップ）に戻る
                </a>
                <a href="history.php" class="block bg-white hover:bg-stone-50 text-stone-800 border border-stone-300 text-center font-bold py-3 px-4 rounded-xl transition">
                    履歴を見る
                </a>
            </div>
        </div>
    </div>

    <footer class="text-center py-6 text-xs text-stone-500">
        &copy; 気まずさ撃退! 即効トークおみくじ All Rights Reserved.
    </footer>
</body>
</html>