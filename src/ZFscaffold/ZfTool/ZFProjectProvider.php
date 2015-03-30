<?php

// PHP < 5.3 compatibility
!defined('__DIR__') || define('__DIR__', dirname(__FILE__));

require_once __DIR__ . '/../../ZFscaffold/ZfTool/Exception.php';


class ZFscaffold_ZfTool_ZFProjectProvider extends Zend_Tool_Framework_Provider_Abstract
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
    public function generate()
    {
        /** @var $projectProvider Zend_Tool_Project_Provider_Project */
        $projectProvider = $this->_registry->getProviderRepository()->getProvider('project');

        $contextRegistry = Zend_Tool_Project_Context_Repository::getInstance();
        $contextRegistry->addContextsFromDirectory(__DIR__ . '/Context', 'ZFscaffold_ZfTool_Context_');

        $file = __DIR__ . '/templates/project/profile.xml';

        $projectProvider->create(null, 'default', $file);

        $file = substr(__DIR__, 0, strpos(__DIR__, 'vendor')) . '/logs';

        chmod($file, 0777);

    }

    public static function bootstrapFile()
    {

    }

    public static function publicIndexFile()
    {

    }


}