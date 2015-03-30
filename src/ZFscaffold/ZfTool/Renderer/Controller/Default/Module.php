<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 03.07.14
 * Time: 13:26
 */
class ZFscaffold_ZfTool_Renderer_Controller_Default_Module extends ZFscaffold_ZfTool_Renderer_Abstract
{
    public function render()
    {


        $menuConfig = array();

        /** @var $tableDefinition TableMap */
        /** @noinspection PhpWrongForeachArgumentTypeInspection */
        foreach ($this->getObject('tables') as $tableDefinition) {
            $menuConfig[] = array(
                'label' => $tableDefinition->getPhpName(),
                'module' => $this->getVariable('moduleName'),
                'controller' => strtolower($this->getObject('c2d')->filter($this->getObject('d2c')->filter($tableDefinition->getName()))),
                /*'class' => 'special-one',*/
                /*'title' => 'This element has a special class',*/
                /*'active' => true*/
            );
        }
        $menu = var_export($menuConfig, true);
        $menuParts = explode("\n", $menu);
        $menu = $this->formatLineArray($menuParts, 3);
        $this->addVariable('menu', $menu);

        if ($this->getVariables('renderAuth')) {
            $this->addVariable('helperLogin', '$this->_helper->Login;');
            $this->addVariable('user', $this->getVariable('authTable'));
        } else {
            $this->addVariable('helperLogin', '');
        }

        $this->addVariable('js', $this->prepareJS());
        $this->addVariable('css', $this->prepareCSS());

        $this->addVariable('BASE_TITLE', $this->getVariable('moduleName') . ' ' . $this->getVariable('packageName'));

        //TODO ad to config
        $this->addVariable('domainConst', '_DOMAIN');

        parent::render();
    }

    private function prepareCSS()
    {
        $css = array();
        foreach ($this->getObject('staticFiles')['css'] as $file => $path) {
            $media = 'screen';
            if (is_array($file)) {
                $tmp = $file;
                $file = $tmp[0];
                if (isset($tmp[1])) {
                    $media = $tmp[1];
                }
            }
            $css[] = '$this->view->headLink()->appendStylesheet(_CSS . \'' . $file . '\', \'' . $media . '\');';
        }

        return $this->formatLineArray($css);

    }

    private function prepareJS()
    {
        $js = array();
        foreach ($this->getObject('staticFiles')['js'] as $file => $path) {

            $js[] = '$this->view->headScript()->appendFile(_JS . \'' . $file . '\');';
        }

        return $this->formatLineArray($js);
    }

}