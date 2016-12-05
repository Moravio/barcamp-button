<?php
/**
 * Call by slack when OAuthor autenticating
 */
require_once('include.php');

$code = $_GET['code'];

$api = new SlackApi();
$api->setAccessTokenFromCode($code);
echo 'Access code saved - run "php index.php"';

