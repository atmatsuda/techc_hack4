<?php
// public/post_process.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('不正なアクセスです。');
}

// CSRFトークン検証
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    exit('トークンが無効です。');
}

$situation_id = filter_input(INPUT_POST, 'situation_id', FILTER_VALIDATE_INT);
$contributor_name = trim($_POST['contributor_name'] ?? '匿名');
$content = trim($_POST['content'] ?? '');

if (!$situation_id || $content === '') {
    exit('入力データが不正です。');
}

$pdo = get_db_connection();

// SQLインジェクションを防ぐためのプリペアドステートメント
$stmt = $pdo->prepare('INSERT INTO topics (situation_id, content, contributor_name, is_approved, created_at) VALUES (:situation_id, :content, :contributor_name, 0, NOW())');
$stmt->bindValue(':situation_id', $situation_id, PDO::PARAM_INT);
$stmt->bindValue(':content', $content, PDO::PARAM_STR);
$stmt->bindValue(':contributor_name', $contributor_name, PDO::PARAM_STR);
$stmt->execute();

// PRGパターン：投稿完了画面へリダイレクト
header('Location: post_thanks.php');
exit;