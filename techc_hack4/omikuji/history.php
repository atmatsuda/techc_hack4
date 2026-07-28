<?php
// public/history.php
session_start();
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>端末内履歴 - 気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>端末内でおみくじを引いた履歴</h1>
    <p>この端末（ブラウザ）で過去に引いたおみくじの履歴（直近20件）が表示されます。</p>

    <!-- 履歴一覧を表示するエリア -->
    <div id="history-container">
        <p id="no-history" style="display: none;">まだ履歴がありません。おみくじを引いてみましょう！</p>
        <ul id="history-list"></ul>
    </div>

    <p>
        <button id="clear-history" type="button" style="display: none;">履歴をすべて削除する</button>
    </p>

    <p><a href="index.php">トップに戻る</a></p>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const historyListElem = document.getElementById('history-list');
            const noHistoryElem = document.getElementById('no-history');
            const clearBtnElem = document.getElementById('clear-history');

            // ローカルストレージから履歴を取得
            const historyList = JSON.parse(localStorage.getItem('omikuji_history')) || [];

            if (historyList.length === 0) {
                noHistoryElem.style.display = 'block';
                clearBtnElem.style.display = 'none';
            } else {
                noHistoryElem.style.display = 'none';
                clearBtnElem.style.display = 'inline-block';

                // 履歴を1件ずつHTML要素として生成して表示（XSS対策としてtextContentを使用）
                historyList.forEach(item => {
                    const li = document.createElement('li');
                    li.style.marginBottom = '15px';
                    
                    const contentP = document.createElement('p');
                    contentP.innerHTML = `<strong>話題:</strong> `;
                    const contentSpan = document.createElement('span');
                    contentSpan.textContent = item.content;
                    contentP.appendChild(contentSpan);

                    const metaSmall = document.createElement('small');
                    metaSmall.textContent = `投稿者: ${item.contributor} ／ 引いた日時: ${item.date}`;
                    
                    li.appendChild(contentP);
                    li.appendChild(metaSmall);
                    historyListElem.appendChild(li);
                });
            }

            // 履歴削除ボタンの処理
            clearBtnElem.addEventListener('click', () => {
                if (confirm('本当にこの端末の履歴をすべて削除しますか？')) {
                    localStorage.removeItem('omikuji_history');
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>