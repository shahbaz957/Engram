-- Existing database: add role if the users table was created without it.
USE engram_db;

ALTER TABLE users
ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user';
