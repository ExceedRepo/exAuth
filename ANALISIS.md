# exAuth — Analisis & Dokumentasi Proyek

## Ringkasan

exAuth adalah hasil penggabungan konsep terbaik dari dua authentication library CodeIgniter 4:

- **CodeIgniter Shield** (official, active — /var/www/authx/shield)
- **Myth/Auth** (pendahulu, archived — /var/www/authx/myth-auth)

## Tujuan

Menciptakan authentication library yang:

1. **Multi-autentikasi** seperti Shield (Session, AccessTokens, HMAC, JWT)
2. **Groups & Permissions di database** seperti yang diinginkan pengguna (Myth-Auth style)
   - Tidak hardcoded di Config seperti Shield
   - Dapat dikelola secara dinamis
3. **Lebih sederhana** dari Shield namun lebih lengkap dari Myth/Auth

## Arsitektur exAuth

```
exAuth/
├── src/
│   ├── Authentication/
│   │   ├── Authentication.php          # Factory pattern
│   │   ├── AuthenticatorInterface.php  # Interface
│   │   └── Authenticators/
│   │       ├── Session.php       # Stateful login (tradisional)
│   │       ├── AccessTokens.php  # Token akses personal
│   │       ├── HmacSha256.php    # HMAC signed requests
│   │       └── JWT.php           # JSON Web Token
│   ├── Authorization/
│   │   ├── Groups.php
│   │   ├── Permission.php
│   │   └── PermissionMatcher.php (wildcard matching)
│   ├── Config/
│   │   ├── exAuth.php            # Main config
│   │   ├── AuthGroups.php        # Groups & permissions config
│   │   └── Registrar.php
│   ├── Controllers/
│   │   ├── LoginController.php
│   │   ├── RegisterController.php
│   │   └── MagicLinkController.php
│   ├── Database/Migrations/ (5 migrations)
│   ├── Entities/
│   │   ├── User.php
│   │   ├── UserIdentity.php
│   │   ├── AccessToken.php
│   │   ├── Login.php
│   │   └── Group.php
│   ├── Exceptions/
│   │   └── ExAuthException.php
│   ├── Filters/
│   │   ├── SessionAuth.php
│   │   ├── TokenAuth.php
│   │   ├── HmacAuth.php
│   │   ├── JWTAuth.php
│   │   ├── ChainAuth.php
│   │   ├── GroupFilter.php
│   │   └── PermissionFilter.php
│   ├── Helpers/
│   │   └── exAuth_helper.php
│   └── Models/ (7 models)
├── Language/
│   ├── en/exAuth.php
│   └── id/exAuth.php
├── composer.json
├── README.md
└── ANALISIS.md
```

## Perbandingan Shield vs exAuth vs Myth-Auth

| Aspek | Myth/Auth | Shield | exAuth |
|---|---|---|---|
| Autentikasi | Session only | Session, Tokens, HMAC, JWT | Session, Tokens, HMAC, JWT |
| Groups/Permissions | Config + DB | Config + DB | DB-backed (simpler) |
| 2FA | Tidak ada | Ada (Email 2FA) | Structure ready |
| Magic Link | Tidak ada | Ada | Ada |
| Traits | Minimal | 6+ traits | 6 traits |
| Chain Auth | Tidak ada | Ada | Ada |
| DB Tables | 6 | 7 | 5 (simplified) |
| Language | ~10 | 22 | 2 (extensible) |

## Catatan Penting untuk OpenCode Agent

- **Namespace**: `exAuth\` — semua kelas under `src/`
- **Framework**: CodeIgniter 4, PHP 8.2+, strict_types
- **Config setup**: Users can override config via Config\exAuth, Config\AuthGroups
- **Migrations**: Run with `php spark migrate -n exAuth` to create tables
- **Groups/Permissions approach**: Groups can be defined in Config\AuthGroups.php for defaults, but can also be managed via database at runtime
- **Authorization flow**: PermissionMatcher supports wildcard (`admin.*`)
- **Filters**: Chained via ChainAuth — define order in `Config\exAuth::$authenticationChain`
- **This project is in active development**: Some features (e.g., full 2FA, email sending) are stubbed but need a mail library to be functional

## Referensi

- Upstream Shield: `../shield/`
- Original Myth/Auth: `../myth-auth/`
