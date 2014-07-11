<?php

/**
 * Form definition for table {VAR_tableName.
 *
 * @package VAR_packageName
 * @author ZFscaffold
 * @version \$Id\$
 *
 */
class VAR_tableFormClassName extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        VAR_fields;

        parent::init();
    }
}