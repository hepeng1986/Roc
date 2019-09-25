<?php

/**
 * Roc_Response_Cli
 *
 * CLI response for controllers
 *
 */
class Roc_Response_Cli extends Roc_Response_Abstract
{

    /**
     * Magic __toString functionality
     *
     * @return string
     */
    public function __toString ()
    {
        return $this->_body;
    }
}
