<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 03.07.14
 * Time: 13:26
 */
class ZFscaffold_ZfTool_Renderer_Controller_Standard extends ZFscaffold_ZfTool_Renderer_Abstract
{
    public function render()
    {
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

        $controllerName = $this->getVariable('controllerNamePrefix') . $this->getVariable('baseName') . 'Controller';
        $this->addVariable('controllerName', $controllerName);

        $formInclude = 'modules/'.$this->getVariable('moduleName').'/forms/Edit'.$tableDefinition->getPhpName().'.php';
        $this->addVariable('formInclude', $formInclude);

        //{$this->_controllerNamePrefix}
        //{$baseName}


        //$this->addVariable('VAR_searchableFields', $searchableFields);
        //$this->addVariable('VAR_headers', $headers);
        //$this->addVariable('VAR_fields', $rowFields);


        return parent::render();
    }

}
