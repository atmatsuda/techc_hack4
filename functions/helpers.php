<?php
// functions/helpers.php

/**
 * 1. XSS対策：エスケープ処理関数
 */
function h(?string $str): string {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 2. CSRF対策：ワンタイムトークンの生成
 */
function generate_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        // 暗号学的に安全なランダムバイトからトークンを生成
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 3. CSRF対策：トークンの比較検証
 */
function validate_csrf_token(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    // タイミング攻撃を防ぐハッシュ比較関数を使用
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 4. アクセス制御：ログイン必須チェック
 */
function require_login(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}