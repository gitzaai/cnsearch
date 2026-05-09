# CN Search for Flarum 2.0

`gitzaai/cnsearch` 是一个面向 Flarum 2 的 Meilisearch 搜索扩展，用于把讨论标题和帖子内容同步到 Meilisearch，并接管 Flarum 默认讨论搜索的全文检索部分。

## 功能

- 使用 Meilisearch 作为讨论搜索后端
- 每条讨论对应一个 Meilisearch 文档
- 将讨论下所有未隐藏的普通回复聚合到同一个索引文档
- 为中文内容额外生成 CJK n-gram 检索字段，提高中文短词命中率
- 接入 Flarum 顶部默认搜索框
- 将顶部搜索弹窗预览的最小触发长度从 3 降到 1，避免 1-2 个中文字符不触发即时搜索
- 提供搜索、状态检查、连接测试和重建索引 API
- 提供命令行配置、状态检查、搜索测试和重建索引命令
- 在发帖、编辑、隐藏、恢复、删除帖子，以及讨论创建、改名、隐藏、恢复、删除后自动同步索引

## 安装

```bash
composer config repositories.cnsearch vcs https://github.com/gitzaai/cnsearch
composer require gitzaai/cnsearch:dev-main@dev -W --no-audit
php flarum assets:publish
php flarum cache:clear
```

如果 Composer 提示审计失败，可临时加上 `--no-audit`。如果你的站点已经锁定 `meilisearch/meilisearch-php` 2.x beta，请保留 `-W` 让 Composer 重新解依赖。

> 注意：Flarum 2.0 beta 8 及以后版本，安装或更新扩展后应执行 `php flarum assets:publish`，以确保前端资产与当前扩展代码同步。

## 配置

推荐使用扩展自带命令写入 Flarum `settings` 表：

```bash
php flarum cnsearch:configure http://YOUR_MEILISEARCH_HOST:7700 --key=YOUR_MEILISEARCH_API_KEY --index=flarum_discussions
php flarum cache:clear
```

也可以手动写入数据库：

```sql
INSERT INTO settings (`key`, `value`) VALUES
  ('cnsearch.meili.host', 'http://YOUR_MEILISEARCH_HOST:7700'),
  ('cnsearch.meili.key', 'YOUR_MEILISEARCH_API_KEY'),
  ('cnsearch.meili.index', 'flarum_discussions')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
```

如果你的 Flarum 数据表有前缀，请把 `settings` 改成对应的 `前缀_settings`。

## 验证

重建索引：

```bash
php flarum cnsearch:reindex
```

> 如果输入 php flarum cnsearch:reindex
>
>  提示  There are no commands defined in the "cnsearch" namespace.
>
> 请进入网站后台管理面板启用 **CN Search** 插件，再执行此命令。



查看状态：

```bash
php flarum cnsearch:status
```

状态里的 `Documents` 是 Meilisearch 文档数，也就是已索引的讨论数。由于扩展是一条讨论一个文档，所以如果论坛有 8 个未隐藏讨论，`Documents: 8` 是正常的。

`Source posts` 是参与索引的未隐藏普通回复数。如果有很多回复，这个数应该大于 `Documents`。

直接测试 Meilisearch 是否能搜到某个中文词：

```bash
php flarum cnsearch:search 中文关键词
```

如果这个命令能返回讨论 ID，Flarum 顶部搜索框也应该能搜到对应讨论。更新扩展后请务必执行：

```bash
composer dump-autoload -o
php flarum cache:clear
php flarum cnsearch:reindex
```

## API

搜索：

```bash
curl "https://your-flarum-site.example/api/cnsearch/search?q=关键词&page=1&perPage=20"
```

状态检查和重建索引需要管理员权限：

```bash
curl "https://your-flarum-site.example/api/cnsearch/status"
curl -X POST "https://your-flarum-site.example/api/cnsearch/reindex"
curl "https://your-flarum-site.example/api/cnsearch/test-connection"
```

## 中文搜索说明

扩展会把原始标题、原始内容以及中文 n-gram 字段一起写入 Meilisearch。这样即使 Meilisearch 当前实例没有理想的中文分词配置，常见中文短词也可以命中。

## 注意

- Meilisearch API Key 属于敏感信息，不要放到公开仓库、前端代码或截图中。
- 如果更新后 Flarum 顶部搜索仍没有走新索引，请先确认 `php flarum cache:clear` 已执行，并在浏览器中强制刷新前台页面。
