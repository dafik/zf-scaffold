<?

/**
 * Class VAR_extends
 * @method  Zend_Controller_Request_Http getRequest()
 */
abstract class VAR_extends extends application_BaseController
{
    /**
     * VAR_user
     *
     * @var VAR_user
     */
    protected $user;

    /**
     * messages
     *
     * @var Zend_Controller_Action_Helper_FlashMessenger
     */
    protected $messages;

    protected $initLayout = true;
    protected $renderMenu = true;

    /**
     * View object
     * @var Zend_View
     */
    public $view;


    public function init()
    {

        parent::init();

        $this->view->getHelper('headTitle')->headTitle('VAR_BASE_TITLE');

        $this->view->getHelper('HeadMeta')
            ->appendHttpEquiv('pragma', 'no-cache')
            ->appendHttpEquiv('Cache-Control', 'no-cache')
            ->appendHttpEquiv('Content-Type', 'application/xhtml+xml; charset=UTF-8')
            ->appendHttpEquiv('Content-Language', 'pl-PL');
        //		/->appendName('author', '');

        $this->view->getHelper('Doctype')->setDoctype(Zend_View_Helper_Doctype::XHTML11);

        $this->messages = Dfi_Controller_Action_Helper_Messages::getInstance();
        Zend_Controller_Action_HelperBroker::addHelper($this->messages);

        Zend_Auth::getInstance()->setStorage(new Dfi_Auth_Storage_Cookie('VAR_user'));

        /*   $this->messages->addMessage(Dfi_Controller_Action_Helper_Messages::TYPE_DEBUG, 'test  - DEBUG');
           $this->messages->addMessage('error', 'test');
           $this->messages->addMessage('confirmation', 'test');
           $this->messages->addMessage('notice', 'test');*/

        $this->view->addHelperPath('View/Helper', 'View_Helper');
        $this->view->addBasePath(APPLICATION_PATH . 'views/partials/');


    }


    public  function initLayout()
    {
        parent::init();

        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout('adminLayout');

        VAR_css;

        VAR_js;


    }


    public function preDispatch()
    {
        parent::preDispatch();


        if ($this->_getParam('controller') != 'login' && (!$this->isUserAny())) {
            /** @var $view Zend_View */
            $view = $this->view;
            /** @var $url Zend_View_Helper_Url */
            $url = $view->getHelper('url');
            $this->_redirect($url->url(array('controller' => 'login')));
        }


    }

    public function postDispatch()
    {
        parent::postDispatch();

        if ($this->initLayout) {
            $this->initLayout();
        }

        if ($this->renderMenu) {
            $this->renderMenu();
        }

        $storage = Zend_Auth::getInstance()->getStorage();
        $storage->write(null);
    }


    protected function renderMenu()
    {
        $container = new Zend_Navigation(
            VAR_menu
        );
        Zend_Layout::getMvcInstance()->assign('menu', $container);
    }


    public  function isUserAny()
    {
        if (!$this->user && Zend_Auth::getInstance()->hasIdentity()) {
            $this->user = Zend_Auth::getInstance()->getIdentity();
        }

        if ($this->user) {
            return true;
        }
        return false;
    }
}

