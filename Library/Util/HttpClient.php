<?php

class Util_HttpClient
{

    private $ch;

    public $referer;

    public $content;

    public $cookie_jar = '';

    public $charset = 'utf-8';
    
    //public $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.0.16';

    public $user_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_1_3 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10B329 Safari/8536.25';
    
    public function __construct ($cookie = null, $charset = 'utf-8')
    {
        $this->charset = $charset;
        $this->ch = curl_init();
        $this->cookie_jar = Util_Tools::getSysTempDir() . '/cookie.txt';
        if (file_exists($this->cookie_jar)) {
            unlink($this->cookie_jar);
        }
    }

    public function setUrl ($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
    }

    public function setRetHeader ($state = false)
    {
        curl_setopt($this->ch, CURLOPT_HEADER, $state);
    }

    public function setNoBody ($state = false)
    {
        curl_setopt($this->ch, CURLOPT_NOBODY, $state);
    }

    public function setFollow ($state = true)
    {
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $state);
    }

    public function setTimeout ($seconds = 180)
    {
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $seconds);
    }

    public function setReferer ($referer = '')
    {
        if ($referer) {
            curl_setopt($this->ch, CURLOPT_REFERER, $referer);
        } elseif ($this->referer) {
            curl_setopt($this->ch, CURLOPT_REFERER, $this->referer);
        }
    }
    
    public function setProxy($proxy, $type = CURLPROXY_HTTP) 
    {
        curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
        curl_setopt($this->ch, CURLOPT_PROXYTYPE, $type);
    }

    public function setCookie ($cookie)
    {
    	curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
    }
    
    public function setHttpHeader ($header)
    {
    	curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
    }

    public function setReturn ($state = true)
    {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, $state);
    }

    public function exec ($tran = true, $method = 'get', $data = array())
    {
        $this->setFollow(true);
        $this->setReturn(true);
        $this->setTimeout(300);
        curl_setopt($this->ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        //curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookie_jar);
        //curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 300);
        if ($method == 'post') {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $this->content = curl_exec($this->ch);
        if (curl_errno($this->ch)) {
            echo 'Curl error: ' . curl_error($this->ch);
        }
        if ($this->charset != 'utf-8' && $tran == true) {
            $this->content = mb_convert_encoding($this->content, "utf-8", $this->charset);
        }
        return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }

    public function get ($url, $tran = true)
    {
        $this->setUrl($url);
        $this->setRetHeader(false);
        $this->setNoBody(false);
        $this->setReferer();
        $code = $this->exec($tran);
        $this->referer = $url;
        return $code;
    }

    public function getInfo ($url)
    {
        $this->setUrl($url);
        $this->setNoBody(true);
        $this->setRetHeader(true);
        $this->setReferer();
        return curl_getinfo($this->ch);
    }

    public function post ($url, $data)
    {
        $this->setUrl($url);
        $this->setRetHeader(false);
        $this->setNoBody(false);
        $this->setReferer($url);
        $code = $this->exec(true, 'post', $data);
        $this->referer = $url;
        return $code;
    }

    public function __destruct ()
    {
        curl_close($this->ch);
    }
}