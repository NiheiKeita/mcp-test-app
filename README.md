# Laravel TV API + MCP Server

このリポジトリは、Laravelで動くTV登録APIと、OpenAPIを読んでAPIを叩くMCPサーバ、TV登録ウィザードを含みます。

## 起動手順（Laravel）

```bash
# 起動
docker compose up -d

# 依存インストール
docker compose exec app composer install

# SQLiteファイル作成
docker compose exec app touch database/database.sqlite

# マイグレーション & シード
docker compose exec app php artisan migrate --seed
```

- APIは `http://localhost:8081/api` で動作します。

## OpenAPI

- ファイル: `src/openapi.yaml`
- Laravelから取得: `http://localhost:8081/api/openapi.yaml`
- Swagger UI: `http://localhost:8082`

## MCPサーバ起動手順

```bash
cd mcp
cp .env.example .env
npm install
npm run dev
```

- `mcp/.env` の `MCP_API_BASE_URL` でLaravel APIのURLを指定できます。
- MCP HTTP Bridge は `http://localhost:5175`（`MCP_HTTP_PORT`）で起動します。

## WebチャットUI

Inertia React のチャットUIは `http://localhost:8081/mcp-chat` です。

```bash
cd src
npm install
npm run dev
```

- `src/.env` に `OPENAI_API_KEY` を設定してください。
- MCP HTTP Bridge のURLは `MCP_HTTP_URL`（Laravel側）で変更できます。
- フロントから別ホストに投げる場合は `VITE_MCP_CHAT_URL` を `src/.env` に設定します（未設定なら同一オリジン）。

## 動作確認（API）

```bash
# TVオプション一覧
curl "http://localhost:8081/api/tv-options"

# TV作成
curl -X POST "http://localhost:8081/api/tvs" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "BRAVIA X90L",
    "maker": "SONY",
    "inch": 55,
    "resolution": "4K",
    "panel": "LCD",
    "hdmi_ports": 4,
    "has_hdr": true,
    "has_wifi": true,
    "selected_options": [
      {"tv_option_id": 1, "quantity": 1}
    ]
  }'

# TV一覧
curl "http://localhost:8081/api/tvs?maker=SONY"
```

## 動作確認（MCPツール例）

- `api.list_tools_from_openapi`

```json
{
  "tool": "api.list_tools_from_openapi",
  "arguments": {
    "openapiPath": "../src/openapi.yaml"
  }
}
```

- `api.call`

```json
{
  "tool": "api.call",
  "arguments": {
    "method": "GET",
    "path": "/api/tvs"
  }
}
```

- TV登録ウィザード

```json
{ "tool": "tvWizard.start", "arguments": {} }
```

```json
{ "tool": "tvWizard.next", "arguments": { "wizard_id": "..." } }
```

```json
{ "tool": "tvWizard.submit", "arguments": { "wizard_id": "...", "field": "maker", "value": "SONY" } }
```

```json
{ "tool": "tvWizard.confirm", "arguments": { "wizard_id": "..." } }
```

```json
{ "tool": "tvWizard.create", "arguments": { "wizard_id": "..." } }
```
