<?php
// config/db.php

function get_db_connection() {
    $dsn = 'mysql:host=localhost;dbname=techc_hack4_db;charset=utf8mb4';
    $user = 'root'; // 開発環境のユーザー名（必要に応じて変更）
    $password = ''; // 開発環境のパスワード（必要に応じて変更）

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // セキュリティ対策: エラーの詳細は画面に出さず、汎用メッセージのみ表示します
        exit('システムエラーが発生しました。時間を置いて再度お試しください。');
    }
}