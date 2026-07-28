サポートレベル「レベル2〜3（初級〜中級：セキュリティコアロジック提示＋スケルトン構築）」の選択ですね！

設計書に基づき、重要セキュリティ対策のコアロジックを提示しつつ、全体の構造はスケルトンと`// TODO:`コメントで組み立てる『セキュア実装手順書』を作成しました。

以下の内容をコピーして、ローカル環境に **`05_implement_guide.md`** として保存してください。

---

# デザトレ（DesaTre） セキュア実装手順書（レベル2〜3：初級・中級向け）

## 1. 実装の全体方針・進め方

本手順書は、詳細設計書（`03_detailed_design.md`）に基づいて安全なWEBアプリケーションを構築するためのガイドです。
セキュリティ上極めて重要なコアロジック（PDO接続、エスケープ処理、CSRFトークン検証、セッション再生成など）のサンプルコードを提示しています。その他の基本構造やHTML組み込み処理はスケルトン（骨組み）として提示していますので、`// TODO:` コメントの指示に従ってコードを自力で補完・結合してください。

### 実装の優先順序

1. **基盤作成：** DB接続（`config/db.php`）＆ 共通関数（`functions/helpers.php`）
2. **認証機能：** 会員登録（`register.php`） → ログイン（`login.php`） → ログアウト（`logout.php`）
3. **メイン機能：** お題・提出フォーム（`index.php`） → 提出処理（`submission_process.php`）
4. **閲覧機能：** マイページ（`mypage.php`） → 匿名ギャラリー（`gallery.php`）

---

## 2. ディレクトリ構造の確認

開発を開始する前に、`desatre` フォルダ配下が以下の構成になっていることを確認してください。

```text
desatre/
├── config/
│   └── db.php
├── functions/
│   └── helpers.php
├── css/
│   └── style.css
├── index.php
├── submission_process.php
├── gallery.php
├── mypage.php
├── register.php
├── login.php
└── logout.php

```

---

## 3. 各ファイルのセキュア実装手順・スケルトン

### Step 1: データベース接続の設定（`config/db.php`）

> **目的：** SQLi防止およびエラー情報の保護（生例外メッセージを画面に出さずログ出力する）。

```php
<?php
// config/db.php

$host = 'localhost';
$dbname = 'desatre_db';
$username = 'root';
$password = ''; // 環境に合わせて変更

try {
    // セキュアなPDOインスタンスの生成
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // 静的プレースホルダーを使用（SQLi対策）
    ]);
} catch (PDOException $e) {
    // 生のエラーメッセージはサーバーログへ記録（情報漏洩防止）
    error_log('DB Connection Error: ' . $e->getMessage());
    
    // ユーザーには汎用メッセージのみを表示して処理中断
    die('システムエラーが発生しました。時間を置いて再度お試しください。');
}

```

---

### Step 2: セキュリティ共通関数の実装（`functions/helpers.php`）

> **目的：** XSS対策、CSRF対策、および認証状態チェックの共通化。

```php
<?php
// functions/helpers.php

// 1. XSS対策：エスケープ処理関数
function h(?string $str): string {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 2. CSRF対策：ワンタイムトークンの生成
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

// 3. CSRF対策：トークンの比較検証
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

// 4. アクセス制御：ログイン必須チェック
function require_login(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

```

---

### Step 3: 新規会員登録機能（`register.php`）

> **目的：** パスワードの安全なハッシュ化（`password_hash`）とSQLi対策。

```php
<?php
// register.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

session_start();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF検証
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('不正なリクエストです。');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');

    // TODO: バリデーション処理を実装してください
    // - 各項目が空でないか
    // - メールアドレスの形式チェック (filter_var)
    // - パスワードの桁数チェック (8文字以上など)

    if (empty($errors)) {
        // パスワードの安全なハッシュ化
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // TODO: PDOプリペアドステートメントで users テーブルに INSERT する処理を記述してください
            // SQL文例: INSERT INTO users (email, password_hash, display_name) VALUES (:email, :password_hash, :display_name)
            
            // 登録成功後、login.php へリダイレクト
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            // メールアドレスの重複エラー（1062）などの判定
            if ($e->getCode() == 23000) {
                $errors[] = 'このメールアドレスは既に登録されています。';
            } else {
                error_log($e->getMessage());
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
    <title>新規会員登録 - デザトレ</title>
</head>
<body>
    <h1>新規会員登録</h1>
    <!-- TODO: エラーメッセージがある場合に表示する処理 -->
    
    <form action="register.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
        <!-- TODO: email, password, display_name の入力フォームを構成してください -->
        <button type="submit">登録する</button>
    </form>
</body>
</html>

```

---

### Step 4: ログイン機能（`login.php`）

> **目的：** セッション固定攻撃対策（`session_regenerate_id(true)`）と認証。

```php
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

    // TODO: ユーザー検索用SQLのプリペアドステートメントを実行
    // SQL: SELECT * FROM users WHERE email = :email

    // ユーザーが存在し、パスワード照合が成功した場合
    if ($user && password_verify($password, $user['password_hash'])) {
        // ★セキュアポイント：セッション固定攻撃対策（IDの再生成）
        session_regenerate_id(true);

        // セッションへの保存
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['display_name'] = $user['display_name'];

        // TODO: 最終ログイン日時（last_login_at）を UPDATE する処理

        header('Location: index.php');
        exit;
    } else {
        $errors[] = 'メールアドレスまたはパスワードに誤りがあります。';
    }
}
$csrfToken = generate_csrf_token();
?>
<!-- TODO: ログイン用HTMLフォームの作成（CSRFトークンのhidden埋め込みを含む） -->

```

---

### Step 5: 提出・POST処理＆PRGパターン（`submission_process.php`）

> **目的：** CSRFの厳密検証、PRGパターン（二重送信防止）、IDOR対策。

```php
<?php
// submission_process.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login(); // ログイン認証チェック

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF検証
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    die('不正なアクセスです。（CSRF検証失敗）');
}

$topicId = (int)($_POST['topic_id'] ?? 0);
$figmaUrl = trim($_POST['figma_url'] ?? '');
$userId = $_SESSION['user_id'];

// バリデーション
if ($topicId <= 0 || empty($figmaUrl) || !filter_var($figmaUrl, FILTER_VALIDATE_URL)) {
    // TODO: エラー時のリダイレクトまたはセッションへのエラー保持処理
    exit('入力データに不備があります。');
}

// 模擬AIスコア・フィードバック生成（仕様に基づき算出）
$totalScore = rand(75, 95);
$aiFeedback = "全体的にバランス良く構成されています。余白（マージン）の取り方をさらに意識すると、視線誘導がよりスムーズになります。";

try {
    // TODO: submissions テーブルへ INSERT するプリペアドステートメント処理を記述
    // INSERT INTO submissions (user_id, topic_id, figma_url, total_score, ai_feedback) VALUES (...)

    // ★PRGパターン（Post-Redirect-Get）：二重送信を防ぐためGETリクエストへリダイレクト
    header('Location: mypage.php');
    exit;

} catch (PDOException $e) {
    error_log('Submission Error: ' . $e->getMessage());
    die('提出処理中にエラーが発生しました。');
}

```

---

### Step 6: 閲覧画面のXSS対策と集計（`mypage.php` & `gallery.php`）

> **目的：** `h()` による徹底的なエスケープと、安全なSQL結合（`COUNT`, `AVG`）。

#### スケルトンポイント（`mypage.php`）：

1. `require_login()` で未認証アクセスを遮断。
2. ログインユーザーのID（`$_SESSION['user_id']`）を使って SQL を実行：
```sql
SELECT COUNT(*) AS total_count, AVG(total_score) AS avg_score 
FROM submissions 
WHERE user_id = :user_id

```


3. 過去の提出履歴を取得し、HTMLへ出力する際は **全項目（`figma_url` や `ai_feedback`）に対して `h()` 関数を適用** する。

#### スケルトンポイント（`gallery.php`）：

1. 他ユーザーも含めた最新提出物の一覧を取得（`JOIN` で `topics` や `users` テーブルを結合）。
2. 匿名表示指定のため、ユーザー名はイニシャル化または `h($row['display_name'])` を適切に処理。
3. 画面描画時のXSSエスケープを徹底。

---

## 4. 最終セキュリティチェックリスト

コードの実装が完了したら、以下の項目をすべてクリアしているかセルフチェックしてください。

* [ ] **SQLi:** 直接変数をつないだ `query("SELECT ... WHERE id = " . $id)` が存在せず、すべて `prepare()` と `execute()` を通しているか。
* [ ] **XSS:** HTML内に `<?php echo $var; ?>` 形式の出力がなく、すべて `<?php echo h($var); ?>` になっているか。
* [ ] **CSRF:** すべての POST フォームに `csrf_token` の hidden 項目があり、受信側で `validate_csrf_token()` を読んでいるか。
* [ ] **Session:** `login.php` の認証成功時に `session_regenerate_id(true)` を呼んでいるか。
* [ ] **Error Leak:** DB処理の `catch(PDOException $e)` 内で `$e->getMessage()` を `echo` していないか（`error_log()` に書いているか）。



---
### 【実装補足：Figma OAuth & API サムネイル自動取得処理】

```php
// Figma API サムネイル取得処理関数（helpers.php等へ追記）
function fetch_figma_thumbnail(string $figmaUrl, string$accessToken): ?string {
    if (!preg_match('/(?:file|design)\/([a-zA-Z0-9]+)/', $figmaUrl, $matches)) {         return null;     }$fileKey = $matches[1];$ch = curl_init("[https://api.figma.com/v1/files/](https://api.figma.com/v1/files/){$fileKey}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Figma-Token: {$accessToken}"]);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data['thumbnailUrl'] ?? null;
}



---

セキュア実装手順書の作成が完了しました。この手順書をコピーして、ローカルに『05_implement_guide.md』というファイル名で保存してください。保存できたら、このチャットを閉じ、実装用の新規チャット『スレッドB2（開発支援）』を立ち上げてVSCodeでの開発を開始しましょう！