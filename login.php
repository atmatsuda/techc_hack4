<?php
// login.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('不正なリクエストです。');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'メールアドレスとパスワードを入力してください。';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['display_name'] = $user['display_name'];

                $updateStmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = :user_id');
                $updateStmt->execute([':user_id' => $user['user_id']]);

                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'メールアドレスまたはパスワードに誤りがあります。';
            }
        } catch (PDOException $e) {
            error_log('Login Error: ' . $e->getMessage());
            $errors[] = 'ログイン処理中にエラーが発生しました。';
        }
    }
}
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - デザトレ</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 90vh;">
    <div class="container" style="width: 100%; max-width: 420px; padding: 40px;">
        <h1 style="text-align: center; margin-bottom: 30px;">ログイン</h1>

        <?php if (!empty($errors)): ?>
            <ul style="color: #ef4444; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 12px 20px; border-radius: 6px; font-size: 14px; list-style-type: none;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
            
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" style="width: 100%; margin-top: 10px;">ログイン</button>
        </form>
        
        <p style="text-align: center; margin-top: 25px; font-size: 14px;">
            <a href="register.php" style="color: var(--text-muted); text-decoration: none;">新規会員登録はこちら</a>
        </p>
    </div>
</body>
</html>