<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 03.07.14
 * Time: 13:26
 *
 *
 *
 */
class VAR_controllerNamePrefixLoginController extends VAR_extends
{


    /**
     * @return $this
     */
    public function init()
    {
        parent::init();
        $this->renderMenu = false;
        return $this;
    }

    public function returnAction()
    {
        //$url = $this->_getParam('url');

        $this->forward('index');
    }

    public function indexAction()
    {


        if ($this->isUserAny()) {
            $this->messages->addMessage('debug', 'jestes juz zalogowany');
            //$this->messages->addMessage('notice', 'logged');
            $this->_redirect('/admin');
        }

        $form = $this->getFormLogin();

        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            $this->view->assign('form', $form);
        } else {
            $values = $form->getValues();

            $adapter = new Dfi_Auth_Adapter_Db($values['login'], $values['password'], array(
                    'table' => 'VAR_authTable',
                    'loginField' => 'VAR_authLoginField',
                    'passwordField' => 'VAR_authPasswordField',
                    'activityField' => 'VAR_authActivityField',
                    'passwordHash' => 'VAR_authPasswordHash',
                )
            );

            $result = Zend_Auth::getInstance()->authenticate($adapter);

            if (Zend_Auth::getInstance()->hasIdentity()) {
                $url = $this->_getParam('url', false);
                if ($url) {
                    $this->_redirect($url);
                }
                $this->_redirect('/');
            }
            $this->messages->addMessage('debug', implode(';', $result->getMessages()));
            $this->messages->addMessage('error', 'login');
            $this->view->assign('form', $form);
        }
    }


    private function getFormLogin()
    {
        $form = new Zend_Form ();

        $email = new Zend_Form_Element_text('login');
        $email->setOptions(array('label' => 'Login'))->setRequired(true);

        $password = new Zend_Form_Element_password('password');
        $password->setOptions(array('label' => 'Hasło'))->setRequired(true);
        $filter = new Zend_Filter_StripTags ();
        $password->addFilters(array($filter));

        $submit = new Zend_Form_Element_submit('submit');
        $submit->setOptions(array('label' => 'Dalej'));

        $form->addElement($email)->addElement($password)->addElement($submit);

        return $form;
    }

}
