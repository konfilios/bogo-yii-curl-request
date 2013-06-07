<?php
/**
 * Client script calling the server ones.
 */

header('Content-type: text/plain; charset=utf8');

$serverUrl = 'http://localhost/bogo-yii-curl-request/tests/server.php';

// Or, using an anonymous function as of PHP 5.3.0
spl_autoload_register(function ($class) {
    include '../components/'.$class. '.php';
});

$responseObject = CBHttpMessageRequest::create('GET', $serverUrl)
		->setGetParams(array(
			'chunkDelays' => array(1, 2)
		))
		->createCall()
		->setTimeoutSeconds(20)
		->exec()->validateStatus()->getRawBody();

print($responseObject);