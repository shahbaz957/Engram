-- Run once in MySQL (engram_db). New signups get role 'student' by default.
USE engram_db;

ALTER TABLE users
ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'student';

-- Example: promote your test admin account (change the email):
-- UPDATE users SET role = 'admin' WHERE email = 'you@example.com';
