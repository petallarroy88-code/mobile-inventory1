<?php
// config/config.php

define('APP_NAME', 'MobileStock Pro');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/mobile_inventory/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', BASE_URL . 'uploads/products/');
define('LOW_STOCK_THRESHOLD', 10);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Manila');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
