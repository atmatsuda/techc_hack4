<?php
// public/post.php
session_start();
require_once '../includes/functions.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>話題の奉納（投稿） - 気まずさ撃退! 即効トークおみくじ</title>
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
        <div class="text-center mb-8">
            <span class="inline-block border border-amber-700 text-amber-800 text-xs tracking-widest px-4 py-1 rounded-full mb-3 bg-amber-50">言葉の奉納所</span>
            <h1 class="text-3xl font-bold text-stone-900 tracking-wider">新しい話題を投稿する</h1>
            <p class="text-stone-600 mt-2 text-sm">気まずさを撃退するあなたの知恵やユーモアを分かち合いましょう。</p>
        </div>

        <!-- 投稿フォームカード -->
        <div class="bg-white shadow-xl rounded-2xl p-6 md:p-8 border border-amber-900/10 relative">
            <form action="post_process.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                
                <div>
                    <label class="block text-sm font-bold text-stone-700 mb-2">シチュエーション選択</label>
                    <select name="situation_id" class="w-full px-4 py-3 rounded-lg border border-stone-300 focus:ring-2 focus:ring-red-800 focus:border-red-800 transition bg-stone-50 text-base">
                        <option value="1">初対面</option>
                        <option value="2">デート</option>
                        <option value="3">飲み会</option>
                        <option value="4">ビジネス・会議</option>
                        <option value="5">学校・授業</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-stone-700 mb-2">詠み人（ニックネーム / 最大15文字）</label>
                    <input type="text" name="contributor_name" maxlength="15" required placeholder="例：トークの神様" class="w-full px-4 py-3 rounded-lg border border-stone-300 focus:ring-2 focus:ring-red-800 focus:border-red-800 transition bg-stone-50 text-base">
                </div>

                <div>
                    <label class="block text-sm font-bold text-stone-700 mb-2">話題の本文（最大50文字）</label>
                    <input type="text" name="content" maxlength="50" required placeholder="例：もし一日だけ動物になれるとしたら何になる？" class="w-full px-4 py-3 rounded-lg border border-stone-300 focus:ring-2 focus:ring-red-800 focus:border-red-800 transition bg-stone-50 text-base">
                </div>

                <button type="submit" class="w-full bg-red-900 hover:bg-red-950 text-amber-50 font-bold py-4 px-6 rounded-xl transition duration-200 shadow-md tracking-widest text-lg">
                    ✨ 話題を奉納する
                </button>
            </form>
        </div>

        <!-- 戻るリンク -->
        <div class="mt-8 text-center">
            <a href="index.php" class="text-amber-900 font-bold hover:underline text-sm">
                ← 社頭（トップ）に戻る
            </a>
        </div>
    </div>

    <footer class="text-center py-6 text-xs text-stone-500">
        &copy; 気まずさ撃退! 即効トークおみくじ All Rights Reserved.
    </footer>
</body>
</html>