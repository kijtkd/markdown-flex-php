# markdown-flex-php プロジェクト記録

## 概要
MarkdownをLINE WORKS Bot用Flexible Templateに変換するPHPライブラリ。
変換・検証・CLI機能を提供する完全実装。

## 主要機能
- **変換**: Markdown → LINE WORKS Flex Message
- **検証**: Flex Message仕様準拠チェック
- **制限対応**: サイズ・文字数制限の自動処理
- **CLI**: コマンドライン変換・検証ツール

## アーキテクチャ
### 名前空間: `Kijtkd\`
- **MarkdownFlexConverter**: メインファサード
- **ComponentFactory**: Markdown→Flex変換ロジック
- **FlexValidator**: Flex仕様検証
- **BubbleBuilder/CarouselBuilder**: サイズ制限管理
- **Theme**: DefaultTheme/DarkTheme

## Markdown対応要素
| 要素 | Flexコンポーネント | 備考 |
|-----|------------------|------|
| `# 見出し` | text | サイズ: xxl(H1)~xs(H6) |
| 段落 | text | wrap: true |
| `- リスト` | box + text | 順序・非順序対応 |
| `![画像](url)` | image | aspectRatio: 20:13 |
| `` `code` `` | text/box | monospace styling |
| `> 引用` | box | 背景色付き |
| テーブル | box(vertical) + box(horizontal) | ヘッダー・データ行対応 |

## CLI使用方法
```bash
# 変換
php8.3 markdown-flex.php -f input.md              # stdout
php8.3 markdown-flex.php -f input.md -o out.json  # ファイル出力
php8.3 markdown-flex.php -f input.md -t dark      # テーマ指定

# 検証
php8.3 flex-checker.php -f message.json           # ファイル
php8.3 flex-checker.php -v                        # 詳細モード
cat message.json | php8.3 flex-checker.php        # パイプ
```

## 基本使用例
```php
use Kijtkd\MarkdownFlexConverter;
use Kijtkd\FlexValidator;

// 変換
$converter = new MarkdownFlexConverter();
[$json, $altText] = $converter->convert($markdown);

// 検証
$validator = new FlexValidator();
$errors = $validator->validate($flexMessage);
```

## LINE WORKS制限
自動対応済み：
- Bubble: ≤10KB（超過時自動分割）
- Text: ≤2000文字（自動切り詰め）
- altText: ≤400文字（自動切り詰め）
- Carousel: ≤10 Bubbles、≤50KB

## インストール
```json
{
  "repositories": [{"type": "path", "url": "../markdown-flex-php"}],
  "require": {"kijtkd/markdown-flex-php": "*"}
}
```

## 技術仕様
- PHP 8.1+ (8.3で検証済み)
- league/commonmark ^2.4
- PSR-4: `Kijtkd\` → `src/`
- Flex Simulator互換検証

## 開発履歴
### v1.0 - 基本実装
- 基本Markdown要素対応
- Flex変換・検証機能
- CLI tools

### v1.1 - テーブル対応
- テーブル要素の完全実装
- インラインコード・画像のテーブル内対応
- NodeVisitor最適化

### v1.2 - 名前空間変更
- MdFlex → Kijtkd名前空間変更
- 全ファイル・ドキュメント更新完了

## 除外ファイル
- DESIGN.md（設計書）
- .claude/（メタデータ）
- test-*.md、*.json（テスト用）