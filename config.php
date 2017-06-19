<?php
error_reporting (E_ALL ^E_DEPRECATED ^E_NOTICE);
define('SERVER_NAME', 'barcamp');
define('REAL_PATH', realpath(dirname(__FILE__)).'/');

date_default_timezone_set(date_default_timezone_get());

/*******************************************************************************
pristup k databazi
*******************************************************************************/

define('DB_NAME',   'barcamp');
define('DB_SERVER', 'localhost');
define('DB_USER',   '[here-user]');
define('DB_PWD',    '[here-password]');
define('DB_PORT',   '3307');


define('EXCEPTION_SET_NOT_SEND_EXCEPTION_TO_GLOBAL_REPORTING', true); // pokud TRUE, tak se nebude posilat do globalniho reportingu, standardne FALSE
define('EXCEPTION_SET_SHOW_ERROR_MESSAGE', true); //zobrazovat popis chyby , standardne FALSE
define('EXCEPTION_SET_ERROR_MAIL_ADDRESS', '[here-email]'); // mail pro zasilani chyb
