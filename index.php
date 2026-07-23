<?php
// index.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions/helpers.php';

require_login();

// --------------------------------------------------
// 1. 具体的で実践的なお題データ（無限生成用）
// --------------------------------------------------
$specificTopics = [
    [
        'title' => '高級和食専門店のトップページファーストビュー',
        'desc' => 'ターゲット：50代以上の富裕層・接待利用。シックで重厚感のある和モダンなトーン＆マナーで、予約ボタン（CTA）への導線を意識したデザインを制作してください。'
    ],
    [
        'title' => 'Webデザイナーのポートフォリオサイト・ヘッダー＆ヒーローセクション',
        'desc' => 'ターゲット：Web制作会社の実務担当者。「信頼感」と「独自の個性」を両立させ、実績ページへのスムーズな視線誘導を行うレイアウトを制作してください。'
    ],
    [
        'title' => 'オーガニックカフェの期間限定スイーツ紹介SNSバナー (1080x1080)',
        'desc' => 'ターゲット：20〜30代女性。自然体でナチュラルな配色を用い、旬の素材感と限定感が一目で伝わるコピー配置のバナーを制作してください。'
    ],
    [
        'title' => 'SaaS系タスク管理ツールの料金プラン比較テーブルUI',
        'desc' => 'ターゲット：IT企業のチームリーダー。3つのプラン（Free/Pro/Enterprise）の差異を明確にし、「Proプラン（推奨）」が一番目立つ視認性の高いUIを制作してください。'
    ],
    [
        'title' => 'フィットネスジムの新規入会キャンペーンLPヘッダー',
        'desc' => 'ターゲット：夏に向けて運動を始めたい初心者。「初月無料」のインパクトを強調し、情熱的かつ清潔感のあるアクティブな配色で制作してください。'
    ]
];

// リクエストに応じてランダムなお題を選択
$topicIndex = isset($_GET['generate']) ? array_rand($specificTopics) : 0;
$currentTopic = $specificTopics[$topicIndex];

$topic = [
    'topic_id' => $topicIndex + 1,
    'title' => $currentTopic['title'],
    'description' => $currentTopic['desc']
];

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ホーム / お題提出 - デザトレ</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="app-header">
        <div>ようこそ、<strong><?php echo h($_SESSION['display_name']); ?></strong> さん！</div>
        <nav>
            <a href="index.php">ホーム</a>
            <a href="mypage.php">マイページ</a>
            <a href="gallery.php">ギャラリー</a>
            <a href="logout.php">ログアウト</a>
        </nav>
    </header>

    <div class="container">
        <h1>本日のお題：<?php echo h($topic['title']); ?></h1>
        <p style="color: var(--text-muted); font-size: 16px; margin-bottom: 20px;">
            <?php echo nl2br(h($topic['description'])); ?>
        </p>

        <!-- 🔄 お題の再生成（無限生成）ボタン -->
        <div style="margin-bottom: 30px;">
            <a href="index.php?generate=1" class="btn" style="background-color: var(--border-color); color: var(--text-color); font-size: 13px; padding: 8px 16px; text-decoration: none;">
                🔄 別のお題をAIに新しく作ってもらう（無限生成）
            </a>
        </div>

        <hr style="border:0; border-top: 1px solid var(--border-color); margin: 30px 0;">

        <h2>課題を提出する</h2>
        <form action="submission_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
            <input type="hidden" name="topic_id" value="<?php echo h($topic['topic_id']); ?>">
            
            <div class="form-group">
                <label for="persona">AI審査員を選択</label>
                <select id="persona" name="persona">
                    <option value="demon">🔥 鬼軍曹AI（厳格・辛口アドバイス）</option>
                    <option value="angel" selected>👼 エンジェルAI（激甘・自己肯定感UP）</option>
                    <option value="business">💼 ビジネスAI（実務・成果重視）</option>
                </select>
            </div>

            <div class="form-group">
                <label for="figma_url">Figma 共有URL</label>
                <input type="url" id="figma_url" name="figma_url" placeholder="https://www.figma.com/file/..." required>
            </div>
            
            <button type="submit">提出してAI分析を受ける</button>
        </form>
    </div>
</body>
</html>