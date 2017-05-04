<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    const CONFIG_KEY = 'appConfig';

    protected function _initAutoload()
    {

        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('application_');
        $autoloader->registerNamespace('EasyBib_');
        // $autoloader->registerNamespace('nusoap_');
    }

    protected function _initEnvironment()
    {

        date_default_timezone_set('Europe/Warsaw');
        setlocale(LC_ALL, 'pl_PL.utf8');


        $constPath = APPLICATION_PATH . '/configs/constants.php';

        if (Zend_Loader::isReadable($constPath)) {
            /** @noinspection PhpIncludeInspection */
            include_once $constPath;
        } else {
            throw new Exception('app config read failed');
        }
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            gc_enable();
        }
    }

    protected function _initLoggers()
    {

        try {
            $writer = new Zend_Log_Writer_Stream(_APP_LOG_FILE);
            $logger = new Zend_Log($writer);
            Zend_Registry::set('appLogger', $logger);

            $writerAD = new Zend_Log_Writer_Stream(_AD_LOG_FILE);
            $loggerAD = new Zend_Log($writerAD);
            Zend_Registry::set('ADLogger', $loggerAD);

            $writerError = new Zend_Log_Writer_Stream(_ERROR_LOG_FILE);
            $loggerError = new Zend_Log($writerError);
            Zend_Registry::set('errorLogger', $loggerError);

            $writerDebug = new Zend_Log_Writer_Stream(_DEBUG_LOG_FILE);
            $loggerDebug = new Zend_Log($writerDebug);
            Zend_Registry::set('debugLogger', $loggerDebug);

            $writerShutDown = new Zend_Log_Writer_Stream(_SHUTDOWN_LOG_FILE);
            $loggerShutDown = new Zend_Log($writerShutDown);
            Zend_Registry::set('shutdownLogger', $loggerShutDown);

            $writerPropel = new Zend_Log_Writer_Stream(_PROPEL_LOG_FILE);
            $loggerPropel = new Zend_Log($writerPropel);
            Zend_Registry::set('propelLogger', $loggerPropel);


        } catch (Exception $e) {
            $logFiles = array(_APP_LOG_FILE, _ERROR_LOG_FILE, _DEBUG_LOG_FILE, _SHUTDOWN_LOG_FILE, _PROPEL_LOG_FILE);
            foreach ($logFiles as $logFile) {
                if (!Dfi\File::isWriteable($logFile)) {
                    throw new Exception('file ' . $logFile . ' is not readeable');
                }
            }
        }
    }

    protected function _initErrorsHandlers()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('error_reporting', E_ALL);
        ini_set('log_errors', 1);
        ini_set('display_errors', 0);
        ini_set('error_log', _LOG_PATH . 'php.errors.log');

        set_error_handler(array('\\Dfi\\Error\\Handler', 'errorHandler'), E_ALL);
        register_shutdown_function(array('\\Dfi\\Error\\Handler', 'shutdown'));

        if (Dfi\App\Config::get('main.showDebug')) {
            Zend_Controller_Front::getInstance()->throwExceptions(true);
            ini_set('display_errors', 1);
        }


        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Dfi\Controller\Plugin\Error());

    }

    protected function _initConfig()
    {
        $options = $this->getApplication()->getOptions();
        $config = new Zend_Config($options);
        Zend_Registry::set(self::CONFIG_KEY, $config);

    }


    protected function _initDatabase()
    {
        if (Zend_Loader::isReadable(Dfi\App\Config::get('db.config'))) {

            try {
                $logger = Zend_Registry::get('propelLogger');
                $adapter = new Dfi\Log\Adapter\Propel2Zend($logger);

                require_once 'Propel.php';

                Propel::setLogger($adapter);
                Propel::init(Dfi\App\Config::get('db.config'));
            } catch (Exception $e) {
                throw new Exception('Can\'t setup database: ' . $e->getMessage());
            }
        } else {
            throw new Exception('database config read failed');
        }

    }


    protected function _initActionHelpers()
    {
        Zend_Controller_Action_HelperBroker::addPrefix('\\Dfi\\Controller\\Action\\Helper');

        Zend_Controller_Action_HelperBroker::addHelper(Dfi\Controller\Action\Helper\Messages::getInstance());
        Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
    }

    protected function _initRoutes()
    {
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $file = APPLICATION_PATH . '/configs/routes.ini';

        if (Zend_Loader::isReadable($file)) {
            $config = new Zend_Config_Ini($file, 'production');

            if ($config->count()) {
                $router->addConfig($config, 'routes');
            }
        }
    }

    protected function _initView()
    {
        // Initialize view
        $view = new Zend_View();
        $view->doctype('XHTML1_STRICT');


        $view->addHelperPath(array(_BASE_PATH . 'library/Dfi/View/Helper', 'Dfi/View/Helper/'), '\\Dfi\\View\\Helper\\');
        $view->addBasePath(APPLICATION_PATH . '/layouts/partials/');



        // Add it to the ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        //Zend_Form::setDefaultTranslator(new Zend_Translate_Adapter_Array(include(APPLICATION_PATH . '/configs/messages/validation.php'), 'pl'));

        // Return it, so that it can be stored by the bootstrap
        return $view;
    }

}

