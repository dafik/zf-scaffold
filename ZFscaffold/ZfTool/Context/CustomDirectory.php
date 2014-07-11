<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 09.07.14
 * Time: 15:53
 */
class ZFscaffold_ZfTool_Context_CustomDirectory extends Zend_Tool_Project_Context_Filesystem_Directory
{
    /**
     * init()
     *
     * @return Zend_Tool_Project_Context_Filesystem_File
     */
    public function init()
    {
        if ($this->_resource->hasAttribute('filesystemName')) {
            $this->_filesystemName = $this->_resource->getAttribute('filesystemName');
        }
        parent::init();
        return $this;
    }

    public function getName()
    {
        return 'customDirectory';
    }

}