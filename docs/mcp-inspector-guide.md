# MCP Inspector 手順書

この手順書は、MCP Inspector を使って TV 登録 API とウィザードを操作する流れをまとめたものです。

## 1. 前提

- Docker で Laravel API が起動できること
- Node.js が使えること

## 2. Laravel API 起動

```bash
docker compose up -d
```

## 3. MCP サーバ起動

別ターミナルで実行します。

```bash
cd /Users/niheikeita/develop/mcp-test-app/mcp
npm run dev
```

## 4. MCP Inspector 起動

別ターミナルで実行します。

```bash
cd /Users/niheikeita/develop/mcp-test-app
npx @modelcontextprotocol/inspector
```

## 5. Inspector 接続設定

Inspector の接続設定を次のように入力します。

- Transport: `stdio`
- Command: `node`
- Args: `/Users/niheikeita/develop/mcp-test-app/mcp/dist/index.js`
- Environment Variables:
  - `MCP_API_BASE_URL=http://localhost:8081`

## 6. OpenAPI 読み込み確認

- Tool: `api.list_tools_from_openapi`
- openapiPath*: `/Users/niheikeita/develop/mcp-test-app/src/openapi.yaml`

## 7. API 呼び出し確認

- Tool: `api.call`
- Arguments:

```json
{
  "method": "GET",
  "path": "/api/tv-options"
}
```

## 8. ウィザード開始

- Tool: `tvWizard.start`
- Arguments:

```json
{}
```

返ってきた `wizard_id` を控えます。

## 9. ウィザード進行

- Tool: `tvWizard.next`
- Arguments:

```json
{ "wizard_id": "取得したID" }
```

## 10. 質問に回答

例: maker を SONY にする場合

- Tool: `tvWizard.submit`
- Arguments:

```json
{
  "wizard_id": "取得したID",
  "field": "maker",
  "value": "SONY"
}
```

同様に `tvWizard.next` と `tvWizard.submit` を繰り返します。

## 11. 確認

- Tool: `tvWizard.confirm`
- Arguments:

```json
{ "wizard_id": "取得したID" }
```

## 12. 作成

- Tool: `tvWizard.create`
- Arguments:

```json
{ "wizard_id": "取得したID" }
```
