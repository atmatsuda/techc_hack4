<?php
// public/history.php
session_start();
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>引いたおみくじの履歴 - 気まずさ撃退! 即効トークおみくじ</title>
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
            <span class="inline-block border border-amber-700 text-amber-800 text-xs tracking-widest px-4 py-1 rounded-full mb-3 bg-amber-50">参拝の足跡</span>
            <h1 class="text-3xl font-bold text-stone-900 tracking-wider">引いたおみくじの履歴</h1>
            <p class="text-stone-600 mt-2 text-sm">この端末で過去に授かったおみくじの履歴（直近20件）です。</p>
        </div>

        <!-- 履歴表示エリア -->
        <div id="history-container" class="space-y-4">
            <p id="no-history" class="text-center text-stone-500 py-10 bg-white rounded-2xl border border-stone-200" style="display: none;">
                まだ履歴がありません。おみくじを引いてみましょう！
            </p>
            <ul id="history-list" class="space-y-4"></ul>
        </div>

        <!-- 削除ボタン & 戻るリンク -->
        <div class="mt-8 space-y-3">
            <button id="clear-history" type="button" class="w-full bg-stone-200 hover:bg-stone-300 text-stone-700 font-bold py-3 px-6 rounded-xl transition duration-200 text-sm tracking-wider" style="display: none;">
                履歴をすべて消納（削除）する
            </button>
            <a href="index.php" class="block w-full bg-red-900 hover:bg-red-950 text-amber-50 text-center font-bold py-3.5 px-6 rounded-xl transition duration-200 shadow-md tracking-wider">
                社頭（トップ）に戻る
            </a>
        </div>
    </div>

    <footer class="text-center py-6 text-xs text-stone-500">
        &copy; 気まずさ撃退! 即効トークおみくじ All Rights Reserved.
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const historyListElem = document.getElementById('history-list');
        const noHistoryElem = document.getElementById('no-history');
        const clearBtnElem = document.getElementById('clear-history');
        
        let historyList = [];
        try {
            historyList = JSON.parse(localStorage.getItem('omikuji_history')) || [];
        } catch (e) {
            historyList = [];
        }

        if (historyList.length === 0) {
            noHistoryElem.style.display = 'block';
            clearBtnElem.style.display = 'none';
        } else {
            noHistoryElem.style.display = 'none';
            clearBtnElem.style.display = 'block';
            
            historyList.forEach(item => {
                const li = document.createElement('li');
                li.className = "bg-white p-5 rounded-2xl border border-amber-900/10 shadow-sm relative overflow-hidden";
                
                const contentP = document.createElement('p');
                contentP.className = "text-stone-900 font-bold text-lg mb-3 leading-relaxed";
                contentP.innerHTML = `<span class="text-xs text-amber-800 font-normal block mb-1">神託の話題</span>${escapeHtml(item.content)}`;
                
                const metaSmall = document.createElement('div');
                metaSmall.className = "flex justify-between items-center text-xs text-stone-500 border-t border-stone-100 pt-3 mt-2";
                metaSmall.innerHTML = `<span>詠み人: ${escapeHtml(item.contributor || '匿名')}</span><span>日時: ${escapeHtml(item.date)}</span>`;
                
                li.appendChild(contentP);
                li.appendChild(metaSmall);
                historyListElem.appendChild(li);
            });
        }

        clearBtnElem.addEventListener('click', () => {
            if (confirm('本当に履歴をすべて削除しますか？')) {
                localStorage.removeItem('omikuji_history');
                location.reload();
            }
        });
    });

    // 簡易HTMLエスケープ関数（XSS対策用）
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>"`']/g, (match) => {
            const escapeTable = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return escapeTable[match];
        });
    }
    </script>
</body>
</html>