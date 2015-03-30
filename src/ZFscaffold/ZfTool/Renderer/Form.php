<?php

class ZFscaffold_ZfTool_Renderer_Form extends ZFscaffold_ZfTool_Renderer_Abstract
{

    public function render()
    {
        /** @var $tableDefinition TableMap */
        $tableDefinition = $this->getObject('tableDefinition');

        //$tableName = $tableDefinition->getName();
        $tablePhpName = $tableDefinition->getPhpName();
        $this->addVariable('tablePhpName', $tablePhpName);

        $tableQueryClass = $tableDefinition->getPhpName() . 'Query';
        $this->addVariable('tableQueryClass', $tableQueryClass);

        $tablePeerClass = $tableDefinition->getPhpName() . 'Peer';
        $this->addVariable('tablePeerClass', $tablePeerClass);


        $tableFormClassName = $this->provider->_getFormClassName($tableDefinition->getPhpName());
        $this->addVariable('tableFormClassName', $tableFormClassName);


        $fields = array();
        $fieldNames = array();


        /** @var $field ColumnMap */
        foreach ($tableDefinition->getColumns() as $field) {


            $fieldNames[] = $field->getName();

            $addedCode = null;
            $fieldType = null;
            $referenceTableClass = null;
            $fieldConfigs = array();
            $validators = array();
            $filters = array();

            if ($field->isForeignKey()) {
                $referenceTable = $field->getRelatedTable();
                $fieldType = 'select';
                $baseClass = $referenceTable->getPhpName();
                $referenceTableClass = $baseClass;
            }

            if ($field->isPrimaryKey()) {
                $fieldType = 'hidden';

                $fieldsConfigs[] = '->setAttrib("class", "hidden-input")';
            } elseif ($referenceTableClass) {

                $fieldConfigs[] = "->setLabel('" . $field->getPhpName() . "')";
                $fieldConfigs[] = '->setMultiOptions(array("" => "- - Select - -") + Dfi_Propel_Adapter_Options::get(\'' . $baseClass . '\'))';


                if ($field->isNotNull()) {
                    $fieldConfigs[] = '->setRequired(true)';
                }

                $fieldsConfigs[] = '->setAttrib("class", "element-input")';
            } else {
                $fieldConfigs[] = "->setLabel('" . $field->getPhpName() . "')";

                // base on the type and type arguments, add corresponding validators and filters
                switch (strtolower($field->getType())) {
                    case 'set':
                    case 'enum':
                        /**
                         * For example, ENUM('Male', 'Female') would be converted to
                         *
                         * ->setMultiOptions(array("Male" => "Male", "Female" => "Female"))
                         */
                        $numericOptions = eval("return array($field[type_arguments]);");
                        $assocOptions = array();
                        foreach ($numericOptions as $option) {
                            $option = str_replace("'", "\'", $option);
                            $assocOptions[] = "'$option' => '$option'";
                        }
                        $array = 'array(' . implode(',', $assocOptions) . ')';
                        $fieldType = 'radio';
                        $fieldConfigs[] = '->setMultiOptions(' . $array . ')';
                        $validators[] = "new Zend_Validate_InArray(array('haystack' => $array))";
                        $fieldConfigs[] = '->setSeparator(" ")';
                        break;
                    case 'tinytext':
                    case 'mediumtext':
                    case 'text':
                    case 'longtext':
                        $fieldType = 'textarea';
                        $filters[] = 'new Zend_Filter_StringTrim()';
                        break;
                    case 'tinyint':
                    case 'mediumint':
                    case 'int':
                    case 'integer':
                    case 'year':
                        $fieldType = 'text';
                        $filters[] = 'new Zend_Filter_StringTrim()';
                        $validators[] = 'new Zend_Validate_Int()';
                        break;
                    case 'decimal':
                    case 'float':
                    case 'double':
                    case 'bigint':
                        $fieldType = 'text';
                        $filters[] = 'new Zend_Filter_StringTrim()';
                        $validators[] = 'new Zend_Validate_Float()';
                        break;
                    case 'varchar':
                    case 'char':
                        $validators[] = 'new Zend_Validate_StringLength(array("max" => ' . $field->getSize() . '))';
                        $fieldType = 'password' == $field->getType() ? 'password' : 'text';
                        $filters[] = 'new Zend_Filter_StringTrim()';
                        $fieldConfigs[] = '->setAttrib("maxlength", ' . $field->getSize() . ')';

                        if ('email' === strtolower($field->getName()) || 'emailaddress' === strtolower($field->getName())) {
                            $validators[] = 'new Zend_Validate_EmailAddress()';
                        }
                        break;
                    case 'bit':
                    case 'date':
                    case 'datetime':
                    case 'time':
                    case 'timestamp':
                    default:
                        $fieldType = 'text';
                        $filters[] = 'new Zend_Filter_StringTrim()';

                        if ('datetime' == $field->getType() || 'timestamp' == $field->getType()) {
                            $fieldConfigs[] = '->setValue(date("Y-m-d H:i:s"))';
                        } elseif ('date' == $field->getType()) {
                            $fieldConfigs[] = '->setValue(date("Y-m-d"))';
                        } elseif ('time' == $field->getType()) {
                            $fieldConfigs[] = '->setValue(date("H:i:s"))';
                        }
                        break;
                }

                if ($field->isNotNull()) {
                    $fieldConfigs[] = '->setRequired(true)';
                }

                $fieldsConfigs[] = '->setAttrib("class", "element-input")';
            }

            if ($field->getDefaultValue()) {
                $fieldConfigs[] = '->setValue("' . str_replace('"', '\"', $field->getDefaultValue()) . '")';
            }

            foreach ($validators as $validator) {
                $fieldConfigs[] = '->addValidator(' . $validator . ')';
            }

            foreach ($filters as $filter) {
                $fieldConfigs[] = '->addFilter(' . $filter . ')';
            }

            $fieldConfigs = $this->formatLineArray($fieldConfigs);

            $fieldCode = array(
                '$this->addElement(',
                '$this->createElement(\'' . $fieldType . '\', \'' . $field->getName() . '\')',
                $fieldConfigs,
                ');'
            );

            if ($addedCode) {
                $fieldCode = array_unshift($fieldCode, $addedCode);
            }

            $fields[] = $this->formatLineArray($fieldCode);
        }

        $buttonDecorators = '';

        $fields[] = $this->formatLineArray(
            array(
                '$this->addElement(',
                '$this->createElement(\'button\', \'submit\')',
                '->setLabel(\'Save\')',
                '->setAttrib(\'type\', \'submit\')' . $buttonDecorators,
                ');'
            )
        );
        $fields[] = $this->formatLineArray(
            array(
                '$this->addElement(',
                '$this->createElement(\'button\', \'cancel\')',
                '->setLabel(\'Cancel\')',
                '->setAttrib(\'type\', \'submit\')' . $buttonDecorators,
                ');'
            )
        );

        $fields = implode("\n\n", $fields);
        $fieldNames[] = 'submit';
        $fieldNames[] = 'cancel';


        $this->addVariable('fields', $fields);
        $this->addVariable('fieldNames', preg_replace('/\n/', '', var_export($fieldNames, true)));


        parent::render();
    }
}