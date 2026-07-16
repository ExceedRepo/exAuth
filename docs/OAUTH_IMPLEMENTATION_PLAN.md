# Rencana Implementasi: exauth-oauth (Sub-Paket Social Login)

> **Status:** Rencana (belum diimplementasi)
> **Target:** v1.0.0 dari paket `exauth-oauth`, kompatibel dengan `exceed/exauth ^1.5`
> **Tujuan:** Tambah login via Google (fase 1) tanpa mengubah paket utama `exceed/exauth`.
> GitHub / Facebook menyusul di fase 2 setelah Google stabil.

> **Catatan fase 1:** Hanya provider **Google** yang diimplementasi. Structure
> dibuat extensibel (satu method `getProvider($name)`) sehingga menambah GitHub/Facebook
> nanti hanya tinggal menambah konfigurasi + entry di provider map, tanpa refactor.

---

## 0. Prinsip Keamanan (Paling Penting)

1. **Sub-paket terpisah** — `exceed/exauth` TIDAK diubah. OAuth hanya menambah controller, route, service, dan satu type identity baru.
2. **Account linking wajib** — jika email OAuth sudah terdaftar, arahkan ke "hubungkan akun", JANGAN buat user baru sembarangan (cegah takeover akun).
3. **State + PKCE wajib** — cegah CSRF pada OAuth flow.
4. **Type identity unik** — gunakan `oauth_google`, `oauth_github`, `oauth_facebook`. Jangan timpa type `access_token` / `hmac_sha256` yang sudah ada.
5. **Token rahasia disimpan di `extras` (TEXT)**, bukan `secret`/`secret2` (VARCHAR 255 terlalu pendek untuk access token panjang). `secret` = provider_user_id.

---

## 1. Struktur Paket

```
exauth-oauth/
├── src/
│   ├── Auth.php                     # Facade: oauthRoutes($routes)
│   ├── Config/
│   │   ├── Services.php             # service('oauth'), service('oauth.google'), dll
│   │   ├── Registrar.php            # Auto-register filter 'oauth'
│   │   └── OAuth.php                # Config: client_id, client_secret, redirect_uri per provider
│   ├── Controllers/
│   │   └── OAuthController.php      # redirect(), callback()
│   ├── Authentication/
│   │   └── OAuthProvider.php        # Wrapper league/oauth2-client
│   ├── Exceptions/
│   │   └── OAuthException.php       # extends exAuth\Exceptions\ExAuthException
│   ├── Filters/
│   │   └── OAuthAuth.php            # Verifikasi session setelah OAuth login
│   ├── Models/
│   │   └── OAuthIdentityModel.php   # Helper simpan/baca auth_identities type oauth_*
│   └── Migrations/
│       └── 2025-xx-xx-000001_add_oauth_cols.php  # (opsional) index pada auth_identities
├── Language/
│   ├── en/OAuth.php
│   └── id/OAuth.php
├── composer.json
└── README.md
```

**composer.json (inti):**
```json
{
  "name": "exceed/exauth-oauth",
  "type": "library",
  "require": {
    "php": "^8.2",
    "exceed/exauth": "^1.5",
    "league/oauth2-google": "^4.0"
  },
  "autoload": {
    "psr-4": { "exAuthOauth\\": "src" }
  }
}
```

---

## 2. Penyimpanan Identity (pakai tabel existing `auth_identities`)

Tidak perlu tabel baru. Simpan via `UserIdentityModel::insert()`:

| Kolom `auth_identities` | Isi untuk OAuth |
|-------------------------|-----------------|
| `user_id` | ID user lokal |
| `type` | `oauth_google` / `oauth_github` / `oauth_facebook` |
| `name` | `google` / `github` / `facebook` |
| `secret` | `provider_user_id` (unik per provider) |
| `secret2` | kosong / encrypted refresh token |
| `extras` | JSON: `{access_token, refresh_token, expires_at, name, avatar}` |
| `expires_at` | waktu kedaluwarsa access token |

Helper `OAuthIdentityModel`:
- `findByProvider(string $provider, string $providerUserId): ?array`
- `attach(int $userId, array $data): void`
- `getTokens(int $userId, string $provider): ?array` (baca dari extras)

---

## 3. Flow Login (OAuthController)

```
GET /oauth/{provider}/redirect
  └─ generate state + PKCE, simpan di session
  └─ redirect ke provider authorize URL

GET /oauth/{provider}/callback
  └─ validasi state (cegah CSRF)
  └─ tukar code → access token (+ id_token)
  └─ ambil profile (email, name, avatar, provider_user_id)
  └─ CARI user:
       a. OAuth identity ada?           → login user tsb
       b. Email sama dengan user lokal? → TAMPILKAN "hubungkan akun"
                                          (minta password lokal) → attach identity
       c. Email belum ada?              → buat user baru (password = dummy hash)
                                          + attach identity → login
  └─ Session::login($userEntity)  (set auth_user_id + auth_logged_in)
  └─ redirect ke dashboard
```

**Pembuatan user tanpa password:**
`users.password` NOT NULL → isi dengan `password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)` sebagai placeholder. Tandai `status = 'oauth'` agar jelas akun ini tidak punya password lokal.

---

## 4. Registrasi Route & Service (polanya sama seperti exAuth)

`src/Auth.php` (facade sub-paket):
```php
public function oauthRoutes(RouteCollection &$routes, array $config = []): void
{
    $namespace = $config['namespace'] ?? 'exAuthOauth\Controllers';
    $routes->group('/', ['namespace' => $namespace], static function ($routes) {
        $routes->get('oauth/(:segment)/redirect', 'OAuthController::redirect/$1');
        $routes->get('oauth/(:segment)/callback', 'OAuthController::callback/$1');
    });
}
```
Dipakai di `Config/Routes.php`:
```php
service('oauth')->oauthRoutes($routes);
```

`src/Config/Services.php`:
```php
public static function oauth(bool $getShared = true)
{
    if ($getShared) { return self::getSharedInstance('oauth'); }
    return new OAuthProvider(config('OAuth'));
}
```

`src/Config/Registrar.php` (auto-register filter, mirip exAuth):
```php
public static function Filters(): array
{
    return ['aliases' => ['oauth' => OAuthAuth::class]];
}
```

---

## 5. Account Linking (anti takeover)

Jika `getUserByEmail($email)` menemukan user tapi BELUM punya identity OAuth:
- JANGAN auto-login.
- Tampilkan halaman "Hubungkan ke akun Anda" → user input password lokal.
- Verifikasi dengan `password_verify()` (pakai `UserModel`).
- Jika benar → `OAuthIdentityModel::attach()` → login.
- Jika salah → error (cegah enumerasi: pesan generik).

---

## 6. Risiko & Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Token melebihi VARCHAR 255 | Simpan di `extras` (TEXT) |
| CSRF pada callback | Wajib `state` + PKCE, cocokkan dengan session |
| Account takeover via email sama | Account linking wajib (tidak auto-create) |
| Provider down / timeout | Try/catch → `OAuthException` → pesan ramah |
| User hapus OAuth lalu lupa password | `status='oauth'` → sediakan "set password" lewat email reset |

---

## 7. Tidak Menyentuh exAuth Inti

✅ Aman karena:
- Tidak ubah `Session`, `Tokens`, `HMAC`, `JWT` authenticator.
- Tidak ubah `auth_groups_users` / `auth_permissions_users` (OAuth user pakai grup sama).
- Cache RBAC & Rate Limit tetap jalan normal untuk user OAuth.
- Hanya TAMBAH type identity baru + controller/route/service baru.

---

## 8. Checklist Implementasi (urutan — FASE 1: GOOGLE ONLY)

- [ ] 1. Buat repo `exauth-oauth`, composer.json (hanya `league/oauth2-google`), PSR-4 `exAuthOauth\`
- [ ] 2. `Config/OAuth.php` — `google` client_id/secret/redirect + `enabled` flag
- [ ] 3. `Authentication/OAuthProvider.php` — wrap league/oauth2-google (state+PKCE), method `getProvider('google')`
- [ ] 4. `Models/OAuthIdentityModel.php` — attach/find/getTokens (type `oauth_google`)
- [ ] 5. `Controllers/OAuthController.php` — redirect + callback + account linking (hanya terima `google`)
- [ ] 6. `Auth.php` facade — `oauthRoutes()`
- [ ] 7. `Config/Services.php` + `Config/Registrar.php`
- [ ] 8. `Language/en+OAuth.php` + `id/OAuth.php`
- [ ] 9. Views: tombol "Login dengan Google" + halaman "hubungkan akun"
- [ ] 10. Tests (PHPUnit + SQLite) — happy path + email collision + state mismatch
- [ ] 11. README + publish ke Packagist

**Fase 2 (nanti, setelah Google stabil):** tambah `league/oauth2-github` + `league/oauth2-facebook`,
entry di `Config/OAuth::$providers`, dan type `oauth_github` / `oauth_facebook`. Tidak ada refactor结构.

---

## 9. Contoh Pemakaian (end user)

```bash
composer require exceed/exauth-oauth
```

`Config/Routes.php`:
```php
service('oauth')->oauthRoutes($routes);
```

`Config/OAuth.php` (publish & isi):
```php
public bool $enabled = true;
public array $google = [
    'clientId'     => '....apps.googleusercontent.com',
    'clientSecret' => '...',
    'redirectUri'  => 'https://app/oauth/google/callback',
];
```

Login view:
```php
<a href="<?= site_url('oauth/google/redirect') ?>">Login dengan Google</a>
```
