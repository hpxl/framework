<?php

namespace TE\Helper;

/**
 * Fetcher  
 * 
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com> 
 * @license GNU General Public License 2.0
 */
class Fetcher
{
    /**
     * _url  
     * 
     * @var mixed
     * @access private
     */
    private $_url;

    /**
     * _data  
     * 
     * @var mixed
     * @access private
     */
    private $_data;

    /**
     * _timeout  
     * 
     * @var mixed
     * @access private
     */
    private $_timeout = 10;

    /**
     * _agent  
     * 
     * @var string
     * @access private
     */
    private $_agent = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';

    /**
     * _maxSize  
     * 
     * @var float
     * @access private
     */
    private $_maxSize = 0;

    /**
     * _contentType 
     * 
     * @var array
     * @access private
     */
    private $_contentType = array();

    /**
     * _headers  
     * 
     * @var array
     * @access private
     */
    private $_headers = array();

    /**
     * _responseStatusCode
     * 
     * @var float
     * @access private
     */
    private $_responseStatusCode = 0;

    /**
     * _responseContentLength  
     * 
     * @var float
     * @access private
     */
    private $_responseContentLength = 0;

    /**
     * _responseBody  
     * 
     * @var string
     * @access private
     */
    private $_responseBody = '';

    /**
     * _responseContentType  
     * 
     * @var mixed
     * @access private
     */
    private $_responseContentType;

    /**
     * __construct  
     * 
     * @param mixed $url 
     * @param array $data 
     * @access public
     * @return void
     */
    public function __construct($url)
    {
        $this->_url = $url;
    }

    /**
     * buildUrl  
     * 
     * @param array $params 
     * @access private
     * @return void
     */
    private function buildUrl(array $params)
    {
        return (isset($params['scheme']) ? $params['scheme'] . '://' : null)
            . (isset($params['user']) ? $params['user'] . (isset($params['pass']) ? ':' . $params['pass'] : null) . '@' : null)
            . (isset($params['host']) ? $params['host'] : null)
            . (isset($params['port']) ? ':' . $params['port'] : null)
            . (isset($params['path']) ? $params['path'] : null)
            . (isset($params['query']) ? '?' . $params['query'] : null)
            . (isset($params['fragment']) ? '#' . $params['fragment'] : null);
    }

    /**
     * request  
     * 
     * @param mixed $url 
     * @param array $data 
     * @access private
     * @return void
     */
    private function request($url, array $data = NULL)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);

        // disable ssl check
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (!empty($this->_headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
        }

        $redirect = false;
        $type = '';
        $options = array(
            'max_size'  =>  $this->_maxSize,
            'mime_type' =>  $this->_contentType
        );

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $str) use ($options, &$redirect, &$type) {
            $line = trim($str);

            if (preg_match("/^HTTP\/1\.[0-9]\s+([0-9]{3})\s+.*/i", $line, $matches)) {
                $status = intval($matches[1]);

                if (200 == $status) {
                    $redirect = false;
                } else if (301 == $status || 302 == $status) {
                    $redirect = true;
                } else {
                    throw new \Exception('Error http status', 5001);
                }
            } else if (!empty($line) && !$redirect) {
                list ($key, $value) = array_map('trim', explode(':', $line));
                $key = strtolower($key);

                switch ($key) {
                    case 'content-length':
                        if (!empty($options['max_size']) && intval($value) > $options['max_size']) {
                            throw new \Exception('Error http size', 5002);
                        }
                        break;
                    case 'content-type':
                        list ($type) = explode(';', $value, 2);
                        $type = strtolower(trim($type));

                        if (!empty($options['mime_type'])) {
                            if (!in_array($type, $options['mime_type'])) {
                                throw new \Exception('Error http type', 5003);
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
            
            return strlen($str);
        });

        $size = 0;
        $content = '';

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $str) use ($options, &$size, &$content) {
            $length = strlen($str);
            $size += $length;
            $content .= $str;

            if (!empty($options['max_size']) && $size > $options['max_size']) {
                throw new \Exception('Error http size', 5002);
            }

            return $length;
        });

        $result = curl_exec($ch);

        if (false === $result) {
            curl_close($ch);
            throw new \Exception('Curl error', 5004);
        }

        $this->_responseStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->_responseBody = $content;
        $this->_responseContentType = $type;
        $this->_responseContentLength = $size;
        curl_close($ch);
    }

    /**
     * setTimeout  
     * 
     * @param mixed $timeout 
     * @access public
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;
        return $this;
    }

    /**
     * setMaxSize  
     * 
     * @param mixed $maxSize 
     * @access public
     * @return void
     */
    public function setMaxSize($maxSize)
    {
        $this->_maxSize = $maxSize;
        return $this;
    }

    /**
     * setContentType  
     * 
     * @param mixed $contentType 
     * @access public
     * @return void
     */
    public function setContentType($contentType)
    {
        if (is_array($contentType)) {
            $this->_contentType = array_merge($this->_contentType, $contentType);
        } else {
            $this->_contentType[] = $contentType;
        }

        return $this;
    }

    /**
     * setAgent  
     * 
     * @param mixed $agent 
     * @access public
     * @return void
     */
    public function setAgent($agent)
    {
        $this->_agent = $agent;
        return $this;
    }

    /**
     * setHeader  
     * 
     * @param mixed $name 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function setHeader($name, $value)
    {
        $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
        $this->_headers[] = $name . ': ' . $value;
        return $this;
    }

    /**
     * getResponseStatusCode  
     * 
     * @access public
     * @return void
     */
    public function getResponseStatusCode()
    {
        return $this->_responseStatusCode;
    }

    /**
     * getResponseContentType  
     * 
     * @access public
     * @return void
     */
    public function getResponseContentType()
    {
        return $this->_responseContentType;
    }

    /**
     * getResponseContentLength  
     * 
     * @access public
     * @return void
     */
    public function getResponseContentLength()
    {
        return $this->_responseContentLength;
    }

    /**
     * getResponseBody  
     * 
     * @access public
     * @return void
     */
    public function getResponseBody()
    {
        return $this->_responseBody;
    }

    /**
     * get  
     * 
     * @param mixed $data 
     * @access public
     * @return void
     */
    public function get($data)
    {
        if (is_string($data)) {
            parse_str($data, $out);
            $data = $out;
        }

        $params = parse_url($this->_url);

        if (!empty($params['query'])) {
            parse_str($params['query'], $out);
            $params['query'] = http_build_query(array_merge($out, $data));
        }

        $url = $this->buildUrl($params);
        $this->request($url);
    }

    /**
     * post  
     * 
     * @param mixed $data 
     * @access public
     * @return void
     */
    public function post($data)
    {
        if (is_string($data)) {
            parse_str($data, $out);
            $data = $out;
        }

        $this->request($this->_url, $data);
    }
}

