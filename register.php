<?php
// register.php
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
    $displayName = trim($_POST['display_name'] ?? '');

    if ($email === '') {
        $errors[] = 'メールアドレスを入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '正しいメールアドレスの形式で入力してください。';
    }

    if ($password === '') {
        $errors[] = 'パスワードを入力してください。';
    } elseif (mb_strlen($password) < 8) {
        $errors[] = 'パスワードは8文字以上で入力してください。';
    }

    if ($displayName === '') {
        $errors[] = '表示名を入力してください。';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, display_name) VALUES (:email, :password_hash, :display_name)');
            $stmt->execute([
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':display_name' => $displayName,
            ]);

            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'このメールアドレスは既に登録されています。';
            } else {
                error_log('Register Error: ' . $e->getMessage());
                $errors[] = '登録処理中にエラーが発生しました。';
            }
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
    <title>新規会員登録 - デザトレ</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 90vh;">
    <div class="container" style="width: 100%; max-width: 420px; padding: 40px;">
        <h1 style="text-align: center; margin-bottom: 30px;">新規会員登録</h1>

        <?php if (!empty($errors)): ?>
            <ul style="color: #ef4444; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 12px 20px; border-radius: 6px; font-size: 14px; list-style-type: none;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
            
            <div class="form-group">
                <label for="display_name">表示名</label>
                <input type="text" id="display_name" name="display_name" value="<?php echo h($_POST['display_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">パスワード（8文字以上）</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" style="width: 100%; margin-top: 10px;">登録する</button>
        </form>
        
        <p style="text-align: center; margin-top: 25px; font-size: 14px;">
            <a href="login.php" style="color: var(--text-muted); text-decoration: none;">すでにアカウントをお持ちの方はこちら</a>
        </p>
    </div>
</body>
</html>