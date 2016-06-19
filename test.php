<?php

use rdx\http\HTTP;

require 'autoload.php';

header('Content-type: text/plain');

$request = HTTP::create('https://api.github.com/gists/public');
$response = $request->request();

// print_r($response->response);

print_r($response->cookies);
print_r($response->cookies_by_name);

echo $response->head . "\n\n";
print_r($response->headers);

echo $response->head . "\n\n";
var_dump($response->code, $response->status);

// $response->prePlain(function($body) {
// 	return preg_replace('#<tr[^>]*>#', ' ----tr---- ', $body);
// });
// echo $response->plain;

// print_r($response);
