<?php
/**
 * Server script.
 *
 * Echoes the id passed by the client. If the client also passes delay chunks, then
 * the server will echo a chunk, then delay for determined number of seconds, etc.
 */

$callId = empty($_GET['id']) ? uniqid() : $_GET['id'];
$chunkDelays = empty($_GET['chunkDelays']) ? array(0) : $_GET['chunkDelays'];

foreach ($chunkDelays as $chunkId=>$chunkDelay) {
	echo date('Y-m-d H:i:s').' - Server Call ['.$callId.'], chunk '.$chunkId.': Will sleep for '.$chunkDelay." seconds...\n";
	ob_flush();
	sleep($chunkDelay);
}
	echo date('Y-m-d H:i:s').' - Server Call ['.$callId."] done\n";
exit(0);