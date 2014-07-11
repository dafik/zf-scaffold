<?php

// PHP < 5.3 compatibility
!defined('__DIR__') || define('__DIR__', dirname(__FILE__));

require_once __DIR__ . '/../../ZFscaffold/ZfTool/Exception.php';

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
class ZFscaffold_ZfTool_ZFProjectProvider extends Zend_Tool_Framework_Provider_Abstract
{
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

    }

    public static function bootstrapFile()
    {

    }

    public static function publicIndexFile()
    {

    }


}