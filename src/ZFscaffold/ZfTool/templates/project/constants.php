<?
define('_BASE_PATH', realpath(APPLICATION_PATH . '/../') . '/');
define('_LIBRARY_PATH', realpath(_BASE_PATH . 'library/') . '/');
define('_LOG_PATH', realpath(_BASE_PATH . 'logs/') . '/');
define('_STATIC_PATH', realpath(_BASE_PATH . 'static/') . '/');
define('_DATA_PATH', realpath(_BASE_PATH . 'data/') . '/');

// --------- logs
define('_APP_LOG_FILE', _LOG_PATH . 'app.log');
define('_ERROR_LOG_FILE', _LOG_PATH . 'error.log');
define('_DEBUG_LOG_FILE', _LOG_PATH . 'debug.log');
define('_SHUTDOWN_LOG_FILE', _LOG_PATH . 'shutdown.log');
define('_PROPEL_LOG_FILE', _LOG_PATH . 'propel.log');
define('_AD_LOG_FILE', _LOG_PATH . 'ad.log');

// --------- static
define('_CSS', Dfi_App_Config::getString('http.static') . 'css/');
define('_IMG', Dfi_App_Config::getString('http.static') . 'img/');
define('_IMG_LAYOUT', _IMG . 'layout/');
define('_SWF', Dfi_App_Config::getString('http.static') . 'swf/');
define('_XML', Dfi_App_Config::getString('http.static') . 'xml/');
define('_JS', Dfi_App_Config::getString('http.static') . 'js/');
define('_IMG_TMP', _STATIC_PATH . 'tmp/');

define('_CKEDITOR', _JS . 'lib/ckeditor/');
define('_CODEMIRROR', _JS . 'lib/codemirror/');