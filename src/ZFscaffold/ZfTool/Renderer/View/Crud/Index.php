<?php

class ZFscaffold_ZfTool_Renderer_View_Crud_Index extends ZFscaffold_ZfTool_Renderer_Abstract
{

    public function render()
    {
        /** @var $tableDefinition TableMap */
        $tableDefinition = $this->getObject('tableDefinition');

        $searchableFields = array('all' => 'All');

        $rowFields = array('<td align="center"><input class="checkbox" type="checkbox" name="del_id[]" value="<?= $model->get' . $tableDefinition->getPrimaryKeys()[0]->getPhpName() . '(); ?>" /></td>');
        $headers = array('<th><input class="checkbox" type="checkbox" onchange="toggleCheckboxes(this);" /></th>');

        /** @var $field ColumnMap */
        foreach ($tableDefinition->getColumns() as $field) {


            $columnFilters = '';
            $renderedFieldData = array('echo $model->get' . $field->getPhpName() . '();');

            if (('CHAR' === substr($field->getType(), -4) || 'TEXT' === substr($field->getType(), -4)) && 'PASSWORD' !== $field->getName()) {
                $searchableFields[$field->getName()] = $field->getPhpName();
            }
            if ('ENUM' == $field->getType() || 'SET' == $field->getType()) {
                $optionsCode = array('' => '- - Change - -');
                $options = preg_split('#\s*,\s*#', $field->getSize());
                foreach ($options as $option) {
                    $option = trim($option, "'");
                    $optionsCode[$option] = $option;
                }
                $optionsCode = preg_replace('/\n/', ' ', var_export($optionsCode, true));
                $columnFilters = '<?= $this->formSelect(\'' . $field->getName() . '\', $this->param' . $field->getName() . ', array(\'onchange\' => \'updateFilters(\'' . $field->getName() . '\', this.options[this.selectedIndex].value)\'), ' . $optionsCode . '); ?>';
            } elseif (isset($tableDefinition->getForeignKeys()[$field->getName()])) {

                /** @var $referenceData RelationMap */
                $referenceData = $tableDefinition->relationsFK[$field->getName()];
                $refTableName = $referenceData->getForeignTable()->getName();

                /** @var $refTableDefinition TableMap */
                $refTableDefinition = $this->getObject('tables')[$refTableName];

                $columnFilters = '<?= $this->formSelect(\'' . $field->getName() . '\', $this->param' . $field->getName() . ', array(\'class\'=>\'form-control\',\'onchange\' => \'updateFilters(\\\'' . $field->getName() . '\\\', this.options[this.selectedIndex].value)\'), array(\'\' => \'- - Change - -\') + Dfi_Propel_Adapter_Options::get(\'' . $refTableDefinition->getPhpName() . '\'));?>';

                if ($referenceData->getType() == RelationMap::MANY_TO_ONE) {
                    $renderedFieldData = array('$linkedRow = $model->get' . $referenceData->getName() . '();');
                } elseif ($referenceData->getType() == RelationMap::ONE_TO_MANY) {
                    $renderedFieldData = array('$linkedRow = $model->get' . $refTableDefinition->getPhpName() . 'RelatedBy' . $referenceData->getLocalColumns()[0]->getPhpname() . '();');
                } else {
                    throw new Exception('relation not nknown');
                }
                $renderedFieldData[] = 'if ($linkedRow) {';
                $renderedFieldData[] = 'echo $linkedRow->get' . $refTableDefinition->autoLabel->getPhpName() . '();';
                $renderedFieldData[] = '} else {';
                $renderedFieldData[] = 'echo $model->get' . $field->getPhpName() . '(). ($model->get' . $field->getPhpName() . '() ? \' (unlinked)\' : \'\');';
                $renderedFieldData[] = '}';

            }

            $tmp = array('<th<?= (\'' . $field->getName() . '\' == $this->sortField ? \' class="sort-field sort-\'.htmlspecialchars($this->param_so). \'"\':\'\'); ?>>');
            $tmp [] = '<a href="<?= $this->url($_GET + array(\'_sf\' => \'' . $field->getName() . '\', \'_so\' => \'asc\')); ?>"><i class=\'glyphicon glyphicon-arrow-up\'></i></a>';
            $tmp [] = $field->getPhpName();
            $tmp [] = '<a href="<?= $this->url($_GET + array(\'_sf\' => \'' . $field->getName() . '\', \'_so\' => \'desc\')); ?>"><i class=\'glyphicon glyphicon-arrow-down\'></i></a>';
            $tmp [] = $columnFilters;
            $tmp [] = '</th>';
            $headers[] = $this->formatLineArray($tmp);

            $align = ('INTEGER' == $field->getType() || 'FLOAT' == $field->getType()) && !isset($tableDefinition->getForeignKeys()[$field->getName()]) ? ' align="right"' : '';

            if ('TEXT' == $field->getType() || 'MEDIUMTEXT' == $field->getType() || 'LONGTEXT' == $field->getType() || 'TINYTEXT' == $field->getType()) {
                $rowFields[] = '<td' . $align . '><?= mb_substr($model->get' . $field->getPhpName() . '(), 0, 100), \'...\'; ?></td>';
            } else {
                $rowFields[] = '<td' . $align . '><? ' . $this->formatLineArray($renderedFieldData) . ' ?></td>';
            }
        }
        $headers[] = '<th>Actions</th>';
        $tmp = array('<td align="center"><a class="btn btn-info btn-xs" href="<?= $this->url(array_merge(' . $this->getVariable('routeParams') . ', array(\'action\' => \'update\', \'id\' => $model->get' . $tableDefinition->getPrimaryKeys()[0]->getPhpName() . '())), null, true); ?>"><i class="glyphicon glyphicon-pencil"></i> Edit</a>');
        $tmp[] = '<a class="btn btn-danger btn-xs" onclick="return confirm(\'Confirm deletion!\');" href="<?= $this->url(array_merge(' . $this->getVariable('routeParams') . ', array(\'action\' => \'delete\', \'del_id\' =>$model->get' . $tableDefinition->getPrimaryKeys()[0]->getPhpName() . '())), null, true); ?>"><i class="glyphicon glyphicon-trash"></i> Delete</a></td>';

        $rowFields[] = $this->formatLineArray($tmp, 4);

        array_unshift($headers, '<tr>');
        array_push($headers, '</tr>');
        $headers = $this->formatLineArray($headers, 3);

        array_unshift($rowFields, '<tr>');
        array_push($rowFields, '</tr>');
        $rowFields = $this->formatLineArray($rowFields, 3);


        $searchableFields = preg_replace('/\n/', ' ', var_export($searchableFields, true));


        $this->addVariable('searchableFields', $searchableFields);
        $this->addVariable('headers', $headers);
        $this->addVariable('fields', $rowFields);

        $tablePhpName = $tableDefinition->getPhpName();
        $this->addVariable('tablePhpName', $tablePhpName);


        return parent::render();

    }
}
