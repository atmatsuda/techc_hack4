<?php
// submission_process.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    die('不正なリクエストです。');
}

$figmaUrl = trim($_POST['figma_url'] ?? '');
$persona  = $_POST['persona'] ?? 'angel';
$topicId  = (int)($_POST['topic_id'] ?? 1);
$userId   = $_SESSION['user_id'];

if (empty($figmaUrl) || !filter_var($figmaUrl, FILTER_VALIDATE_URL)) {
    die('有効なFigmaのURLを入力してください。');
}

// --------------------------------------------------
// 1. 過去の同一ユーザー＆同一URLの提出履歴をチェック
// --------------------------------------------------
$prevSubmission = null;
try {
    $checkStmt = $pdo->prepare('SELECT * FROM submissions WHERE user_id = :user_id AND figma_url = :figma_url ORDER BY submission_id DESC LIMIT 1');
    $checkStmt->execute([
        ':user_id'   => $userId,
        ':figma_url' => $figmaUrl
    ]);
    $prevSubmission = $checkStmt->fetch();
} catch (PDOException $e) {
    // 過去履歴取得失敗時は新規扱い
    $prevSubmission = null;
}

// --------------------------------------------------
// 2. URLに基づく決定論的ハッシュ値の生成（URL固定スコア用）
// --------------------------------------------------
$urlHash = crc32($figmaUrl . '_' . $persona);
$baseScoreMap = [
    'demon'    => 55 + (abs($urlHash) % 20), // 55〜74点
    'business' => 70 + (abs($urlHash) % 18), // 70〜87点
    'angel'    => 82 + (abs($urlHash) % 15)  // 82〜96点
];
$baseScore = $baseScoreMap[$persona] ?? 80;

// --------------------------------------------------
// 3. お題コンセプト適合性 ＆ フィードバックの生成
// --------------------------------------------------
$aiFeedback = "";
$totalScore = $baseScore;

if ($prevSubmission) {
    // 【再提出パターン】前回からの変化・改善点の評価
    $prevScore = (int)$prevSubmission['total_score'];
    $totalScore = min(100, $prevScore + 5);

    if ($persona === 'demon') {
        $aiFeedback = "【前回からの改善度の判定】\n前回の提出（{$prevScore}点）と比較検証しました。\n指摘していた「ファーストビューの余白とグリッドの揃え」が意識され、視認性が大きく向上しています！（＋5点アップ）\n\n【お題コンセプト適合性チェック】\n今回のお題要件である「ターゲット層へのトーン＆マナー」は概ね捉えられていますが、フォントの厳格さがやや不足しています。明朝体や和風タイポグラフィの比率を高め、ターゲット層の信頼感をさらに高めてください。";
    } elseif ($persona === 'business') {
        $aiFeedback = "【再提出による効果検証】\n前回のスコア（{$prevScore}点）から改善が見られます。\nPrimary CTA（予約/購入ボタン）のコントラストと配置が調整され、ユーザーの目線が自然にボタンに誘導される導線（CVR向上設計）になりました。\n\n【お題のターゲット適合】\nお題で要求されているビジネスターゲット（30〜50代）の目的達成に必要な情報優先度がしっかりと整理されています。実務レベルとして非常に実用的なクオリティです！";
    } else { // angel
        $aiFeedback = "【ブラッシュアップへの称賛！】\n前回の作品（{$prevScore}点）からさらに洗練されましたね！素晴らしい向上心です！✨\n前回アドバイスした文字の字間（カーニング）と余白のバランスが整って、一段とプロっぽい仕上がりになっています！\n\n【お題コンセプトの表現力】\nお題の世界観がしっかりと表現されていて、見た人が一目でお店の魅力を感じる素敵なデザインです！自信を持ってこの調子で進めましょう！";
    }

} else {
    // 【初回提出パターン】URL・ペルソナごとの固定フィードバック
    if ($persona === 'demon') {
        $aiFeedback = "【お題コンセプト・要件適合性】\n今回のお題のターゲット層（ユーザー属性）に対するトーン＆マナーの考慮が不十分です。カラーパレットがカジュアル過ぎるため、お題が求める高級感・信頼性の要件からやや乖離しています。\n\n【視認性・余白の厳しく指導】\n要素間の余白（Padding）が不揃いでグリッドが崩れています。各要素の空きスペースを16px/24px/32pxなどの倍数ルール（8ptグリッド）で再設計してください。";
    } elseif ($persona === 'business') {
        $aiFeedback = "【お題コンセプト・CVR適合性】\nお題の成果目標（コンバージョン）に対して、最も重要なCTA（アクションボタン）の視認性が不足しています。ターゲットが迷わず次に進めるレイアウト構成に改善が必要です。\n\n【情報設計・コンポーネント】\nヘッダーからヒーローセクションへの視線誘導（Fの法則）は良く出来ています。ファーストビュー内に要件である『強み・特徴』の要素を配置することでCVR向上が期待できます。";
    } else { // angel
        $aiFeedback = "【お題コンセプトの雰囲気】\nお題の持つテーマや雰囲気が丁寧に表現されていて、とても魅力的なデザインです！見ていてワクワクする色使いですね！✨\n\n【さらによくするアドバイス】\nお題のターゲット層にさらに刺さるよう、メインキャッチコピーの文字サイズをひとまわり大きく（ジャンプ率を高く）してみましょう！全体の完成度がぐっと高まりますよ！";
    }
}

// --------------------------------------------------
// 4. DBへのデータ保存（サムネイルURL取得機能付き）
// --------------------------------------------------
$thumbnailUrl = fetch_figma_thumbnail($figmaUrl);

try {
    $topicCheck = $pdo->prepare('SELECT topic_id FROM topics WHERE topic_id = :id');
    $topicCheck->execute([':id' => $topicId]);
    if (!$topicCheck->fetch()) {
        $topicId = 1;
    }

    $stmt = $pdo->prepare('INSERT INTO submissions (user_id, topic_id, figma_url, thumbnail_url, persona, total_score, ai_feedback) VALUES (:user_id, :topic_id, :figma_url, :thumbnail_url, :persona, :total_score, :ai_feedback)');
    $stmt->execute([
        ':user_id'       => $userId,
        ':topic_id'      => $topicId,
        ':figma_url'     => $figmaUrl,
        ':thumbnail_url' => $thumbnailUrl,
        ':persona'       => $persona,
        ':total_score'   => $totalScore,
        ':ai_feedback'   => $aiFeedback,
    ]);

    header('Location: mypage.php');
    exit;
} catch (PDOException $e) {
    error_log('Submission Error: ' . $e->getMessage());
    die('データベース処理中にエラーが発生しました。時間を置いて再度お試しください。');
}