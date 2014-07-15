<?php
define('APPLICATION_ENV', 'development');
try {
    // Define path to application directory
    defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

    // Define application environment
    defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

    // Ensure library/ is on include_path
    set_include_path(implode(PATH_SEPARATOR, array(APPLICATION_PATH, realpath(APPLICATION_PATH . '/../vendor/zendframework/zendframework1/library'), get_include_path())));


    require_once '../vendor/autoload.php';

//    require_once '../library/Zend/Loader/AutoloaderFactory.php';
//    require_once '../library/Zend/Loader/ClassMapAutoloader.php';

    Zend_Loader_AutoloaderFactory::factory(
        array(
//            'Zend_Loader_ClassMapAutoloader' => array(
//                __DIR__ . '/../application/configs/autoload_classmap.php'
//            ),
            'Zend_Loader_StandardAutoloader' => array(
                'prefixes' => array(
                    'Zend' => realpath(__DIR__ . '/../vendor/zendframework/zendframework1/library/Zend'),
                    'application' => APPLICATION_PATH
                ),
                'fallback_autoloader' => true
            )
        )
    );


    /** Zend_Application */
    //require_once 'Zend/Application.php';

    // Create application, bootstrap, and run
    $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
    $application->bootstrap();
    $application->run();

} catch (Exception $e) {

    try {
        $guid = Dfi_Error_Report::saveException($e);
    } catch (Exception $e) {
        $guid = false;
    }

    if (APPLICATION_ENV != 'development') {
        $url = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . '/';
        header("Location: " . $url . "error/error" . ($guid ? '/guid/' . $guid : ''));
        exit();
    } else {
        echo '<pre>REPORT: ' . ($guid ? $guid : 'brak') . "\n";
        echo 'REQUEST: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '') . "\n";
        echo 'REFERER: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . "\n";
        echo 'ERROR: ' . $e->getMessage() . ' : ' . $e->getFile() . ' : (' . $e->getLine() . ')' . "\n" . $e->getTraceAsString() . '</pre>';

    }
}