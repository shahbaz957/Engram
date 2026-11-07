<?php
// Protected pages: database, helpers, and login check in one include.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
requireLogin();
