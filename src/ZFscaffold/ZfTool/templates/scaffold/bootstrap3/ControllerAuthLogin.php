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

            $adapter = new Dfi_Auth_Adapter_Db($values['login'], Dfi_App_Config::getString('cake.salt') . $values['password'], array(
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
                $this->_redirect($this->view->url(array('controller' => 'index')));
            }
            $this->messages->addMessage('debug', implode(';', $result->getMessages()));
            $this->messages->addMessage('error', 'login');
            $this->view->assign('form', $form);
        }
    }


    private function getFormLogin()
    {
        $form = new Zend_Form (array('disableLoadDefaultDecorators' => true));

        $email = new Zend_Form_Element_Text('login', array('disableLoadDefaultDecorators' => true));
        $email->addDecorator('ViewHelper');
        $email->addDecorator('Errors');

        $email->setRequired(true);
        $email->setAttrib('class', 'form-control');
        $email->setAttrib('placeholder', 'Login');
        $email->setAttrib('required', 'required');
        $email->setAttrib('autofocus', 'autofocus');


        $password = new Zend_Form_Element_Password('password', array('disableLoadDefaultDecorators' => true));
        $password->addDecorator('ViewHelper');
        $password->addDecorator('Errors');
        $password->setRequired(true);

        $password->setAttrib('class', 'form-control');
        $password->setAttrib('placeholder', 'Hasło');
        $password->setAttrib('required', 'required');
        $password->setAttrib('autofocus', 'autofocus');


        $submit = new Zend_Form_Element_Submit('submit', array('disableLoadDefaultDecorators' => true));
        $submit->setAttrib('class', 'btn btn-lg btn-primary btn-block');
        $submit->setOptions(array('label' => 'Zaloguj'));
        $submit->addDecorator('ViewHelper')
            ->addDecorator('Errors');

        $form
            ->addElement($email)
            ->addElement($password)
            ->addElement($submit);

        return $form;
    }

}
