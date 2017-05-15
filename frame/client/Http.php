<?php

namespace frame\client;


class Http extends Base
{

    public $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
    public $acceptLanguage = 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2';
    public $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36';
    public $acceptEncoding = 'gzip,deflate,sdch';

    public $requestHeaders = array();
    public $rspHeaders = array();

    public $persistReferers = false; //
    public $handleRedirects = false;
    public $redirectCount = 0;
    public $maxRedirects = 5;
    public $persistCookies = false; //cookie

    public $username;
    public $password;
    public $calltime;
    public $callback;
    public $timeout;
    public $postdata;

    public $cookies = array();
    public $request = '';
    public $method;
    public $useGzip = false;
    public $referer;
    protected $querystring='';

    public $contents = '';
    public $host;
    public $port;
    public $path;
    public $key;

    public $proxyHost;
    public $proxyPort;
    public $proxy = false;


    private $firstRsp = true;

    private $curChunkLen = 0;

    private $headerBuf = '';
    
    private $client;
    
    private $header;

    /**
     * [__construct 构造函数]
     * @param [type] $referer [description]
     */
    public function __construct($uri)
    {

        $this->referer = $uri;


        if (empty($uri)) {
            return;
        }
        $info = parse_url($uri);

        //port
        $this->port = isset($info['port']) ? $info['port'] : 80;
        // scheme
        if (!isset($info['scheme'])) {

            \frame\log\Log::error(__METHOD__ . " miss scheme ", __CLASS__);
            return false;
        }
        if ('https' === $info['scheme']) {
            $this->port = 443;
        }
        //host
        if (!isset($info['host'])) {

            \frame\log\Log::error(__METHOD__ . " miss host ", __CLASS__);
            return false;
        }

        $this->path = $info['path'];
        $this->host = $info['host'];
        $this->querystring= $info['query'];
        $this->key = md5($uri . microtime(true) . rand(0, 10000));
        
    }

    /**
     * [get get方法]
     * @param  [type] $path    [description]
     * @param  array $data [description]
     * @param  array $headers [description]
     * @return [type]          [description]
     */
    public function get( $data = array(), $headers = array())
    {
        $this->method = 'GET';
        //拼接请求数据
        if (!empty($data)) {  //todo 这边有bug 需要判断是否有问号
            $this->path .=    (strpos($this->path,'?')?'':'?'). http_build_query($data);
        }

        //设置请求headers信息
        if (!empty($headers)) {
            $this ->requestHeaders = array_merge($this ->requestHeaders, $headers);
        }

        $this->buildRequest();
        //	\frame\log\Log::debug(__METHOD__ . " httpclient == " .print_r($this, true), __CLASS__);
        return $this->send();
    }

    /**
     * [post post方法]
     * @param  [type] $path    [description]
     * @param  [type] $data    [description]
     * @param  [type] $headers [description]
     * @return [type]          [description]
     */
    public function post($data, $headers)
    {

        $this->method = 'POST';

        if (!empty($headers)) {

            $this->setRequestHeaders($headers);
        }
        $this->postdata = $data;
        $this->buildRequest();

        //\frame\log\Log::debug(__METHOD__ . " httpclient == " . print_r($this, true), __CLASS__);
        return $this->send();
    }
    
    public function send()
    {
        if($this->port == '443') 
            $cli = new Swoole\Coroutine\Http\Client($this->host, $this->port, true);
        else 
            $cli = new Swoole\Coroutine\Http\Client($this->host, $this->port);
        
        $cli->setHeaders($this->header);
        $cli->set([ 'timeout' => $this->timeout]);
        
        if($this->method == 'GET') 
            $cli->get($this->querystring);            
        else 
            $cli->post($this->querystring, $this->postdata);
        
        $res = $cli->body;
        $cli->close();
        
        return $res;
    }

    /**
     * [useGzip 是否压缩]
     * @param  [type] $boolean [description]
     * @return [type]          [description]
     */
    public function useGzip($boolean)
    {

        $this->useGzip = $boolean;
    }

    /**
     * [setUserAgent 设置代理]
     * @param [type] $string [description]
     */
    public function setUserAgent($string)
    {

        $this->user_agent = $string;
    }

    public function setProxy($host,$port){
        $this->proxy = true;
        $this->proxyHost = $host;
        $this->proxyPort = $port;
    }


    /**
     * [setAuthorization 设置权限]
     * @param [type] $username [description]
     * @param [type] $password [description]
     */
    public function setAuthorization($username, $password)
    {

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * [getCookies 获取cookies]
     * @param  [type] $host [description]
     * @return [type]       [description]
     */
    public function getCookies($host = null)
    {

        if (isset($this->cookies[isset($host) ? $host : $this->host])) {

            return $this->cookies[isset($host) ? $host : $this->host];
        }
        return array();
    }

    /**
     * [setCookies 设置cookies]
     * @param [type]  $array   [description]
     * @param boolean $replace [description]
     */
    public function setCookies($array, $replace = false)
    {

        if ($replace || (!isset($this->cookies[$this->host])) || (!is_array($this->cookies[$this->host]))) {

            $this->cookies[$this->host] = array();
        }

        $this->cookies[$this->host] = array_merge($array, $this->cookies[$this->host]);
    }

    /**
     * [setPersistReferers 设置重定向时，是否保持referer]
     * @param [type] $boolean [description]
     */
    public function setPersistReferers($boolean)
    {

        $this->persistReferers = $boolean;
    }

    /**
     * [setHandleRedirects 设置是否支持重定向]
     * @param [type] $boolean [description]
     */
    public function setHandleRedirects($boolean)
    {

        $this->handleRedirects = $boolean;
    }

    /**
     * [setMaxRedirects 设置重定向总次数]
     * @param [type] $num [description]
     */
    public function setMaxRedirects($num)
    {

        $this->maxRedirects = $num;
    }

    /**
     * [setPersistCookies 设置cookie保持]
     * @param [type] $boolean [description]
     */
    public function setPersistCookies($boolean)
    {

        $this->persistCookies = $boolean;

    }

    /**
     * [setTimeout 定时]
     * @param [type] $timeout [description]
     */
    public function setTimeout($timeout)
    {

        $this->timeout = $timeout;
    }

    /**
     * [setRequestHeaders 设置请求的headers]
     * @param array $headers [description]
     */
    private function setRequestHeaders($headers = array())
    {

        foreach ($headers as $h_k => $h_v) {

            $this->requestHeaders[$h_k] = $h_v;
        }
    }

    /**
     * [buildRequest 创建request信息]
     * @return [type] [description]
     */
    private function buildRequest()
    {

        $headers = "{$this->method} {$this->path}".(empty($this->querystring)?'':'?'.$this->querystring)." HTTP/1.1";

        $headerArray = array();
        $headerArray['Host'] = $this->port === 80 ? $this->host : "{$this->host}:{$this->port}";
        $headerArray['User-Agent'] = $this->userAgent;
        $headerArray['Accept'] = $this->accept;

        if ($this->useGzip) {

            $headerArray['Accept-encoding'] = $this->acceptEncoding;
        }

        $headerArray['Accept-language'] = $this->acceptLanguage;

        if (isset($this->referer)) {

            $headerArray['Referer'] = $this->referer;
        }

        if (isset($this->cookies[$this->host])) {
            $cookie = '';
            foreach ($this->cookies[$this->host] as $key => $value) {
                $cookie .= "$key=$value; ";
            }
            $headerArray['Cookie'] = $cookie;
        }

        if (isset($this->username) && isset($this->password)) {

            $headerArray['Authorization'] = 'BASIC ' . base64_encode($this->username . ':' . $this->password);
        }


        //将用户设置的header信息覆盖默认值
        foreach ($this->requestHeaders as $h_k => $h_v) {

            $headerArray[$h_k] = $h_v;
        }

        //拼header
        foreach ($headerArray as $ha_k => $ha_v) {
            $headers .= "\r\n{$ha_k}: {$ha_v}";
        }
        
        $this->header = $headers;


    }

    /**
     * [parseHeader description]
     * @param  [type] $headerBuf [description]
     * @return [type]            [description]
     */
    private function parseHeader($headerBuf)
    {

        /*
            version + status_code + message
         */
        $headParts = explode("\r\n", $headerBuf);
        if (is_string($headParts)) {
            $headParts = explode("\r\n", $headParts);
        }

        if (!is_array($headParts) || !count($headParts)) {

            //TODO header buffer valid
            return false;
        }

        list($this->rspHeaders['protocol'], $this->rspHeaders['status'], $this->rspHeaders['msg']) = explode(' ', $headParts[0], 3);
        unset($headParts[0]);

        foreach ($headParts as $header) {

            $header = trim($header);
            if (empty($header)) {
                continue;
            }

            $h = explode(':', $header, 2);
            $key = trim($h[0]);
            $value = trim($h[1]);
            $this->rspHeaders[$key] = $value;
        }

        //\frame\log\Log::debug(__METHOD__ . " header == " . print_r($this->rspHeaders, true), __CLASS__);
    }

}
