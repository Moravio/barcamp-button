<?php

class App
{
  protected $lightStatus = 0;

  /**
   * @var SlackMessaging
   */
  protected $slackMessaging;
  /**
   * @var SlackApi
   */
  protected $slackApi;

  public function __construct()
  {
    $this->slackMessaging = new SlackMessaging();
    $this->slackApi = new SlackApi();
    $this->messages = new Messages($this->slackApi);
    $this->raspberry = new Raspberry();
    $this->raspberry->reset();
  }


  public function run()
  {
    $this->slackApi->testAccessToken();

    $lastSlackApi = time();
    while (true)
    {
      if (time() - $lastSlackApi > 5) // repeat every 5 seconds
      {
        $lastSlackApi = time();
        $this->slackMessaging->sendNotSendedMessages();
        $this->slackApi->retriveMessagesFromChannel();
        $this->messages->parseNewMessagesForCommand();
      }

      if ($this->raspberry->isButtonClicked() && !$this->raspberry->isLightOn())
      {
        $this->raspberry->lightOn();
        $this->slackMessaging->sendMessage('Někdo zmáčknul tlačítko, běž mu nalít panáka !!!!!!');
      }

      usleep(100);
    }

  //  $this->slackApi->getChannels();

    //$message = 'Pojď si dát panáka!!!';
    //$this->slackMessaging->sendMsg($message);
  }

}