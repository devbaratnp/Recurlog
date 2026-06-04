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

## 8. Verify
- [ ] `/pages/login.php` — both admin and staff login work
- [ ] `/pages/dashboard.php` — admin dashboard renders
- [ ] `/pages/staff-dashboard.php` — staff dashboard renders
- [ ] Create / edit / delete customers, tasks, orders
- [ ] Signature capture works
- [ ] Notifications display and mark-as-read work
