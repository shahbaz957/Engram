-- Promote exactly ONE administrator (run after you register that account via signup).
-- 1. Sign up in the app first.
-- 2. Edit the email below to match your account.
-- 3. Run: sudo mysql < /var/www/engram/sql/set_admin.sql

USE engram_db;

-- Everyone stays a user; only your account becomes admin.
UPDATE users SET role = 'user';
UPDATE users SET role = 'admin' WHERE email = 'admin@example.com' LIMIT 1;

-- Verify:
-- SELECT id, username, email, role FROM users;
