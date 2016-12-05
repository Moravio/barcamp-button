<?php

class Raspberry
{
  const MODE_OUT = 'out';
  const MODE_IN = 'in';
  const LIGHT_PIN = 1;
  const DIODE_PIN = 0;
  const BUTTON_PIN = 4;
  const GPIO_PATH = '/usr/bin/gpio ';


  public function reset()
  {
    $this->setup(self::LIGHT_PIN, self::MODE_OUT);
    $this->setup(self::DIODE_PIN, self::MODE_OUT);
    $this->setup(self::BUTTON_PIN, self::MODE_IN);
    $this->lightOff();
  }

  public function isButtonClicked()
  {
    return $this->read(self::BUTTON_PIN) == 1 ? true : false;
  }


  public function write($pin, $value)
  {
    return shell_exec(self::GPIO_PATH.'write '.$pin.' '.$value);
  }

  public function read($pin)
  {
    return shell_exec(self::GPIO_PATH.'read '.$pin);
  }

  public function setup($pin, $value = self::MODE_IN)
  {
    return shell_exec(self::GPIO_PATH.' mode '.$pin.' '.$value);
  }

  public function lightOn()
  {
    echo 'light on'."\n";
    return $this->write(self::LIGHT_PIN, 1);
  }

  public function lightOff()
  {
    echo 'light off'."\n";
    return $this->write(self::LIGHT_PIN, 0);
  }


  public function isLightOn()
  {
    return $this->read(self::LIGHT_PIN) == 1 ? true : false;
  }

}