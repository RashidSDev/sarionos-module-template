# SarionOS Module Template

Base Laravel module template for SarionOS capability modules.

This template is used to create standalone SarionOS modules that connect to Core, Web, and the shared SarionOS UI package.

A module owns its own business records only.

Core owns identity and SSO.
Web owns the operational surface, contexts, shares, comments, likes, and public access.
Files owns canonical binaries.

---

## Repository

Repository:

    https://github.com/RashidSDev/sarionos-module-template.git

Development branch:

    main

---

## Required environment keys

Each module must use environment-driven domains.

Development example:

    APP_URL=https://template.dev.sarionos.com
    SARIONOS_MODULE_KEY=template
    SARIONOS_MODULE_NAME=Template

    SARIONOS_DOMAIN=dev.sarionos.com
    SARIONOS_COOKIE_DOMAIN=.dev.sarionos.com
    SESSION_DOMAIN=.dev.sarionos.com
    SESSION_COOKIE=sarionos_template_session

    SARIONOS_CORE_URL=https://core.dev.sarionos.com
    SARIONOS_WEB_URL=https://app.dev.sarionos.com
    SARIONOS_SELF_URL=https://template.dev.sarionos.com

Production example:

    APP_URL=https://template.sarionos.com
    SARIONOS_MODULE_KEY=template
    SARIONOS_MODULE_NAME=Template

    SARIONOS_DOMAIN=sarionos.com
    SARIONOS_COOKIE_DOMAIN=.sarionos.com
    SESSION_DOMAIN=.sarionos.com
    SESSION_COOKIE=sarionos_template_session

    SARIONOS_CORE_URL=https://core.sarionos.com
    SARIONOS_WEB_URL=https://app.sarionos.com
    SARIONOS_SELF_URL=https://template.sarionos.com

Do not hardcode dev domains in PHP, Blade, JS, config, routes, or seeders.

---

## Development workflow

Work inside the module checkout:

    cd /mnt/web_files/sarionos/modules/module-template
    git status --short
    git branch -vv

Before changing a file:

    cp path/to/file.php path/to/file.php.bak_YYYYMMDD_reason

After PHP, route, config, or Blade changes:

    php -l path/to/changed-file.php
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

After JS or CSS changes:

    node --check resources/js/app.js
    npm run build

Restore generated Laravel cache before commit:

    git restore bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null || true

---

## Commit and push

Check status:

    git status --short

Stage explicit files when possible:

    git add README.md

Commit:

    git commit -m "Update module template deployment instructions"

Push current branch:

    git push origin HEAD

Check GitHub auth safely:

    git fetch origin
    git push --dry-run origin HEAD

Remote URL must stay clean:

    git remote -v

Expected format:

    https://github.com/RashidSDev/sarionos-module-template.git

Do not store GitHub tokens inside remote URLs.

---

## Deploy to development server

Development domain pattern:

    template.dev.sarionos.com

Deploy:

    cd /mnt/web_files/sarionos/modules/module-template
    git pull origin main
    composer install
    npm install
    npm run build
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan migrate

Check:

    php artisan route:list
    git status --short

---

## Deploy to production server

Production domain pattern:

    template.sarionos.com

Before production deployment, update `.env` domain keys.

Deploy:

    cd /mnt/web_files/sarionos/modules/module-template
    git pull origin main
    composer install --no-dev --optimize-autoloader
    npm install
    npm run build
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan migrate --force

Optional optimized cache after verification:

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

---

## Domain switch checklist

When switching from development to production, update only `.env` values:

    APP_URL
    SARIONOS_DOMAIN
    SARIONOS_COOKIE_DOMAIN
    SESSION_DOMAIN
    SARIONOS_CORE_URL
    SARIONOS_WEB_URL
    SARIONOS_SELF_URL

Then clear cache:

    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

Run hardcoded development-domain scan:

    grep -R "https://.*dev.sarionos.com\|'.dev.sarionos.com'\|\".dev.sarionos.com\"" -n \
      app routes config resources database vite.config.js \
      --exclude="*copy*.php" \
      --exclude="*old*.blade.php" \
      --exclude-dir=vendor \
      --exclude-dir=node_modules \
      --exclude-dir=storage \
      --exclude-dir=bootstrap/cache || true

Expected result: no active hardcoded development domains.

---

## Creating a new module from this template

1. Copy the template repository.
2. Rename APP_URL, SARIONOS_SELF_URL, SESSION_COOKIE, SARIONOS_MODULE_KEY, and SARIONOS_MODULE_NAME.
3. Keep Core/Web/SSO middleware behavior unchanged.
4. Add module business records only inside the module.
5. Do not duplicate Web-owned systems such as shares, comments, likes, public access, or contexts.
6. Do not store binaries directly; use Files and store file UUID references.

---

## Related repositories

    sarionos-core
    sarionos-web
    sarionos-ui
    sarionos-files
    sarionos-media
    sarionos-documents
    sarionos-vault
