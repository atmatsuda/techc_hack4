<?php
// public/post.php
session_start();
require_once '../includes/functions.php'; // パスが正しく ../ から始まっているか確認してください

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>話題投稿 - 気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>話題を投稿する</h1>
    <form action="post_process.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= ($csrf_token) ?>">
        
       <div>
            <label>シチュエーション選択:</label>
            <select name="situation_id">
                <option value="1">初対面</option>
                <option value="2">デート</option>
                <option value="3">飲み会</option>
                <option value="4">ビジネス・会議</option>
                <option value="5">学校・授業</option>
            </select>
        </div>
        
        <button type="submit">投稿する</button>
    </form>
    <p><a href="index.php">トップに戻る</a></p>
</body>
</html>