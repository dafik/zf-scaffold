<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 03.07.14
 * Time: 13:26
 */
class VAR_controllerNamePrefixLogoutController extends VAR_extends
{

    function indexAction()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {

            Zend_Auth::getInstance()->clearIdentity();

            $this->messages->addMessage('debug', 'zostałeś wylogowany');
            //$this->messages->addMessage('confirmation', 'logout');

        } else {
            $this->messages->addMessage('debug', 'musisz byc zalogowany zeby sie wylogować');
            //$this->messages->addMessage('notice', 'logged2logout');
        }
        $this->_redirect('/admin');

    }
}
