<?php
require_once(__DIR__.'/cCurlTools.php');

$MESSAGE_URL = 'https://hooks.slack.com/services/TTT/BBB/HHH';

$option['CURLOPT_HTTPHEADER'] = 'Content-Type: application/json';

$data['text'] = 'cmd light off';

$res = cCurlTools::get($MESSAGE_URL, null, json_encode($data));
