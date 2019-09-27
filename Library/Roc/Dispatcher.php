<?php

/**
 * Yaf Dispatcher
 */
class Roc_Dispatcher
{

    /**
     * Singleton instance
     *
     * @var Roc_Dispatcher
     */
    protected static $_instance = null;

    /**
     * Instance of Roc_Router_Interface
     *
     * @var Roc_Router
     */
    protected $_router = null;

    /**
     * View object
     *
     * @var Roc_View_Interface
     */
    protected $_view = null;

    /**
     * Instance of Roc_Request_Abstract
     *
     * @var Roc_Request_Abstract
     */
    protected $_request = null;
    protected $_response = null;

    /**
     * Whether or not to enable view.
     *
     * @var boolean
     */
    protected $_auto_render = true;

    /**
     * Whether or not to return the response prior to rendering output while in
     * {@link dispatch()}; default is to send headers and render output.
     *
     * @var boolean
     */
    protected $_returnResponse = false;

    /**
     * Constructor
     *
     * Instantiate using {@link getInstance()}; dispatcher is a singleton
     * object.
     *
     * @return void
     */
    protected function __construct ()
    {}

    /**
     * Singleton instance
     *
     * @return Roc_Dispatcher
     */
    public static function getInstance ()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->_router = new Roc_Router();
        }

        return self::$_instance;
    }

    /**
     * Dispatch an HTTP request to a controller/action.
     *
     * @param Roc_Request_Abstract|null $request
     *
     * @return void Roc_Response_Abstract
     */
    public function dispatch ()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        // 选择路由
        $router = $this->getRouter();
        $router->route($request);
        $this->_fixDefault($request);


        // 执行Action
        try {
            $view = $this->initView();
            $this->handle($request, $response, $view);
            $this->_fixDefault($request);

        } catch (Exception $oExp) {
            if (Roc_G::isDebug() || $request->getMethod() == 'CLI') {
                if ($request->getMethod() == 'CLI') {
                    Roc_Logger::error(Roc_G::parseException($oExp));
                    echo Roc_G::parseException($oExp);
                } else {
                    echo "<pre>";
                    echo Roc_G::parseException($oExp);
                    echo "</pre>";
                }
            } else {
                Roc_Logger::error(Roc_G::parseException($oExp));
                $response->setResponseCode(404);
                $view->display('404.phtml');
            }
        }

        if ($this->returnResponse() == false) {
            $response->response();
        }

        return $response;
    }

    /**
     * 设置是否自动render
     *
     * @param unknown $flag
     */
    public function autoRender ($flag = null)
    {
        if (is_null($flag)) {
            return $this->_auto_render;
        } else {
            $this->_auto_render = $flag;
        }
    }

    /**
     * Return the request object.
     *
     * @return null Roc_Request_Abstract
     */
    public function getRequest ()
    {
        return $this->_request;
    }
    public function getResponse ()
    {
        return $this->_response;
    }
    /**
     * Set the request object.
     *
     * @param Roc_Request_Abstract $request
     *
     * @return Roc_Dispatcher
     */
    public function setRequest (Roc_Request_Abstract $request)
    {
        $this->_request = $request;

        return $this;
    }
    public function setResponse (Roc_Response_Abstract $reponse)
    {
        $this->_response = $reponse;

        return $this;
    }

    /**
     * Return the router object.
     *
     * @return Roc_Router
     */
    public function getRouter ()
    {
        return $this->_router;
    }

    /**
     * 初始化View
     *
     * @param string $templates_dir
     * @param unknown $options
     * @return Roc_View_Interface
     */
    public function initView ($options = array())
    {
        if ($this->_view == null) {
            $this->_view = new Roc_View_Simple($options);
        }

        return $this->_view;
    }

    /**
     * Set whether {@link dispatch()} should return the response without first
     * rendering output.
     * By default, output is rendered and dispatch() returns
     * nothing.
     *
     * @param boolean $flag
     *
     * @return boolean Roc_Dispatcher as a setter,
     *         returns object; as a getter, returns boolean
     */
    public function returnResponse ($flag = null)
    {
        if (true === $flag) {
            $this->_returnResponse = true;

            return $this;
        } elseif (false === $flag) {
            $this->_returnResponse = false;

            return $this;
        }

        return $this->_returnResponse;
    }

    public function setErrorHandler ($callback, $error_types = E_ALL)
    {
        set_error_handler($callback, $error_types);
    }

    /**
     * Set the view object.
     *
     * @param Roc_View_Interface $view
     *
     * @return Roc_Dispatcher
     */
    public function setView (Roc_View_Interface $view)
    {
        $this->_view = $view;

        return $this;
    }

    private function handle (Roc_Request_Abstract $request, Roc_Response_Abstract $response, Roc_View_Interface $view)
    {
        $request->setDispatched(true);
        $module = $request->getModuleName();
        if (empty($module)) {
            throw new Roc_Exception('Unexcepted an empty module name');
            return false;
        }
        $controllerName = $request->getControllerName();
        if (empty($controllerName)) {
            throw new Roc_Exception('Unexcepted an empty controller name');
            return false;
        }
        $className = $this->getController($module, $controllerName);
        if (! $className) {
            return false;
        }
        $controller = new $className($request, $response, $view);
        if (! ($controller instanceof Roc_Controller)) {
            throw new Roc_Exception('Controller must be an instance of Roc_Controller');
            return false;
        }
        $action = $request->getActionName();
        $actionMethod = $action . 'Action';
        
        try {
            $ret = call_user_func(array(
                $controller,
                'actionController'
            ));
            
            if ($ret !== false && class_exists($ret)) {
                $controller = new $ret($request, $response, $view);
            }

            $ret = call_user_func(array(
                $controller,
                'actionBefore'
            ));
            if ($ret === false) {
                return false;
            }

            $ret = call_user_func(array(
                $controller,
                $actionMethod
            ));
            if ($ret === false) {
                return false;
            }

            $ret = call_user_func(array(
                $controller,
                'actionAfter'
            ));
        } catch (Exception $oExp) {
            if ($oExp->getCode() != 40004) {
                throw $oExp;
            }
        }

        if ($this->_auto_render == true) {
            $response->setBody($controller->render($action));
        }
        $controller = null;
    }

    private function getController ($module, $controller)
    {
        $classname = $module . '_' . Roc_G::$Roc_CONTROLLER_DIRECTORY_NAME . '_' . $controller;
        return $classname;
    }

    private function _fixDefault (Roc_Request_Abstract $request)
    {
        $module = $request->getModuleName();
        if (empty($module) || ! is_string($module)) {
            $request->setModuleName(Roc_G::Roc_ROUTER_DEFAULT_MODULE);
        } else {
            $request->setModuleName($module);
        }
        $controller = $request->getControllerName();
        if (empty($controller) || ! is_string($controller)) {
            $request->setControllerName(Roc_G::Roc_ROUTER_DEFAULT_CONTROLLER);
        } else {
            $request->setControllerName($controller);
        }
        $action = $request->getActionName();
        if (empty($action) || ! is_string($action)) {
            $request->setActionName(Roc_G::Roc_ROUTER_DEFAULT_ACTION);
        } else {
            $request->setActionName($action);
        }
    }
}
