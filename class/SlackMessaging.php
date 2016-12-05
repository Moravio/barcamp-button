<?php

class SlackMessaging
{

  /**
   * @var MySQL
   */
  protected $db;
  /**
   * @var SlackApi
   */
  protected $slackApi;

  public function __construct()
  {
    $this->db = MySQL::getInstance();
    $this->slackApi = new SlackApi;
  }


  /**
   * @param string $s
   */
  public function sendMessage($s)
  {
    $this->saveMessage($s);
    //$this->sendNotSendedMessages();
  }


  public function sendNotSendedMessages()
  {
    $sql = 'SELECT *
              FROM message
              WHERE date_sended IS NULL';
    $res = $this->db->query($sql);
    while($data = $this->db->fetch_array($res))
    {
      $res = $this->slackApi->sendMessage($data['text']);
      if ($res)
      {
        $this->updateAsSended($data['id']);
      }
    }
  }


  /**
   * @param int $id
   * @return mixed
   */
  public function updateAsSended($id)
  {
    $sql = 'UPDATE message SET 
              date_sended=NOW() 
              WHERE id='.$id;
    return $this->db->query($sql);
  }


  /**
   * @param string $s
   */
  private function saveMessage($s)
  {
    $sql = 'INSERT INTO message SET 
              text="'.$this->db->escape_string($s).'",
              date_insert=NOW()';
    $this->db->query($sql);
  }

}