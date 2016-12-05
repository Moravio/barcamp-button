<?php

class Messages
{
  /**
   * @var MySQL
   */
  protected $oDB;
  /**
   * @var Command
   */
  protected $command;
  /**
   * @var SlackApi
   */
  protected $slackApi;


  public function __construct(SlackApi $slackApi)
  {
    $this->oDB = MySQL::getInstance();
    $this->slackApi = $slackApi;
    $this->command = new Command();
  }


  /**
   * @param object $message
   */
  public function saveChannelMessage($message)
  {
    $hash = md5($message->ts.$message->text);
    $sql = 'SELECT id 
              FROM channel_messages
              WHERE hash="'.$hash.'"
              LIMIT 1';
    $res = $this->oDB->query($sql);
    if ($this->oDB->num_rows($res) == 0)
    {
      $sql = 'INSERT INTO channel_messages SET
                hash="'.$hash.'",
                text="'.$this->oDB->escape_string($message->text).'",
                date_insert=NOW(),
                user="'.$this->oDB->escape_string($message->user).'",
                ts='.$message->ts;
      $this->oDB->query($sql);
    }
  }


  public function parseNewMessagesForCommand()
  {
    $sql = 'SELECT *
              FROM channel_messages
              WHERE command_done="0"';
    $res = $this->oDB->query($sql);
    while ($data = $this->oDB->fetch_array($res))
    {
      $command = $this->parseCommand($data['text']);
      if ($command)
      {
        $this->command->{$command}();
        $this->slackApi->sendMessage('Raspberry PI - Running command: '.$command);
      }

      $this->updateAsDone($data['id']);
    }
  }


  /**
   * @param $text
   * @return null|string
   */
  private function parseCommand($text)
  {
    foreach ($this->command->getSlackCommands() as $command => $function)
    {
      if (stristr($text, 'cmd '.$command))
      {
        return $function;
      }
    }

    return null;
  }


  /**
   * @param int $id
   */
  private function updateAsDone($id)
  {
    $sql = 'UPDATE channel_messages SET command_done="1"
                WHERE id='.$id.'
                LIMIT 1';
    $this->oDB->query($sql);
  }


}