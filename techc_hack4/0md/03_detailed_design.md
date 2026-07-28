[気まずさ撃退！即効トークおみくじ（仮）] セキュリティ強化型詳細設計書1. 開発基本方針アーキテクチャ・実装モデル： PHPによる画面・処理一体型（スクリプト指向）モデル＋共通セキュリティモジュール切り出し構造ベースURL： http://localhost:8000/techc_hack4/データベース名： techc_hack4_db命名規則：テーブル名・カラム名：スネークケース（小文字＋アンダースコア / 例: situation_id, contributor_name）PHP変数・関数名：スネークケース（例: $csrf_token, h()）ファイル名：スネークケース、小文字アルファベット（例: admin_dashboard.php）2. データベース設計① ER図（Mermaid表記）コード スニペットerDiagram
    situations ||--o{ topics : "1対多"

    situations {
        int id PK
        string name
        datetime created_at
    }

    topics {
        int id PK
        int situation_id FK
        string content
        string contributor_name
        int is_approved
        int draw_count
        datetime created_at
    }
※テキスト補足：situations（シチュエーションカテゴリ）テーブルに対して、1対多のリレーションで topics（話題・おみくじデータ）テーブルが紐付きます。② テーブル定義書1. situations テーブル（シチュエーションカテゴリ）カラム名データ型制約説明idINTPRIMARY KEY, AUTO_INCREMENTシチュエーションIDnameVARCHAR(50)NOT NULLカテゴリ名（例：「初対面」「デート」「飲み会」）created_atDATETIMEDEFAULT CURRENT_TIMESTAMP登録日時2. topics テーブル（話題データ）カラム名データ型制約説明idINTPRIMARY KEY, AUTO_INCREMENT話題IDsituation_idINTNOT NULL, FOREIGN KEY (situations.id)紐付くシチュエーションIDcontentVARCHAR(50)NOT NULL話題本文（最大50文字）contributor_nameVARCHAR(15)NOT NULL DEFAULT '匿名'投稿者ニックネーム（最大15文字）is_approvedTINYINT(1)NOT NULL DEFAULT 0承認フラグ（0:未承認, 1:承認済）draw_countINTNOT NULL DEFAULT 0おみくじで引かれた回数（インサイト集計用）created_atDATETIMEDEFAULT CURRENT_TIMESTAMP投稿日時3. 画面遷移図・制御フロー① 画面遷移図（Mermaid表記）コード スニペットgraph TD
    A[index.php<br>トップ・おみくじ引く画面] -->|シチュエーション選択| B[result.php<br>おみくじ結果表示画面]
    A -->|履歴確認ボタン| C[history.php<br>端末内履歴一覧画面]
    A -->|話題投稿ボタン| D[post.php<br>話題投稿フォーム]
    D -->|POST送信＋CSRF検証| D_PROC[post_process.php<br>投稿完了処理]
    D_PROC -->|PRGリダイレクト| E[post_thanks.php<br>投稿完了画面]
    
    F[admin_dashboard.php<br>?key=secret123 アクセス] -->|URLキー＋BASIC認証| G[管理ダッシュボード・インサイト]
    G -->|承認/非表示/削除操作 POST| G_PROC[admin_action.php<br>ステータス変更処理]
    G_PROC -->|PRGリダイレクト| G
② PRGパターンの適用箇所とCSRFトークンの検証フローPRG（Post-Redirect-Get）パターンの適用：「ユーザー話題投稿」および「管理者画面での承認/非表示操作」のPOST送信後、二重送信（F5アタックや誤再送信）を防止するため、処理完了後は直ちに header('Location: ...') を用いてGET画面へリダイレクトします。CSRFトークン検証フロー：フォーム描画時（GET）：$_SESSION['csrf_token'] を生成し、フォーム内の <input type="hidden" name="csrf_token" ...> に埋め込み。処理実行時（POST）：送られてきた $_POST['csrf_token'] と $_SESSION['csrf_token'] を照合。不一致または存在しない場合は処理を安全に拒否（403 Forbidden またはエラー終了）。4. ディレクトリ・ファイル構成（VSCode対応）Plaintexttechc_hack4/
├── config/
│   └── db.php                # DB接続処理（PDOインスタンス生成・try-catch保護）
├── includes/
│   ├── auth.php              # 管理者アクセス制限・セッション認証補助
│   ├── functions.php         # 共通関数（h()によるサニタイズ、CSRFトークン生成/検証など）
│   └── header.php / footer.php # 共通HTMLヘッダー・フッター
├── public/ (またはルート直下)
│   ├── index.php             # メイン画面（シチュエーション選択フォーム）
│   ├── result.php            # おみくじ結果表示（SQLランダム取得＋draw_count加算）
│   ├── history.php           # 端末内履歴一覧画面（Cookie/LocalStorage参照）
│   ├── post.php              # 話題投稿フォーム画面（CSRFトークン埋め込み）
│   ├── post_thanks.php       # 投稿完了画面
│   ├── admin_dashboard.php   # 管理者精査ダッシュボード＆インサイト集計表示
│   └── css/
│       └── style.css         # スタイルシート（カード調UI等）
└── README.md
5. データ整合性・セキュリティ仕様の設計① 脆弱性対策ポリシーSQLインジェクション（SQLi）対策：すべてのDB操作（SELECT, INSERT, UPDATE）において、変数を直接埋め込んだSQL文字列連結を完全禁止とします。必ずPDOのプリペアドステートメント（prepare() / execute()）を使用し、入力値はバインド変数として安全に渡します。クロスサイトスクリプティング（XSS）対策：DB保存時にはサニタイズを行わず、生のデータとして保持します。HTML出力箇所（ブラウザに表示する直前）で、自作の安全関数 h()（htmlspecialchars($str, ENT_QUOTES, 'UTF-8') のラッパー関数）を通すことを徹底します。クロスサイトリクエストフォージェリ（CSRF）対策：投稿フォームおよび管理者操作フォームのすべてのPOST処理において、セッション発行のワンタイム暗号化トークン（bin2hex(random_bytes(32))）による厳格な検証を実施します。情報漏洩（エラーハンドリング）対策：DB接続処理およびすべてのSQL処理は try-catch (PDOException $e) ブロックで保護します。例外捕捉時は、生のエラーメッセージ（接続パスやテーブル構造）を出力せず、「システムエラーが発生しました。時間を置いて再度お試しください。」という汎用テキストのみを表示します。② 権限管理とセッション保護設計管理者認証とアクセス制御：管理者ダッシュボード（admin_dashboard.php）アクセス時、クエリパラメータ ?key=secret123 およびBASIC認証の両方を検証します。初回検証成功時に $_SESSION['admin_auth'] = true; をセットし、以降の操作はセッションの認証フラグで権限チェックを行います。認証キー不一致または無制限アクセスの場合は、管理者ページの存在を秘匿するためトップページ（index.php）へ自動リダイレクトします。セッション保護：session_start() 実行時、セッションハイジャック対策として session_regenerate_id(true) を必要に応じて呼び出し、セッションIDの定期更新および固定化攻撃を防止します。