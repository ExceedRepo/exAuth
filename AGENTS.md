# exAuth — AGENTS.md

Project repository for **exAuth** authentication library for CodeIgniter 4.

## repo structure

```
exAuth/
├── src/
│   ├── Authentication/       # Auth factory + interface + 4 authenticators
│   │   └── Authenticators/   # Session, AccessTokens, HmacSha256, JWT
│   ├── Authorization/        # Groups, Permission, PermissionMatcher (wildcard)
│   ├── Config/               # exAuth.php (main config), AuthGroups.php, Registrar.php
│   ├── Controllers/          # LoginController, RegisterController, MagicLinkController
│   ├── Database/Migrations/  # 5 migration files (users, identities, logins, tokens, groups)
│   ├── Entities/             # User, UserIdentity, AccessToken, Login, Group
│   ├── Exceptions/           # ExAuthException
│   ├── Filters/              # 7 filter files (Session, Token, Hmac, JWT, Chain, Group, Permission)
│   ├── Helpers/              # exAuth_helper.php
│   ├── Models/               # 7 models (User, UserIdentity, Login, Remember, Group, Permission, GroupPermission)
│   └── Traits/               # 6 traits (Authorizable, HasAccessTokens, HasHmacTokens, Activatable, Bannable, Resettable)
├── Language/
│   ├── en/exAuth.php
│   └── id/exAuth.php
├── tests/                    # Scaffold (empty, to be filled)
├── composer.json
├── README.md
├── ANALISIS.md
├── AGENTS.md
└── .gitignore
```

## Package info

- **Name**: `exceed/exauth`
- **Namespace**: `exAuth\` (maps to src/)
- **PHP**: ^8.2
- **License**: MIT
- **Dependencies**: codeigniter4/framework, firebase/php-jwt
- **Repository**: https://github.com/exceed/exAuth (not yet created by user)

## How to install (for end users)

### Local path repository (dev)
```json
// proyek target composer.json
"repositories": [
    {
        "type": "path",
        "url": "../exAuth"
    }
]
```
Then: `composer require exceed/exauth:dev-main`

### After push to GitHub
1. Create repo at https://github.com/exceed/exAuth
2. Run (inside exAuth/):
   ```
   git remote add origin https://github.com/exceed/exAuth.git
   git push -u origin main
   git tag v1.0.0
   git push --tags
   ```
3. Submit to https://packagist.org/packages/submit (URL: https://github.com/exceed/exAuth)
4. Then `composer require exceed/exauth` works for anyone.

## Important conventions

- **PSR-4**: Namespace `exAuth\` maps to `src/`
- **Strict types**: All classes use `declare(strict_types=1)`
- **No comments/docblocks**: Code is self-documenting; no PHPDoc or inline comments
- **Migrations**: 5 files, run with `php spark migrate -n exAuth`; excluded from classmap
- **Config**: Main configuration in `Config/exAuth.php` (single file, not spread across multiple like Shield)
- **Groups/Permissions**: DB-driven, can be managed at runtime; config file AuthGroups.php only for defaults
- **Filters all implement**: `CodeIgniter\Filters\FilterInterface`

## Key differences from Shield

| Aspect | Shield | exAuth |
|--------|--------|--------|
| Config groups | Hardcoded in Auth.php config | DB-backed, dynamic |
| Min PHP | 8.1+ | 8.2+ |
| Namespace | CodeIgniter\Shield\ | exAuth\ |
| Auth methods | 4 (Session, Tokens, Hmac, JWT) | 4 (same, same interface) |
| Traits | Located in src/Traits | src/Traits |
| Framework minimum | CI4 4.3.5 | CI4 4.3.5 |

## Key differences from Myth-Auth

| Aspect | Myth-Auth | exAuth |
|--------|-----------|--------|
| Auth methods | Session only | 4 methods (Session, Tokens, Hmac, JWT) |
| ChainAuth | No | Yes |
| Permission wildcard | No | Yes (admin.*) |
| 2FA | No | Structure ready |

## User-specific commands to remember

| Intent | Command |
|--------|---------|
| Install path repo | `composer require exceed/exauth:dev-main` (after setting up repositories) |
| Run tests | `composer test` (from exAuth/ or project dir) |

## Current state

- **v1.0.0 dev** — core structure complete, all php files pass syntax check
- **Not yet pushed to GitHub** — only local git repo
- **No tests written yet** — tests/ directory is scaffold only
- **Not yet registered on Packagist**

## How to push (when user is ready)

1. Create repo https://github.com/exceed/exAuth
2. git remote add origin, git push -u origin main
3. git tag v1.0.0; git push --tags
4. Submit to Packagist (fast, automated)
5. Copy the packagist badge for README.md
