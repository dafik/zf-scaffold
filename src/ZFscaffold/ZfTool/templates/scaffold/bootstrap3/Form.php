<?php

/**
 * Form definition for table {VAR_tableName.
 *
 * @package VAR_packageName
 * @author ZFscaffold
 * @version \$Id\$
 *
 */
class VAR_tableFormClassName extends EasyBib_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-horizontal');

        VAR_fields;


        // add display group
        $this->addDisplayGroup(
            VAR_fieldNames,
            'VAR_tablePhpName'
        );
        $this->getDisplayGroup('VAR_tablePhpName')->setLegend('Add VAR_tablePhpName');


        EasyBib_Form_Decorator::setFormDecorator($this, EasyBib_Form_Decorator::BOOTSTRAP, 'submit', 'cancel');

        parent::init();
    }
}