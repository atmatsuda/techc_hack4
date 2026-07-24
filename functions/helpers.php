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

/**
 * Figma URLからファイルキーを抽出し、Figma APIからサムネイル画像URLを取得する
 * 
 * @param string $figmaUrl
 * @return string|null
 */
/**
 * Figma URLからファイルキーを抽出し、Figma APIからサムネイル画像URLを取得する
 */
function fetch_figma_thumbnail($figmaUrl) {
    $figmaToken = defined('FIGMA_ACCESS_TOKEN') ? FIGMA_ACCESS_TOKEN : '';
    
    if (empty($figmaToken)) {
        return null;
    }

    // design や file など、Figmaの各種URL形式からキーを抽出
    if (!preg_match('/figma\.com\/(?:file|design|proto)\/([a-zA-Z0-9]+)/', $figmaUrl, $matches)) {
        return null;
    }

    $fileKey = $matches[1];
    $apiUrl = "https://api.figma.com/v1/files/{$fileKey}?depth=1";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false, // ローカル開発環境でのSSLエラー回避
        CURLOPT_HTTPHEADER => [
            "X-Figma-Token: {$figmaToken}"
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        return $data['thumbnailUrl'] ?? null;
    }

    return null;
}