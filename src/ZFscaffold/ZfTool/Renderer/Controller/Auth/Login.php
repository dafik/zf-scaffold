<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 03.07.14
 * Time: 13:26
 */
class ZFscaffold_ZfTool_Renderer_Controller_Auth_Login extends ZFscaffold_ZfTool_Renderer_Abstract
{

    public function render()
    {
        $this->addVariable('controllerNamePrefixLoginController', $this->getVariable('controllerNamePrefix') . 'LoginController');

        return parent::render();
    }
}
