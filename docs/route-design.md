## 概要

BookShelf本レビューアプリの基礎機能に関するルーティング設計を定義する。

本設計書では、以下の基礎機能を対象とする。

- 会員登録・ログイン・ログアウト機能
- 書籍一覧・詳細・登録・編集・削除機能
- レビュー投稿・編集・削除機能
- お気に入り機能
- レビューいいね機能
- ジャンル管理機能
- ランキング機能
- 書籍API

## 設計方針

- トップ画面のURLは `/books` に統一する。
- `/` は使用しない。
- Webの認証必須Routeには `auth` ミドルウェアを使用する。
- 書籍の編集・更新・削除には `BookPolicy` を使用する。
- レビューの編集・更新・削除には `ReviewPolicy` を使用する。
- APIは基礎段階では認証なしで実装する。
- Route NameはLaravelのresource Routeの命名規則に合わせる。
- `{book}`、`{review}`、`{genre}`にはRoute Model Bindingを使用する。

---

# Web Route

## 認証機能（Fortify）

Fortifyが認証Routeを登録する。

| Method | URI | 処理 | Route Name | Middleware |
| --- | --- | --- | --- | --- |
| GET | `/register` | 会員登録画面を表示 | `register` | `guest` |
| POST | `/register` | ユーザー登録処理 | ― | `guest` |
| GET | `/login` | ログイン画面を表示 | `login` | `guest` |
| POST | `/login` | ログイン処理 | ― | `guest` |
| POST | `/logout` | ログアウト処理 | `logout` | `auth` |

### 補足

- 会員登録成功後は `/login` へ遷移する。
- ログイン成功後は `/books` へ遷移する。
- ログアウト後は `/login` へ遷移する。
- ログイン済みユーザーが `/login` または `/register` へアクセスした場合は、`/books` へリダイレクトする。

---

## 書籍閲覧・ランキング機能

認証：不要

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| GET | `/books` | `BookController` | `index` | `books.index` | ― | ― |
| GET | `/books/{book}` | `BookController` | `show` | `books.show` | ― | ― |
| GET | `/ranking` | `RankingController` | `index` | `ranking.index` | ― | ― |

### 補足

- `/books` は書籍一覧画面として使用する。
- 書籍一覧は10件ごとにページネーションする。
- `/books/{book}` と `/ranking` はゲストもアクセスできる。

---

## 書籍管理機能

middleware: `auth`

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| GET | `/books/create` | `BookController` | `create` | `books.create` | `auth` | ― |
| POST | `/books` | `BookController` | `store` | `books.store` | `auth` | ― |
| GET | `/books/{book}/edit` | `BookController` | `edit` | `books.edit` | `auth` | `BookPolicy@update` |
| PUT | `/books/{book}` | `BookController` | `update` | `books.update` | `auth` | `BookPolicy@update` |
| DELETE | `/books/{book}` | `BookController` | `destroy` | `books.destroy` | `auth` | `BookPolicy@delete` |

### 補足

- 書籍登録は認証済みユーザーが実行できる。
- 書籍の編集・更新・削除は、書籍登録者本人だけが実行できる。
- 別ユーザーによる編集・更新・削除はHTTP 403とする。
- 未認証ユーザーは `/login` へリダイレクトする。

---

## レビュー機能

middleware: `auth`

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| POST | `/books/{book}/reviews` | `ReviewController` | `store` | `reviews.store` | `auth` | ― |
| GET | `/reviews/{review}/edit` | `ReviewController` | `edit` | `reviews.edit` | `auth` | `ReviewPolicy@update` |
| PUT | `/reviews/{review}` | `ReviewController` | `update` | `reviews.update` | `auth` | `ReviewPolicy@update` |
| DELETE | `/reviews/{review}` | `ReviewController` | `destroy` | `reviews.destroy` | `auth` | `ReviewPolicy@delete` |

### 補足

- レビュー投稿は認証済みユーザーが実行できる。
- 同一ユーザーが同一書籍へ投稿できるレビューは1件までとする。
- レビューの編集・更新・削除は、レビュー投稿者本人だけが実行できる。
- 別ユーザーによる編集・更新・削除はHTTP 403とする。
- 未認証ユーザーは `/login` へリダイレクトする。

---

## お気に入り機能

middleware: `auth`

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| GET | `/favorites` | `FavoriteController` | `index` | `favorites.index` | `auth` | ― |
| POST | `/books/{book}/favorites` | `FavoriteController` | `toggle` | `favorites.toggle` | `auth` | ― |

### 補足

`FavoriteController@toggle`は、現在の登録状態に応じて以下を切り替える。

- 未登録の場合：お気に入りへ追加する。
- 登録済みの場合：お気に入りを解除する。

---

## レビューいいね機能

middleware: `auth`

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| POST | `/reviews/{review}/like` | `ReviewLikeController` | `toggle` | `review-likes.toggle` | `auth` | ― |

### 補足

`ReviewLikeController@toggle`は、現在の登録状態に応じて以下を切り替える。

- 未登録の場合：レビューへいいねを追加する。
- 登録済みの場合：レビューのいいねを解除する。

---

## ジャンル管理機能

middleware: `auth`

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| GET | `/genres` | `GenreController` | `index` | `genres.index` | `auth` | ― |
| GET | `/genres/create` | `GenreController` | `create` | `genres.create` | `auth` | ― |
| POST | `/genres` | `GenreController` | `store` | `genres.store` | `auth` | ― |
| GET | `/genres/{genre}` | `GenreController` | `show` | `genres.show` | `auth` | ― |
| GET | `/genres/{genre}/edit` | `GenreController` | `edit` | `genres.edit` | `auth` | ― |
| PUT | `/genres/{genre}` | `GenreController` | `update` | `genres.update` | `auth` | ― |
| DELETE | `/genres/{genre}` | `GenreController` | `destroy` | `genres.destroy` | `auth` | ― |

### 補足

- 書籍との紐付けがあるジャンルは削除しない。
- 削除を拒否した場合は、`/genres`へリダイレクトしてエラーメッセージを表示する。
- 基礎要件では、ジャンル操作は認証済みユーザーへ許可する。

---

# API Route

## Prefix

```text
/api/v1
```

基礎段階では、すべてのAPIエンドポイントを認証なしで実装する。

| Method | URI | Controller | Action | Route Name | Middleware | Policy |
| --- | --- | --- | --- | --- | --- | --- |
| GET | `/api/v1/books` | `Api\V1\BookController` | `index` | `api.v1.books.index` | ― | ― |
| GET | `/api/v1/books/{book}` | `Api\V1\BookController` | `show` | `api.v1.books.show` | ― | ― |
| POST | `/api/v1/books` | `Api\V1\BookController` | `store` | `api.v1.books.store` | ― | ― |
| PUT | `/api/v1/books/{book}` | `Api\V1\BookController` | `update` | `api.v1.books.update` | ― | ― |
| DELETE | `/api/v1/books/{book}` | `Api\V1\BookController` | `destroy` | `api.v1.books.destroy` | ― | ― |

## APIステータスコード

| 処理 | 成功時 |
| --- | --- |
| 書籍一覧取得 | `200 OK` |
| 書籍詳細取得 | `200 OK` |
| 書籍登録 | `201 Created` |
| 書籍更新 | `200 OK` |
| 書籍削除 | `204 No Content` |

## APIエラー

| 状況 | ステータス |
| --- | --- |
| 対象書籍が存在しない | `404 Not Found` |
| バリデーションエラー | `422 Unprocessable Entity` |

### 補足

- 基礎段階では、書籍登録APIはリクエストの`user_id`を使用する。
- 書籍更新APIでは`user_id`を受け取らず、登録時の所有者を維持する。
- 書籍削除成功時はレスポンスボディを返さない。

---

# Middleware・Policy一覧

## Middleware

| Middleware | 役割 |
| --- | --- |
| `guest` | 未ログインユーザーだけが認証画面へアクセスできるようにする |
| `auth` | Web Routeでログイン済みか確認する |

## Policy

| Model | Policy | Ability | 対象Route |
| --- | --- | --- | --- |
| `Book` | `BookPolicy` | `update` | `books.edit`、`books.update` |
| `Book` | `BookPolicy` | `delete` | `books.destroy` |
| `Review` | `ReviewPolicy` | `update` | `reviews.edit`、`reviews.update` |
| `Review` | `ReviewPolicy` | `delete` | `reviews.destroy` |

## 責務分担

- Middleware：ユーザーが認証済みかを確認する。
- Policy：認証済みユーザーが対象データを操作できるか判定する。
- Controller：認証・認可通過後の処理を行う。

---

# Controller一覧

## Web

- `BookController`
- `ReviewController`
- `FavoriteController`
- `ReviewLikeController`
- `GenreController`
- `RankingController`

## 認証

- Laravel FortifyのControllerを使用する。

## API

- `Api\V1\BookController`

---

# Route Model Binding

URLパラメータをもとに、Laravelが対応するModelを自動取得する。

## Book

| URI | Controller | Action |
| --- | --- | --- |
| `/books/{book}` | `BookController` | `show`、`update`、`destroy` |
| `/books/{book}/edit` | `BookController` | `edit` |
| `/books/{book}/favorites` | `FavoriteController` | `toggle` |
| `/books/{book}/reviews` | `ReviewController` | `store` |
| `/api/v1/books/{book}` | `Api\V1\BookController` | `show`、`update`、`destroy` |

## Review

| URI | Controller | Action |
| --- | --- | --- |
| `/reviews/{review}/edit` | `ReviewController` | `edit` |
| `/reviews/{review}` | `ReviewController` | `update`、`destroy` |
| `/reviews/{review}/like` | `ReviewLikeController` | `toggle` |

## Genre

| URI | Controller | Action |
| --- | --- | --- |
| `/genres/{genre}` | `GenreController` | `show`、`update`、`destroy` |
| `/genres/{genre}/edit` | `GenreController` | `edit` |
