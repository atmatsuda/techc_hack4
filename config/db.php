<?php
// config/db.php

$host = 'localhost';
$dbname = 'desatre_db'; // ご自身のデータベース名に合わせて変更してください
$username = 'root';
$password = '';        // XAMPPのデフォルトは空文字ですが、設定している場合は変更してください

try {
    // 静的プレースホルダーを使用し、SQLインジェクションを防止するセキュアなPDO接続
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // PDO側でのエミュレートを無効化（SQLi対策）
    ]);
} catch (PDOException $e) {
    // 生のエラーメッセージは画面に出さずログへ出力（情報漏洩対策）
    error_log('DB Connection Error: ' . $e->getMessage());
    
    // ユーザーには汎用エラーメッセージのみ表示
    die('システムエラーが発生しました。時間を置いて再度お試しください。');
}