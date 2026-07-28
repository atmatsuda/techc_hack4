<?php
// logout.php
require_once __DIR__ . '/functions/helpers.php';

session_start();

// セッション変数をすべて解除
$_SESSION = [];

// クッキーに保存されているセッションIDを削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// セッションを破棄
session_destroy();

// ログイン画面へリダイレクト
header('Location: login.php');
exit;