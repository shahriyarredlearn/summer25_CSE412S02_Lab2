<?php
/**
 * Database Configuration Wrapper
 * ----------------------------
 * This file serves as a wrapper for the main database configuration file.
 * It ensures consistent database access across the application.
 */

// Include the main database configuration file
require_once __DIR__ . '/config/database.php';

// This file exists for backward compatibility
// All database functions are now defined in config/database.php
