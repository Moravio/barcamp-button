<?php
/**
 * @Author: Tomas Nedela
 * @Date: 2007-10-04 22:06:28 +0200 (čt, 04 X 2007) $
 * @version 3.70
 * 
 * @last-update 21.1.2014
 * 
 * @changes log:
 * 3.70
 * - pridana metoda escape_string()
 * 3.62
 * - createLogTable presunuto tam, kde to ma smysl - zamezeni neustaleho volani SHOW TABLES
 * 3.61
 * - jen pridan dalsi moznost nastaveni self::$iCacheDurationInHoursForAutoRefreshCache
 * 3.60
 * - nova metoda queryCacheAutoRefresh - pro generovani cache a pro pouziti nasledne funkce generateAllCache 
 * - nova metoda regenerateAllCache - pregeneruje vsechny cache -- pouzivat opatrne, muze znacne zbrzdit server, zalezi na tom, co vsecho je v cache
 * - nova metoda queryCacheRewrite - okamzite prepise cachovany dotaz
 * 3.50
 * - nova metoda queryCache - vytvori cache z dotazu, takze podruhe jiz neni predavan dotaz databazi, ale vezme se z cache
 * - vyvarovat se pouziti u transakci, nebot vysledky mohou byt neocekavane
 * 3.40
 * - pokud je chyba v konektu do databaze, nebo chyba pri vyberu databaze, zobrazi se hlaska, ktera obsahuje soubor a radek volani pro lepsi lazeni
 * 3.30
 * - pokud je chyba query; DEADLOCK found, tak se skript pokusi jeste 5x udelat stejny dotaz, nez vyhodi vyjimku
 * 3.20
 * - pridan PORT pro pripojeni do DB
 * 3.19
 * - u cPageAnalysis pridana podpora pro DEBUG
 * 3.18
 * - nove metody a attributy pro pouziti tridy cPageAnalysis
 * 3.17
 * - uprava logovani do DB
 * - nyni nejprve ulozi na disk a az na konci to vlozi do DB
 * 3.16
 * - jen uprava: (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')  
 * - dale jen uprava pri logovani do DB
 * 3.15
 * - logovani do DB se nzni provadi ihned, ne az z DESTRUCTORu
 * - automaticky si zalozi databazi, pokud jeste neni pro logovani
 * 3.14
 * - nove funkce: transaction, commit, isTransaction
 * 3.13
 * - upraven ereg vyraz pri logovani insertu, deletu,...  
 * 3.12 
 * - odstranena funkce setQueryStatistics 
 * - metoda repairSQLQuery jiz neloguje - zbytecne rostl log, ktery zabiral i nekolik GB 
 * - nyni se  $bLogQueriesPerInstance uklada i do DB, pokud je promena $bLogQueriesPerInstanceToDB rovna TRUE - samozrejme musi existovat i tabulka:
  CREATE TABLE `_log_sql_per_instance` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`instance_timestamp` INT(10) NOT NULL COMMENT 'U jedne instance je tohle cislo staticke.',
	`date_insert` DATETIME NOT NULL,
	`sql` TEXT NOT NULL,
	`line` INT NOT NULL,
	`file` VARCHAR(255) NOT NULL,
	`time` double NOT NULL COMMENT 'jak dlouho dotaz bezel',
	PRIMARY KEY (`id`)
  ) COLLATE='utf8_czech_ci' ENGINE=InnoDB ROW_FORMAT=DEFAULT
 *  
 * 3.11 - uprava logovani TIME - pridano $_SERVER['REQUEST_URI'] 
 * 3.1 - upravena funkce - zaloguj inserty a delety - protoze tato funkce byla spatne uvedena a vzdy logovala vse 
 * 3.0 - nedik
 * - pridany metody CLOSE() a CONNECT() pro bezpecne odpojeni a znovu pripojeni databaze
 * - provedeny upravy pro save statistik. Nyni kontroluje soubory, jestli jsou WRITABLE
 * - !!odstraneni metody TABLE_NAME - je to nesmyslna funkce, ktera neni v PHP definovana
 * - !!metoda queryArray() ODSTRANENA - nebyla nijak zvlast pouzivana
 * 2.95 - Tomas Hojgr
 * - pridani kontroly isset($_SESSION) pred vypsanim session do logu pri update/insert/delete
 * 2.94 - Tomas Hojgr
 * - u logovani update/delete/atd se chybne upravovat nazev souboru, takze k logovani nedoslo
 * 2.93 - Tomas Hojgr
 * - pro backupy potrebuju mit moznost zadat prazdny nazev databaze, a v takovem pripade se ma mysql trida jen pripojit k serveru, ale nevybirat databazi
 * - sInstanceName se vzdycky prepsala nejakym automatickym nazvem, coz delalo neplechu. Kdyza zadam instancename, pouzije se. Kdyz nezadam, urci se sama.
 * 2.92 - Tomas Hojgr
 * - nahrada prikazu ereg za preg
 * - do mysql_stats se uklada i DB server, na kterem bylo toto pripojeni volano - abychom vedeli, jak je ktery DB server zatizeny
 * 2.91
 * - MYSQL BUG - uprava
 * 2.9
 * - u metody QUERY se u druheho parametru muze pouzit BOOL true, ktera vypise query na obrazovku (jiz se nemusi davat jako treti parametr)
 * 2.8
 * - tomasH - upraveni statistik, ted uz se uklada jen vzor dotazu vznikly v konkretnim souboru na konkretnim radku
 *   cimz se usetri zatez na generovani statistik
 * 2.7
 * - pridana metoda data_seek();
 * 2.6
 * - pri chybe QUERY se automaticky uzavira transakce -> ROLLBACK
 * 2.51
 * - upraveno logovani instance, nyni i s casem trvani dotazu  
 * 2.5
 * - neni jiz treba volat za kazdym query __FILE__, __LINE__, nyni se tak deje automaticky
 * - zrusena zbytecna metoda getMicroTime()
 * 2.43
 * - upravena metoda saveTimeToMysqlLoadLog(), nyni kontroluje soubor, jestli existuje pred tim, nez se ho snazi nacist  
 * 2.42
 * - znovu zakomentovani nefunkcniho preg_replace v metode setQueryStatistics_normalizeQueryString, který vytvářel chybu
 * 2.41
 * - pridana metoda affected_rows()  
 * 2.4
 * - po oprave chyby v PRLibrary byla obnovena funkce preg_replace v metode setQueryStatistics_normalizeQueryString  
 * 2.39
 * - pridana metoda getFieldFlags();
 * 2.38
 * - zakomentovani nefunkcniho preg_replace v metode setQueryStatistics_normalizeQueryString, který vytvářel chybu
 *
 * 2.37
 * - oprava metody fetch_fields()
 * 2.36
 * - pridana privatni metoda pro vybrani database (selectDB), tzn. nejprve se pripoji na server a az potom vybere danou databazi  
 * 2.35
 * - jen odstranene zbytecne settery v _constructu
 * - atribut $showErrorOnScreen prepsan pro lepsi pochopeni na $bShowErrorAdvanceMessage
 *
 * - pridana metoda queryOne - vrati jeden radek z SQL dotazu
 * - upravena metoda GetMicroTime
 * - pridana funkcnost pro logovani Query Statistic
 * - pridan do eregi "delete" pro logovani deletu a insertu
 * - oprava metody queryArray()
 * - upravena metoda queryArray() - pokud je jen jeden sloupec, nebo pokud jsou dva sloupce a jeden z nich je ID a je zapnuta volba $bIdAsKey=true, tak 
 *   vrati pole s hodnotami tohoto sloupce bez nazvu sloupcu, napr: [{ID}] = $value;  Jinak i nadále vrací take pole s nazvy sloupcu.
 * - upravena metoda queryArray() - vraceni pole s key=ID z databaze je defaultne vypnuto, tedy klice pole, pokud primo nereknu, tak nejsou urceny (jsou autmaticky definovany strojem PHP)
 * - pridana metoda queryArray() - vraci ARRAY s vysledky dotazu polozenych databazi -> nutno pouzivat s class.exception ver. >= 1.5
 * - pridana metoda saveTimeToMysqlLoadLog(float) pro logovani casu - metoda je volana az v destruktoru 
 */

class MySQL
{

  private static $instance, $sInstanceName;
  protected static $iNumOfInstances = 0;  // pocet vytvorených instancí tohoto objektu
  protected static $fTotalTimeOfQueries = 0;      // celkový čas dotazů
  protected static $iTotalQueries = 0;   // celkový počet dotazů
  protected static $aQueriesForStatistics;   // pole dotazu, jejich poctu a trvani, poctu vracenych radku

  static private $iCacheDurationInHours = 5; // pocet hodin, kdy je cache aktivni
  static private $iCacheDurationInHoursForAutoRefreshCache = 24; // pocet hodin, kdy je cache aktivni - pro autorefresh cache, aby se nahodou nestalo, ze zustane stara cache
  static private $iMaxRowsLengthForCache = 1000; //mozno nastavit maximalni pocet vracenych radku, ktere je mozno ukladat v cache
  
  private $bQueryCacheForceRewrite = false; // pouziti u funkce queryCacheRewrite

  /**
   * vytvorene spojeni s mysql
   * @var object MySQL
   */
  private $link;

  private $bShowErrorAdvanceMessage = false; // pokud TRUE, tak se budou zobrazovat detailni popis chyby (tedy SQL dotaz a chyba Mysql)
  private $fLongQueriesTimeLimitForLog = 5; // poud trva dotaz dele, nez tato hodnota, tak bude zalogovan
  private $bLogInsertUpdateDeleteQueries = false; // pokud TRUE, tak bude logovat všechny delety, updaty a inserty
  private static $bLogQueriesPerInstance = false; // pokud TRUE, tak bude logovat všechny dotazy a v ramci jedne instance je zapise do jednoho souboru
  private static $bLogQueriesPerInstanceToDB = true; // bude logovat $bLogQueriesPerInstance do databaze
  private static $iTimeForLogQueriesPerInstance; // zde se uklada cas, kterym je potom mozne v DB radit queriesPerInstance
  private static $sLogQueriesPerInstanceDBAnalysisHash; // hash, ktery se pouziva pri logovani do DB tridou cPageAnalysis
  
  private $sLogQueriesFile = false; // nazev souboru pro logovani SQL
  
  private $bStartTransaction = false; // indikuje, jestli je zapnuta transakce, nebo neni
  
  private $sLogQueriesPerInstance; // soubor logu dotazu behem jedne instance
  private $sLogTimesFile; // soubor logu pro time
  private $sLogPath; // adresar pro logy
  private $fLogQueryFile;
  private $aFieldTypes; // typy sloupcu MySQL
  
  private $bDbConected = false; // znaci, jestli je Database pripojena na konkretni databazi, tedy nestaci, ze je jen pripojeno na server

  private $sDbServer = '';
  private $sDbUser = '';
  private $sDbPwd = '';
  private $sDbName = '';
  private $sDbPort = '';
  
  private $iLastInsertId;

  private $iErrorTryCount = 0; // pocitadlo pokusu o znovu odeslani dotazu, pokud je error: deadlock found
  

  private function __construct($sInstanceName = null, $sDbServer = null, $sDbUser = null, $sDbPwd = null, $sDbName = null, $sDbPort = null, $sFile=__FILE__, $sLine=__LINE__)
  {
    self::$iNumOfInstances++;
    if (is_null($sInstanceName)) $sInstanceName = md5(microtime());
    self::$sInstanceName = $sInstanceName;
    //echo 'self::$sInstanceName='.self::$sInstanceName.'<br />';
    //self::$sInstanceName = md5(microtime());

    // soubor pro logovani
    if (self::$bLogQueriesPerInstance == true && self::$bLogQueriesPerInstanceToDB == true) 
    {
      if (is_writable(dirname(__FILE__).'/../files/cache'))
        $this->sLogQueriesFile = dirname(__FILE__).'/../files/cache/'.md5(time()).rand(1,999999);
    }
    
    $this->setFieldTypes();
    
    $this->sLogQueriesPerInstance = dirname(__FILE__).'/log/_instance_'.(self::$sInstanceName).'.txt';
    $this->sLogTimesFile          = dirname(__FILE__).'/log/_times.txt';
    $this->sLogPath               = dirname(__FILE__).'/log/';

    
    if (is_null($sDbServer)) $sDbServer = DB_SERVER;
    if (is_null($sDbUser))   $sDbUser = DB_USER;
    if (is_null($sDbPwd))    $sDbPwd = DB_PWD;
    if (is_null($sDbName))   $sDbName = DB_NAME; // kdyz nezadam databazi, tak se chci jen pripojit k serveru, a ne ze mi to mysql trida prepise
    if (is_null($sDbPort))   $sDbPort = defined('DB_PORT') ? DB_PORT : 3306;

    // nastaveni promenych tridy pro napr. odpojeni database a nasledne pripojeni
    $this->sDbServer = $sDbServer;
    $this->sDbUser = $sDbUser;
    $this->sDbPwd = $sDbPwd;
    $this->sDbName = $sDbName;    
    $this->sDbPort = $sDbPort;    

    try
    {
      $this->_connect($this->sDbServer, $this->sDbUser, $this->sDbPwd, $this->sDbPort);
      if ($this->sDbName != '') $this->selectDB($this->sDbName);
    }
    catch (FatalException $e) {}
  }


  /*
   *  metoda pro přístup ke sdílené instanci aplikace
   * @return class MySQL
   */
  public static function getInstance($instanceName = 'default', $sDbServer = null, $sDbUser = null, $sDbPwd = null, $sDbName = null, $sDbPort = null, $sFile=__FILE__, $sLine=__LINE__)
  {
    $sClassName=__CLASS__;
    if (!isset(self::$instance[$instanceName]))
    {
      // jestlize je logQueriesPerInstance == true, tak musim si zajistit datum pro ulozeni do DB
      if (!self::$iTimeForLogQueriesPerInstance) self::$iTimeForLogQueriesPerInstance = time();
      
      self::$instance[$instanceName] = new $sClassName($instanceName, $sDbServer, $sDbUser, $sDbPwd, $sDbName, $sDbPort, $sFile, $sLine);
    }

    return self::$instance[$instanceName];
  }


  /**
   * pripoji se k Mysql serveru
   * @param STRING $server
   * @param STRING $user
   * @param STRING $pwd
   * @return BOOL
   */
  private function _connect($server, $user, $pwd, $port)
  {
    if (!$link = @mysqli_connect($server, $user, $pwd, null, $port)) 
    {
      $aErrorFileLineInfo = $this->getFileLineInfoNotMysql(debug_backtrace());
      throw new FatalException(mysqli_connect_error()."\nFile:".$aErrorFileLineInfo['file']." \nLine:".$aErrorFileLineInfo['line'], 'Can not connect to DB');
    }
    else 
    {
      $this->link = $link;
      return true;
    }
  }
  
  
  /**
   * @param array $aDebugBacktrace
   */
  private function getFileLineInfoNotMysql($aDebugBacktrace)
  {
    if ($aDebugBacktrace) foreach ($aDebugBacktrace as $val)
    {
      if (stristr($val['file'], 'mysql')) continue;
      return array('file' => $val['file'], 'line' => $val['line']);
    }
    return array('file' => 'undefined', 'line' => 'undefined');
  }
  


  /**
   * vybere databazi pro pripojeny mysql server
   * @param STRING $sDBName
   */
  private function selectDB($sDBName)
  {    
    if (!@mysqli_select_db($this->link, $sDBName))    
    {
      $aErrorFileLineInfo = $this->getFileLineInfoNotMysql(debug_backtrace());
      throw new FatalException('Database '.$sDBName.' does not exists.'."\nFile:".$aErrorFileLineInfo['file']." \nLine:".$aErrorFileLineInfo['line']);
    }
    else
    {
      $this->query('SET NAMES utf8;', __FILE__, __LINE__);
      $this->query('SET CHARACTER SET "utf8"', __FILE__, __LINE__);

      $this->bDbConected = true;
    }
    
  }


########## SETTERY #################
  
  public function setShowErrorOnScreen($bool)
  {
    if (!$bool) $this->bShowErrorAdvanceMessage = false;
    else $this->bShowErrorAdvanceMessage = true;
  }


  public function setLongQueriesTimeLimitForLog($float)
  {
    $float=$float+0;
    if ($float<=0) $this->fLongQueriesTimeLimitForLog=5;
    else $this->fLongQueriesTimeLimitForLog=$float;
  }


  public function setLogInsertUpdateDeleteQueries($bool)
  {
    if ($bool===true OR $bool==1 or $bool=="true") $this->bLogInsertUpdateDeleteQueries=true;
    else $this->bLogInsertUpdateDeleteQueries=false;
  }


  public static function setLogQueriesPerInstance($bool)
  {
    if ($bool===true || strtolower($bool)=='true' || $bool==1 || $bool=='1') self::$bLogQueriesPerInstance = true;
    else self::$bLogQueriesPerInstance = false;
  }
  
  public static function setLogQueriesPerInstanceToDB($bool)
  {
    if ($bool===true || strtolower($bool)=='true' || $bool==1 || $bool=='1') self::$bLogQueriesPerInstanceToDB = true;
    else self::$bLogQueriesPerInstanceToDB = false;
  }
  
  public static function setLogQueriesPerInstanceDBAnalysisHash($sHash)
  {
    self::$sLogQueriesPerInstanceDBAnalysisHash = $sHash;
  }
  
  public static function createLogQueriesTimeInstance()
  {
    self::$iTimeForLogQueriesPerInstance = time();
    return self::$iTimeForLogQueriesPerInstance;
  }

###########################################################

  /**
   * posle dotaz na server MySQL
   *@param STRING       - SQL dotaz
   *@param STRING|BOOL  - název souboru, odkud je volána tato funkce, nebo BOOL hodnota, ktera se bude chovat stejne jako 4 parametr teto funkce
   *@param STRING       - číslo řádku, odkud je volána tato funkce
   *@param BOOL         - zobrazit dotaz
   *@return mixed       - sadu výsledků po volání dotazu MYSQL nebo pokud nastala chyba, tak vrací FALSE
   */
  public function query($sql, $file = null, $line = null, $bShowQuery = FALSE)
  {
    $aBackTrace = debug_backtrace();

    // zajistim, aby kdyz je druhy parameter BOOL, tak se jedna o bShowQuery
    if (is_bool($file))
    {
      $bShowQuery = $file;
      $file = null;
    }
    if (!$file) $file = $aBackTrace[0]['file'];
    if (!$line) $line = $aBackTrace[0]['line'];

    $time_start = microtime(true);
    $sPlainSql = $sql;
    $sql = '-- file:'.str_replace(realpath(dirname(__FILE__).'/../'), '', $file).' line:'.$line." \r\n".
           $sql;

    $sql = $this->repairSqlQuery($sql);


    try
    {
      // pokud neni vubec MySQL pripojen, tak vyhodim chybu
      if ($this->link === false) throw new MySQLWarningException ('SQL not connected. Connect to mysql first.', $this->bShowErrorAdvanceMessage, 'SQL not connected', $file, $line);
      if (!$res = mysqli_query($this->link, $sql))
      {
        $sError = mysqli_error($this->link);
        
        // pokud bude Deadlock, tak zkusim jeste nekolikrat, teprve pak vyhodim chybu
        if (stristr($sError, 'Deadlock found when trying') && $this->iErrorTryCount < 5)
        {
          $this->iErrorTryCount++;
          sleep(2);
          return $this->query($sql, $file, $line, $bShowQuery);
        }
        
        if (!strstr(strtolower($sql), 'rollback')) $this->query('rollback');
        $this->bStartTransaction = false;
        throw new MySQLWarningException ($sql."\n".'ERROR: '.($this->bDbConected === false ? 'Not connected to any database. Choose database first' : '').' '.$sError.($this->iErrorTryCount ? "\n Pokusil se o $this->iErrorTryCount x, nez vyhodil vyjimku." : ''), $this->bShowErrorAdvanceMessage, 'Chyba dotazu', $file, $line);
      }
    }
    catch(MySQLWarningException $e)
    {
      $this->iErrorTryCount = 0;
    }
    $this->iErrorTryCount = 0;
    
    // musim si zajistit INSERT ID, protoze kdyz mam zapnute logovani, tak insert id volane ve skriptu mi vlastne vrati INSERT ID toho logu
    $this->iLastInsertId = @mysqli_insert_id($this->link);
    
    $time_end = microtime(true);

    $time = $time_end - $time_start;
    self::$fTotalTimeOfQueries += $time;
    self::$iTotalQueries++;


    // zaloguju dotazy, ktere trvaji moc dlouho
    if ($time > $this->fLongQueriesTimeLimitForLog)
    {
      $fp = fopen($this->sLogTimesFile, 'ab');
      $s = date("Y-m-d H:i:s")."\n".
           'URL: '.$_SERVER['REQUEST_URI']."\n".
           'QUERY TIME: '.$time.'s'."\n".
           'FILE: '.$file."\n".
           'LINE: '.$line."\n".
           'SQL: '.$sql."\n".
           '----------------------------------------'."\n\n";
      @fwrite($fp, $s);
      @fclose($fp);
    }


    // zaloguju inserty a delety
    if ($this->bLogInsertUpdateDeleteQueries === true && (1 == 2 || preg_match('/\binsert |update |replace |truncate |drop |delete \b/i', $sql)))
    {
      $logFile = str_replace(realpath(dirname(__FILE__).'/../'), '', $file);
      $logFile = str_replace('/', '#', $logFile);
      $logFile = str_replace('\\', '#', $logFile);
      $logFile = preg_replace('/^#/', '', $logFile);
      $logFile = $this->sLogPath . $logFile . '.txt';

      $fp = fopen($logFile, 'ab');
      $s = date("Y-m-d H:i:s")."\n".
           's_auth_id='.(isset($_SESSION) && isset($_SESSION['s_auth_id']) ? $_SESSION['s_auth_id'] : '')."\n".
           'URL: '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".
           'QUERY TIME: '.$time.'s'."\n".
           'FILE: '.$file."\n".
           'LINE: '.$line."\n".
           'SQL: '.$sql."\n".
           '----------------------------------------'."\n\n";
      @fwrite($fp, $s);
      @fclose($fp);
    }

    // zaloguju dotazy v ramci jedne instance
    if (self::$bLogQueriesPerInstance === true)
    {
      if (self::$bLogQueriesPerInstanceToDB === true && $this->sLogQueriesFile)
      {
        if (!$this->fLogQueryFile)
        {
          $this->fLogQueryFile = fopen($this->sLogQueriesFile, 'w');
        }
        if ($this->fLogQueryFile)
        {
          $aDebug = array(); $xDebug = 0;
          foreach ($aBackTrace as $aBackTraceTemp)
          {
            $aDebug[$xDebug]['file'] = $aBackTraceTemp['file'];
            $aDebug[$xDebug]['line'] = $aBackTraceTemp['line'];
            $aDebug[$xDebug]['function'] = $aBackTraceTemp['function'];
            $aDebug[$xDebug]['class'] = $aBackTraceTemp['class'];
            $aDebug[$xDebug]['args'] = $aBackTraceTemp['args'];
            $xDebug++;
          }

          $sLogSql = '('.self::$iTimeForLogQueriesPerInstance.',
                        "'.addslashes($sPlainSql).'",
                        '.$line.',
                        "'.addslashes($file).'",
                        '.$time.',
                        "'.date('Y-m-d H:i:s').'",
                        "'.self::$sLogQueriesPerInstanceDBAnalysisHash.'",
                        "'.addslashes(serialize($aDebug)).'"
                       )
                        ';
          $sqlLog = 'INSERT INTO _log_sql_per_instance (instance_timestamp, `sql`, `line`, `file`, `time`, date_insert, analysis_hash, `debug`) VALUES '.$sLogSql;
          @fwrite($this->fLogQueryFile, $sqlLog."#:nextrow:#");
          //mysqli_query($this->link, $sqlLog);
        }
      }
      else
      {
        $s = date("Y-m-d H:i:s")."\n".
             'URL: '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')."\n".
             'QUERY TIME: '.$time.'s'."\n".
             'FILE: '.$file."\n".
             'LINE: '.$line."\n".
             'SQL: '.$sql."\n".
             '----------------------------------------'."\n\n";
        $fp = fopen($this->sLogQueriesPerInstance, 'ab');
        @fwrite($fp, $s);
        @fclose($fp);
      }
    }

    // ukazu dotaz
    if ($bShowQuery === true)
    {
      echo '<hr /><pre class="mysql-query">'.htmlspecialchars($sql).'</pre><hr />';
    }

    return $res;
  }

#######################################
###  OPRAVNA FUNKCE PRO BUG MySQL
#######################################
  private function repairSqlQuery($sql)
  {
    if (strstr(strtolower($sql), 'match') && strstr(strtolower($sql), 'against'))
    {
      $sql2 = $this->cs_utf2ascii($sql);
      $sql2 = preg_replace("/[^\\a-zA-Z0-9 \"!\?@'`\-\+\*\.,\/(){}\[\]\n_<>=%\r\t\f\e]*/i", '', $sql2);
      if ($sql2 != $sql)
      {
        /*$fp = @fopen(REAL_PATH.'log/MySQL_bug_query_log.txt', 'a');
        if ($fp)
        {
          fwrite($fp, "\n##################################\ndate: ".date('Y-m-d H:i:s')."\n"."Puvodni: ".$sql."\nUpraveno: ".$sql2."\n\n");
          fclose($fp);
        } */
        return $sql2;
      }
    }
    return $sql;
  }


  private function cs_utf2ascii($s)
  {
    static $tbl = array("\xc3\xa1"=>"a","\xc3\xa4"=>"a","\xc4\x8d"=>"c","\xc4\x8f"=>"d","\xc3\xa9"=>"e","\xc4\x9b"=>"e","\xc3\xad"=>"i","\xc4\xbe"=>"l","\xc4\xba"=>"l","\xc5\x88"=>"n","\xc3\xb3"=>"o","\xc3\xb6"=>"o","\xc5\x91"=>"o","\xc3\xb4"=>"o","\xc5\x99"=>"r","\xc5\x95"=>"r","\xc5\xa1"=>"s","\xc5\xa5"=>"t","\xc3\xba"=>"u","\xc5\xaf"=>"u","\xc3\xbc"=>"u","\xc5\xb1"=>"u","\xc3\xbd"=>"y","\xc5\xbe"=>"z","\xc3\x81"=>"A","\xc3\x84"=>"A","\xc4\x8c"=>"C","\xc4\x8e"=>"D","\xc3\x89"=>"E","\xc4\x9a"=>"E","\xc3\x8d"=>"I","\xc4\xbd"=>"L","\xc4\xb9"=>"L","\xc5\x87"=>"N","\xc3\x93"=>"O","\xc3\x96"=>"O","\xc5\x90"=>"O","\xc3\x94"=>"O","\xc5\x98"=>"R","\xc5\x94"=>"R","\xc5\xa0"=>"S","\xc5\xa4"=>"T","\xc3\x9a"=>"U","\xc5\xae"=>"U","\xc3\x9c"=>"U","\xc5\xb0"=>"U","\xc3\x9d"=>"Y","\xc5\xbd"=>"Z");
    return strtr($s, $tbl);
  }



###############################
  function transaction()
  {
    $this->query('START TRANSACTION');
    $this->bStartTransaction = true;
  }
  
  function commit()
  {
    $this->query('COMMIT');
    $this->bStartTransaction = false;
  }
  
  function rollback()
  {
    $this->query('ROLLBACK');
    $this->bStartTransaction = false;
  }
  
  function isTransaction() {return $this->bStartTransaction;}
  
  
  public function escape_string($str)
  {
    return @mysqli_real_escape_string($this->link, $str);
  }
  
  
  function fetch_array($res, $sType = MYSQLI_ASSOC)
  {
    if (is_object($res) && is_a($res, 'MySQLQueryStack'))
      return $res->fetch();
    else 
      return @mysqli_fetch_array($res, $sType);
  }

  function data_seek($res, $iPos)
  {
    if (is_object($res) && is_a($res, 'MySQLQueryStack'))
      return $res->data_seek($iPos);
    else 
      return @mysqli_data_seek($res, $iPos);
  }

  function fetch_row($res)
  {
    return @mysqli_fetch_row($res);
  }

  function fetch_assoc($res)
  {
    return @mysqli_fetch_assoc($res);
  }

  function num_rows($res)
  {
    if (is_object($res) && is_a($res, 'MySQLQueryStack'))
      return $res->num_rows();
    else 
      return @mysqli_num_rows($res);
  }

  function insert_id()
  {
    //return @mysqli_insert_id($this->link);
    return $this->iLastInsertId;
  }

  function num_fields($res)
  {
    return mysqli_num_fields($res);
  }

  function fetch_fields($res)
  {
    return @mysqli_fetch_fields($res);
  }

  function list_tables($db)
  {
    if ($this->link)
    {
      $sql = 'show tables';
      $res = $this->query($sql);
      $a = array();
      while ($data = $this->fetch_array($res, MYSQLI_NUM))
        $a[] = $data[0];
      return $a;
    }
    else return false;
  }

  function affected_rows()
  {
    return @mysqli_affected_rows($this->link);
  }

  function free_result($result)
  {
    mysqli_free_result ($result);
  }

  /**
   * vrati vysledek jednoho radku z daneho SQL dotazu
   * @param STRING $sql
   * @param STRING $sFile
   * @param STRING $sLine
   * @param STRING $sType 
   */
  function queryOne($sql, $sFile = null, $sLine = null, $sType = MYSQLI_ASSOC)
  {
    $res = $this->query($sql, $sFile, $sLine);
    $data = $this->fetch_array($res, $sType);
    return $data;
  }

  
  public function close()
  {
    @mysqli_close($this->link);
    $this->link = false;
    $this->bDbConected = false;
    return true;
  }


  public function connect()
  {
    try
    {
      $this->_connect($this->sDbServer, $this->sDbUser, $this->sDbPwd, $this->sDbPort);
      if ($this->sDbName != '') $this->selectDB($this->sDbName);
    }
    catch (FatalException $e) {}
  }


  public function fieldInformationForQuery ($sql, $sFile = __FILE__, $sLine = __LINE__)
  {
    $res = $this->query($sql, $sFile, $sLine);
    
    $aFieldInfo = array();
    $oFieldInfo = mysqli_fetch_fields($res);
    foreach ($oFieldInfo as $val)
    {
      $sTempName = $val->name;
      $iType = $val->type;
      $aFieldInfo[$sTempName] = $val;
      $aFieldInfo[$sTempName]->fieldType = $this->aFieldTypes[$iType];
      $aFieldInfo[$sTempName]->fieldFlags = $this->getFieldFlags($val);
    }
    return $aFieldInfo;
  }
  

  public static function getTotalTimeOfQueries ()
  {
    return self::$fTotalTimeOfQueries;
  }


  public static function getNumberOfInstances()
  {
    return self::$iNumOfInstances;
  }


  public static function getNumberOfQueries()
  {
    return self::$iTotalQueries;
  }


  private function setFieldTypes()
  { 
    $typeAr = array();
    $typeAr[MYSQLI_TYPE_DECIMAL]     = 'real';
    $typeAr[MYSQLI_TYPE_NEWDECIMAL]  = 'real';
    $typeAr[MYSQLI_TYPE_BIT]         = 'bool';
    $typeAr[MYSQLI_TYPE_TINY]        = 'int';
    $typeAr[MYSQLI_TYPE_SHORT]       = 'int';
    $typeAr[MYSQLI_TYPE_LONG]        = 'int';
    $typeAr[MYSQLI_TYPE_FLOAT]       = 'real';
    $typeAr[MYSQLI_TYPE_DOUBLE]      = 'real';
    $typeAr[MYSQLI_TYPE_NULL]        = 'null';
    $typeAr[MYSQLI_TYPE_TIMESTAMP]   = 'timestamp';
    $typeAr[MYSQLI_TYPE_LONGLONG]    = 'int';
    $typeAr[MYSQLI_TYPE_INT24]       = 'int';
    $typeAr[MYSQLI_TYPE_DATE]        = 'date';
    $typeAr[MYSQLI_TYPE_TIME]        = 'time';
    $typeAr[MYSQLI_TYPE_DATETIME]    = 'datetime';
    $typeAr[MYSQLI_TYPE_YEAR]        = 'year';
    $typeAr[MYSQLI_TYPE_NEWDATE]     = 'date';
    $typeAr[MYSQLI_TYPE_ENUM]        = 'unknown';
    $typeAr[MYSQLI_TYPE_SET]         = 'unknown';
    $typeAr[MYSQLI_TYPE_TINY_BLOB]   = 'blob';
    $typeAr[MYSQLI_TYPE_MEDIUM_BLOB] = 'blob';
    $typeAr[MYSQLI_TYPE_LONG_BLOB]   = 'blob';
    $typeAr[MYSQLI_TYPE_BLOB]        = 'blob';
    $typeAr[MYSQLI_TYPE_VAR_STRING]  = 'string';
    $typeAr[MYSQLI_TYPE_STRING]      = 'string';
    $this->aFieldTypes = $typeAr;    
  }
  

  /**
   * - return an ARRAY of human readable flags of a specified field
   * @param OBJECT $oField - field object
   * @return ARRAY
   */
  private function getFieldFlags($oField)
  {
    // This is missing from PHP 5.2.5, see http://bugs.php.net/bug.php?id=44846
    if (! defined('MYSQLI_ENUM_FLAG'))
    {
        define('MYSQLI_ENUM_FLAG', 256); // see MySQL source include/mysql_com.h
    }
    
    $type = $oField->type;
    $charsetnr = $oField->charsetnr;
    $f = $oField->flags;
    $flags = array();
    if ($f & MYSQLI_UNIQUE_KEY_FLAG)     { $flags[] = 'unique';}
    if ($f & MYSQLI_NUM_FLAG)            { $flags[] = 'num';}
    if ($f & MYSQLI_PART_KEY_FLAG)       { $flags[] = 'part_key';}
    if ($f & MYSQLI_SET_FLAG)            { $flags[] = 'set';}
    if ($f & MYSQLI_TIMESTAMP_FLAG)      { $flags[] = 'timestamp';}
    if ($f & MYSQLI_AUTO_INCREMENT_FLAG) { $flags[] = 'auto_increment';}
    if ($f & MYSQLI_ENUM_FLAG)           { $flags[] = 'enum';}
    // See http://dev.mysql.com/doc/refman/6.0/en/c-api-datatypes.html:
    // to determine if a string is binary, we should not use MYSQLI_BINARY_FLAG
    // but instead the charsetnr member of the MYSQL_FIELD
    // structure. Watch out: some types like DATE returns 63 in charsetnr
    // so we have to check also the type.
    // Unfortunately there is no equivalent in the mysql extension.
    if (($type == MYSQLI_TYPE_TINY_BLOB || $type == MYSQLI_TYPE_BLOB || $type == MYSQLI_TYPE_MEDIUM_BLOB || $type == MYSQLI_TYPE_LONG_BLOB || $type == MYSQLI_TYPE_VAR_STRING || $type == MYSQLI_TYPE_STRING) && 63 == $charsetnr)                { $flags[] = 'binary';}
    if ($f & MYSQLI_ZEROFILL_FLAG)       { $flags[] = 'zerofill';}
    if ($f & MYSQLI_UNSIGNED_FLAG)       { $flags[] = 'unsigned';}
    if ($f & MYSQLI_BLOB_FLAG)           { $flags[] = 'blob';}
    if ($f & MYSQLI_MULTIPLE_KEY_FLAG)   { $flags[] = 'multiple_key';}
    if ($f & MYSQLI_UNIQUE_KEY_FLAG)     { $flags[] = 'unique_key';}
    if ($f & MYSQLI_PRI_KEY_FLAG)        { $flags[] = 'primary_key';}
    if ($f & MYSQLI_NOT_NULL_FLAG)       { $flags[] = 'not_null';}
    return $flags;
  }

  
  private function createLogTableInDB()
  {
    $res = $this->query('SHOW TABLES');
    while ($data = $this->fetch_array($res, MYSQLI_NUM))
    {
      if ($data['0'] == '_log_sql_per_instance')
      {
        return true;
      }
    }
    
    $sql = '
    CREATE TABLE `_log_sql_per_instance` (
      `id` INT(10) NOT NULL AUTO_INCREMENT,
      `instance_timestamp` INT(10) NOT NULL COMMENT "U jedne instance je tohle cislo staticke.",
      `date_insert` DATETIME NOT NULL,
      `sql` TEXT NOT NULL,
      `line` INT NOT NULL,
      `file` VARCHAR(255) NOT NULL,
      `debug` TEXT NOT NULL,
      `analysis_hash` VARCHAR(255) NOT NULL,
      `time` double NOT NULL COMMENT "jak dlouho dotaz bezel",
      PRIMARY KEY (`id`),
      INDEX `analysis_hash` (`analysis_hash`),
      INDEX `hash_timestamp` (`analysis_hash`, `instance_timestamp`)
      ) COLLATE="utf8_czech_ci" ENGINE=myisam ROW_FORMAT=DEFAULT';
    $this->query($sql);
  }
  
  
  public static function truncateLogTable()
  {
    $o = MySQL::getInstance();
    if (self::$bLogQueriesPerInstance && self::$bLogQueriesPerInstanceToDB)
    {
      $o->query('TRUNCATE table _log_sql_per_instance');
    }
  }
  
  
  public function regenerateAllCache()
  {
    $sql = 'SELECT id, `sql` 
              FROM _sql_cache
              WHERE is_auto_refresh="1"';
    $res = $this->query($sql);
    while ($data = $this->fetch_array($res)) 
    {
      set_time_limit(60);
      $mysqlRes = $this->query($data['sql']);
      
      $oMySqlQueryStack = new MySQLQueryStack();
      while ($data2 = $this->fetch_array($mysqlRes))
      {
        $oMySqlQueryStack->addRow($data2);
      }
      $sql = 'UPDATE _sql_cache SET
                sql_result_cache="'.addslashes($oMySqlQueryStack->getSerializedData()).'",
                date_insert=NOW()
              WHERE id='.$data['id'];
      $this->query($sql);
    }
  }
  
  
  public function queryCacheAutoRefresh($sql, $file = null, $line = null, $bShowQuery = false)
  {
    $this->bQueryCacheAutoRefresh = true;
    return $this->queryCache($sql, $file, $line, $bShowQuery);
    $this->bQueryCacheAutoRefresh = false;
  }
  
  
  public function queryCacheRewrite($sql, $file = null, $line = null, $bShowQuery = false)
  {
    $this->bQueryCacheForceRewrite = true;
    return $this->queryCache($sql, $file, $line, $bShowQuery);
    $this->bQueryCacheForceRewrite = false;
  }
  
  
  public function queryCache($sql, $file = null, $line = null, $bShowQuery = FALSE)
  {
    $aBackTrace = debug_backtrace();
    if (is_bool($file))
    {
      $bShowQuery = $file;
      $file = null;
    }
    if (!$file) $file = $aBackTrace[0]['file'];
    if (!$line) $line = $aBackTrace[0]['line'];
    
    $res = false;
    if (!$this->bQueryCacheForceRewrite) $res = $this->setDataFromCache($sql);
    if (!$res)
    {
      $iTime = microtime(true);
      $res = $this->query($sql, $file, $line, $bShowQuery);
      $iDuration = round(microtime(true) - $iTime, 4);
      
      if ($res && $this->num_rows($res) < self::$iMaxRowsLengthForCache && $this->isSelectQuery($sql))
      {
        $oMySqlQueryStack = $this->createCache($res, $sql, $iDuration, $file, $line);
        $res = $oMySqlQueryStack;
      }
    }
    return $res;
  }

  
  private function createCache($mysqlRes, $sqlForSave, $iDuration, $file, $line)
  {
    $oMySqlQueryStack = new MySQLQueryStack();
    while ($data = $this->fetch_array($mysqlRes))
    {
      $oMySqlQueryStack->addRow($data);
    }
    
    $sql = 'INSERT IGNORE INTO _sql_cache SET
              sql_query_hash="'.md5($sqlForSave).'",
              `sql`="'.addslashes($sqlForSave).'",
              duration_query='.$iDuration.',
              sql_result_cache="'.addslashes($oMySqlQueryStack->getSerializedData()).'",
              date_insert=NOW(),
              is_auto_refresh="'.($this->bQueryCacheAutoRefresh ? 1 : 0).'",
              `file`="'.addslashes($file).'",
              `line`='.($line+0);
    if (!$this->query($sql))
    {
      if (!isset($this->bTableCacheCreated))
      {
        $this->createCacheTable();
        $this->bTableCacheCreated = true;
        $this->createCache($mysqlRes, $sqlForSave, $iDuration, $file, $line);
      }
      //else exit('Error, table sql_cache not created');
    }
    
    return $oMySqlQueryStack;
  }
  
  
  private function setDataFromCache($sql)
  {
    $sSqlHash = md5($sql);
    $sql = 'SELECT sql_result_cache FROM _sql_cache WHERE sql_query_hash="'.myaddslashes($sSqlHash).'"';
    $res = $this->query($sql);
    if (!$res)
    {
      if (!isset($this->bTableCacheCreated))
      {
        $this->createCacheTable();
        $this->bTableCacheCreated = true;
        $this->setDataFromCache($sql);
      }
      //else exit('Error, table sql_cache not created');
    }
    
    $data = $this->fetch_array($res);
    if ($data)
    {
      $oDataQueryStack = new MySQLQueryStack($data['sql_result_cache']);
      return $oDataQueryStack;
    }
    return false;
  }
  
  
  private function isSelectQuery($sql)
  {
    $sql = str_replace("\n", ' ', $sql);
    $sql = mb_strtolower($sql, 'utf-8');
    if (preg_match('/^ *select.*/', $sql)) return true;
    else return false;
  }
  
  
  private function createCacheTable()
  {
    $res = $this->query('SHOW TABLES');
    while ($data = $this->fetch_array($res, MYSQLI_NUM))
    {
      if ($data['0'] == '_sql_cache')
      {
        return true;
      }
    }
    
    $sql = "
    CREATE TABLE `_sql_cache` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`sql_query_hash` VARCHAR(255) NOT NULL,
	`sql` TEXT NOT NULL,
	`duration_query` DOUBLE UNSIGNED NOT NULL,
	`sql_result_cache` LONGTEXT NOT NULL,
	`date_insert` DATETIME NOT NULL,
	`file` VARCHAR(255) NOT NULL,
	`line` INT UNSIGNED NOT NULL,
	`is_auto_refresh` ENUM('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `sql_query_hash` (`sql_query_hash`),
	INDEX `is_auto_refresh` (`is_auto_refresh`),
	INDEX `date_insert` (`date_insert`)
          )
          COLLATE='utf8_general_ci'
          ENGINE=InnoDB;

        ";
    $this->query($sql);
  }

  
  public function clearCache()
  {
    $bIs = false;
    $res = @mysqli_query($this->link, 'SHOW TABLES');
    while ($data = @mysqli_fetch_array($res, MYSQLI_NUM))
    {
      if ($data['0'] == '_sql_cache')
      {
        $bIs = true;
      }
    }
    if ($bIs)
    {
      $sql = 'TRUNCATE TABLE _sql_cache';
      @mysqli_query($this->link, $sql);
    }
  }
  
  
  private function cleanUpCacheDatabase()
  { 
    $bIs = false;
    $res = @mysqli_query($this->link, 'SHOW TABLES');
    while ($data = @mysqli_fetch_array($res, MYSQLI_NUM))
    {
      if ($data['0'] == '_sql_cache')
      {
        $bIs = true;
      }
    }
    if ($bIs)
    {
      $sql = 'DELETE FROM _sql_cache 
                WHERE date_insert<DATE_ADD(NOW(), INTERVAL - '.self::$iCacheDurationInHours.' HOUR)
                  AND is_auto_refresh="0"';
      @mysqli_query($this->link, $sql);
      
      $sql = 'DELETE FROM _sql_cache 
                WHERE date_insert<DATE_ADD(NOW(), INTERVAL - '.self::$iCacheDurationInHoursForAutoRefreshCache.' HOUR)
                  AND is_auto_refresh="1"';
      @mysqli_query($this->link, $sql);
    }
  }
  
  
  function __destruct()
  {
    if ($this->fLogQueryFile)
    {
      fclose($this->fLogQueryFile);
      $s = @file_get_contents($this->sLogQueriesFile);
      $a = @explode('#:nextrow:#', $s);
      if ($a) foreach ($a as $val)
      {
        if ($val) 
        {
          if (!@mysqli_query($this->link, $val))
          {
            $this->createLogTableInDB();
            @mysqli_query($this->link, $val);
          }
          
        }
      }
      unlink($this->sLogQueriesFile);
      
      // smazu stara data
      $sql = 'DELETE FROM _log_sql_per_instance
                WHERE date_insert<DATE_ADD(NOW(), INTERVAL - 20 MINUTE)';
      @mysqli_query($this->link, $sql);
    }
    if (date('s') == 30) $this->cleanUpCacheDatabase();
    if ($this->link) @mysqli_close($this->link);
  }
}



class MySQLQueryStack
{
  private $data = array();
  private $iterator = 0;
          
  public function __construct($sSerializedData = null)
  {
    if ($sSerializedData) $this->data = unserialize($sSerializedData);
  }
  
  
  public function fetch()
  {
    if (isset($this->data[$this->iterator]))
      return $this->data[$this->iterator++];
    else return null;
  }
  
 
  public function data_seek($i)
  {
    $this->iterator = $i;
  }
  
  
  public function num_rows()
  {
    return count($this->data);
  }
  
  
  public function addRow($row)
  {
    $this->data[] = $row;
  }
  
  
  public function getSerializedData()
  {
    return serialize($this->data);
  }
  
}

