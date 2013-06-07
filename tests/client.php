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

$chunkDelaySets = array(
	array(1, 2),
	array(3, 1),
	array(2, 4),
	array(1, 1),
	array(5, 0),
	array(2, 2, 2),
	array(1, 3, 1),
	array(1, 1, 1, 1, 1)
);

//
// Assemble array of calls
//
$calls = array();
$serialDelayTime = 0;
$parallelDelayTime = 0;
foreach ($chunkDelaySets as $chunkDelays) {
	// Calculate delays
	$callDelayTime = array_sum($chunkDelays);
	$serialDelayTime += $callDelayTime;
	$parallelDelayTime = max($callDelayTime, $parallelDelayTime);

	// Create the call. Set 1 second buffer for timeout
	$calls[] = CBHttpMessageRequest::create('GET', $serverUrl)
		->setGetParam('chunkDelays', $chunkDelays)
		->createCall()
		->setTimeoutSeconds($callDelayTime + 1);
}

//
// Warn the user before executing
//
echo "Prepared ".count($calls)." calls with expected delay times:\n";
echo " - Serial execution: ".$serialDelayTime." sec\n";
echo " - Parallel execution: ".$parallelDelayTime." sec\n\n";

//
// Perform the multi-call
//
$multiCall = new CBHttpMultiCallCurlParallel($calls);

foreach ($multiCall->exec()->getResponseMessages() as $key=>$responseMessage) {
	/* @var $responseMessage CBHttpMessageResponse */
	$responseObject = $responseMessage->validateStatus()->getRawBody();

	print($responseObject."\n");
}

//
// Dump results
//
print("All calls executed in ".$multiCall->getExecutionSeconds()." sec\n");
