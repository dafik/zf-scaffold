<?php

// PHP < 5.3 compatibility
!defined('__DIR__') || define('__DIR__', dirname(__FILE__));

require_once __DIR__ . '/../../ZFscaffold/ZfTool/Exception.php';


class ZFscaffold_ZfTool_FormsProvider extends Zend_Tool_Framework_Provider_Abstract
{

    public function setRegistry(Zend_Tool_Framework_Registry_Interface $registry)
    {
        $response = $registry->getResponse();
        ZFscaffold_ZfTool_Helpers_Messages::setResponse($response);
        return parent::setRegistry($registry);
    }

    /**
     * The public method that would be exposed into ZF tool
     */
    public function extract()
    {
        try {
            $this->setup();
            $controllers = $this->findControllers();

            foreach ($controllers as $controller) {
                $res = $this->findForms($controller);
                if (count($res['forms']) > 0) {
                    $this->extractForms($res);
                }
            }

        } catch (Exception $e) {
            //$this->_printMessage($e->getMessage(), self::MSG_ERROR);
            throw $e;
        }
    }

    private function setup()
    {

        $this->cwd = getcwd();
        $this->_initPaths($this->cwd . '/application');
        $this->_readAppConfig();
        $includeConfig = Dfi\App\Config::get('phpSettings.include_path', '');


        $this->_initIncludePaths($includeConfig);
    }


    private function findControllers()
    {
        $path = constant('APPLICATION_PATH') . '/modules';

        $found = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        $it->rewind();
        while ($it->valid()) {

            if (!$it->isDot()) {
                if (false !== strpos($it->getSubPathName(), 'Controller') && false == strpos($it->getSubPathName(), 'Abstract')) {
                    //echo 'SubPathName: ' . $it->getSubPathName() . "\n";
                    //echo 'SubPath:     ' . $it->getSubPath() . "\n";
                    //echo 'Key:         ' . $it->key() . "\n\n";
                    $found[] = $it->key();
                }
            }

            $it->next();
        }
        return $found;
    }

    private function  findForms($file)
    {
        $className = $this->getClassNameFromFile($file);
        $ref = new ReflectionClass($className);

        $found = [];

        foreach ($ref->getMethods() as $reflectionMethod) {
            $name = $reflectionMethod->getName();
            if (false !== strpos($name, 'form') || false !== strpos($name, 'Form')) {
                $found[] = $name;
            }
        }
        return array(
            'class' => $className,
            'ref' => $ref,
            'forms' => $found,
            'fileName' => $file
        );
    }

    private function _initPaths($path)
    {
        defined('APPLICATION_PATH') || define('APPLICATION_PATH', $path);
        defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'production');

        $path = constant('APPLICATION_PATH');
        $env = constant('APPLICATION_ENV');
    }

    private function _initIncludePaths($path)
    {

        if (false != strpos($path, PATH_SEPARATOR)) {
            $parts1 = explode(PATH_SEPARATOR, $path);
            $parts2 = explode(PATH_SEPARATOR, get_include_path());

            $parts = array_merge($parts1, $parts2);

        } else {
            $parts = array();
        }

        $parts[] = APPLICATION_PATH;
        $parts[] = realpath(APPLICATION_PATH . '/..');
        $parts[] = realpath(APPLICATION_PATH . '/../vendor/zendframework/zendframework1/library');

        $parts = array_unique($parts);

        sort($parts);

        function path($value)
        {
            if (false !== strpos($value, '.')) {
                return realpath($value);
            }
            return $value;
        }

        $parts = array_map("path", $parts);
        set_include_path(implode(PATH_SEPARATOR, $parts));

        Zend_Loader_Autoloader::getInstance()
            ->registerNamespace('application_')
            ->registerNamespace('ZFscaffold_');

    }

    private function extractForms($res)
    {

        extract($res);
        /**
         * @var ReflectionClass $ref
         */
        /**
         * @var array $forms
         */
        /**
         * @var string $fileName
         */
        foreach ($forms as $formName) {

            $partParts = explode('/', $fileName);
            $controllerName = array_pop($partParts);
            array_pop($partParts);
            $partParts[] = 'forms';
            $dirPath = implode('/', $partParts);


            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0777, true);
            }

            $newFileName = str_replace('Controller', '', $controllerName);


            if (file_exists($dirPath . '/' . $newFileName)) {
                $i = 1;
                list($newFileName) = explode('.', $newFileName);
                do {
                    $tmp = $newFileName . $i . '.php';
                    $i++;
                } while (file_exists($dirPath . '/' . $tmp));
                $newFileName = $tmp;
            }

            list($newClassName) = explode('.', $newFileName);


            $refMethod = $ref->getMethod($formName);

            $start = $refMethod->getStartLine();
            $end = $refMethod->getEndLine();

            $length = $end - $start - 3;

            $source = file($fileName);
            $body = implode("", array_slice($source, $start + 2, $length));

            $fullBody =
                '<?php
class  forms_' . $newClassName . ' extends \\Dfi\\Form
{
    public function init()
    {
        $this->setMethod(\'post\');
        $this->setAttrib(\'class\', \'form-horizontal row-border\');
'

                . str_replace('$form', '$this', $body) .
                '
        $opt = Dfi\Form\Decorator::getDefaults(\'_ElementDecorator\', Dfi\Form\Decorator::BOOTSTRAP);
        $opt[4][1][\'class\'] = \'col-md-2 \' . $opt[4][1][\'class\'];
        $opt[3][1][\'class\'] = \'col-md-10\';
        Dfi\Form\Decorator::overrideDefaults(\'_ElementDecorator\', Dfi\Form\Decorator::BOOTSTRAP, $opt);

        $opt = Dfi\Form\Decorator::getDefaults(\'_FormDecorator\', Dfi\Form\Decorator::BOOTSTRAP);
        $opt[] = \'BootstrapWidget\';
        Dfi\Form\Decorator::overrideDefaults(\'_FormDecorator\', Dfi\Form\Decorator::BOOTSTRAP, $opt);


        Dfi\Form\Decorator::setFormDecorator($this, Dfi\Form\Decorator::BOOTSTRAP, \'submit\', \'cancel\');

        parent::init();
    }
}';
            $newPath = $dirPath . '/' . $newFileName;
            $res = file_put_contents($newPath, $fullBody);


            echo "\n" . $newPath;

        }

    }

    private function getClassNameFromFile($file)
    {
        //$class = shell_exec("php -r \"include('$file'); echo end(get_declared_classes());\"");

        include_once($file);
        $classes = get_declared_classes();
        $class = end($classes);

        return $class;
    }

    private function _readAppConfig()
    {

        // replace the slash just to print a beautiful message :D
        $configDir = str_replace('/', DIRECTORY_SEPARATOR, $this->cwd . '/application/configs/');
        $configFilePath = $configDir . 'application.ini';


        if (!file_exists($configFilePath)) {


            /** @var $projectProvider ZFscaffold_ZfTool_ZFProjectProvider */
            $projectProvider = $this->_registry->getProviderRepository()->getProvider('zfproject');
            $projectProvider->generate();
        }

        $this->appConfigFile = $configFilePath;

        // used to get db configs
        $this->appConfig = new Zend_Config_Ini($configFilePath);


        //$this->_printMessage(var_export($this->appConfig->toArray(), true));
    }
}