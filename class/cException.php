<?php
/**
 * @package Exception
 * @Author: nedik
 * @Date: 1.1.2008
 * @version 1.7.6
 * 
 * @lastUpdate: 29.8.2012
 * 
 * @changes log:
 * 1.7.7
 * - upraveno - strict error
 * 1.7.6
 * - upraveno posilani mailu, kdyz zjisti moc velky soubor na poslatni, tak z nej posle jen konec
 * 1.7.5
 * - zrusen ereg
 * 1,74
 * - upraven WarningException, odstranena chyba kdy nezobrazoval chyby na obrazovku i kdyz attribut bShowErrorAdvanceMessage ma hodnotu TRUE
 * 1.73
 * - novy atribut bSendErrorMail , lze nastavit constantou EXCEPTION_SET_ERROR_MAIL_SEND, pokud false, tak se nebudou odesilat zadne maily
 * 1.72
 * - opraven hlaskovaci mechanizmus. (hlaska, ktera se mela v message presouvat se nepresouvala a tudiz $e->getMessage() byl vzdy prazdy retezec)  
 * 1.71
 * - getShowMessage nyni defaultne zobrazuje zpravu i s LINE a FILE
 * 1.7
 * - pridany komentare k jednotlivym tridam Exception
 * - nastveni zobrazeni detailniho popisu chyby je osetreno u kazdeho typu chyby a globalne mozno se nastavit constantou EXCEPTION_SET_SHOW_ADVANCE_ERROR_MESSAGE
 * - opraveno zobrazovani chyby z MySQLException - nyni zobrazovani detailnich chyb konfigurovatelne pres tridu MySQL
 * 1.6
 * - vytvorena trida GlobalErrorReporting, ktera zabezpecuje transport chyb do Globalniho centra
 * 
 * - jen upravena chyba ve tride MySQLWarningException
 * - u tridy MySQLWarningException doplnen constructor o $line a $file pro moznost zobrazovat spravnou cestu k souboru pri pouzibi mysql(ver. >= 2.2) metody simpleQuery()
 * - metoda showErrorOnScreen obsahuje class="error" -> dulezite pro tridu class.cache, ktera podle toho muze zjistit, ze se vyskytla chyba a danou stranku necachovat
 * - metoda getLogMessage je nyni i s URL
 * - metoda sendMail() nyni posla maily az po 10 minutach od vytvoreni logu
 */

class ParentException extends Exception
{
  protected $sLogPath;
  protected $sMessage;
  protected $sMsgToGlobalReport;
  protected $ExceptionType = 'undefined';
  protected $iSendMailAfterMinutes = 10; // posle mail az po techto minutach od prvni chyby
  
  
  protected $dontSendToGlobalReporting = false; // pri vytvareni obchodu, at se neposila zbytecne kazda chyba
  protected $bShowErrorAdvanceMessage = false; // zobrazovat detailni popis chyby na verejnych strankach
  protected $sErrorMailAddress = 'nedela.tomas@gmail.com'; // vice adres oddelit carkou (,)
  protected $bSendErrorMail = true; // zasilat maily - u localhostu vetsinou vypnute
  
  
  public function __construct ($message = null)
  {
    parent::__construct($message);
    
    $this->sLogPath = REAL_PATH.'log/';
    
    if (defined('EXCEPTION_SET_NOT_SEND_EXCEPTION_TO_GLOBAL_REPORTING') && EXCEPTION_SET_NOT_SEND_EXCEPTION_TO_GLOBAL_REPORTING == true)
      $this->dontSendToGlobalReporting = true;
    
    if (defined('EXCEPTION_SET_SHOW_ADVANCE_ERROR_MESSAGE')) $this->bShowErrorAdvanceMessage = EXCEPTION_SET_SHOW_ADVANCE_ERROR_MESSAGE;
    else if (defined('EXCEPTION_SET_SHOW_ERROR_MESSAGE')) $this->bShowErrorAdvanceMessage = EXCEPTION_SET_SHOW_ERROR_MESSAGE; // pro zpetnou kompatibilitu
    
    if (defined('EXCEPTION_SET_ERROR_MAIL_ADDRESS') && EXCEPTION_SET_ERROR_MAIL_ADDRESS != '') $this->sErrorMailAddress = EXCEPTION_SET_ERROR_MAIL_ADDRESS;

    if (defined('EXCEPTION_SET_ERROR_MAIL_SEND')) $this->bSendErrorMail = (bool)EXCEPTION_SET_ERROR_MAIL_SEND;
  }


  protected function getLogMessage($sFile = null, $iLine = null)
  {
    return date("Y-m-d H:i:s")."\n".
           'URL: '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".
           'FILE: '.$this->getFile()."\n".
           'LINE: '.$this->getLine()."\n".
           'MSG: '.$this->sMessage."\n".
           '----------------------------------------'."\n\n";
  }

  
  protected function getShowMessage()
  {
    return date("Y-m-d H:i:s")."\n".
           'URL: '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".
           'FILE: '.$this->getFile()."\n".
           'LINE: '.$this->getLine()."\n".
           'MSG: '.$this->sMessage."\n";
  }


  protected function getLogFileName()
  {
    $logFile = str_replace(realpath(dirname(__FILE__).'/../'), '', $this->getFile());
    $logFile = $this->sLogPath.$this->ExceptionType.'#'.(str_replace('/', '#', str_replace('\\', '#', $logFile))).'.txt';
    return $logFile;
  }
  
  
  protected function log ($s)
  {
    $logFile = $this->getLogFileName();
    $fp = fopen($logFile, 'ab');
    @fwrite($fp, $s);
    @fclose($fp);
    
  }

  
  protected function sendMail($s)
  {
    // zjistim jmeno mailoveho souboru
    $logFile = $this->getLogFileName();
    $sMailLogFile = str_replace($this->sLogPath, $this->sLogPath.'Email#', $logFile);
    
    // zjistim cas posledniho pristupu
    $iTime = time(); // aktualni datum
    if (!$iFileTime = @filemtime($sMailLogFile)) // datum posledni modifikace souboru
      $iFileTime = $iTime;
    
    if (!file_exists($sMailLogFile)) $bForceSend = true;
    else $bForceSend = false;
    
      
    // ulozim do mailoveho souboru
    $fp = fopen($sMailLogFile, 'a');
    $s .= "---------------------------------------------------------------------------\n";
    @fwrite($fp, $s);
    @fclose($fp);
    
    
    // nastavim cas souboru zpet na puvodni
    @touch($sMailLogFile, $iFileTime);
    clearstatcache();
        
    // zjistim, zda je vhodne jiz poslat mail
    if (($iFileTime + (60*$this->iSendMailAfterMinutes) <= $iTime) || $bForceSend === true)
    {
      if (filesize($sMailLogFile) > (1024 * 1024 * 10))
      {
        $iStartFrom = filesize($sMailLogFile) - (1024 * 1024 * 30);
        $sMsg = file_get_contents($sMailLogFile, FILE_USE_INCLUDE_PATH, null, $iStartFrom);
      }
      else $sMsg = file_get_contents($sMailLogFile);
      
      echo 'sadfsadfsd';
      $sSubj = 'Chyba na serveru '.SERVER_NAME.' typu '.$this->ExceptionType;
      $aTo = explode(',',$this->sErrorMailAddress);
      $sFrom = SERVER_NAME.' - ERROR'.'<err@'.SERVER_NAME.'>';
      if ($this->mailSender($aTo, $sSubj, $sMsg, $sFrom))
      {
        // soubor byl odeslan, tak ho smazu
        if ($bForceSend === false) unlink($sMailLogFile);
      }
    }
    return true;
  }
  
  
  protected function mailSender($aTo, $sSubj, $sMsg, $sFrom)
  {
    return true;
    if (!$this->bSendErrorMail) return true;
    $sMsg = iconv('UTF-8', 'iso-8859-2', $sMsg);
 
    // stavba mailu
    $header = "From: ".(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '')." <$this->sErrorMailAddress>\r\n";
    $header .= "MIME-version: 1.0\r\n";
    $header .= "X-Mailer: PHP\r\n";
    $header .= "Return-Path: <$this->sErrorMailAddress>\r\n";
    $header .= "Reply-To: $this->sErrorMailAddress\r\n";
    return mail($this->sErrorMailAddress, $sSubj, $sMsg, $header);
  }
  
  
  /**
   * zasila chyby na globalni error reporting na
   */  
  protected function sendToGlobalErrorReporting()
  {
    
    $sMsg = (empty($this->sMsgToGlobalReport) ? $this->sMessage : $this->sMsgToGlobalReport);
  
    if ($this->dontSendToGlobalReporting === true) return true; // neposilat na global reporting
    
    cGlobalErrorReporting::setError($sMsg);
  }
  
  
  protected function showErrorOnScreen($sMessage = '')
  {
    if (empty($sMessage)) $sMessage = $this->getShowMessage();
    echo '<div style="color:red; border:double red; padding:5px; background:white; text-align:left;" class="error">
            Na této stránce došlo k chybě. Je možné, že požadovaná operace nebyla provedena.<br/>
            '.($this->bShowErrorAdvanceMessage === true ? nl2br($sMessage) : '').
            '</div>';
  }

}


/**
 * fatalni chyba programu, kdy neni dale mozno pokracovat v provadeni skriptu
 * - vzdy se zobrazi uzivateli varovani o chybe
 * - mozno nastavit, zda zobrazit detailni popis chyby
 *
 * @param STRING $message - detailni zprava o chybe -> pokud neni vyslovne receno, tak se nezobrazi uzivateli
 * @param STRING $sMsgToGlobalReport - zprava, ktera se odesle do global report -> vetsinou zkracena zprava, ktera se vleze do SMS
 * @param BOOL $bShowErrorAdvanceMessage - pokud TRUE, tak se zobrazi detailni zprava o chybe
 */
class FatalException extends ParentException
{

  public function __construct ($message, $sMsgToGlobalReport = '', $bShowErrorAdvanceMessage = false)
  {
    parent::__construct();

    $this->sMsgToGlobalReport = $sMsgToGlobalReport;
    
    $this->sMessage = $message;

    if ($bShowErrorAdvanceMessage === true) $this->bShowErrorAdvanceMessage = true;

    $this->showErrorOnScreen($this->getShowMessage());

    $this->ExceptionType = 'fatalError';
    
    $this->log($this->getLogMessage());

    $this->sendMail($this->getLogMessage());
    
    $this->sendToGlobalErrorReporting();
    
    exit();
  }
}



/**
 * chyba, ktera nebrani v pokracovani skriptu
 * - varovani o chybe se zobrazi jen pokud je vyslovne receno (nastaveno globalne pro EXCEPTION, nebo $bShowErrorOnScreen = TRUE)
 *
 * @param STRING $message - detailni zprava o chybe
 * @param BOOL $bShowErrorOnScreen - zobrazit zpravu uzivateli -> zobrazi se automaticky i detailni zprava o chybe
 * @param BOOL $bSendMail - pokud TRUE, tak se odesle mail o chybe
 * @param BOOL $bSendToGlobalReporting - pokud TRUE, tak se odesle zprava do GlobalReporting
 */
class WarningException extends ParentException
{


  public function __construct ($message, $bShowErrorOnScreen = false, $bSendMail = true, $bSendToGlobalReporting = false)
  {
    parent::__construct();
    
    
    $this->sMessage = $message;

    if ($bShowErrorOnScreen === true || $this->bShowErrorAdvanceMessage)
    {
      $this->bShowErrorAdvanceMessage = true;
      $this->showErrorOnScreen($this->getShowMessage());
    }

  
    $this->ExceptionType = 'warningError';

    $this->log($this->getLogMessage());

    if ($bSendMail === true) $this->sendMail($this->getLogMessage());

    if ($bSendToGlobalReporting === true) $this->sendToGlobalErrorReporting(); 
    
  }


}



/**
 * chyba pri provedeni dotazu na databazi
 * - musi byt zvlastni, protoze FILE a LINE je nutno primo definovat, nelze vytahnout ze tridy EXCEPTION
 * - vzdy se zobrazi uzivateli varovani o chybe
 * - mozno nastavit, zda zobrazit detailni popis chyby
 *
 * @param STRING $message
 * @param BOOL $bShowErrorAdvanceMessage - pokud TRUE, zobrazi detailni chybu uzivateli
 * @param STRING $sMsgToGlobalReport - mozno nastavit vlastni zpravu pro Global Report
 * @param STRING $sFile
 * @param INT $iLine
 */
class MySQLWarningException extends ParentException
{

  public function __construct ($message, $bShowErrorAdvanceMessage = false, $sMsgToGlobalReport = '', $sFile = '', $iLine = 0)
  {
    
    parent::__construct();
    
    $this->ExceptionType = 'MySQLWarningException';
    
    $this->sMsgToGlobalReport = $sMsgToGlobalReport;
    
    $this->sMessage = $message;

    $sLogMessage = $this->getLogMessage($sFile, $iLine);
    
    if ($bShowErrorAdvanceMessage === true) $this->bShowErrorAdvanceMessage = true;

    $this->showErrorOnScreen($sLogMessage);

    $this->sendMail($this->getLogMessage($sFile, $iLine));
    
    $this->sendToGlobalErrorReporting();
    
    $this->log($sLogMessage.'----------------------------------------'."\n\n");
  }

  
  protected function getLogMessage($sFile = null, $iLine = null)
  {
    $aTrace = $this->getTrace();
    
    return date("Y-m-d H:i:s")."\n".
           'URL: '.$_SERVER['REQUEST_URI']."\n".
           'FILE: '.(!empty($sFile) ? $sFile : $aTrace[0]['file'])."\n".
           'LINE: '.(!empty($iLine) ? $iLine : $aTrace[0]['line'])."\n".
           $this->sMessage."\n";
  }

  
  protected function log ($s)
  {
    $aTrace = $this->getTrace();
    
    $logFile = str_replace(realpath(dirname(__FILE__).'/../'), '', $aTrace[0]['file']);
    $logFile = $this->sLogPath.'Error'.'#'.(str_replace('/', '#', str_replace('\\', '#', $logFile))).'#error.txt';
    $fp = fopen($logFile, 'ab');
    @fwrite($fp, $s);
    @fclose($fp);
  }
}



/**
 * chyba vetsinou vytvorena ucelne programatorem pro zobrazeni a zalogovani nejakeho stavu
 * - pro volani z funkce a metod je mozne pouzit normalni volani TRY CATCH, avsak pro volani primo ze skriptu (bez zapozdreni do funkce nebo metody, napr. primo z index.php)
 *   je nutne volat statickou metodu toLog(), protoze jinak bude vyhazovat nesmyslny LINE a FILE -> zpusobeno tim, ze PHP nevi, z jake radky
 *
 * @param STRING $message
 * @param BOOL $bShowErrorOnScreen - zobrazit chybu uzivateli?
 */
class userException extends ParentException 
{
  
  private static $bFileLineFromTrace; // znaci, ze bylo volano ze staticke funkce a ne TRY CATCH
  
  public function __construct ($message, $bShowErrorOnScreen = false)
  {        
    parent::__construct($message);
        
    $this->sMessage = $message;
    
    $this->ExceptionType = 'userNotice';

    $sLogMessage = $this->getLogMessage();

    // nemusi se vzdy zobrazovat hlaska, programator muze jen hlasku zalogovat bez zobrazeni uzivateli stranek.
    if ($bShowErrorOnScreen === true)
    {
      $this->bShowErrorAdvanceMessage = true;
      $this->showErrorOnScreen($sLogMessage);
    }

    $this->log($sLogMessage);
  }
  
  
  protected function getLogFileName()
  {
    if (self::$bFileLineFromTrace === false) return parent::getLogFileName();
    
    $aTrace = $this->getTrace();
    $logFile = str_replace(realpath(dirname(__FILE__).'/../'), '', $aTrace[0]['file']);
    $logFile = $this->sLogPath.$this->ExceptionType.'#'.(str_replace('/', '#', str_replace('\\', '#', $logFile))).'.txt';
    return $logFile;
  }
  
  
  protected function getLogMessage($sFile = null, $iLine = null)
  { 
    if (self::$bFileLineFromTrace === false) return parent::getLogMessage();
    
    $aTrace = $this->getTrace();
    
    
    return date("Y-m-d H:i:s")."\n".
           'URL: '.$_SERVER['REQUEST_URI']."\n".
           'FILE: '.$aTrace[0]['file']."\n".
           'LINE: '.$aTrace[0]['line']."\n".
           $this->sMessage."\n"
           .'----------------------------------------'."\n\n";
  }
  
  
  public static function toLog($s, $bShowErrorOnScreen = false)
  {
    self::$bFileLineFromTrace = true;
    $o = new userException($s, $bShowErrorOnScreen, true);
  }
}



class cGlobalErrorReporting
{

  private $aError = array();
  private $sGlobalErrorReportingURL = '';
  static private $instance;

  
  // metoda pro přístup ke sdílené instanci aplikace
  private static function getInstance()
  {
    $sClassName = __CLASS__;
    if (!isset(self::$instance)) self::$instance = new $sClassName();
    return self::$instance;
  }


  public static function setError($sMsg)
  {
    $o = self::getInstance();
    $o->_setError($sMsg);
  }


  private function _setError($sMsg)
  {

    $sHash = md5($sMsg);

    if (!isset($this->aError[$sHash]))
    {
      $this->aError[$sHash]['msg'] = $sMsg;
      $this->aError[$sHash]['count'] = 1;
    }
    else
    {
      $this->aError[$sHash]['count']++;
    }
  }


  public function __destruct()
  {
    return true;
    $sMsg = '';
    if ($this->aError) foreach ($this->aError as $val)
    {
      $sMsg .= $val['msg'];
      if ($val['count'] > 1) $sMsg .= ' - Error count: '.$val['count']."";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->sGlobalErrorReportingURL.'?error_message='.urlencode($sMsg).'&server_name='.urlencode($_SERVER['HTTP_HOST']));
      curl_setopt($ch, CURLOPT_NOBODY, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 5);
      curl_exec($ch);
      curl_close($ch);
    }
  }
}



?>