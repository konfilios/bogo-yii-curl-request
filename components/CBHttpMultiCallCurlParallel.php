<?php
/**
 * Parallel cURL multi-call.
 *
 * @since 2.0
 * @package Components
 * @author Konstantinos Filios <konfilios@gmail.com>
 */
class CBHttpMultiCallCurlParallel extends CBHttpMultiCall
{
	/**
	 * Construct.
	 *
	 * @param array $requestObjects
	 * @throws Exception
	 */
	function __construct(array $requestObjects)
	{
		foreach ($requestObjects as $key => $requestObject) {
			// Add every input object in queue

			if ($requestObject instanceof CBHttpMessageRequest) {
				// Request message, wrap it in a call
				$this->setCall($key, new CBHttpCallCurl($requestObject));

			} else if ($requestObject instanceof CBHttpCallCurl) {
				// A Curl call, keep it as is
				$this->setCall($key, $requestObject);

			} else {
				throw new CBHttpCallException('Unexpected request object of type '.get_class($requestObject));
			}
		}
	}

	/**
	 * Execute all calls.
	 *
	 * @return CBHttpMultiCallCurlParallel
	 */
	public function exec()
	{
		//
		// Create get requests for each URL
		//
		$multiHandle = curl_multi_init();

		$calls = $this->getCalls();

		foreach ($calls as $key => $curlCall) {
			/* @var $curlCall CBHttpCallCurl */

			// Open the handle
			$curlHandles[$key] = $curlCall->curlInit();

			// Add it to the set
			curl_multi_add_handle($multiHandle, $curlHandles[$key]);
		}


		//
		// Start performing the request
		//
		$this->startTimer();

		$runningHandles = 0;
		do {
			$execReturnValue = curl_multi_exec($multiHandle, $runningHandles);
		} while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);

		//
		// Loop and continue processing the requests
		//
		while ($runningHandles && ($execReturnValue == CURLM_OK)) {
			// Wait forever for network
			$numberReady = curl_multi_select($multiHandle);

			if ($numberReady != -1) {
				// Pull in any new data, or at least handle timeouts
				do {
					$execReturnValue = curl_multi_exec($multiHandle, $runningHandles);
				} while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
			}
		}
		$this->stopTimer();

		//
		// Clean-up
		//
		foreach ($calls as $key => $curlCall) {
			/* @var $curlCall CBHttpCallCurl */

			// Remove the handle
			curl_multi_remove_handle($multiHandle, $curlHandles[$key]);

			// Close it
			$curlCall->curlClose($curlHandles[$key]);
		}

		// Clean up the curl_multi handle
		curl_multi_close($multiHandle);

		// Check for any errors
		$this->errorCode = $execReturnValue;

		return $this;
	}
}