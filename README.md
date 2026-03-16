# SarionOS Module Template

Base Laravel module template for SarionOS applications.

## Included

- Core SSO authentication flow
- Shared `sarionos_sso` cookie handling
- Token validation against Core
- Workspace context loading from Core
- Shared SarionOS UI package integration
- Vite + Tailwind setup
- Neutral starter dashboard
- Reverse-proxy friendly HTTPS handling

## Required environment

```env
APP_URL=https://your-module.dev.sarionos.com
SARIONOS_CORE_URL=https://core.dev.sarionos.com
SARIONOS_SELF_URL=https://your-module.dev.sarionos.com
SESSION_DOMAIN=.dev.sarionos.com
SESSION_COOKIE=your_unique_module_session_cookie
