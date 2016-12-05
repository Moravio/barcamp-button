<?php

class SlackApiAccessToken
{
  const AUTHORIZE_URL = 'https://slack.com/oauth/authorize';
  const CLIENT_ID = ''; // TODO - fill in
  const REDIRECT_URL = ''; // TODO - fill in
  const CLIENT_SECRET = ''; // TODO - fill in
  const ACCESS_TOKEN_EXCHANGE_URL = 'https://slack.com/api/oauth.access';
  const ACCESS_TOKEN_FILE = 'data/accessToken.txt';

  protected $res;




  public function getResult()
  {
    return $this->res;
  }


  public function setAccessTokenFromCode($code)
  {
    $get['client_id'] = self::CLIENT_ID;
    $get['client_secret'] = self::CLIENT_SECRET;
    $get['code'] = $code;
    $get['redirect_uri'] = self::REDIRECT_URL;

    $res = cCurlTools::get(self::ACCESS_TOKEN_EXCHANGE_URL.'?'.http_build_query($get));

    $result = json_decode($res);
    //print_r($result);
    $this->saveAccessToken($result->access_token);
  }


  private function saveAccessToken($accessToken)
  {
    file_put_contents(REAL_PATH.self::ACCESS_TOKEN_FILE, serialize($accessToken));
  }


  /**
   * @return bool|string
   */
  protected function getAccessToken()
  {
    if (file_exists(REAL_PATH.self::ACCESS_TOKEN_FILE))
    {
      return unserialize(file_get_contents(REAL_PATH.self::ACCESS_TOKEN_FILE));
    }

    return false;
  }


  public function authorize()
  {
    $get['client_id'] = self::CLIENT_ID;
    $get['scope'] = 'channels:read,groups:read,channels:history';
    $get['redirect_uri'] = self::REDIRECT_URL;

    $option['CURLOPT_VERBOSE'] = 1;
    $option['CURLOPT_HEADER'] = 1;

    cCurlTools::get(self::AUTHORIZE_URL.'?'.http_build_query($get));
    $info = cCurlTools::getInfo();
    $redirectUrl = $info['redirect_url'];
    //echo $redirectUrl."\n";

    cCurlTools::get($redirectUrl);
    $info = cCurlTools::getInfo();
    $redirectUrl = $info['redirect_url'];
    //echo $redirectUrl."\n";

    cCurlTools::get($redirectUrl);
    $info = cCurlTools::getInfo();
    $redirectUrl = $info['redirect_url'];

    echo 'call this in webbrowser: '.$redirectUrl;
  }




}