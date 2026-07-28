<?php
// public/index.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
$pdo = get_db_connection();
$stmt = $pdo->query('SELECT * FROM situations ORDER BY id ASC');
$situations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// シチュエーションごとの文字色やアクセントカラーの定義（残っているカテゴリに合わせて最適化）
function getCylinderThemeConfig($id, $name) {
    $nameLower = mb_strtolower($name);

    if ($id == 1 || strpos($nameLower, '初対面') !== false) {
        return [
            'text_color' => 'text-cyan-200',
            'accent_bg' => 'bg-cyan-400',
            'tag' => 'bg-blue-50 text-blue-900 border-blue-600',
        ];
    } elseif ($id == 2 || strpos($nameLower, 'デート') !== false) {
        return [
            'text_color' => 'text-pink-200',
            'accent_bg' => 'bg-pink-400',
            'tag' => 'bg-rose-50 text-rose-900 border-rose-600',
        ];
    } else {
        // 飲み会・その他（デフォルト）
        return [
            'text_color' => 'text-yellow-200',
            'accent_bg' => 'bg-yellow-400',
            'tag' => 'bg-orange-50 text-orange-900 border-orange-600',
        ];
    }
}
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
            /* 上部が晴れ渡った青空と白い雲、下部に向かって神社の境内をイメージさせる美しいグラデーション */
            background: linear-gradient(180deg, #38bdf8 0%, #e0f2fe 30%, #fef3c7 65%, #78350f 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        @keyframes shake {
            0% { transform: rotate(0deg) translate(0, 0); }
            20% { transform: rotate(-12deg) translate(-5px, 5px); }
            40% { transform: rotate(12deg) translate(5px, -5px); }
            60% { transform: rotate(-10deg) translate(-4px, 2px); }
            80% { transform: rotate(10deg) translate(4px, -2px); }
            100% { transform: rotate(0deg) translate(0, 0); }
        }
        .shaking {
            animation: shake 0.3s ease-in-out infinite;
        }
        .hex-cylinder {
            clip-path: polygon(15% 0%, 85% 0%, 100% 10%, 100% 90%, 85% 100%, 15% 100%, 0% 90%, 0% 10%);
        }
    </style>
</head>
<body class="min-h-screen text-stone-800 flex flex-col justify-between relative overflow-x-hidden">

    <!-- 明転（フラッシュ）用オーバーレイ -->
    <div id="flash-overlay" class="fixed inset-0 z-50 pointer-events-none opacity-0 transition-all duration-600 ease-out bg-white"></div>

    <div class="max-w-xl mx-auto px-4 py-8 md:py-12 w-full my-auto flex flex-col items-center bg-white/80 backdrop-blur-md rounded-3xl shadow-2xl border border-white/40 my-8">
        
        <!-- ヘッダータイトル -->
        <div class="text-center mb-6">
            <span id="header-tag" class="inline-block border text-xs tracking-widest px-3 py-0.5 rounded-full mb-2 font-semibold transition-colors duration-300">参拝・お導き</span>
            <h1 class="text-2xl md:text-3xl font-bold text-stone-900 tracking-wider">気まずさ撃退！ 即効トークおみくじ</h1>
        </div>

        <!-- フォーム -->
        <form id="omikuji-form" action="result.php" method="GET" class="w-full flex flex-col items-center">
            
            <!-- シチュエーション選択（上部・コンパクト） -->
            <div class="w-48 mb-6">
                <select id="situation-select" name="situation_id" class="w-full px-3 py-2 rounded-lg border border-stone-300 focus:ring-2 focus:ring-red-700 focus:border-red-700 transition bg-white text-sm font-medium text-stone-800 cursor-pointer shadow-sm text-center">
                    <?php foreach ($situations as $sit): ?>
                        <option value="<?= h($sit['id']) ?>"><?= h($sit['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- おみくじ筒 -->
            <div class="flex flex-col items-center mb-4">
                <div id="omikuji-cylinder" class="w-40 h-56 md:w-48 md:h-64 hex-cylinder bg-gradient-to-b from-amber-600 via-red-700 to-amber-950 border-amber-400 flex flex-col items-center justify-center relative cursor-pointer select-none transition-transform duration-300 hover:scale-105 active:scale-95 shadow-2xl border-2">
                    <span id="cylinder-text" class="font-bold text-3xl tracking-widest [writing-mode:vertical-rl] py-2 transition-colors drop-shadow-md">おみくじ</span>
                    <div id="cylinder-accent" class="absolute bottom-4 w-3 h-8 rounded-full shadow transition-colors"></div>
                </div>
                <span class="text-xs text-stone-600 mt-3 font-semibold">※ 箱をタップして引く</span>
            </div>
        </form>

        <!-- 下部ナビゲーションリンク -->
        <div class="flex justify-center space-x-6 mt-8 text-sm font-bold text-amber-900">
            <a href="history.php" class="hover:underline bg-white/60 px-3 py-1.5 rounded-lg border border-amber-900/20 shadow-sm">
                📖 履歴
            </a>
            <a href="post.php" class="hover:underline bg-white/60 px-3 py-1.5 rounded-lg border border-amber-900/20 shadow-sm">
                ✍️ 投稿する
            </a>
        </div>

    </div>

    <footer class="text-center py-6 text-xs text-white drop-shadow-md">
        &copy; 気まずさ撃退! 即効トークおみくじ All Rights Reserved.
    </footer>

    <!-- 動的文字カラー切り替え＆アニメーションスクリプト -->
    <script>
    const themes = {
        <?php foreach ($situations as $sit): 
            $conf = getCylinderThemeConfig($sit['id'], $sit['name']);
        ?>
        "<?= $sit['id'] ?>": {
            textColor: "<?= $conf['text_color'] ?>",
            accentBg: "<?= $conf['accent_bg'] ?>",
            tag: "<?= $conf['tag'] ?>"
        },
        <?php endforeach; ?>
    };

    document.addEventListener('DOMContentLoaded', () => {
        const cylinder = document.getElementById('omikuji-cylinder');
        const cylinderText = document.getElementById('cylinder-text');
        const cylinderAccent = document.getElementById('cylinder-accent');
        const headerTag = document.getElementById('header-tag');
        const flashOverlay = document.getElementById('flash-overlay');
        const select = document.getElementById('situation-select');

        if (!cylinder || !flashOverlay || !select) return;

        function updateTheme(id) {
            const theme = themes[id] || Object.values(themes)[0];

            // 文字色の変更
            cylinderText.className = "font-bold text-3xl tracking-widest [writing-mode:vertical-rl] py-2 transition-colors drop-shadow-md " + theme.textColor;
            cylinderText.textContent = "おみくじ";

            // 下部アクセントの色の変更
            cylinderAccent.className = "absolute bottom-4 w-3 h-8 rounded-full shadow transition-colors " + theme.accentBg;

            // ヘッダーのタグの色の変更
            headerTag.className = "inline-block border text-xs tracking-widest px-3 py-0.5 rounded-full mb-2 font-semibold transition-colors duration-300 " + theme.tag;
        }

        // 初期読み込み時
        updateTheme(select.value);

        // プルダウン変更時
        select.addEventListener('change', (e) => {
            updateTheme(e.target.value);
        });

        // タップ時アニメーション＆遷移
        cylinder.addEventListener('click', () => {
            if (cylinder.classList.contains('shaking')) return;

            cylinder.classList.add('shaking');

            setTimeout(() => {
                flashOverlay.classList.remove('opacity-0', 'pointer-events-none');
                flashOverlay.classList.add('opacity-100', 'bg-white');

                setTimeout(() => {
                    const situationId = select.value;
                    window.location.href = `result.php?situation_id=${situationId}`;
                }, 600);
            }, 1200);
        });
    });
    </script>
</body>
</html>