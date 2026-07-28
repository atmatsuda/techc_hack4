<?php
// public/index.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
$pdo = get_db_connection();
$stmt = $pdo->query('SELECT * FROM situations ORDER BY id ASC');
$situations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>気まずさ撃退! 即効トークおみくじ</title>
    <!-- Tailwind CSS CDN -->
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
        <!-- ヘッダー -->
        <div class="text-center mb-10">
            <span class="inline-block border border-amber-700 text-amber-800 text-xs tracking-widest px-4 py-1 rounded-full mb-3 bg-amber-50">参拝・コミュニケーションのお導き</span>
            <h1 class="text-3xl md:text-4xl font-bold text-stone-900 tracking-wider">気まずさ撃退！<br>即効トークおみくじ</h1>
            <p class="text-stone-600 mt-3 text-sm md:text-base">シチュエーションを選び、会話の糸口を引き当てましょう。</p>
        </div>

        <!-- メインフォーム（おみくじ箱風） -->
        <div class="bg-white shadow-xl rounded-2xl p-6 md:p-8 border-2 border-amber-900/10 relative">
            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-red-800 text-amber-100 px-6 py-1 rounded-full text-xs font-bold tracking-widest shadow">
                授与所・おみくじ処
            </div>

            <form action="result.php" method="GET" class="space-y-6 mt-2">
                <div>
                    <label class="block text-sm font-bold text-stone-700 mb-2">シチュエーション（場面）をお選びください</label>
                    <select name="situation_id" class="w-full px-4 py-3 rounded-lg border border-stone-300 focus:ring-2 focus:ring-red-800 focus:border-red-850 transition bg-stone-50 text-base">
                        <?php foreach ($situations as $sit): ?>
                            <option value="<?= h($sit['id']) ?>"><?= h($sit['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full bg-red-900 hover:bg-red-950 text-amber-50 font-bold py-4 px-6 rounded-xl transition duration-200 shadow-md tracking-widest text-lg flex items-center justify-center space-x-2">
                    <span>🎋 おみくじを引く</span>
                </button>
            </form>
        </div>

        <!-- フッターナビゲーション -->
        <div class="flex justify-center space-x-6 mt-8 text-sm font-bold text-amber-900">
            <a href="history.php" class="hover:underline">📖 引いたおみくじの履歴</a>
            <span class="text-stone-300">|</span>
            <a href="post.php" class="hover:underline">✍️ 話題を奉納（投稿）する</a>
        </div>
    </div>

    <footer class="text-center py-6 text-xs text-stone-500">
        &copy; 気まずさ撃退! 即効トークおみくじ All Rights Reserved.
    </footer>
</body>
</html>