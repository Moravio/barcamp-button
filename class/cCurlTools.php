<?php
/**
 * cCurlTools
 * 
 * @version 2.1
 * change:
 * v2.1
 * - pokud se posilaji hlavicky tak ty jdou pak ziskat metodou getLastHeader();
 *
 * v2.0
 * - metoda getNonBlocking() nyni vyuziva tridy cRabbitJob
 *
 * v1.3
 * - vice uprav pro SSL
 * - metoda getNonBlocking() - moznost odeslat data pozdeji, pokud je k dispozici trida cHiddenJob
 *
 * v1.2:
 * - pridana metoda getInfo()
 * - pridana moznost automatickeho redirektu - pri nastaveni hodnoty CURLOPT_FOLLOWLOCATION=1 se bude chovat jako standardni presmerovani a vrátí správně přesměrovaný obsah
 * v1.1
 * - pridana oprava, pokud se jedna o SSL komunikaci   
 *  
 */


class cCurlTools
{
  const MAX_REDIRECT = 3;
  
  private static $instance;
  private static $error;
  private static $httpCode;
  private static $info;
  private static $header;

  public static function getError() {return self::$error;}
  public static function getHttpCode() {return self::$httpCode;}
  public static function getInfo() {return self::$info;}
  public static function getCode() {return self::$httpCode;}
  public static function isError() {if (!empty(self::$error)) return true; else return false;}

  private $iRedirectCount = 0;
  private $bFollowLocation = false;


	/**
   * @return cCurlTools
   */
  public static function n()
  {
    if (is_null(self::$instance))
    {
      self::$instance = new self();
    }
    return self::$instance;
  }


  /**
   * @param string $url
   * @param null|array $option
   * @param null|array|string $data
   * @return mixed|string
   */
  public static function getNonBlocking($url, $option = null, $data = null)
  {
    if (class_exists('cRabbitJob'))
    {
      $oCurlReq = self::n();
      return $oCurlReq->_getNonBlocking($url, $option ,$data);
    }
    return self::get($url, $option, $data);
  }

  /**
   * @param string $url
   * @param null|array $option
   * @param null|array|string $data
   * @return mixed|string
   */
  private function _getNonBlocking($url, $option = null, $data = null)
  {
    $job = cRabbitJob::addJob('curlSendRequest');
    $job->setClass(__CLASS__);
    $job->setStaticMethod('get');
    $job->setParam($url);
    $job->setParam($option);
    $job->setParam($data);
    return $job->done();
  }


	/**
   * @param string $url
   * @param null|array $option
   * @param null|array|string $data
   * @return mixed|string
   */
  public static function get($url, $option = null, $data = null)
  {
    $oCurlReq = self::n();
    return $oCurlReq->_get($url, $option ,$data);
  }


  /**
   * @param string $url
   * @param null|array $option
   * @param null|array|string $data
   * @return mixed|string
   */
  private function _get($url, $option = null, $data = null)
  {
    $ch = curl_init();
    if (stristr($url, 'ssl.') || stristr($url, 'https')) 
    {
      curl_setopt ($ch, CURLOPT_SSLVERSION, 5);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_URL, $url);


    if ($data)
    {
      curl_setopt ($ch, CURLOPT_POST, 1);
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
    }

    if (is_array($option)) 
    {
      foreach ($option as $key => $value)
      {
        if ($key == 'CURLOPT_FOLLOWLOCATION')
        {
          if ($value == 1 || $value === true)
          {
            curl_setopt($ch, CURLOPT_HEADER, true);
            $this->bFollowLocation = true;
          }
          continue;
        }
        eval('curl_setopt($ch, '.$key.', $value);');
      }
    }

    $result = curl_exec($ch);

    self::$error = curl_error($ch);
    self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    self::$info = curl_getinfo($ch);
    
    if ($this->bFollowLocation && (self::$httpCode == 301 || self::$httpCode == 302))
    {
      if ($this->iRedirectCount < self::MAX_REDIRECT)
      {
        preg_match('/Location:(.*?)\n/', $result, $matches);
        $newurl = trim(array_pop($matches));
        $this->iRedirectCount++;
        return $this->_get($newurl, $option, $data);
      }
    }

    self::$header = null;
    if ($this->bFollowLocation)
    {
      list($result, $header) = $this->parseHeaderAndBody($result);
      self::$header = $header;
    }

    $this->bFollowLocation = false;
    $this->iRedirectCount = 0;
    return $result;
  }


  public function getLastHeader()
  {
    return self::$header;
  }


	/**
   * @param string $response
   * @return string
   */
  private function parseHeaderAndBody($response)
  {
    $header = substr($response, 0, self::$info['header_size']);
    $body = substr($response, self::$info['header_size']);
    return array($body, $header);
  }
  
}


