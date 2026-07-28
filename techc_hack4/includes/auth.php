<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_admin_login() {
    // 初回アクセス時のURLキーチェック
    if (isset($_GET['key']) && $_GET['key'] === 'secret123') {
        $_SESSION['admin_auth'] = true;
        // セッションハイジャック対策
        session_regenerate_id(true);
    }

    // セッションに認証フラグがない場合は、トップページへ強制送還（存在を秘匿する）
    if (empty($_SESSION['admin_auth'])) {
        header('Location: index.php');
        exit;
    }
}