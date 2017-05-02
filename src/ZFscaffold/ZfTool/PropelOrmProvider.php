<?php

// PHP < 5.3 compatibility
!defined('__DIR__') || define('__DIR__', dirname(__FILE__));

class ZFscaffold_ZfTool_PropelOrmProvider extends Zend_Tool_Framework_Provider_Abstract
{
    private $config;
    private $colorsSupport;

    const CONFIG_FILE_NAME = 'generator.ini';
    const DEFAULT_NUMBERS_OF_DATABASE = 1;

    public function setRegistry(Zend_Tool_Framework_Registry_Interface $registry)
    {
        $response = $registry->getResponse();
        ZFscaffold_ZfTool_Helpers_Messages::setResponse($response);
        return parent::setRegistry($registry);
    }

    public function createConfig($force = 0)
    {
        $currentWorkingDirectory = getcwd();
        $force = filter_var($force, FILTER_VALIDATE_BOOLEAN);

        $ormDir = $currentWorkingDirectory . '/vendor/dafik/generator';
        $schemaDir = $ormDir . '/schema';

        if (!file_exists($schemaDir)) {
            mkdir($schemaDir, 0777, true);
        } else {
            $this->_init($schemaDir);
        }

        $_countDatabases = Dfi\App\Config::getString('generator.numberOfDatabases', self::DEFAULT_NUMBERS_OF_DATABASE);

        $question = "How many databases do you want setup ($_countDatabases): ";
        $countDatabases = $this->_readInput($question);
        if ('' === $countDatabases) {
            $countDatabases = $_countDatabases;
        }
        Dfi\App\Config::set('generator.numberOfDatabases', $countDatabases);

        $names = array();
        for ($i = 0; $i < $countDatabases; $i++) {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('attempt generate for ' . ($i + 1) . ' database', ZFscaffold_ZfTool_Helpers_Messages::MSG_SPECIAL, array('color' => 'hiMagenta'));

            $this->_createProperties($schemaDir, $force, $i);
            $this->_createRuntime($schemaDir, $force, $i);
            $names[] = Dfi\App\Config::getString('generator.projectName.' . $i);
        }
        $this->_createMerge($names, $schemaDir, $force);


        $this->_writeConfig($schemaDir);
    }

    public function generate()
    {

        $currentWorkingDirectory = getcwd();
        $schemaDir = $currentWorkingDirectory . '/vendor/dafik/generator/schema';

        $this->_init($schemaDir);

        $configs = $this->_checkCountDatabases($schemaDir);

        $this->_mergeRuntime($schemaDir, $configs);

        foreach ($configs as $i => $config) {
            $this->_reverseSchema($schemaDir, $config, $currentWorkingDirectory);
            $this->_modifiSchema($schemaDir, $config);
            $this->_generateOrmCode($schemaDir, $config, $currentWorkingDirectory);
            $this->_postOrmUpdate($schemaDir, $config, $i);
        }


        $this->_deploy($schemaDir, $configs, $currentWorkingDirectory);
    }

    private function _parseIniValue($value)
    {
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
            if (false !== strpos($value, 'APPLICATION_PATH')) {
                $pos = strpos($value, 'APPLICATION_PATH');
                if ($pos != 0) {
                    return '"' . $value . '"';
                }
            }
            return
                $value;
        }
    }

    private function _createProperties($schemaDir, $force, $i)
    {

        $_project = Dfi\App\Config::getString('generator.projectName.' . $i, 'default');
        $question = "Project name ($_project): ";
        $project = $this->_readInput($question);
        if ('' === $project) {
            $project = $_project;
        }
        Dfi\App\Config::set('generator.projectName.' . $i, $project);


        $_targetPackage = Dfi\App\Config::getString('generator.targetPackage.' . $i, 'models');
        $question = "Package name ($_targetPackage): ";
        $targetPackage[$i] = $this->_readInput($question);
        if ('' === $targetPackage[$i]) {
            $targetPackage = $_targetPackage;
        }
        Dfi\App\Config::set('generator.targetPackage.' . $i, $targetPackage);


        $_database = Dfi\App\Config::getString('generator.database.' . $i, 'mysql');
        $question = "Database pgsql|mysql|sqlite|mssql|oracle ($_database): ";
        $database = $this->_readInput($question);
        if ('' === $database) {
            $database = $_database;
        }
        Dfi\App\Config::set('generator.database.' . $i, $database);


        $_url = Dfi\App\Config::getString('generator.url.' . $i, 'mysql:host=localhost;dbname=www');
        $question = "Url ($_url): ";
        $url = $this->_readInput($question);
        if ('' === $url) {
            $url = $_url;
        }
        Dfi\App\Config::set('generator.url.' . $i, $url);


        $_user = Dfi\App\Config::getString('generator.user.' . $i, 'root');
        $question = "User ($_user): ";
        $user = $this->_readInput($question);
        if ('' === $user) {
            $user = $_user;
        }
        Dfi\App\Config::set('generator.user.' . $i, $user);


        $_password = Dfi\App\Config::getString('generator.password.' . $i, 'alamakota');
        $question = "Password ($_password): ";
        $password = $this->_readInput($question);
        if ('' === $password) {
            $password = $_password;
        }
        Dfi\App\Config::set('generator.password.' . $i, $password);

        $builderPath = realpath(__DIR__ . '/Generator/Propel/builder');
        $builderPathParts = explode(DIRECTORY_SEPARATOR, $builderPath);

        $behaviorsPath = realpath(__DIR__ . '/Generator/Propel/behavior');
        $behaviorsPathParts = explode(DIRECTORY_SEPARATOR, $behaviorsPath);

        $options = array();
        $options[] = 'propel.project=' . $project;
        $options[] = 'propel.targetPackage=' . $targetPackage;
        $options[] = 'propel.database=' . $database;
        $options[] = 'propel.database.url=' . $url;
        $options[] = 'propel.database.user=' . $user;
        $options[] = 'propel.database.password=' . $password;
        $options[] = 'propel.addVendorInfo = true';

        $options[] = 'propel.builder.object.class = ' . implode('.', $builderPathParts) . '.DfiPHP5ObjectBuilder';
        if ($database == 'mysql' || $database == 'pgsql') {
            //TODO other databases
            $options[] = 'propel.reverse.parser.class = ' . implode('.', $builderPathParts) . '.Dfi${propel.database}SchemaParser';
        }
        $options[] = 'propel.builder.tablemap.class = ' . implode('.', $builderPathParts) . '.DfiPHP5TableMapBuilder';
        $options[] = 'propel.builder.pluralizer.class = builder.util.StandardEnglishPluralizer';

        $options[] = 'propel.behavior.equal_nest.class = ' . implode('.', $behaviorsPathParts) . '.equal_nest.EqualNestBehavior';
        $options[] = 'propel.behavior.hashable.class = ' . implode('.', $behaviorsPathParts) . '.hashable.HashableBehavior';


        $configPath = $schemaDir . '/' . $project . '/build.properties';
        $dir = dirname($configPath);

        if (!file_exists($dir)) {
            $res = mkdir($dir, 0777, true);
            if (!$res) {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('create directory failed:' . $dir, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
            }
        }

        if (!file_exists($configPath) || $force) {
            $res = file_put_contents($configPath, implode("\r\n", $options));
            if (!$res) {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('configuration for ' . $project . ' write failed:' . $configPath, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
            }
            ZFscaffold_ZfTool_Helpers_Messages::printOut('configuration for ' . $project . ':');
            ZFscaffold_ZfTool_Helpers_Messages::printOut(implode("\r\n", $options), ZFscaffold_ZfTool_Helpers_Messages::MSG_SPECIAL, array('color' => 'yellow', 'indention' => 2));
            ZFscaffold_ZfTool_Helpers_Messages::printOut('build.properties for ' . $project . ' written to: ' . $configPath);
        } else {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('build.properties  for ' . $project . ' exist: ' . $configPath);
        }
    }

    private function _createRuntime($schemaDir, $force, $i)
    {
        $project = Dfi\App\Config::getString('generator.projectName.' . $i);
        $configPath = $schemaDir . '/' . $project . '/' . $project . '-runtime-conf.xml';


        $template = __DIR__ . '/templates/orm/runtime-conf.xml';

        $sxe = simplexml_load_file($template);

        $sxe->propel->datasources->attributes()->default = Dfi\App\Config::get('generator.projectName.' . $i);
        $sxe->propel->datasources->datasource->attributes()->id = Dfi\App\Config::get('generator.projectName.' . $i);
        $sxe->propel->datasources->datasource->adapter = Dfi\App\Config::get('generator.database.' . $i);
        $sxe->propel->datasources->datasource->connection->dsn = Dfi\App\Config::get('generator.url.' . $i);
        $sxe->propel->datasources->datasource->connection->user = Dfi\App\Config::get('generator.user.' . $i);
        $sxe->propel->datasources->datasource->connection->password = Dfi\App\Config::get('generator.password.' . $i);

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = FALSE;
        $dom->loadXML($sxe->asXML());
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//text()') as $domText) {
            $domText->data = trim($domText->nodeValue);
        }
        $dom->formatOutput = TRUE;
        $dom->encoding = 'UTF-8';

        $out = $dom->saveXml();


        if (!file_exists($configPath) || $force) {
            $res = file_put_contents($configPath, $out);
            if (!$res) {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('configuration for ' . $project . ' write failed:' . $configPath, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
            } else {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('configuration for ' . $project . ' :');
                ZFscaffold_ZfTool_Helpers_Messages::printOut($out, ZFscaffold_ZfTool_Helpers_Messages::MSG_SPECIAL, array('color' => 'yellow', 'indention' => 2));
                ZFscaffold_ZfTool_Helpers_Messages::printOut('runtime-conf.xml for ' . $project . ' written to: ' . $configPath);
            }
        } else {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('runtime-conf.xml for ' . $project . ' exist');
        }
    }

    private function _createMerge($names, $schemaDir, $force)
    {
        foreach ($names as $key => $name) {
            $options = array();
            $options[] = 'project.count=' . count($names);
            $options[] = 'project.name=' . Dfi\App\Config::getString('generator.projectName.' . $key);

            $configPath = $schemaDir . '/' . Dfi\App\Config::getString('generator.projectName.' . $key) . '/merge.properties';

            if (!file_exists($configPath) || $force) {
                $res = file_put_contents($configPath, implode("\r\n", $options));
                if (!$res) {
                    ZFscaffold_ZfTool_Helpers_Messages::printOut('error writing merge.properties  for ' . $name . ' :' . $configPath, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
                } else {
                    ZFscaffold_ZfTool_Helpers_Messages::printOut('merge.properties  for ' . $name . ':');
                    ZFscaffold_ZfTool_Helpers_Messages::printOut(implode("\r\n", $options), ZFscaffold_ZfTool_Helpers_Messages::MSG_SPECIAL, array('color' => 'yellow', 'indention' => 2));
                    ZFscaffold_ZfTool_Helpers_Messages::printOut('merge.properties  for ' . $name . 'written to: ' . $configPath);
                }
            } else {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('merge.properties  for ' . $name . ' exist');
            }
        }
    }

    /**
     * Show the question and retrieve answer from user
     *
     * @param string $question
     * @return string
     */
    protected function _readInput($question)
    {
        ZFscaffold_ZfTool_Helpers_Messages::printOut($question, ZFscaffold_ZfTool_Helpers_Messages::MSG_SPECIAL, array('color' => 'green'));
        return trim(fgets(STDIN));
    }

    /*    private function _getAppConfig($mode = false)
        {
            $currentWorkingDirectory = getcwd();

            $configDir = str_replace(
                '/', DIRECTORY_SEPARATOR, $currentWorkingDirectory . '/application/configs/');

            $configFilePath = $configDir . 'application.ini';

            if (!file_exists($configFilePath)) {

                throw new ZFscaffold_ZfTool_Exception(
                    'Application config file not found: ' . $configFilePath
                );
            }

            $this->_cwd = $currentWorkingDirectory;

            define('APPLICATION_PATH', 'asasdasd');
            // used to get db configs
            $config = new Zend_Config_Ini($configFilePath, null, array(
                'skipExtends' => true,
                'allowModifications' => $mode
            ));


            return $config;
        }

        private function _writeAppConfig($config)
        {
            $currentWorkingDirectory = getcwd();

            $configDir = str_replace(
                '/', DIRECTORY_SEPARATOR, $currentWorkingDirectory . '/application/configs/');

            $configFilePath = $configDir . 'application.ini';

            if (!file_exists($configFilePath)) {

                throw new ZFscaffold_ZfTool_Exception(
                    'Application config file not found: ' . $configFilePath
                );
            }


            // used to get db configs
            $writer = new Zend_Config_Writer_Ini(array(
                    'config' => $config,
                    'filename' => $configFilePath
                )
            );
            $backupName = 'application.ini';
            $backupCount = 1;

            // create a backup
            while (file_exists($configDir . "$backupName.$backupCount")) {
                ++$backupCount;
            }
            copy($configFilePath, $configDir . "$backupName.$backupCount");

            $writer->write();
        }*/
    private function _checkCountDatabases($path)
    {

        $numberOfDatabases = Dfi\App\Config::get('generator.numberOfDatabases');

        $found = array();
        for ($i = 0; $i < $numberOfDatabases; $i++) {

            $projectName = Dfi\App\Config::getString('generator.projectName.' . $i);
            $dirIterator = new DirectoryIterator($path . '/' . $projectName);
            /** @var $splFileInfo DirectoryIterator */
            foreach ($dirIterator as $splFileInfo) {
                if (!$splFileInfo->isDot() && $splFileInfo->isFile()) {
                    if (preg_match('/build\.properties/', $splFileInfo->getFilename())) {
                        $found[$i] = $projectName;
                    }
                }
            }
        }
        return $found;
    }

    //$ormDir, $config, $currentWorkingDirectory
    private function _reverseSchema($schemaDir, $config, $currentWorkingDirectory)
    {
        $buildFile = $schemaDir . '/' . $config . '/build.properties';
        $runtimeFile = $schemaDir . '/' . $config . '/runtime-conf.xml';

        if (!file_exists($schemaDir) || !file_exists($buildFile) || !file_exists($runtimeFile)) {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('schema not exist attempt to generate');
            $this->createConfig(1);
        }


        $_SERVER['argv'] = array(
            'scriptname',
            '-f',
            $currentWorkingDirectory . '/vendor/propel/propel1/generator/build.xml',
            '-Dusing.propel-gen=true',
            '-Dproject.dir=' . $currentWorkingDirectory . '/vendor/dafik/generator/schema/' . $config,
            '-Dbuild.properties=build.properties',
            'reverse'
            //'-verbose'
        );

        if ($this->_hasColorsSupport()) {
            $_SERVER['argv'][] = '-logger';
            $_SERVER['argv'][] = 'phing.listener.AnsiColorLogger';
        } else {
            $_SERVER['argv'][] = '-logger';
            $_SERVER['argv'][] = 'phing.listener.DefaultLogger';
        }


        putenv("PHING_HOME=" . realpath($currentWorkingDirectory . '/vendor/phing/phing'));

        $phingFile = $currentWorkingDirectory . '/vendor/phing/phing/bin/phing.php';
        $result = include $phingFile;

        return $result;
    }

    private function _modifiSchema($schemaDir, $config)
    {
        $schemaPath = $schemaDir;
        $mergeConfig = $schemaDir . '/' . $config . '/merge.properties';

        ZFscaffold_ZfTool_Helpers_Messages::printOut('modifiying schema');
        $merger = new ZFscaffold_ZfTool_Generator_Propel_Merger();
        @$merger->merge($schemaDir . '/' . $config, $mergeConfig);
        return $schemaPath;
    }

    private function _generateOrmCode($schemaDir, $config, $currentWorkingDirectory)
    {
        $_SERVER['argv'] = array(
            'scriptname',
            '-f',
            $currentWorkingDirectory . '/vendor/propel/propel1/generator/build.xml',
            '-Dusing.propel-gen=true',
            '-Dproject.dir=' . $schemaDir . '/' . $config,
            '-Dbuild.properties=build.properties',
            //'-verbose'

        );
        if ($this->_hasColorsSupport()) {
            $_SERVER['argv'][] = '-logger';
            $_SERVER['argv'][] = 'phing.listener.AnsiColorLogger';
        } else {
            $_SERVER['argv'][] = '-logger';
            $_SERVER['argv'][] = 'phing.listener.DefaultLogger';
        }

        putenv("PHING_HOME=" . realpath($currentWorkingDirectory . '/vendor/phing/phing'));
        $phingFile = $currentWorkingDirectory . '/vendor/phing/phing/bin/phing.php';
        /** @noinspection PhpIncludeInspection */
        $result = include $phingFile;

        return $result;
    }

    private function _mergeRuntime($ormDir, $configs)
    {
        $files = array();

        $default = Dfi\App\Config::get('generator.default');

        foreach ($configs as $config) {
            $runtimeFile = $ormDir . '/' . $config . '/' . $config . '-runtime-conf.xml';
            $files[] = $runtimeFile;
        }
        $defaultIndex = array_search($default, $configs);

        $main = $files[$defaultIndex];
        unset($files[$defaultIndex]);

        $destinationDocument = new DOMDocument();
        $destinationDocument->preserveWhiteSpace = false;
        $destinationDocument->formatOutput = true;

        $res = $destinationDocument->loadXML(file_get_contents($main));
        if (!$res) {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('error loding xml  runtime conf ' . $main, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
        }


        foreach ($files as $file) {

            $docSource = new DOMDocument();
            $res = $docSource->loadXML(file_get_contents($file));
            if (!$res) {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('error loding xml  runtime conf ' . $file, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
            }


            $xpath = new DOMXPath($docSource);

            $result = $xpath->query('//datasource');
            foreach ($result as $node) {

                $result = $destinationDocument->importNode($node, true); //Copy the node to the other document
                $items = $destinationDocument->getElementsByTagName('datasources')->item(0);
                $items->appendChild($result); //Add the copied node to the destination document
            }
        }

        foreach ($configs as $config) {
            $runtimeFile = $ormDir . '/' . $config . '/runtime-conf.xml';
            $res = $destinationDocument->save($runtimeFile);
            if (!$res) {
                ZFscaffold_ZfTool_Helpers_Messages::printOut('error writing runtime conf ' . $runtimeFile, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
            }
        }


    }

    private function _postOrmUpdate($ormDir, $config, $i)
    {
        $postUpdateFile = $ormDir . '/' . $config . '/' . 'post-update.php';
        /** @noinspection PhpUnusedLocalVariableInspection */
        $package = Dfi\App\Config::getString('generator.targetPackage.' . $i);
        if (file_exists($postUpdateFile)) {
            include $postUpdateFile;
        }
    }

    private function _deploy($schemaDir, $configs, $currentWorkingDirectory)
    {
        if (false !== strpos($currentWorkingDirectory, 'tools')) {
            $pos = strpos($currentWorkingDirectory, 'tools');
            $currentWorkingDirectory = substr($currentWorkingDirectory, 0, $pos - 1);
            chdir($currentWorkingDirectory);
        }

        $_targetDir = Dfi\App\Config::getString('generator.targetDir', $currentWorkingDirectory . '/application/models');
        $question = "Target DirProject name ($_targetDir): ";

        //$targetDir = $this->_readInput($question);
        //if ('' === $targetDir) {
            $targetDir = $_targetDir;
        //}
        unset($_targetDir, $question);
        Dfi\App\Config::set('generator.targetDir', $targetDir);

        $propelCnf = array();
        $classmap = array();
        $sourceDir = '';

        foreach ($configs as $key => $config) {
            $package = Dfi\App\Config::getString('generator.targetPackage.' . $key);
            $sourceDir = $schemaDir . '/' . $config . '/build';

            $map = $targetDir . '/' . $config . '/map';
            if (!file_exists($map)) {
                mkdir($map, 0777, true);
            }
            $om = $targetDir . '/' . $config . '/om';
            if (!file_exists($om)) {
                mkdir($om, 0777, true);
            }

            $copy = array();
            $copy[] = 'cp -n ' . $sourceDir . '/classes/' . $package . '/*.php ' . $targetDir . '/' . $config;
            $copy[] = 'cp -f ' . $sourceDir . '/classes/' . $package . '/map/*.php ' . $targetDir . '/' . $config . '/map';
            $copy[] = 'cp -f ' . $sourceDir . '/classes/' . $package . '/om/*.php ' . $targetDir . '/' . $config . '/om';

            $file = $sourceDir . '/conf/classmap-' . $config . '-conf.php';
            $arr = include $file;
            $classmap = array_merge($classmap, $arr);
            $propelCnf[$config] = $sourceDir . '/conf/' . $config . '-conf.php';

            foreach ($copy as $command) {
                $out = array();
                $val = 0;
                exec($command, $out, $val);
                if ($val) {
                    foreach ($out as $line) {
                        ZFscaffold_ZfTool_Helpers_Messages::printOut($line, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
                    }
                }
            }
        }

        $default = Dfi\App\Config::getString('generator.default');

        $dst = realpath($targetDir . '/..') . '/configs/db/' . $default . '-conf.php';
        $src = $propelCnf[$default];
        rename($src, $dst);
        $dst = realpath($targetDir . '/..') . '/configs/db/classmap-' . $default . '-conf.php';
        $map = '<? return ' . var_export($classmap, true) . ';';
        $res = file_put_contents($dst, $map);
        if (!$res) {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('error writing classmap:' . $dst, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
        }


        //$currentWorkingDirectory = getcwd();

        $configDir = str_replace(
            '/', DIRECTORY_SEPARATOR, $currentWorkingDirectory . '/application/configs/');

        $configFilePath = $configDir . 'ini/application.ini';

        $rawConfig = parse_ini_file($configFilePath, true, INI_SCANNER_RAW);
        $rawConfig['production']['db.config'] = 'APPLICATION_PATH' . '  "/configs/db/' . $default . '-conf.php"';

        $res = array();
        foreach ($rawConfig as $key => $val) {
            if (is_array($val)) {
                $res[] = '[' . $key . ']';
                foreach ($val as $skey => $sval) {
                    $res[] = $skey . ' = ' . $this->_parseIniValue($sval);
                }
            } else {
                $res[] = $key . ' = ' . $this->_parseIniValue($val);
            }
        }
        $res = file_put_contents($configFilePath, implode("\r\n", $res));

        if (!$res) {
            ZFscaffold_ZfTool_Helpers_Messages::printOut('error writing app config :' . $configFilePath, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
        }

        foreach ($configs as $config) {
            $sourceDir = $schemaDir . '/' . $config . '/build';
            $command = 'rm -rf ' . $sourceDir;
            $out = array();
            $val = 0;
            exec($command, $out, $val);
            if ($val) {
                foreach ($out as $line) {
                    ZFscaffold_ZfTool_Helpers_Messages::printOut($line, ZFscaffold_ZfTool_Helpers_Messages::MSG_ERROR);
                }
            }
        }
    }

    private function _init($schemaDir)
    {
        defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'production');
        $configLocation = $schemaDir . '/' . self::CONFIG_FILE_NAME;


        if (file_exists($configLocation)) {
            $this->config = new Zend_Config_Ini($configLocation, null, array(
                'skipExtends' => true,
                'allowModifications' => true
            ));
        } else {
            $this->config = new Zend_Config(array(), true);
        }

        Dfi\App\Config::setConfig($this->config);
    }

    private function _writeConfig($schemaDir)
    {
        $configWriter = new Zend_Config_Writer_Ini(array(
            'filename' => $schemaDir . '/' . self::CONFIG_FILE_NAME,
            'config' => $this->config,
            'renderWithoutSections' => true,
            'exclusiveLock' => true
        ));
        $configWriter->write();
    }

    private function _hasColorsSupport()
    {
        if (null === $this->colorsSupport) {
            $color_numbers = @exec('tput colors');
            if (empty($color_numbers)) {
                $this->colorsSupport = false;
            } else {
                $this->colorsSupport = true;
            }
        }
        return $this->colorsSupport;
    }
}