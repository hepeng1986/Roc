<?php

/**
 * Yaf Plugin Abstract
 */
class Roc_Plugin
{

    public function dispatchLoopShutdown (Roc_Request_Abstract $request, Roc_Response_Abstract $response)
    {}

    public function dispatchLoopStartup (Roc_Request_Abstract $request, Roc_Response_Abstract $response)
    {}

    public function postDispatch (Roc_Request_Abstract $request, Roc_Response_Abstract $response)
    {}

    public function preDispatch (Roc_Request_Abstract $request, Roc_Response_Abstract $response)
    {}

    public function routerShutdown (Roc_Request_Abstract $request, Roc_Response_Abstract $response)
    {}

    public function routerStartup (Roc_Request_Abstract $request, Roc_Response_Abstract $response)
    {}
}
