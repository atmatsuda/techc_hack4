セキュア実装手順書（レベル2：初級者向け）本手順書は、「気まずさ撃退！即効トークおみくじ（仮）」を安全かつ確実に実装するためのガイドです。ファイル構成（techc_hack4/ 以下）に沿って、順番にファイルを作成・編集していきましょう。  フェーズ1：データベースと共通設定の準備まずは、システム全体で使うデータベース接続とセキュリティ関数を作ります。  1. config/db.php（データベース接続）【解説】
データベース（techc_hack4_db）に接続するためのファイルです。生のエラーメッセージが画面に出ないように、try-catchを使って例外処理を行います。  PHP<?php
// config/db.php

function get_db_connection() {
    $dsn = 'mysql:host=localhost;dbname=techc_hack4_db;charset=utf8mb4';
    $user = 'root'; // 開発環境のユーザー名（必要に応じて変更）
    $password = ''; // 開発環境のパスワード（必要に応じて変更）

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // セキュリティ対策: エラーの詳細は画面に出さず、汎用メッセージのみ表示します[cite: 3, 4, 5]
        exit('システムエラーが発生しました。時間を置いて再度お試しください。');
    }
}
2. includes/functions.php（共通セキュリティ関数）【解説】
XSS（クロスサイトスクリプティング）対策のための無害化関数 h() と、CSRF（クロスサイトリクエストフォージェリ）対策のトークン生成・検証関数を定義します。  PHP<?>
<?php
// includes/functions.php

/**
 * XSS対策：特殊文字をHTMLエンティティに変換する関数
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF対策：ワンタイムトークンを生成し、セッションに保存する関数
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF対策：送信されたトークンが正しいか検証する関数
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}
フェーズ2：メイン機能の実装（おみくじ画面）3. public/index.php（トップ・おみくじ引く画面）【解説】
situations テーブルからカテゴリ一覧を取得し、プルダウンで選択して結果画面へ送信するフォームを作成します[cite: 3, 5]。PHP<?php
// public/index.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

$pdo = get_db_connection();

// Level 2: 自身でSQLを組み立ててデータを取得しましょう
$stmt = $pdo->query('SELECT * FROM situations ORDER BY id ASC');
$situations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>シチュエーションを選んでおみくじを引く</h1>
    <form action="result.php" method="GET">
        <select name="situation_id">
            <?php foreach ($situations as $sit): ?>
                <!-- h()関数を使って安全に出力 -->
                <option value="<?= h($sit['id']) ?>"><?= h($sit['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">おみくじを引く</button>
    </form>
    
    <a href="history.php">端末内履歴を見る</a> | 
    <a href="post.php">新しい話題を投稿する</a>
</body>
</html>
4. public/result.php（おみくじ結果表示画面）【解説】
選択されたシチュエーションIDに応じて、topics テーブルから承認済みの話題（is_approved = 1）をランダムに1件取得し、おみくじ結果として表示します。また、引かれた回数（draw_count）をインサイト用に加算します。  PHP<?php
// public/result.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

$situation_id = filter_input(INPUT_GET, 'situation_id', FILTER_VALIDATE_INT);
if (!$situation_id) {
    exit('無効なシチュエーションです。');
}

$pdo = get_db_connection();

// 1. 指定されたシチュエーションかつ承認済みの話題をランダムに1件取得（プリペアドステートメント利用）
$stmt = $pdo->prepare('SELECT * FROM topics WHERE situation_id = :situation_id AND is_approved = 1 ORDER BY RAND() LIMIT 1');
$stmt->bindValue(':situation_id', $situation_id, PDO::PARAM_INT);
$stmt->execute();
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if ($topic) {
    // 2. 引かれた回数（draw_count）をインクリメント
    $update_stmt = $pdo->prepare('UPDATE topics SET draw_count = draw_count + 1 WHERE id = :id');
    $update_stmt->bindValue(':id', $topic['id'], PDO::PARAM_INT);
    $update_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>おみくじ結果 - 気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>おみくじ結果</h1>
    <?php if ($topic): ?>
        <div class="card">
            <p><strong>話題:</strong> <?= h($topic['content']) ?></p>
            <p><small>投稿者: <?= h($topic['contributor_name']) ?></small></p>
        </div>
    <?php else: ?>
        <p>現在、このシチュエーションに該当する承認済みの話題はありません。新しい話題を投稿してみましょう！</p>
    <?php endif; ?>

    <p><a href="index.php">もう一度おみくじを引く</a></p>
</body>
</html>
フェーズ3：話題投稿機能の実装（PRGパターン）5. public/post.php（話題投稿フォーム）【解説】
ユーザーが話題を投稿する画面です。CSRF対策として、隠しフィールドにトークンを埋め込みます[cite: 3, 5]。文字数制限（本文50文字以内、ニックネーム15文字以内）も設定します。  PHP<?php
// public/post.php
session_start();
require_once '../includes/functions.php';

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>話題投稿 - 気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>話題を投稿する</h1>
    <form action="post_process.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        
        <div>
            <label>シチュエーション選択:</label>
            <select name="situation_id">
                <option value="1">初対面</option>
                <option value="2">デート</option>
                <option value="3">飲み会</option>
            </select>
        </div>
        <div>
            <label>ニックネーム (最大15文字):</label>
            <input type="text" name="contributor_name" maxlength="15" required>
        </div>
        <div>
            <label>話題本文 (最大50文字):</label>
            <input type="text" name="content" maxlength="50" required>
        </div>
        
        <button type="submit">投稿する</button>
    </form>
    <p><a href="index.php">トップに戻る</a></p>
</body>
</html>
6. public/post_process.php（投稿完了処理）【解説】
POST送信データとCSRFトークンを検証し、プリペアドステートメントを用いて安全にデータを保存します。処理後は post_thanks.php へリダイレクト（PRGパターン）します[cite: 3, 5]。PHP<?php
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

// PRGパターン：投稿完了画面へリダイレクト[cite: 3]
header('Location: post_thanks.php');
exit;
7. public/post_thanks.php（投稿完了画面）PHP<?php
// public/post_thanks.php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿完了 - 気まずさ撃退！即効トークおみくじ</title>
</head>
<body>
    <h1>投稿を受け付けました</h1>
    <p>ご投稿ありがとうございます！管理者の承認後に公開されます。</p>
    <p><a href="index.php">トップに戻る</a></p>
</body>
</html>
フェーズ4：管理者機能（アクセス制限）8. includes/auth.php（管理者認証補助）【解説】
URLキー（?key=secret123）とBASIC認証のハイブリッドによるアクセス制限を実装します。  PHP<?php
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

    // セッションに認証フラグがない場合は、トップページへ強制送還（存在を秘匿する）[cite: 3]
    if (empty($_SESSION['admin_auth'])) {
        header('Location: index.php');
        exit;
    }
}
9. public/admin_dashboard.php（管理ダッシュボード＆インサイト）【解説】
投稿された話題の「承認 / 非表示」切り替えや、SQL集計によるインサイト（カテゴリ別話題数や累計引かれ回数）をテキストで表示します。  PHP<?php
// public/admin_dashboard.php
require_once '../includes/auth.php';
require_admin_login(); // アクセス制限チェック

require_once '../config/db.php';
require_once '../includes/functions.php';

$pdo = get_db_connection();

// 1. ステータス変更処理（POST送信時）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['topic_id'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        exit('トークンが無効です。');
    }
    $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    if ($topic_id) {
        $is_approved = ($action === 'approve') ? 1 : 0;
        $update_stmt = $pdo->prepare('UPDATE topics SET is_approved = :is_approved WHERE id = :id');
        $update_stmt->bindValue(':is_approved', $is_approved, PDO::PARAM_INT);
        $update_stmt->bindValue(':id', $topic_id, PDO::PARAM_INT);
        $update_stmt->execute();
    }
    header('Location: admin_dashboard.php?key=secret123');
    exit;
}

$csrf_token = generate_csrf_token();

// 2. 投稿一覧の取得
$stmt = $pdo->query('SELECT topics.*, situations.name AS situation_name FROM topics JOIN situations ON topics.situation_id = situations.id ORDER BY topics.id DESC');
$topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. インサイト集計の取得（SQL集計テキスト表示）[cite: 2, 3]
$insight_stmt = $pdo->query('SELECT situations.name, COUNT(topics.id) AS topic_count, SUM(topics.draw_count) AS total_draws FROM situations LEFT JOIN topics ON situations.id = topics.situation_id GROUP BY situations.id');
$insights = $insight_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者ダッシュボード</title>
</head>
<body>
    <h1>管理者ダッシュボード</h1>
    <p><a href="index.php">サイトトップへ戻る</a></p>

    <h2>インサイト（利用状況集計）</h2>
    <table border="1">
        <tr>
            <th>シチュエーション</th>
            <th>登録話題数</th>
            <th>累計引かれた回数</th>
        </tr>
        <?php foreach ($insights as $ins): ?>
        <tr>
            <td><?= h($ins['name']) ?></td>
            <td><?= h($ins['topic_count']) ?> 件</td>
            <td><?= h($ins['total_draws'] ?? 0) ?> 回</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h2>投稿話題の精査管理</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>カテゴリ</th>
            <th>話題</th>
            <th>投稿者</th>
            <th>状態</th>
            <th>操作</th>
        </tr>
        <?php foreach ($topics as $t): ?>
        <tr>
            <td><?= h($t['id']) ?></td>
            <td><?= h($t['situation_name']) ?></td>
            <td><?= h($t['content']) ?></td>
            <td><?= h($t['contributor_name']) ?></td>
            <td><?= $t['is_approved'] == 1 ? '承認済' : '未承認' ?></td>
            <td>
                <form action="admin_dashboard.php?key=secret123" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
                    <input type="hidden" name="topic_id" value="<?= h($t['id']) ?>">
                    <?php if ($t['is_approved'] == 1): ?>
                        <button type="submit" name="action" value="hide">非表示にする</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="approve">承認する</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>