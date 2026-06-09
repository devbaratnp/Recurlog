# Production Checklist

## 1. Database
- [ ] Set environment variables: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`
- [ ] Or hardcode them in `includes/config.php` and `api/config.php` (not recommended)
- [ ] Run `staff_id` migration if not reseeding:
  ```sql
  ALTER TABLE fscrm_users ADD COLUMN staff_id INT DEFAULT NULL AFTER role;
  ```
  Then insert 5 staff user rows (see `api/seed.php` for the insert pattern)

## 2. HTTPS
- [ ] `session.cookie_secure` now auto-detects HTTPS — verify by checking headers
- [ ] Force HTTPS via `.htaccess` or server config

## 3. File permissions
- [ ] `includes/config.php` and `api/config.php` should not be world-readable
- [ ] Web root should be `pages/` — block direct access to `includes/` and `api/` via `.htaccess`

## 4. PHP settings (verify on production)
- [ ] `display_errors = Off` (already set in configs)
- [ ] `error_reporting = 0` (already set)
- [ ] `log_errors = On` (already set)
- [ ] `session.use_strict_mode = 1` (already set)

## 5. CORS
- [ ] In `api/config.php`, update the origin check if production domain is not localhost
  ```php
  $allowedOrigin = (!empty($_SERVER['HTTP_ORIGIN']) && (
      strpos($_SERVER['HTTP_ORIGIN'], 'http://localhost') !== false ||
      strpos($_SERVER['HTTP_ORIGIN'], 'https://yourdomain.com') !== false
  )) ? $_SERVER['HTTP_ORIGIN'] : '';
  ```

## 6. Auth
- [ ] Change demo passwords before going live (both admin and 5 staff users)
- [ ] Rate limiting already active (5 attempts / 5 min per IP)
- [ ] CSRF protection active on all forms

## 7. Staff login (if needed)
- [ ] Staff accounts were seeded locally — recreate on production
- [ ] Each staff user: `{firstname}@demo.com` / `demo123`

## 8. v1 REST API
- [ ] Set `JWT_SECRET` environment variable (or change default in `api/v1/config.php`)
- [ ] Verify JWT auth: `POST /api/v1/auth.php` returns token
- [ ] Test CRUD endpoints with Bearer token
- [ ] Rate limiting at 10 req / 60s per IP — adjust if needed

## 9. Push notifications
- [ ] Generate VAPID keys (see `vendor/` custom helper) if not using defaults
- [ ] Set Expo Push token endpoint if using mobile push
- [ ] Verify `playNotificationSound()` works on target browsers

## 10. PWA
- [ ] Verify `sw.js` caches login page + assets correctly
- [ ] Check `manifest.json` icons exist in `assets/icons/`
- [ ] Test install prompt on supporting browsers (Chrome/Edge)
- [ ] Regenerate icons if branding changes: `node scripts/generate-icons.mjs`

## 11. Verify
- [ ] `/pages/login.php` — both admin and staff login work
- [ ] `/pages/dashboard.php` — admin dashboard renders
- [ ] `/pages/staff-dashboard.php` — staff dashboard renders
- [ ] Create / edit / delete customers, tasks, orders
- [ ] Staff reassignment modal works for tasks, services, and orders
- [ ] Signature capture works
- [ ] Notifications display and mark-as-read work
- [ ] Task detail (`task-detail.php?id=N`) and task edit (`task-edit.php?id=N`) render correctly
- [ ] Locality CRUD at `/pages/localities.php`
