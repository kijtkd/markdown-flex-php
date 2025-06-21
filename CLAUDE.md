# markdown-flex-php プロジェクト記録

## 概要
MarkdownをLINE WORKS Bot用Flexible Templateに変換するPHPライブラリの実装。
DESIGN.mdの仕様に基づき、完全な機能を持つライブラリとCLIツールを作成。

## 実装コンポーネント

### 核となるクラス
- **MarkdownFlexConverter**: メインファサード
- **SizeCalculator**: UTF-8バイト長測定
- **BubbleBuilder**: 10KB制限管理、自動分割
- **CarouselBuilder**: 50KB/10Bubble制限、altText生成
- **ComponentFactory**: Markdown要素→Flexコンポーネント変換
- **NodeVisitor**: AST走査とコンポーネント生成

### パーサー・テーマ
- **CommonMarkParser**: league/commonmark使用、テーブル拡張対応
- **DefaultTheme/DarkTheme**: カスタマイズ可能なUI設定

## CLIツール: markdown-flex.php

### 機能
```bash
php8.3 markdown-flex.php -f input.md              # 標準出力
php8.3 markdown-flex.php -f input.md -o out.json  # ファイル出力
php8.3 markdown-flex.php -f input.md -t dark      # ダークテーマ
php8.3 markdown-flex.php -f input.md -c           # コード画像化
```

### オプション（getopt使用）
- `-f <file>`: 入力Markdownファイル（必須）
- `-o <file>`: 出力JSONファイル（省略時は標準出力）
- `-t <theme>`: テーマ（default|dark）
- `-c`: コードブロック画像化
- `-h`: ヘルプ表示

## LINE WORKS Bot対応

### 最終出力形式
samplesディレクトリを参考に修正済み：
```json
{
  "type": "flex",
  "altText": "テキスト（400文字以内）",
  "contents": {
    "type": "bubble",
    "body": {...}
  }
}
```

### 制限対応
- **Bubble**: ≤10KB（自動分割）
- **テキスト**: ≤2000文字（自動切り詰め）
- **altText**: ≤400文字（自動切り詰め）
- **Carousel**: ≤10 Bubble、≤50KB

## パッケージ構成

### Composer設定
- **パッケージ名**: `kijtkd/markdown-flex-php`
- **PHP要件**: ^8.1
- **依存関係**: league/commonmark ^2.4, psr/simple-cache ^3.0
- **PSR-4**: `MdFlex\\` → `src/`

### インストール方法
```bash
# ローカルパス指定
composer require kijtkd/markdown-flex-php

# またはパス指定でリポジトリ追加
{
  "repositories": [{"type": "path", "url": "../markdown-flex-php"}],
  "require": {"kijtkd/markdown-flex-php": "*"}
}
```

## 使用例

### 基本的な使用
```php
use MdFlex\MarkdownFlexConverter;

$converter = new MarkdownFlexConverter();
[$json, $altText] = $converter->convert($markdown);

// LINE WORKS Bot送信
$client->post('/messages', [
    'type' => 'flex',
    'altText' => $altText,
    'contents' => $json
]);
```

### テーマとオプション
```php
use MdFlex\Theme\DarkTheme;

$converter = (new MarkdownFlexConverter())
    ->setTheme(new DarkTheme())
    ->setOptions(['code_img' => true, 'max_lines' => 18]);
```

## テスト
- PHPUnit設定済み（`composer test`）
- 基本機能のユニットテスト実装
- サイズ計算、変換機能の検証

## 注意事項
- DESIGN.mdはリポジトリに含めない（設計書のため）
- .claude/ディレクトリもコミット対象外
- PHP 8.3での動作確認済み