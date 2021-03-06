<?php

/**
 * Controller for table VAR_tableName
 *
 * @package VAR_packageName
 * @author Scaffold
 * @version $Id$
 *
 */
/** @noinspection PhpIncludeInspection */
require_once 'VAR_formInclude';

class VAR_controllerName extends VAR_extends
{
    public function indexAction()
    {
        $this->getFrontController()->getRequest()->setParams($_GET);
        $params = $this->getAllParams();
        unset($params['module'], $params['controller'], $params['action'], $params['page'], $params['_sf'], $params['_so']);

        $sortField = $this->_getParam('_sf', '');
        $sortOrder = $this->_getParam('_so', '');
        $pageNumber = $this->_getParam('page', 1);

        $qry = VAR_tableQueryClass::create();
        if ($sortField) {
            $qry->orderBy($sortField, $sortOrder);
        }
        if (count($params) > 0) {
            foreach ($params as $param => $value) {
                $name = VAR_tablePeerClass::translateFieldName($param, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME);
                $qry->filterBy($name, $value);
            }
        }
        $pager = $qry->paginate($pageNumber, 20);

        $paginator = new Zend_Paginator(new Dfi\Paginator\Adapter\PropelPager($pager));
        $paginator
            ->setItemCountPerPage(20)
            ->setCurrentPageNumber($pageNumber);

        $this->view->assign(array(
            'paginator' => $paginator,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder,
            'pageNumber' => $pageNumber,
            'pager' => $pager
        ));

        foreach ($this->getAllParams() as $paramName => $paramValue) {
            // prepend 'param' to avoid error of setting private/protected members
            $this->view->assign('param' . $paramName, $paramValue);
        }
    }

    public function createAction()
    {
        $form = new VAR_tableFormClassName();

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $values = $form->getValues();

                $model = new VAR_tablePhpName();
                Dfi\Propel\Adapter\ModelValues::setByArray($model, $values);
                $model->save();

                $this->_helper->redirector('index');
                exit;
            } else {
                $form->buildBootstrapErrorDecorators();
            }
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $this->view->form = $form;
    }

    public function updateAction()
    {
        $form = new VAR_tableFormClassName();
        $id = (int)$this->_getParam('id', 0);

        /** @var $model VAR_tablePhpName */
        $model = VAR_tableQueryClass::create()->findOneById($id);

        if (!$model) {
            $this->_helper->redirector('index');
            exit;
        }

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $values = $form->getValues();

                Dfi\Propel\Adapter\ModelValues::setByArray($model, $values);
                $model->save();

                $this->_helper->redirector('index');
                exit;
            } else {
                $form->buildBootstrapErrorDecorators();
            }
        } else {

            $form->populate($model->toArray(BasePeer::TYPE_FIELDNAME));
        }

        $this->view->form = $form;
    }

    public function deleteAction()
    {
        $ids = $this->_getParam('del_id', array());

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (!empty($ids)) {
            VAR_tableQueryClass::create()
                ->filterById($ids)
                ->delete();
        }

        $this->_helper->redirector('index');
        exit;
    }
}