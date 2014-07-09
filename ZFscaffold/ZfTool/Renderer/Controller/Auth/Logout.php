<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 03.07.14
 * Time: 13:26
 */
class ZFscaffold_ZfTool_Renderer_Controller_Auth_Logout extends ZFscaffold_ZfTool_Renderer_Abstract
{

    public function render()
    {
        $this->addVariable('controllerNamePrefixLogoutController', $this->getVariable('controllerNamePrefix') . 'LogoutController');

        return parent::render();
    }
}

