<?php
/**
 * Centralized Path Configuration
 * Sistem Akademik & PMB Online
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', APP_ROOT . '/config');
}

if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', APP_ROOT . '/public');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', PUBLIC_PATH . '/uploads/user_photos');
}

if (!defined('PMB_DOCS_PATH')) {
    define('PMB_DOCS_PATH', PUBLIC_PATH . '/uploads/pmb_docs');
}

if (!defined('PMB_CERT_PATH')) {
    define('PMB_CERT_PATH', PMB_DOCS_PATH . '/certificates');
}

if (!defined('PMB_PAY_PATH')) {
    define('PMB_PAY_PATH', PMB_DOCS_PATH . '/payments');
}

if (!defined('PMB_UPLOAD_PATH')) {
    define('PMB_UPLOAD_PATH', PMB_DOCS_PATH);
}

if (!defined('VIEWS_PATH')) {
    define('VIEWS_PATH', APP_ROOT . '/views');
}

if (!defined('SRC_PATH')) {
    define('SRC_PATH', APP_ROOT . '/src');
}

if (!defined('REPORTING_PATH')) {
    define('REPORTING_PATH', SRC_PATH . '/reporting');
}

if (!defined('DOCS_PATH')) {
    define('DOCS_PATH', APP_ROOT . '/docs');
}

if (!defined('SCRIPTS_PATH')) {
    define('SCRIPTS_PATH', APP_ROOT . '/scripts');
}

// Relative web URL for uploads
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', './public/uploads/user_photos/');
}

if (!defined('PMB_DOCS_URL')) {
    define('PMB_DOCS_URL', './public/uploads/pmb_docs/');
}

if (!defined('PMB_CERT_URL')) {
    define('PMB_CERT_URL', PMB_DOCS_URL . 'certificates/');
}

if (!defined('PMB_PAY_URL')) {
    define('PMB_PAY_URL', PMB_DOCS_URL . 'payments/');
}

if (!defined('PMB_UPLOAD_URL')) {
    define('PMB_UPLOAD_URL', PMB_DOCS_URL);
}
