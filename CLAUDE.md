# markdown-flex-php プロジェクト記録

## 概要
MarkdownをLINE WORKS Bot用Flexible Templateに変換するPHPライブラリ。
DESIGN.mdに基づく完全実装で、変換と検証の両機能を提供。

## 主要コンポーネント

### 変換系クラス
- **MarkdownFlexConverter**: メインファサード
- **ComponentFactory**: Markdown要素→Flexコンポーネント変換
- **BubbleBuilder**: 10KB制限管理、自動分割
- **CarouselBuilder**: 50KB/10Bubble制限、altText生成（400文字制限）
- **NodeVisitor**: AST走査
- **SizeCalculator**: UTF-8バイト長測定

### 検証系クラス
- **FlexValidator**: Flexメッセージの完全検証
  - 構造検証（flex/bubble/carousel）
  - サイズ制限チェック
  - コンポーネント仕様検証（box/text/image/button/icon等）
  - 特殊形式対応（hero-only bubble、separator footer）

### テーマ・パーサー
- **CommonMarkParser**: league/commonmark使用
- **DefaultTheme/DarkTheme**: カスタマイズ可能

## CLIツール

### markdown-flex.php（変換ツール）
```bash
php8.3 markdown-flex.php -f input.md              # 標準出力
php8.3 markdown-flex.php -f input.md -o out.json  # ファイル出力
php8.3 markdown-flex.php -f input.md -t dark      # テーマ指定
php8.3 markdown-flex.php -f input.md -c           # コード画像化
```

### flex-checker.php（検証ツール）
```bash
php8.3 flex-checker.php -f message.json           # ファイル検証
php8.3 flex-checker.php -j '{"type":"flex",...}'  # JSON文字列検証
php8.3 flex-checker.php -v                        # 詳細出力
cat message.json | php8.3 flex-checker.php        # パイプ入力
```

LINE WORKS Flex Simulatorと同等の検証機能を提供。samplesディレクトリの9サンプル全てに対応。

## 出力形式

### Flexメッセージ形式
```json
{
  "type": "flex",
  "altText": "400文字以内",
  "contents": {
    "type": "bubble",
    "body": {...}
  }
}
```

### 制限自動対応
- Bubble: ≤10KB（超過時に自動分割）
- Text: ≤2000文字（自動切り詰め）
- altText: ≤400文字（自動切り詰め）
- Carousel: ≤10 Bubbles、≤50KB

## Markdown対応要素
| 要素 | Flexコンポーネント |
|-----|------------------|
| `# 見出し` | text (size: xxl~xs) |
| 段落 | text (wrap: true) |
| `- リスト` | box + bullet + text |
| `![画像](url)` | image |
| `` `code` `` | text/box |
| `> 引用` | box (背景色付き) |

## インストール

### Composerローカルパス
```json
{
  "repositories": [{"type": "path", "url": "../markdown-flex-php"}],
  "require": {"kijtkd/markdown-flex-php": "*"}
}
```

## 使用例

### 基本変換
```php
use MdFlex\MarkdownFlexConverter;

$converter = new MarkdownFlexConverter();
[$json, $altText] = $converter->convert($markdown);
```

### 検証
```php
use MdFlex\FlexValidator;

$validator = new FlexValidator();
$errors = $validator->validate($flexMessage);
if (empty($errors)) {
    // Valid
}
```

## 動作環境
- PHP 8.1以上（8.3で動作確認済み）
- 依存: league/commonmark ^2.4, psr/simple-cache ^3.0

## 除外ファイル
- DESIGN.md（設計書）
- .claude/（メタデータ）
- 出力JSONファイル