<?php

// PHP < 5.3 compatibility
!defined('__DIR__') || define('__DIR__', dirname(__FILE__));

//require_once __DIR__ . '/../../ZFscaffold/ZfTool/Exception.php';
//require_once __DIR__ . '/../../ZFscaffold/Merger.php';
//require_once __DIR__ . '/../../ZFscaffold/Inflector.php';

/**
 * This class defines a provider for the ZF tool, it allows you generate
 * Data mapper, DbTables, Rowset, Row classes and the ZF controllers, views,
 * forms used for basic CRUD actions.
 *
 * All code is put into ZF application's default folders as guided by ZF.
 *
 * Usage: <code>generate propel-orm</code>
 *
 * For the provider to be properly loaded, please append the line below into
 * your .zf.ini file:
 *
 *  <code>basicloader.classes.10 = "ZFscaffold_ZfTool_ZodekenProvider"</code>
 *
 * (The number 10 is the order of the loaded class, it may be another number
 * up to your preferred configs)
 *
 * The .zf.ini file is located at your home folder, if it does not exist,
 * please run the command:
 *
 *  <code>create-config propel-orm</code>
 */

/**
 *  provider for Zend Tool
 *
 * @package Zodeken
 * @author Thuan Nguyen <me@ndthuan.com>
 * @copyright Copyright(c) 2011 Thuan Nguyen <me@ndthuan.com>
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt
 * @version $Id: ZodekenProvider.php 67 2012-08-25 11:47:08Z me@ndthuan.com $
 */
class ZFscaffold_ZfTool_PropelOrmProvider extends Zend_Tool_Framework_Provider_Abstract
{
    private $url;
    private $database;
    private $user;
    private $password;
    private $project;

    /**
     * The public method that would be exposed into ZF tool
     */
    public function generate()
    {

        $currentWorkingDirectory = getcwd();
        $ormDir = $currentWorkingDirectory . '/vendor/dafik/generator/schema';
        $buildFile = $ormDir . '/build.properties';
        $runtimeFile = $ormDir . '/runtime-conf.xml';

        if (!file_exists($ormDir) || !file_exists($buildFile) || !file_exists($runtimeFile)) {
            echo('schema not exist atempt to generate');
            $this->createConfig(1);
        }

        $currentWorkingDirectory = getcwd();
        //$shouldUpdateConfigFile = false;

        //$forceOverriding = $force ? true : false;

        // replace the slash just to print a beautiful message :D
        $configDir = str_replace(
            '/', DIRECTORY_SEPARATOR, $currentWorkingDirectory . '/application/configs/');

        $configFilePath = $configDir . 'application.ini';

        if (!file_exists($configFilePath)) {

            throw new ZFscaffold_ZfTool_Exception(
                'Application config file not found: ' . $configFilePath
            );
        }
        //$buildConfig = new Zend_Config_Ini($buildFile);

        //$currentWorkingDirectory = $currentWorkingDirectory . '/tools/generator/schema';
        //chdir($currentWorkingDirectory);

        $argv = array(
            'scriptname',
            '-f',
            $currentWorkingDirectory . '/vendor/propel/propel1/generator/build.xml',
            '-Dusing.propel-gen=true',
            '-Dproject.dir=' . $currentWorkingDirectory . '/vendor/dafik/generator/schema',
            'reverse',
            '-logger',
            'phing.listener.AnsiColorLogger'

        );


        putenv("PHING_HOME=" . realpath($currentWorkingDirectory . '/vendor/phing/phing'));

        include '/srv/vhosts/local/test/vendor/phing/phing/bin/phing.php';

        $schemaPath = $ormDir;
        $mergeConfig = $schemaPath . '/merge.properties';


        echo("\nmodifiying schema\n");
        $merger = new ZFscaffold_ZfTool_Generator_Propel_Merger();
        @$merger->merge($schemaPath, $mergeConfig);

        $argv = array(
            'scriptname',
            '-f',
            $currentWorkingDirectory . '/vendor/propel/propel1/generator/build.xml',
            '-Dusing.propel-gen=true',
            '-Dproject.dir=' . $currentWorkingDirectory . '/vendor/dafik/generator/schema',
            '-logger',
            'phing.listener.AnsiColorLogger'

        );

        include '/srv/vhosts/local/test/vendor/phing/phing/bin/phing.php';

        if (false !== strpos($currentWorkingDirectory, 'tools')) {
            $pos = strpos($currentWorkingDirectory, 'tools');
            $currentWorkingDirectory = substr($currentWorkingDirectory, 0, $pos - 1);
            chdir($currentWorkingDirectory);
        }

        $_targetDir = $currentWorkingDirectory . '/application/models';
        $question = "Target DirProject name ($_targetDir): ";
        $targetDir = $this->_readInput($question);
        if ('' === $targetDir) {
            $targetDir = $_targetDir;
        }

        $tmp = explode("\n", file_get_contents($buildFile));
        $buildConfig = array();
        foreach ($tmp as $line) {
            $parts = explode('=', $line);
            $key = trim($parts[0]);
            unset($parts[0]);
            $buildConfig[$key] = trim(implode('=', $parts));
        }


        $package = $buildConfig['propel.targetPackage'];
        $project = $buildConfig['propel.project'];
        $sourceDir = $schemaPath . '/build';


        $map = $targetDir . '/map';
        if (!file_exists($map)) {
            mkdir($map, 0777, true);
        }
        $om = $targetDir . '/om';
        if (!file_exists($om)) {
            mkdir($om, 0777, true);
        }


        $copy[] = 'cp -n ' . $sourceDir . '/classes/' . $package . '/*.php ' . $targetDir;
        $copy[] = 'cp -f ' . $sourceDir . '/classes/' . $package . '/map/*.php ' . $targetDir . '/map';
        $copy[] = 'cp -f ' . $sourceDir . '/classes/' . $package . '/om/*.php ' . $targetDir . '/om';

        $copy[] = 'cp -f ' . $sourceDir . '/conf/classmap-' . $project . '-conf.php ' . $targetDir . '/../configs';
        $copy[] = 'cp -f ' . $sourceDir . '/conf/' . $project . '-conf.php ' . $targetDir . '/../configs';

        foreach ($copy as $command) {
            $out = array();
            $val = 0;
            exec($command, $out, $val);
            if ($val) {
                foreach ($out as $line) {
                    echo $line . "\n";
                }
            }
        }

        $command = 'rm -rf ' . $sourceDir;
        $out = array();
        $val = 0;
        exec($command, $out, $val);
        if ($val) {
            foreach ($out as $line) {
                echo $line . "\n";
            }
        }

        //$currentWorkingDirectory = getcwd();

        $configDir = str_replace(
            '/', DIRECTORY_SEPARATOR, $currentWorkingDirectory . '/application/configs/');

        $configFilePath = $configDir . 'application.ini';

        $rawConfig = parse_ini_file($configFilePath, true, INI_SCANNER_RAW);
        $rawConfig['production']['db.config'] = 'APPLICATION_PATH' . '  "/configs/' . $project . '-conf.php"';

        $res = array();
        foreach ($rawConfig as $key => $val) {
            if (is_array($val)) {
                $res[] = '[' . $key . ']';
                foreach ($val as $skey => $sval) {
                    $res[] = $skey . ' = ' . $this->parseIniValue($sval);
                }
            } else {
                $res[] = $skey . ' = ' . $this->parseIniValue($sval);
            }
        }
        $res = file_put_contents($configFilePath, implode("\r\n", $res));
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


    public function createConfig($force = 0)
    {
        $currentWorkingDirectory = getcwd();
        $force = filter_var($force, FILTER_VALIDATE_BOOLEAN);

        //$path = realpath(APPLIATION_PATH . '/../tools/');


        $ormDir = $currentWorkingDirectory . '/vendor/dafik/generator';
        $schemaDir = $ormDir . '/schema';

        if (!file_exists($schemaDir)) {
            mkdir($schemaDir, 0777, true);
        }
        $this->_createProperties($schemaDir, $force);
        $this->_createRuntime($schemaDir, $force);


    }

    private function _createProperties($schemaDir, $force)
    {

        $_project = 'mylime';
        $question = "Project name ($_project): ";
        $this->project = $this->_readInput($question);
        if ('' === $this->project) {
            $this->project = $_project;
        }

        $_targetPackage = 'models';
        $question = "Package name ($_targetPackage): ";
        $targetPackage = $this->_readInput($question);
        if ('' === $targetPackage) {
            $targetPackage = $_targetPackage;
        }

        $_database = 'mysql';
        $question = "Database pgsql|mysql|sqlite|mssql|oracle ($_database): ";
        $this->database = $this->_readInput($question);
        if ('' === $this->database) {
            $this->database = $_database;
        }

        $_url = 'mysql:host=localhost;dbname=www';
        $question = "Url ($_url): ";
        $this->url = $this->_readInput($question);
        if ('' === $this->url) {
            $this->url = $_url;
        }

        $_user = 'root';
        $question = "User ($_user): ";
        $this->user = $this->_readInput($question);
        if ('' === $this->user) {
            $this->user = $_user;
        }

        $_password = 'alamakota';
        $question = "Password ($_password): ";
        $this->password = $this->_readInput($question);
        if ('' === $this->password) {
            $this->password = $_password;
        }

        $builderPath = realpath(__DIR__ . '/Generator/Propel/builder');
        $builderPathParts = explode(DIRECTORY_SEPARATOR, $builderPath);

        $options = array();
        $options[] = 'propel.project=' . $this->project;
        $options[] = 'propel.targetPackage=' . $targetPackage;
        $options[] = 'propel.database=' . $this->database;
        $options[] = 'propel.database.url=' . $this->url;
        $options[] = 'propel.database.user=' . $this->user;
        $options[] = 'propel.database.password=' . $this->password;
        $options[] = 'propel.addVendorInfo = true';

        $options[] = 'propel.builder.object.class = ' . implode('.', $builderPathParts) . '.DfiPHP5ObjectBuilder';
        $options[] = 'propel.reverse.parser.class = ' . implode('.', $builderPathParts) . '.Dfi${propel.database}SchemaParser';
        $options[] = 'propel.builder.tablemap.class = ' . implode('.', $builderPathParts) . '.DfiPHP5TableMapBuilder';


        $configPath = $schemaDir . '/build.properties';
        if (!file_exists($configPath) || $force) {
            $res = file_put_contents($configPath, implode("\r\n", $options));
            echo "\nconfiguration :\n\n" . implode("\r\n", $options) . "\n";
            echo "\nbuild.properties written to: $configPath\n";
        } else {
            echo 'build.properties exist';
        }

    }

    private function _createRuntime($schemaDir, $force)
    {
        $configPath = $schemaDir . '/runtime-conf.xml';
        $template = __DIR__ . '/templates/orm/runtime-conf.xml';

        $sxe = simplexml_load_file($template);

        $sxe->propel->datasources->attributes()->default = $this->project;
        $sxe->propel->datasources->datasource->attributes()->id = $this->project;
        $sxe->propel->datasources->datasource->adapter = $this->database;
        $sxe->propel->datasources->datasource->connection->dsn = $this->url;
        $sxe->propel->datasources->datasource->connection->user = $this->user;
        $sxe->propel->datasources->datasource->connection->password = $this->password;

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
            file_put_contents($configPath, $out);
            echo "\nconfiguration :\n\n" . $out . "\n";
            echo "\nruntime-conf.xml written to: $configPath\n";
        } else {
            echo 'runtime-conf.xml exist';
        }
    }

    /**
     *
     * @param string $filePath
     * @param string $code
     * @param bool $allowOverride
     * @return integer -1 = existing, 1 = created, 0 = other
     */
    protected function _createFile($filePath, $code, $allowOverride = false)
    {
        $baseDir = pathinfo($filePath, PATHINFO_DIRNAME);
        $relativePath = str_replace($this->_cwd . '/', '', $filePath);

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


    /**
     * Show the question and retrieve answer from user
     *
     * @param string $question
     * @return string
     */
    protected function _readInput($question)
    {
        echo $question;

        return trim(fgets(STDIN));
    }

    private function _getAppConfig($mode = false)
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

        $x = parse_ini_file($configFilePath);
        $x1 = parse_ini_file($configFilePath, true);
        $x2 = parse_ini_file($configFilePath, true, INI_SCANNER_RAW);

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

    }

}