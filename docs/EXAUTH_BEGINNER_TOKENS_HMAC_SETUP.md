# exAuth Access Tokens & HMAC — Beginner Setup

This guide covers two more ways to authenticate API requests with exAuth:

1. **Personal Access Tokens** — a long random token the client sends as a Bearer
   header. Great for "API keys" issued to a user or third-party integration.
2. **HMAC-SHA256** — the client signs each request body with a shared secret.
   Great for secure server-to-server calls where you don't want to send the
   secret itself over the wire.

> Versions: **exAuth v1.4.0+**, CodeIgniter **4.7**, PHP **8.2**.
> Do the normal setup first: see **[EXAUTH_BEGINNER_SETUP.md](EXAUTH_BEGINNER_SETUP.md)**.
> Prefer stateless tokens with expiry? See the **[JWT guide](EXAUTH_BEGINNER_JWT_SETUP.md)**.

---

## Which one should I use?

| | Access Token | HMAC | JWT |
|--|--------------|------|-----|
| Client sends | the token itself | key + signature | the token itself |
| Secret on the wire | yes (the token) | **no** (only a signature) | yes (the token) |
| Server storage | hashed token in DB | key + secret in DB | nothing (stateless) |
| Revocable instantly | yes (delete row) | yes (delete row) | no (until expiry) |
| Best for | API keys, integrations | server-to-server | SPA / mobile sessions |

---

# Part A — Personal Access Tokens

## A1. Create a token for a user

Tokens are created programmatically from a `User` entity. The **raw token is
shown only once** — store it safely; the database only keeps its hash.

```php
$user  = ex_current_user();               // or model(UserModel::class)->find($id)
$token = $user->createAccessToken('My Mobile App');

// Give this value to the client ONCE — it cannot be retrieved again:
echo $token->token;   // e.g. "9f2c...64 hex chars..."
```

With scopes (optional — defaults to `['*']` = all):

```php
$token = $user->createAccessToken('CI job', ['posts.read', 'posts.write']);
```

You can also do this from a controller action (protected by the `session`
filter) so logged-in users can generate their own API keys.

### Managing tokens

```php
$user->getAccessTokens();          // list this user's tokens
$user->revokeAccessToken($id);     // delete one
$user->revokeAllAccessTokens();    // delete all
```

## A2. Protect routes with the `tokens` filter

`app/Config/Routes.php`:

```php
$routes->group('api', ['filter' => 'tokens'], static function ($routes) {
    $routes->get('posts', 'Api\Posts::index');
});

// Require a specific scope:
$routes->get('api/posts', 'Api\Posts::create', ['filter' => 'tokens:posts.write']);
```

The `tokens` filter (auto-registered) verifies the Bearer token and returns
`401` if it's missing/invalid, or `403` if the required scope is missing.

## A3. Call the API

```bash
curl https://your-app.com/api/posts \
  -H "Authorization: Bearer 9f2c...the-raw-token..."
```

## A4. Read the current user in your controller

```php
$userId = ex_token_id();     // int|null
$user   = ex_token_user();   // User entity|null

// scope check inside the controller:
if (service('tokens')->tokenCan('posts.write')) { /* ... */ }
```

---

# Part B — HMAC-SHA256

With HMAC the client never sends the secret. Instead it sends a **public key**
plus a **signature** computed from the request body and a **shared secret**.
The server recomputes the signature and compares.

## B1. Create an HMAC credential

```php
$user = ex_current_user();
$cred = $user->createHmacKey();

echo $cred->token;    // the public KEY (safe to store on the client)
echo $cred->secret;   // the SHARED SECRET (shown once — keep it private!)
```

### Managing HMAC keys

```php
$user->getHmacKeys();
$user->deleteHmacKey($id);
```

## B2. Protect routes with the `hmac` filter

```php
$routes->post('api/ingest', 'Api\Ingest::store', ['filter' => 'hmac']);
```

## B3. How the client signs a request

The signature is `HMAC-SHA256(requestBody, sharedSecret)` in hex. The header
format is:

```
Authorization: HMAC-SHA256 <key>:<signature>
```

PHP client example:

```php
$key    = '...';   // $cred->token
$secret = '...';   // $cred->secret
$body   = json_encode(['event' => 'ping']);

$signature = hash_hmac('sha256', $body, $secret);

$header = 'Authorization: HMAC-SHA256 ' . $key . ':' . $signature;
// send $body as the raw request body with that header
```

curl example:

```bash
BODY='{"event":"ping"}'
KEY="your-key"
SECRET="your-shared-secret"
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.* //')

curl -X POST https://your-app.com/api/ingest \
  -H "Authorization: HMAC-SHA256 ${KEY}:${SIG}" \
  -H "Content-Type: application/json" \
  --data "$BODY"
```

> **Important:** the signature must be computed over the **exact** bytes of the
> request body the server receives. If a proxy or framework reformats the body,
> the signatures won't match.

## B4. Read the current user

```php
$userId = ex_hmac_id();     // int|null
$user   = ex_hmac_user();   // User entity|null
```

---

## Chaining multiple methods

The `chain` filter tries several authenticators in order (session → tokens →
jwt → hmac) so one route can accept whichever the client uses:

```php
$routes->get('api/profile', 'Api\Profile::show', ['filter' => 'chain:session,tokens,jwt']);
```

---

## Helper reference

| Helper | Returns |
|--------|---------|
| `ex_token_id()` / `ex_token_user()` | Current access-token user id / entity |
| `ex_hmac_id()` / `ex_hmac_user()`   | Current HMAC user id / entity |
| `service('tokens')->tokenCan($scope)` | Whether the current token has a scope |

---

## Troubleshooting

| Symptom | Cause & Fix |
|---------|-------------|
| `401 { "error": "No access token provided" }` | Missing `Authorization: Bearer <token>` header. |
| `401 { "error": "Invalid access token" }` | Token doesn't match any stored hash (typo, or it was revoked). |
| `403 { "error": "Token is missing the required scope" }` | Route requires a scope the token wasn't created with. |
| `401 { "error": "Invalid HMAC signature" }` | Signature mismatch — usually the signed body differs from the sent body, or the wrong secret was used. |
| `401 { "error": "Invalid HMAC key" }` | The public key isn't in the database (typo or deleted). |
| `401 { "error": "No HMAC authorization header" }` | Header must be `Authorization: HMAC-SHA256 <key>:<signature>`. |

Done 🎉 — exAuth now supports API keys (access tokens) and signed requests (HMAC),
both tested end-to-end.
