<?php
// figma_redirect.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login(); // ログインしていない場合はログイン画面へ

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF対策用のランダムな文字列（state）を作成してセッションに保存
$state = bin2hex(random_bytes(16));
$_SESSION['figma_oauth_state'] = $state;

// Figma OAuth 認可URLの構築
$params = [
    'client_id'     => FIGMA_CLIENT_ID,
    'redirect_uri'  => FIGMA_REDIRECT_URI,
    'scope'         => 'file_content:read', // サムネイル取得用の権限
    'state'         => $state,
    'response_type' => 'code'
];

$authUrl = 'https://www.figma.com/oauth?' . http_build_query($params);

// Figmaの認証画面へリダイレクト
header('Location: ' . $authUrl);
exit;