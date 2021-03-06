<?php

namespace TE\Mvc\Router;

/**
 * RouterResult  
 * 
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
class RouterResult
{
    /**
     * _action  
     * 
     * @var mixed
     * @access private
     */
    private $_action;

    /**
     * _params  
     * 
     * @var array
     * @access public
     */
    private $_params = array();

    /**
     * _interceptors
     * 
     * @var array
     * @access public
     */
    private $_interceptors = array('default');

    /**
     * __construct  
     * 
     * @param mixed $action 
     * @param array $params 
     * @param array $interceptors 
     * @access public
     * @return void
     */
    public function __construct($action, array $params = NULL, $interceptors = NULL)
    {
        $this->_action = $action;

        if (!empty($params)) {
            $this->_params = $params;
        }

        if (NULL !== $interceptors) {
            $this->_interceptors = is_array($interceptors) ? $interceptors : array($interceptors);
        }
    }

    /**
     * getAction  
     * 
     * @access public
     * @return void
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * getParams  
     * 
     * @access public
     * @return void
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * getInterceptors  
     * 
     * @access public
     * @return void
     */
    public function getInterceptors()
    {
        return $this->_interceptors;
    }
}

