# Route設計書

## 概要

この設計書では、BookShelf の Web 画面用 Route と API Route の責務、認証・認可、Route Model Binding の設計を整理する。

対象範囲は以下とする。

- 会員登録・ログイン・ログアウト
- 書籍一覧・詳細・CRUD
- レビュー投稿・編集・削除
- レビューへのいいね
- お気に入り書籍
- ジャンル管理
- ランキング
- マイ読書レポート
- 読書計画
- 通知一覧・既読処理
- ISBN検索
- Sanctum を利用した書籍API

## Route設計方針

### 1. 書籍一覧をアプリの入口にする

BookShelf の中心機能は書籍の閲覧であるため、書籍一覧画面を主要な入口にする。

| URI | 役割 |
| --- | --- |
| `/books` | 書籍一覧画面 |

ルート `/` は、現時点では主要画面として使用しない。

### 2. 閲覧系と操作系で認証要否を分ける

未認証ユーザーでも利用できる閲覧系の Route と、ログインが必要な操作系の Route を分ける。

| 種別 | 認証 | 主なRoute |
| --- | --- | --- |
| 閲覧系 | 不要 | 書籍一覧、書籍詳細、ランキング |
| 操作系 | 必要 | 書籍登録、レビュー投稿、お気に入り、読書計画、通知、レポート |
| API閲覧系 | 不要 | 書籍一覧API、書籍詳細API |
| API書き込み系 | 必要 | 書籍登録API、書籍更新API、書籍削除API |

### 3. 認可は操作対象ごとに分ける

ログイン済みであっても、他ユーザーのデータを自由に操作できないようにする。

| 対象 | 認可方式 | 主な確認内容 |
| --- | --- | --- |
| 書籍 | `BookPolicy` | 書籍の登録者本人か |
| レビュー | `ReviewPolicy` | レビューの投稿者本人か |
| 読書計画 | `ReadingPlanPolicy` | 読書計画の所有者本人か |
| 通知 | `NotificationPolicy` | 通知の送信先本人か |

Web側では Controller 内の `$this->authorize()` を中心に確認する。
API側の更新・削除では、Route の `can` ミドルウェアで `BookPolicy` を確認する。

### 4. Route名は機能単位で統一する

Route名は画面や処理の責務が分かるように、機能名と操作名を組み合わせる。

| 機能 | Route名の例 |
| --- | --- |
| 書籍 | `books.index`, `books.show`, `books.store` |
| レビュー | `reviews.store`, `reviews.update`, `reviews.like` |
| ジャンル | `genres.index`, `genres.show`, `genres.update` |
| 読書計画 | `reading-plans.index`, `reading-plans.complete` |
| 通知 | `notifications.index`, `notifications.read` |
| API | `api.v1.books.index`, `api.v1.books.store` |

---

# Web Route

## 認証機能

会員登録、ログイン、ログアウトなどの認証機能は Laravel Fortify によって提供される。

| 機能 | Route | 説明 |
| --- | --- | --- |
| 会員登録画面 | `GET /register` | Fortifyが提供 |
| 会員登録処理 | `POST /register` | Fortifyが提供 |
| ログイン画面 | `GET /login` | Fortifyが提供 |
| ログイン処理 | `POST /login` | Fortifyが提供 |
| ログアウト | `POST /logout` | Fortifyが提供 |

## 未認証ユーザーも利用できるRoute

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/books` | `books.index` | `BookController` | `index` | 書籍一覧を表示 |
| GET | `/books/{book}` | `books.show` | `BookController` | `show` | 書籍詳細を表示 |
| GET | `/ranking` | `ranking.index` | `RankingController` | `index` | ランキングを表示 |

### 書籍一覧

`/books` では、一覧表示に加えて検索・絞り込み・並び替えを扱う。

| Query | 役割 |
| --- | --- |
| `keyword` | タイトル・著者名検索 |
| `genre` | ジャンル絞り込み |
| `sort` | 並び替え |

## 認証ユーザーのみ利用できるRoute

以下の Route は `auth` ミドルウェアの中に定義する。

## 書籍管理

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/books/create` | `books.create` | `BookController` | `create` | 書籍登録画面を表示 |
| POST | `/books` | `books.store` | `BookController` | `store` | 書籍を登録 |
| GET | `/books/{book}/edit` | `books.edit` | `BookController` | `edit` | 書籍編集画面を表示 |
| PUT | `/books/{book}` | `books.update` | `BookController` | `update` | 書籍を更新 |
| DELETE | `/books/{book}` | `books.destroy` | `BookController` | `destroy` | 書籍を削除 |

書籍の編集・更新・削除では `BookPolicy` により、書籍の登録者本人のみ操作できるようにする。

## レビュー

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| POST | `/books/{book}/reviews` | `reviews.store` | `ReviewController` | `store` | 書籍にレビューを投稿 |
| GET | `/reviews/{review}/edit` | `reviews.edit` | `ReviewController` | `edit` | レビュー編集画面を表示 |
| PUT | `/reviews/{review}` | `reviews.update` | `ReviewController` | `update` | レビューを更新 |
| DELETE | `/reviews/{review}` | `reviews.destroy` | `ReviewController` | `destroy` | レビューを削除 |

レビューの編集・更新・削除では `ReviewPolicy` により、レビュー投稿者本人のみ操作できるようにする。

## レビューいいね

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| POST | `/reviews/{review}/like` | `reviews.like` | `ReviewLikeController` | `toggle` | レビューへのいいね登録・解除 |

同じRouteで、未いいねの場合は登録、いいね済みの場合は解除を行う。

## ジャンル管理

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/genres` | `genres.index` | `GenreController` | `index` | ジャンル一覧を表示 |
| GET | `/genres/create` | `genres.create` | `GenreController` | `create` | ジャンル登録画面を表示 |
| POST | `/genres` | `genres.store` | `GenreController` | `store` | ジャンルを登録 |
| GET | `/genres/{genre}` | `genres.show` | `GenreController` | `show` | ジャンル別書籍一覧を表示 |
| GET | `/genres/{genre}/edit` | `genres.edit` | `GenreController` | `edit` | ジャンル編集画面を表示 |
| PUT | `/genres/{genre}` | `genres.update` | `GenreController` | `update` | ジャンルを更新 |
| DELETE | `/genres/{genre}` | `genres.destroy` | `GenreController` | `destroy` | ジャンルを削除 |

## お気に入り書籍

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/favorites` | `favorites.index` | `FavoriteController` | `index` | 自分のお気に入り書籍一覧を表示 |
| POST | `/books/{book}/favorites` | `favorites.toggle` | `FavoriteController` | `toggle` | お気に入り登録・解除 |

## マイ読書レポート

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/reports` | `reports.index` | `ReportController` | `index` | ログインユーザーの読書データを集計して表示 |

レポートでは、ログインユーザーを起点にレビュー数、読了冊数、平均評価、評価分布、高評価書籍、ジャンル別評価傾向を集計する。

## 読書計画

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/reading-plans` | `reading-plans.index` | `ReadingPlanController` | `index` | 自分の読書計画一覧を表示 |
| GET | `/reading-plans/create` | `reading-plans.create` | `ReadingPlanController` | `create` | 読書計画登録画面を表示 |
| POST | `/reading-plans` | `reading-plans.store` | `ReadingPlanController` | `store` | 読書計画を登録 |
| GET | `/reading-plans/{plan}/edit` | `reading-plans.edit` | `ReadingPlanController` | `edit` | 読書計画編集画面を表示 |
| PUT | `/reading-plans/{plan}` | `reading-plans.update` | `ReadingPlanController` | `update` | 読書計画を更新 |
| DELETE | `/reading-plans/{plan}` | `reading-plans.destroy` | `ReadingPlanController` | `destroy` | 読書計画を削除 |
| POST | `/reading-plans/{plan}/complete` | `reading-plans.complete` | `ReadingPlanController` | `complete` | 読書計画を読了に変更 |

読書計画の編集・更新・削除・読了では `ReadingPlanPolicy` により、読書計画の所有者本人のみ操作できるようにする。

## 通知

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/notifications` | `notifications.index` | `NotificationController` | `index` | 自分宛ての通知一覧を表示 |
| POST | `/notifications/{notification}/read` | `notifications.read` | `NotificationController` | `markAsRead` | 自分宛ての通知を既読にする |

通知の既読処理では `NotificationPolicy` により、通知の送信先本人のみ既読にできるようにする。

## ISBN検索

| Method | URI | Route名 | Controller | Action | 役割 |
| --- | --- | --- | --- | --- | --- |
| GET | `/books/isbn/{isbn}` | `books.isbn.show` | `IsbnLookupController` | `show` | ISBNから書籍情報を取得 |

ISBN検索は書籍登録画面で利用する補助機能である。
Google Books APIから取得した書籍情報をJSONで返す。



---

# API Route

## API設計方針

API Route は `/api/v1` を共通prefixとし、Route名は `api.v1.` を共通prefixにする。

| 項目 | 方針 |
| --- | --- |
| バージョン | `/api/v1` |
| Route名 | `api.v1.*` |
| レスポンス | JSON |
| 認証 | 書き込み系のみ `auth:sanctum` |
| 認可 | 更新・削除で `can` ミドルウェアを使用 |

## 書籍API

| Method | URI | Route名 | Controller | Action | 認証 | 認可 | 役割 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GET | `/api/v1/books` | `api.v1.books.index` | `Api\V1\BookController` | `index` | 不要 | 不要 | 書籍一覧を取得 |
| GET | `/api/v1/books/{book}` | `api.v1.books.show` | `Api\V1\BookController` | `show` | 不要 | 不要 | 書籍詳細を取得 |
| POST | `/api/v1/books` | `api.v1.books.store` | `Api\V1\BookController` | `store` | 必要 | 不要 | 書籍を登録 |
| PUT | `/api/v1/books/{book}` | `api.v1.books.update` | `Api\V1\BookController` | `update` | 必要 | `can:update,book` | 書籍を更新 |
| DELETE | `/api/v1/books/{book}` | `api.v1.books.destroy` | `Api\V1\BookController` | `destroy` | 必要 | `can:delete,book` | 書籍を削除 |

## 書籍一覧API

`GET /api/v1/books` では、Web側の書籍一覧と同じように検索・絞り込み・並び替えを扱う。

| Query | バリデーション | 役割 |
| --- | --- | --- |
| `keyword` | 文字列、255文字以内 | タイトル・著者名検索 |
| `genre` | 存在するジャンルID | ジャンル絞り込み |
| `sort` | `newest`, `oldest`, `rating`, `title` | 並び替え |
| `page` | 1以上の整数 | ページ番号 |
| `per_page` | 1以上100以下の整数 | 1ページあたりの件数 |

一覧レスポンスでは、書籍情報、ジャンル、平均評価を返す。
また、追加情報としてジャンル一覧を `genres` に含める。

## 書籍詳細API

`GET /api/v1/books/{book}` では、指定した書籍の詳細、ジャンル、レビューを返す。

レビューには以下の情報を含める。

| 項目 | 内容 |
| --- | --- |
| `id` | レビューID |
| `rating` | 評価 |
| `comment` | コメント |
| `created_at` | レビュー作成日時 |
| `likes_count` | レビューへのいいね数 |
| `user` | レビュー投稿者のID・名前 |

## 書籍登録API

`POST /api/v1/books` は Sanctum 認証済みユーザーのみ利用できる。

登録者IDはリクエストの `user_id` ではなく、アクセストークンから取得できる認証ユーザーIDを使用する。
そのため、リクエストから `user_id` は受け取らない。

書籍登録とジャンル紐付けは同じトランザクション内で実行する。

## 書籍更新API

`PUT /api/v1/books/{book}` は Sanctum 認証に加えて、`can:update,book` による所有者確認を行う。

更新時も `user_id` は変更しない。
書籍情報の更新とジャンル紐付けの更新は同じトランザクション内で実行する。

## 書籍削除API

`DELETE /api/v1/books/{book}` は Sanctum 認証に加えて、`can:delete,book` による所有者確認を行う。

削除成功時は `204 No Content` を返す。

## APIエラーレスポンス

| 状態 | HTTPステータス | message |
| --- | --- | --- |
| 未認証 | 401 | `認証が必要です。` |
| 認可エラー | 403 | `この操作を行う権限がありません。` |
| 対象書籍なし | 404 | `対象の書籍が見つかりませんでした。` |
| APIエンドポイントなし | 404 | `エンドポイントが見つかりません。` |
| バリデーションエラー | 422 | `入力内容に誤りがあります。` |

---

# Middleware・Policy

## Middleware

| Middleware | 使用箇所 | 役割 |
| --- | --- | --- |
| `guest` | 会員登録・ログイン画面 | 未ログインユーザーのみ許可 |
| `auth` | Webの操作系Route | ログインユーザーのみ許可 |
| `auth:sanctum` | APIの書き込み系Route | APIトークン認証済みユーザーのみ許可 |
| `can:update,book` | API書籍更新 | 書籍所有者のみ更新許可 |
| `can:delete,book` | API書籍削除 | 書籍所有者のみ削除許可 |

## Policy

| Policy | 対象 | 主な使用箇所 |
| --- | --- | --- |
| `BookPolicy` | `Book` | 書籍編集・更新・削除、API更新・削除 |
| `ReviewPolicy` | `Review` | レビュー編集・更新・削除 |
| `ReadingPlanPolicy` | `ReadingPlan` | 読書計画編集・更新・削除・読了 |
| `NotificationPolicy` | `DatabaseNotification` | 通知の既読処理 |

---

# Controller責務

| Controller | 役割 |
| --- | --- |
| `BookController` | Web側の書籍一覧・詳細・登録・編集・更新・削除 |
| `ReviewController` | レビュー登録・編集・更新・削除 |
| `ReviewLikeController` | レビューいいねの登録・解除 |
| `FavoriteController` | お気に入り一覧・登録・解除 |
| `GenreController` | ジャンル一覧・詳細・登録・編集・更新・削除 |
| `RankingController` | 書籍ランキング表示 |
| `ReportController` | マイ読書レポート表示 |
| `ReadingPlanController` | 読書計画一覧・登録・編集・更新・削除・読了 |
| `NotificationController` | 通知一覧・既読処理 |
| `IsbnLookupController` | ISBNによる書籍情報取得 |
| `Api\V1\BookController` | API側の書籍一覧・詳細・登録・更新・削除 |

Controller は、画面表示・リダイレクト・認可呼び出し・データ取得の入口を担当する。
入力値の検証は Request、APIレスポンスの形は Resource に分ける。

---

# Route Model Binding

URLパラメータをもとに、Laravelが対応するModelを自動取得する。

| パラメータ | Model | 主なRoute |
| --- | --- | --- |
| `{book}` | `Book` | `/books/{book}`, `/api/v1/books/{book}` |
| `{review}` | `Review` | `/reviews/{review}`, `/reviews/{review}/like` |
| `{genre}` | `Genre` | `/genres/{genre}` |
| `{plan}` | `ReadingPlan` | `/reading-plans/{plan}` |
| `{notification}` | `DatabaseNotification` | `/notifications/{notification}/read` |

`{book}`、`{review}`、`{genre}` はWeb Routeで `whereNumber()` を指定し、数値IDのみを受け付ける。
