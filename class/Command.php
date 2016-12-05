<?php

class Command
{
  private $slackCommands = ['light off' => 'lightOff',
                            'light on' => 'lightOn'
                           ];

  public function getSlackCommands()
  {
    return $this->slackCommands;
  }


  public function lightOff()
  {
    $o = new Raspberry();
    $o->lightOff();
    $this->logCommand(__FUNCTION__);
  }


  public function lightOn()
  {
    $o = new Raspberry();
    $o->lightOn();
    $this->logCommand(__FUNCTION__);
  }


  private function logCommand($function)
  {
    file_put_contents(REAL_PATH.'log/commands.log', date('d.m.Y H:i:s').' - '.$function."\n", FILE_APPEND);
  }

}