# CI 相当の実行記録
- `docker compose up -d` → `composer install` / `composer update`、`.env` 作成 & `php artisan key:generate`
- `docker compose exec app vendor/bin/phpstan analyze` → OK
- `docker compose exec app composer phpcs .` → OK
- `docker compose exec app vendor/bin/phpunit --debug` → OK（6 tests）
- `cd src && npm ci`
- `cd src && npm run lint` → OK
- `cd src && npm run type-check` → OK
- `cd src && npm run build-storybook` → OK（storage シンボリックリンク修正後／chunk size 警告は現状のまま）
- `cd src && npx playwright install --with-deps`
- `cd src && npm run test-storybook` → OK（29 suites、6006 が埋まっていたため 6007 で実行）
- inertiajs/inertia-laravel を `^2.0.14` に更新、`composer update inertiajs/inertia-laravel` → OK
- （再確認）`docker compose exec app vendor/bin/phpstan analyze` → OK、`docker compose exec app vendor/bin/phpunit --debug` → OK

# メモ
- Dockerfile ベースを PHP 8.4 に上げ、composer.json の php 要件も `^8.4` に揃えました。
- `public/storage` を `../storage/app/public` への相対シンボリックリンクに張り直し、Storybook ビルド時の ENOENT を解消。
- `tsconfig.json` に `types` を追加して Storybook/MDX の型チェックエラーを解消。
- npm audit に 4 件（低1/中2/高1）の脆弱性が残っているので、必要なら `npm audit fix` を検討してください。
- Storybook ビルド時に `stories/**/*.stories.@(js|jsx|mjs|ts|tsx)` でマッチ無し警告が出ます（従来挙動のまま）。
