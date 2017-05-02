<?php

// PHP < 5.3 compatibility
!defined('__DIR__') || define('__DIR__', dirname(__FILE__));


class ZFscaffold_ZfTool_ScaffoldProvider extends Zend_Tool_Framework_Provider_Abstract
{
    const MSG_NORMAL = 'normal';
    const MSG_ERROR = 'error';
    const MSG_SPECIAL = 'special';

    /**
     * @var Zend_Config_Ini
     */
    protected $appConfig;
    /**
     * @var string
     */
    protected $appConfigFile;
    /**
     * @var  string
     */
    protected $cwd;
    /**
     * @var string
     */
    protected $scaffoldDir = __DIR__;

    /**
     * @var bool
     */
    protected $autoConfig = false;
    /**
     * @var bool
     */
    protected $forceOverWrite = false;

    /**
     * @var string
     */
    protected $_appNamespace = 'Application_';
    /**
     * @var string
     */
    protected $_controllerNamePrefix = '';
    /**
     * @var string
     */
    protected $_moduleName = 'admin';
    /**
     * @var string
     */
    protected $_outputTemplate = 'bootstrap3';
    /**
     * @var  string
     */
    protected $_packageName;

    /**
     * @var array
     */
    protected $outputGroups = array();
    /**
     * @var array
     */
    protected $outputs = array();
    /**
     * @var array
     */
    protected $selectedKeys = array();
    /**
     * @var array TableMap[]
     */
    protected $tables = array();

    protected $params = array();
    protected $objects = array();


    protected $_useAuth = true;

    protected $_authMethod = 'db';
    protected $_authTable = 'Admin';

    protected $_authLoginField = 'username';
    protected $_authPasswordField = 'password';

    protected $_authPasswordHash = 'md5';

    protected $_authActivityField = 0;


    public function setRegistry(Zend_Tool_Framework_Registry_Interface $registry)
    {
        $response = $registry->getResponse();
        ZFscaffold_ZfTool_Helpers_Messages::setResponse($response);
        return parent::setRegistry($registry);
    }

    /**
     * The public method that would be exposed into ZF tool
     */
    public function generate($force = 0, $autoConfig = 0)
    {

        try {
            $this->autoConfig = filter_var($autoConfig, FILTER_VALIDATE_BOOLEAN);
            $this->forceOverWrite = filter_var($force, FILTER_VALIDATE_BOOLEAN);

            $this->_setup();

            $this->_collectData($autoConfig);
            $this->_prepareConfig($force);
            $this->_generate($force);

        } catch (Exception $e) {

            //$this->_printMessage($e->getMessage(), self::MSG_ERROR);
            throw $e;
        }
    }

    private function _setup()
    {
        $this->cwd = getcwd();
        $this->_initPaths($this->cwd . '/application');
        $this->_readAppConfig();
        $includeConfig = Dfi\App\Config::get('phpSettings.include_path', '');


        $this->_initIncludePaths($includeConfig);

        Zend_Loader_Autoloader::getInstance()->registerNamespace('Dfi_')->suppressNotFoundWarnings(true);


        foreach (Dfi\App\Config::getConfig(true, false, array(), 'scaffold') as $key => $value) {
            $method = '_' . $key;
            if (property_exists($this, $method)) {
                $this->$method = $value;
            }
        }

        $propelConfig = Dfi\App\Config::get('db.config', null);
        if (null === $propelConfig) {

            $projectProvider = $this->_registry->getProviderRepository()->getProvider('propelorm');
            $projectProvider->generate();


        }
        $this->_initPropel($propelConfig);
        $this->_packageName = $this->_getCamelCase(Propel::getConfiguration()['datasources']['default']);
    }

    /**
     * The public method that would be exposed into ZF tool
     */
    protected function _generate($force = 0)
    {

        $this->_includeVariables();


        $params = $this->params;
        $objects = $this->objects;

        $objects['cwd'] = getcwd();
        $objects['forceOverriding'] = $force ? true : false;

        $objects['d2c'] = new Zend_Filter_Word_SeparatorToCamelCase('_');
        $objects['c2d'] = new Zend_Filter_Word_CamelCaseToSeparator('-');
        $objects['c2d1'] = new Zend_Filter_Word_CamelCaseToSeparator('_');
        $objects['tables'] = $this->tables;


        if ($objects['forceOverriding']) {
            $this->_printMessage("ATTENTION! Scaffold will override all existing files!", self::MSG_ERROR);
        }

        $params['moduleBaseDirectory'] = $objects['cwd'] . '/application';

        if (!empty($this->_moduleName)) {
            $params['moduleBaseDirectory'] .= '/modules/' . $this->_moduleName;
        }

        $params['extends'] = 'application_modules_' . $this->_moduleName . '_controllers_AbstractController';

        $params['MODULE'] = ucfirst($this->_moduleName);
        $params['MODULE_BASE_DIR'] = $params['moduleBaseDirectory'];
        $params['APPLICATION_DIR'] = $objects['cwd'] . '/application';


        $isFirst = true;

        /** @var $tableDefinition TableMap */
        foreach ($this->tables as $tableName => $tableDefinition) {
            $objects['tableDefinition'] = $tableDefinition;
            $params['tableName'] = $tableName;

            $params['routeParams'] = preg_replace('/\n/', ' ', var_export(array(
                'module' => $this->_moduleName,
                'controller' => strtolower($objects['c2d']->filter($objects['d2c']->filter($tableName))),
                'action' => 'index',
            ), true));

            foreach ($this->selectedKeys as $key) {
                $output = $this->outputs[$key];

                if ($tableDefinition->isCrossRef() && !$output['acceptMapTable']) {
                    continue;
                }
                if (!$isFirst && $output['once'] == 1) {
                    continue;
                }

                $params['fileName'] = $output['outputPath'];
                $params['canOverride'] = $output['canOverride'];
                $params['templateFile'] = $output['templateFile'];

                $params['baseName'] = $objects['d2c']->filter($tableDefinition->getName());
                $params['controllerName'] = strtolower($objects['c2d']->filter($params['baseName']));


                $params['TABLE_CAMEL_NAME'] = $params['baseName'];
                $params['TABLE_FORM_NAME'] = $tableDefinition->getPhpName();
                $params['TABLE_CONTROLLER_NAME'] = $params['controllerName'];


                $helperClass = 'ZFscaffold_ZfTool_Renderer_' . $objects['c2d1']->filter(str_replace('.php', '', basename($params['templateFile'])));
                if (class_exists($helperClass)) {
                    /** @var $helper ZFscaffold_ZfTool_Renderer_Abstract */
                    $helper = new $helperClass($this, $this->getForceOverWrite());
                } else {
                    $helper = new ZFscaffold_ZfTool_Renderer_Standard($this, $this->getForceOverWrite());
                }

                $helper->setTemplate($params['templateFile']);
                $helper->setDestination($params['fileName']);
                $helper->setVariables($params);
                $helper->setObjects($objects);

                $helper->write();

            }
            $isFirst = false;
        }
        foreach ($this->objects['staticFiles'] as $kind => $files) {
            foreach ($files as $name => $src) {
                $dir = APPLICATION_PATH . '/../static/' . $kind;
                if (!file_exists($dir)) {
                    $res = mkdir($dir, 0777, true);
                    if (!$res) {

                    }
                }
                $dest = $dir . '/' . $name;
                $src = APPLICATION_PATH . '/../' . $src;
                copy($src, $dest);
            }
        }
    }

    public static function copyFileContent(Zend_Tool_Project_Context_Filesystem_File $resource)
    {
        $path = realpath(__DIR__ . '/templates/project') . '/';

        if ($resource->getResource()->getAttribute('sourceName')) {
            $template = $resource->getResource()->getAttribute('sourceName');
        } else {
            $template = $resource->getResource()->getAttribute('filesystemName');
        }

        /** @noinspection PhpToStringImplementationInspection */
        if (Dfi\File::isReadable($path . $template)) {
            return file_get_contents($path . $template);
        } else {
            throw new ZFscaffold_ZfTool_Exception('template defined but not found: ' . $template, self::MSG_ERROR);
        }
    }


    /**
     * Convert a table name to a form class name.
     *
     * @param string $tableName
     * @return string
     */
    public function _getFormClassName($tableName)
    {
        return $this->_appNamespace . 'Form_Edit' . $this->_getCamelCase($tableName);
    }

    /**
     * Convert a string to CamelCase format.
     *
     * Underscores are eliminated, each word's first character is capitalized.
     *
     * Eg, post -> Post, posts_tags => PostsTags
     *
     * @param string $string
     * @return string
     */
    protected function _getCamelCase($string)
    {
        $string = str_replace(array('_', '-'), ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);

        return $string;
    }

    /**
     * Convert a string to CamelCase label.
     *
     * Underscores are eliminated, each word's first character is capitalized.
     *
     * Eg, post -> Post, posts_tags => Posts Tags
     *
     * @param string $string
     * @return string
     */
    protected function _getLabel($string)
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);

        return $string;
    }


    /**
     * Preserve some special constants in application.ini file
     *
     * @param string $iniFilename
     */
    protected function _preserveIniConfigs($iniFilename)
    {
        $ini = file_get_contents($iniFilename);

        //$ini = preg_replace('#"([A-Z_]{2,})#s', '\1 "', $ini);

        $ini = str_replace('"APPLICATION_PATH/', 'APPLICATION_PATH "/', $ini);
        // "0" -> 0, "1" => 1...
        $ini = preg_replace('#= "(\d+)"#si', '= \1', $ini);

        file_put_contents($iniFilename, $ini);
    }

    /**
     * Show the question and retrieve answer from user
     *
     * @param string $question
     * @return string
     */
    protected function _readInput($question)
    {
        $this->_printMessage($question, self::MSG_NORMAL);
        if ($this->autoConfig) {
            echo "\n";
            return '';
        }
        return trim(fgets(STDIN));
    }

    protected function _question($question, $default, $valid = array())
    {
        $doCheckValid = count($valid) > 0;
        if ($doCheckValid) {
            array_unshift($valid, '');
        }
        do {

            $answer = $this->_readInput($question);
            if ($doCheckValid) {
                $isValidAnswer = (false !== array_search($answer, $valid));
            } else {
                $isValidAnswer = true;
            }
            if (!$isValidAnswer) {
                echo 'Incorrect value';
            }

        } while (!$isValidAnswer);

        if (!empty($question)) {
            $answer = $default;
        }
        if ($doCheckValid) {
            $isValidAnswer = (false !== array_search($answer, $valid));
            if (!$isValidAnswer) {
                $messages[] = 'Invalid default value: ' . $default . ' for question: ';
                $messages[] = $question;
                $this->_printMessage($messages, self::MSG_ERROR);
                throw new ZFscaffold_ZfTool_Exception($messages[0]);
            }

        }
        $this->_printMessage($answer, self::MSG_SPECIAL, array('color' => 'green'));
        return $answer;
    }


    /**
     *
     * @param string $filePath
     * @param string $code
     * @param bool $allowOverride
     * @return integer -1 = existing, 1 = created, 0 = other
     */
    public function _createFile($filePath, $code, $allowOverride = false)
    {
//        $filePath = realpath($filePath);
        $baseDir = pathinfo($filePath, PATHINFO_DIRNAME);
        $relativePath = str_replace($this->cwd . '/', '', $filePath);

        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        if (!$allowOverride && file_exists($filePath)) {
            echo "\033[31mExisting\033[37m: $relativePath\n";
            return -1;
        }

        if (@file_put_contents($filePath, $code)) {
            echo "\033[32mCreating\033[37m: $relativePath\n";
            return 1;
        } else {
            echo "\033[31mFAILED creating\033[37m: $relativePath\n";
        }

        return 0;
    }

    private function _initPaths($path)
    {
        defined('APPLICATION_PATH') || define('APPLICATION_PATH', $path);
        defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'production');

        $path = constant('APPLICATION_PATH');
        $env = constant('APPLICATION_ENV');

        $this->_printMessage('path: ' . $path, self::MSG_SPECIAL, array('color' => array('hiCyan')));
        $this->_printMessage('env: ' . $env, self::MSG_SPECIAL, array('color' => array('hiCyan')));

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
            ->registerNamespace('Dfi_')
            ->registerNamespace('ZFscaffold_');

    }

    private function _initPropel($propelConfig)
    {
        if (!file_exists($propelConfig)) {
            throw new ZFscaffold_ZfTool_Exception(
                "Propel config $propelConfig not exist"
            );
        };
        //require_once $this->cwd. . '/../library/propel/Propel.php';
        Propel::init($propelConfig);
        $propelConf = Propel::getConfiguration();

        if ($propelConf['classmap']) {
            foreach ($propelConf['classmap'] as $class => $file) {
                if (substr($class, -4) === 'Peer') {
                    class_exists($class);
                }
            }
        }
        $this->tables = Propel::getDatabaseMap()->getTables();
        /** @var $table TableMap */
        foreach ($this->tables as $table) {
            $table->buildRelations();
            Dfi\Propel\Helper::bulidRelationsOnFK($table);
            Dfi\Propel\Helper::findAutoLabel($table);
        }
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

    private function _prepareConfig($shouldUpdateConfigFile)
    {

        // used to modify the file
        $writableConfigs = new Zend_Config_Ini($this->appConfigFile, null, array(
            'skipExtends' => true,
            'allowModifications' => true
        ));

        // modify the config file
        if (!$writableConfigs->scaffold) {
            $writableConfigs->scaffold = array();
            $shouldUpdateConfigFile = true;
        }


        if ($writableConfigs->scaffold->outputTemplate != $this->_outputTemplate) {
            $writableConfigs->scaffold->outputTemplate = $this->_outputTemplate;
            $shouldUpdateConfigFile = true;
        }
        if ($writableConfigs->scaffold->packageName != $this->_packageName) {
            $writableConfigs->scaffold->packageName = $this->_packageName;
            $shouldUpdateConfigFile = true;
        }


        // auto-add "resources.frontController.moduleDirectory" if module is specified
        if (!empty($this->_moduleName)) {
            if (!$writableConfigs->production->resources->frontController->moduleDirectory) {
                $writableConfigs->production->resources->frontController->moduleDirectory = 'APPLICATION_PATH/modules';
                $shouldUpdateConfigFile = true;
            }
            //TODO poco to jest ?
            if (!isset($writableConfigs->production->resources->modules)) {
                $writableConfigs->production->resources->modules = '';
                $shouldUpdateConfigFile = true;
            }
        }
        if ($writableConfigs->scaffold->moduleName != $this->_moduleName) {
            $writableConfigs->scaffold->moduleName = $this->_moduleName;
            $shouldUpdateConfigFile = true;
        }

        if ($shouldUpdateConfigFile) {
            $configWriter = new Zend_Config_Writer_Ini(array(
                'config' => $writableConfigs,
                'filename' => $this->appConfigFile
            ));

            $backupName = 'application.ini';
            $backupCount = 1;

            $dirConfig = dirname($this->appConfigFile);

            // create a backup
            while (file_exists($dirConfig . "$backupName.$backupCount")) {
                ++$backupCount;
            }
            $destination = $dirConfig . "/$backupName.$backupCount";
            $result = copy($this->appConfigFile, $destination);
            if (!$result) {
                $this->_printMessage('copy ' . $this->appConfigFile . ' to ' . $destination . ' failed');
            }

            $configWriter->write();
            $this->writeConfig();
        }

        // some constants like APPLICATION_PATH is replaced with "APPLICATION_PATH"
        // we need to remove the double quotes...
        $this->_preserveIniConfigs($this->appConfigFile);

        echo 'Configs have been written to application.ini', PHP_EOL;
        // end of modifying configs

    }

    private function writeConfig()
    {
        $currentWorkingDirectory = getcwd();

        $configDir = str_replace(
            '/', DIRECTORY_SEPARATOR, $currentWorkingDirectory . '/application/configs/');

        $configFilePath = $configDir . 'application.ini';

        $rawConfig = parse_ini_file($configFilePath, true);

        $res = array();
        foreach ($rawConfig as $key => $val) {
            if (is_array($val)) {
                $res[] = '';
                $res[] = '[' . $key . ']';
                foreach ($val as $sKey => $sVal) {
                    $res[] = $sKey . ' = ' . $this->parseIniValue($sVal);
                }
            } else {
                $res[] = $key . ' = ' . $this->parseIniValue($val);
            }
        }
        $result = file_put_contents($configFilePath, implode("\r\n", $res));


        if (!$result) {
            $this->_printMessage('write  ' . $configFilePath . ' failed');
        }
    }

    private function parseIniValue($value)
    {
        /*      if (false !== strpos($value, PATH_SEPARATOR)) {
                  $tmp1 = explode(PATH_SEPARATOR, $value);
              } else {
                  $tmp1 = array($value);
              }
              foreach ($tmp1 as $key => $value) {
                  if (false !== strpos($value, '/')) {
                      $tmp = realpath($value);
                      if ($tmp) {
                          $tmp1[$key] = $tmp;
                      }
                  }
              }
              $value = implode(PATH_SEPARATOR, $tmp1);*/

        $currentWorkingDirectory = getcwd() . '/application';
        $pos = strpos($value, $currentWorkingDirectory);
        if (false !== $pos) {
            if (0 == $pos) {
                $value = str_replace($currentWorkingDirectory, 'APPLICATION_PATH "', $value) . '"';
            } else {
                $value = '"' . str_replace($currentWorkingDirectory, '" APPLICATION_PATH "', $value) . '"';
            }
        }

        if (is_numeric($value)) {
            return $value;
        } elseif (false === strpos($value, '"')) {
            return '"' . $value . '"';
        } else {
            return
                $value;
        }

    }


    ///////
    /**
     * @return boolean
     */
    private function getForceOverWrite()
    {
        return $this->forceOverWrite;
    }


    private function _collectData()
    {
        $zodekenDir = dirname(__FILE__);
        $outputGroups = array();
        $asciiChar = 97;
        $allKeys = array();
        $outputs = array();

        $templateQuestion[] = PHP_EOL;
        $templates = array_map('basename', glob($this->scaffoldDir . '/templates/scaffold/*', GLOB_ONLYDIR));
        $templateQuestion[] = "Enter output template:";
        foreach ($templates as $templateName) {
            $templateQuestion[] = array($templateName, self::MSG_SPECIAL, array('indention' => 4));
        }
        $templateQuestion[] = array('Your choice (' . $this->_outputTemplate . '): ', self::MSG_NORMAL);

        $this->_outputTemplate = $this->_question($templateQuestion, $this->_outputTemplate, $templates);


        $question = array(
            'Which files do you want to generate?',
            '- Enter 1 to generate all',
            '- Enter a list of groups, e.g. crud,form...',
            '- Or enter a list of individual template file keys, e.g. a,b,c,d...',
            '- You can combine groups with file keys, e.g. crud,h,i... ' . PHP_EOL);

        // parse the config file
        $xdoc = new DOMDocument();
        $xdoc->load($this->scaffoldDir . '/templates/scaffold/' . $this->_outputTemplate . '/output-config.xml');
        foreach ($xdoc->getElementsByTagName('outputGroup') as $outputGroupElement) {
            /* @var $outputGroupElement DOMElement */

            $outputGroupName = $outputGroupElement->getAttribute('name');

            $setVariable = $outputGroupElement->getAttribute('setVariable');
            if ($setVariable) {
                $this->params[$setVariable] = true;
            }

            $outputGroups[$outputGroupName] = array();

            $question[] = array($outputGroupName, self::MSG_SPECIAL, array('indention' => 4));

            /** @var $outputElement DOMElement */
            foreach ($outputGroupElement->getElementsByTagName('output') as $outputElement) {
                $output = array(
                    'key' => strtolower(chr($asciiChar++)),
                    'templateName' => $outputElement->getAttribute('templateName'),
                    'templateFile' => $zodekenDir . '/templates/scaffold/' . $this->_outputTemplate . '/' . $outputElement->getAttribute('templateName'),
                    'canOverride' => (int)$outputElement->getAttribute('canOverride'),
                    'outputPath' => $outputElement->getAttribute('outputPath'),
                    'acceptMapTable' => $outputElement->getAttribute('acceptMapTable'),
                    'once' => $outputElement->getAttribute('once'),
                );

                $outputs[$output['key']] = $output;
                $outputGroups[$outputGroupName][] = $output;

                $allKeys[] = $output['key'];

                $question[] = array($output['key'] . '. ' . $output['templateName'], self::MSG_SPECIAL, array('indention' => 8));
            }
        }
        $files = array();
        /** @var $kind DOMElement */
        foreach ($xdoc->getElementsByTagName('staticFiles') as $kind) {
            /** @var $node DOMNode */
            foreach ($kind->childNodes as $node) {
                if ($node->nodeType != 1) {
                    continue;
                }
                $kindName = $node->tagName;
                $files[$kindName] = array();
                /** @var $cssFile DOMElement */
                foreach ($node->getElementsByTagName('file') as $file) {
                    $files[$kindName][$file->getAttribute('path')] = $file->getAttribute('src');;
                }
            }
        }
        $this->objects['staticFiles'] = $files;


        $question[] = PHP_EOL;
        $question[] = array('Your choice:', self::MSG_NORMAL);


        $input = strtolower(trim($this->_question($question, 1, array_merge(array(1), $allKeys))));
        if ('1' == $input) {
            $this->selectedKeys = $allKeys;
        } elseif ($input) {
            $keys = array();
            $expectedOutputs = preg_split('#\s*,\s*#', $input);
            $this->selectedKeys = array();
            foreach ($expectedOutputs as $expectedOutput) {
                if (isset($outputGroups[$expectedOutput])) {
                    foreach ($outputGroups[$expectedOutput] as $output) {
                        $keys[] = $output['key'];
                    }
                } elseif (in_array($expectedOutput, $allKeys)) {
                    $keys[] = $expectedOutput;
                }
            }
            $this->selectedKeys = array_unique($keys);
        } else {
            $this->selectedKeys = array();
        }


        $this->_packageName = $this->_question("Your package name ($this->_packageName): ", $this->_packageName);


        $moduleName = $this->_question("Module name ($this->_moduleName): default for disable ", $this->_moduleName);
        if ($moduleName == 'default') {
            $moduleName = '';
        }
        if (!empty($moduleName)) {
            $this->_moduleName = $moduleName;
            $this->_controllerNamePrefix = $this->_getCamelCase($moduleName) . '_';
        }

        $this->outputGroups = $outputGroups;
        $this->outputs = $outputs;

        $this->_useAuth = true;

        $this->_authMethod = 'db';
        $this->_authTable = 'Admin';

        $this->_authLoginField = 'username';
        $this->_authPasswordField = 'password';

        $this->_authPasswordHash = 'sha1';

        $this->_useAuth = $this->_question("Generate auth ($this->_useAuth): ", $this->_useAuth);
        if ($this->_useAuth) {
            $this->_authMethod = $this->_question("Auth method db|ldap ($this->_authMethod): ", $this->_authMethod);

            $this->_authTable = $this->_question("Table for auth ($this->_authTable): ", $this->_authTable);

            $this->_authLoginField = $this->_question("Login field ($this->_authLoginField): ", $this->_authLoginField);
            $this->_authPasswordField = $this->_question("Password field ($this->_authPasswordField): ", $this->_authPasswordField);
            $this->_authActivityField = $this->_question("Activity field 0 for disable ($this->_authActivityField): ", $this->_authActivityField);

            $this->_authPasswordHash = $this->_question("Password hash method plain|md5 ($this->_authPasswordHash): ", $this->_authPasswordHash);

        }


    }

    public function _printMessage($messages, $mode = self::MSG_NORMAL, $options = array())
    {
        if (!is_array($messages)) {
            $tmp = $messages;
            $messages = array();
            $messages[] = $tmp;
        }
        $response = $this->_registry->getResponse();

        foreach ($messages as $key => $message) {
            if (is_array($message)) {
                $tmp = $message;
                $message = $tmp[0];
                if (count($tmp) > 1) {
                    $mode = $tmp[1];
                    if (count($tmp) > 2) {
                        $options = $tmp[2];
                    }
                }

            }
            switch ($mode) {
                case self::MSG_ERROR:

                    $width = @exec('tput cols');
                    $response->appendContent($message, array('color' => array('hiWhite', 'bgRed'), 'aligncenter' => !empty($width) ? $width : 80));
                    if ($key == count($messages) - 1) {
                        $response->appendContent(PHP_EOL);
                    }
                    break;
                case self::MSG_SPECIAL:
                    $response->appendContent($message, $options);
                    break;
                case self::MSG_NORMAL:
                default:
                    $response->appendContent($message);

                    break;
            }
        }
    }

    private function _includeVariables()
    {
        $ref = new ReflectionObject($this);

        $props = $ref->getProperties();

        /** @var $prop ReflectionProperty */
        foreach ($props as $prop) {
            $name = $prop->getName();
            if (preg_match('/^_/', $name) && !is_object($this->$name) && !is_array($this->$name)) {
                $this->params[preg_replace('/^_/', '', $name)] = $this->$name;
            }
        }
    }
}