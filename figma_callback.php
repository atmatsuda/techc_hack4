<?php
// figma_callback.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$code  = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

// CSRF対策（state検証）
if (empty($state) || empty($_SESSION['figma_oauth_state']) || !hash_equals($_SESSION['figma_oauth_state'], $state)) {
    unset($_SESSION['figma_oauth_state']);
    die('不正なリクエスト（state不一致）です。マイページからやり直してください。');
}
unset($_SESSION['figma_oauth_state']);

if (empty($code)) {
    die('Figma認証コードの取得に失敗しました。');
}

// ✅ 正式な Figma OAuth トークン取得APIエンドポイント
$tokenUrl = 'https://api.figma.com/v1/oauth/token';

// POSTボディに送るパラメータ（Client ID / Secret をここに含める）
$postData = [
    'client_id'     => FIGMA_CLIENT_ID,
    'client_secret' => FIGMA_CLIENT_SECRET,
    'redirect_uri'  => FIGMA_REDIRECT_URI,
    'code'          => $code,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

// XAMPP等のローカル環境対策（SSL証明書検証スキップ）
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// アクセストークンが取れた場合
if (isset($data['access_token'])) {
    $accessToken  = $data['access_token'];
    $refreshToken = $data['refresh_token'] ?? null;
    $figmaUserId  = $data['user_id'] ?? null;
    $userId       = $_SESSION['user_id'];

    try {
        // usersテーブルに保存
        $stmt = $pdo->prepare("
            UPDATE users 
            SET figma_access_token = :access_token,
                figma_refresh_token = :refresh_token,
                figma_id = :figma_id
            WHERE user_id = :user_id
        ");
        $stmt->execute([
            ':access_token'  => $accessToken,
            ':refresh_token' => $refreshToken,
            ':figma_id'      => $figmaUserId,
            ':user_id'       => $userId
        ]);

        // 成功通知をつけてマイページへ戻る
        header('Location: mypage.php?figma_success=1');
        exit;

    } catch (PDOException $e) {
        error_log('Figma Token Save Error: ' . $e->getMessage());
        die('トークンのDB保存中にエラーが発生しました。');
    }
} else {
    echo '<h3>Figmaから返ってきたエラー内容：</h3>';
    var_dump($response);
    die();
}