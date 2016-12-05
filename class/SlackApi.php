<?php

class SlackApi extends SlackApiAccessToken
{
  const MESSAGE_URL = 'https://hooks.slack.com/services/tttt/bbbbb/dddddd';

  const SEND_MESSAGES_TO_TEMP = false; // for testing, login messages to slack to file
  const CHANNEL_ID = 'CCCCCCCC';
  /**
   * @var Messages
   */
  protected $messagesObject; // barcamp_button channel


  public function __construct()
  {
    $this->messagesObject = new Messages($this);
  }


  public function retriveMessagesFromChannel()
  {
    $url = 'https://slack.com/api/channels.history';
    $get['channel'] = self::CHANNEL_ID;
    $get['unreads'] = 0;
    $get['count'] = 20;

    try
    {
      $ret = $this->callApi($url, $get);
      $messages = $ret->messages;
      foreach ($messages as $message)
      {
        $this->messagesObject->saveChannelMessage($message);
      }
    }
    catch (WarningException $e)
    {
      echo $e->getMessage();
    }
  }



  public function getChannels()
  {
    $url = 'https://slack.com/api/channels.list';

    $get['exclude_archived'] = 1;

    try
    {
      $res = $this->callApi($url, $get);
      $chanels = $res->channels;
      foreach ($chanels as $chanel)
      {
        echo $chanel->name." - ".$chanel->id."\n";
      }
    }
    catch (WarningException $e)
    {
      echo $e->getMessage();
    }
  }


  /**
   * @param string $url
   * @param array $get
   * @return mixed
   * @throws WarningException
   */
  protected function callApi($url, $get = [])
  {
    $get['token'] = $this->getAccessToken();
    $this->res = cCurlTools::get($url, null, $get);
    if (cCurlTools::isError())
    {
      throw new WarningException(cCurlTools::getError());
    }

    return json_decode($this->res);
  }


  /**
   * @param $s
   * @return bool
   */
  public function sendMessage($s)
  {
    if (self::SEND_MESSAGES_TO_TEMP)
    {
      file_put_contents(REAL_PATH.'log/slack_messages.txt', date('d.m.Y H:i:s'). "\n".$s."\n", FILE_APPEND);

      return true;
    }
    $option['CURLOPT_HTTPHEADER'] = 'Content-Type: application/json';

    $data['text'] = $s;

    echo 'sending Message: ';
    $res = cCurlTools::get(self::MESSAGE_URL, null, json_encode($data));
    echo $res."\n";

    $this->res = $res;

    return stristr($res, 'ok') ? true : false;
  }


  public function testAccessToken()
  {
    if (!$this->getAccessToken())
    {
      $msg = 'Access token not ready, please run "php authorize.php" to setup access token';
      $this->sendMessage($msg);
      echo $msg;
      exit();
    }
  }
}