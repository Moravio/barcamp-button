<?php
require_once 'include.php';

$o = new Raspberry();
$o->reset();
echo 'ligh on'."\n";
$o->lightOn();
sleep(2);
echo 'light off'."\n";
$o->lightOff();


echo 'click button'."\n";

while (true)
{
  if ($o->isButtonClicked())
  {
    echo 'button clicked'."\n";
    exit();
  }
}
