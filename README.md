# 🎓 Alumni Platform API

A production-ready REST API built with **Symfony 6.4** for managing alumni profiles, authentication, and admin operations — inspired by the MyINSEAD platform architecture.

---

## 🚀 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Symfony 6.4 |
| Language | PHP 8.2+ |
| Database (dev) | MySQL |
| Database (test) | SQLite |
| Auth | JWT (LexikJWTAuthenticationBundle) |
| Docs | NelmioApiDocBundle + Swagger UI |
| Testing | PHPUnit 13 |
| Rate Limiting | Symfony RateLimiter |

---

## ✅ Features

- 🔐 JWT Authentication (register, login, me)
- 👤 Alumni profile management
- 🛡️ Role-based access control (ROLE_USER, ROLE_ADMIN)
- 👮 Admin panel (list users, toggle active, change roles, delete)
- 📋 Audit logging (login attempts, role changes, toggles)
- 🚦 Rate limiting (5 login attempts per minute per IP)
- 🔒 Security headers on every response
- 📖 Interactive API docs (Swagger UI)
- ✅ 18 passing tests

---

## ⚙️ Local Setup

### Requirements
- PHP 8.2+
- Composer
- MySQL
- Symfony CLI (optional but recommended)

### 1. Clone the repo

```bash
git clone https://github.com/iis1155/alumni-platform.git
cd alumni-platform
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env .env.local
```

Edit `.env.local`:

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/alumni_platform"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
```

### 4. Generate JWT keys

```bash
php bin/console lexik:jwt:generate-keypair
```

### 5. Create database & run migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Start the server

```bash
symfony server:start --port=8001
# or
php -S localhost:8001 -t public/
```

---

## 📖 API Documentation

Visit: `http://localhost:8001/swagger.html`

Or get the raw OpenAPI spec: `http://localhost:8001/api/doc.json`

### How to use Swagger UI

1. Call `POST /api/auth/login` → copy the `token`
2. Click **Authorize 🔓** → paste token → click Authorize
3. All endpoints are now authenticated — click any and hit **Try it out**

---

## 🔑 Authentication

All `/api/*` routes require a JWT Bearer token except:
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/doc`
- `GET /api/doc.json`

### Register

```bash
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"Password123","firstName":"John","lastName":"Doe"}'
```

### Login

```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"Password123"}'
```

### Use token

```bash
curl http://localhost:8001/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📋 API Endpoints

### Auth
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/api/auth/register` | ❌ | Register new user |
| POST | `/api/auth/login` | ❌ | Login, get JWT token |
| GET | `/api/auth/me` | ✅ | Get current user |

### Profile
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/api/profile/me` | ✅ | Get my profile |
| PUT | `/api/profile/me` | ✅ | Update my profile |
| GET | `/api/profile/alumni` | ✅ | List all alumni profiles |
| GET | `/api/profile/alumni/{id}` | ✅ | Get specific alumni profile |

### Admin (ROLE_ADMIN only)
| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/api/admin/users` | 🛡️ | List all users (paginated) |
| PATCH | `/api/admin/users/{id}/toggle` | 🛡️ | Toggle user active/inactive |
| PATCH | `/api/admin/users/{id}/role` | 🛡️ | Change user role |
| DELETE | `/api/admin/users/{id}` | 🛡️ | Delete user |
| GET | `/api/admin/logs` | 🛡️ | View audit logs |

---

## 🧪 Running Tests

```bash
# Run all tests
php bin/phpunit --testdox

# Reset test database if needed
php bin/console doctrine:schema:drop --env=test --force
php bin/console doctrine:schema:create --env=test
```

Expected output: **18 tests, all passing**

---

## 🔒 Security Features

- JWT token expiry (1 hour)
- Rate limiting: 5 login attempts per minute per IP
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Content-Security-Policy`
- Admin self-protection: admins cannot deactivate/delete/change their own account
- Global exception handler returns consistent JSON errors

---

## 📁 Project Structure

```
src/
├── Controller/
│   ├── AuthController.php       # Register, login, me
│   ├── ProfileController.php    # Alumni profiles
│   └── AdminController.php      # Admin operations
├── Entity/
│   ├── User.php
│   ├── AlumniProfile.php
│   └── AuditLog.php
├── DTO/
│   └── RegisterRequest.php
├── Event/
│   └── AuditEvent.php
├── EventListener/
│   ├── AuditListener.php
│   ├── ApiExceptionListener.php
│   └── SecurityHeadersListener.php
├── Repository/
│   ├── UserRepository.php
│   └── AuditLogRepository.php
├── Service/
│   ├── AuthService.php
│   └── AlumniService.php
└── Traits/
    └── ApiResponseTrait.php
```

---

## 👩‍💻 Author

Built by **Anisa** as a hands-on Symfony learning project, mirroring the MyINSEAD alumni platform architecture at DeeepLabs.
