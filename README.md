bogo-yii-curl-request
=====================

This extension offers HTTP request abstraction as well as cURL implementations.

The API is based on three main entities:
- `CBHttpMessageRequest`: HTTP Request Message
- `CBHttpMessageResponse`: HTTP Response Message
- `CBHttpCall`: The transport layer sending a request and retrieve a response message

On top of these, there's the CBHttpMultiCall which offers batch (parallel or serial) execution
of calls.

## Usage scenaria

### A simple call

This is what a simple `GET` request against a given `$uri` would look like:

```php
$responseObject =
		// Request Message
		CBHttpMessageRequest::create('GET', $uri)
		// Call
		->createCall()
		->exec()
		// Response Message
		->getRawBody();
```

What you see above is a chaing among the three different types of objects, and of course it's
not the only way to do it:
- You start by creating a request message (`create()`). This returns a `CBHttpMessageRequest`.
- You wrap the request message in a call (`createCall()`). This returns a `CBHttpCall`.
- You execute the call (`exec()`). This returns a `CBHttpMessageResponse`.
- You retrieve the body of the response message (`getRawBody()`).

Note that in case something fails during the `exec()` phase, a `CBHttpCallException` will be
thrown with all the details.

### Enhancing the request message

Most of the times you'll probably need more than the HTTP method and the URI for a request message.

There's a number of chainable setter methods you may use to add features to your request, such as:
- Headers: `setHeader()` and `setHeaders()`
- Upload Files: `setFile()` and `setFiles()`
- GET Parameters: `setGetParam()` and `setGetParams()`
- POST Parameters: `setPostParam()` and `setPostParams()`
- Cookies: `setCookie()`
- Raw body: `setRawBody()`

Here's an example with a request in which you set a few parameters and set a JSON `$requestData` payload:

```php
$responseObject =
		// Request Message
		CBHttpMessageRequest::create('POST', $uri)
		->setHeader('Content-type', 'application/json')		// Set a header
		->setGetParams(array(								// Set GET params
			'id' => 230,
			'title' => 'Custom Title'
		))
		->setRawBody(json_encode($requestData))				// Set a body for your message
		// Call
		->createCall()
		->exec()
		// Response Message
		->getRawBody();
```

### Configuring the transport layer

Now, let's take a look at the options you have for configuring the transport layer:
- Timeout: You can set a timeout for the call using `setTimeoutSeconds()`
- Debug mode: You may want debug info for a call not working as you wish, using `setInDebugMode()`

```php
$responseObject =
		// Request Message
		CBHttpMessageRequest::create('POST', $uri)
		// Call
		->createCall()
		->setTimeoutSeconds(5.37)		// Communication timeout
		->setInDebugMode(true)			// Require extra debug info
		->exec()
		// Response Message
		->getRawBody();
```

### Validating the response message

While everything might go fine with the network communication, you might still get an HTTP
error code from the server. In that case you will probably not have any raw body.

Experience shows that in such cases you'd prefer to get an exception for HTTP status codes
indicating an error. You can do this by using the `validateStatus()` message of the response
message, which will either throw an exception for HTTP status codes above 400, otherwise it will
chain back to the response message for the usual retrieval of its body or anything else.

Furthermore, we'll ask the response message to convert its raw body in an XML object before
returning it.

```php
$responseObject =
		// Request Message
		CBHttpMessageRequest::create('POST', $uri)
		// Call
		->createCall()
		->exec()
		// Response Message
		->validateStatus()				// Throws an exception for HTTP CODE >= 400
		->getBodyAsXml();				// Returns SimpleXML representation of raw body
```

### More information from the response message

A response message comes with much more data than the body it wraps, and which we've retrieved
using `getRawBody`.

The server might have returned headers and cookies you're interested in. In such a case, of course,
you must break the fluid style of method calls:


```php
// Now we get the response message instead of the body it wraps
$responseMessage =
		// Request Message
		CBHttpMessageRequest::create('POST', $uri)
		->setRawBody(json_encode($requestData))
		// Call
		->createCall()
		->exec();

// Retrieve object wrapped in response message
$responseObject= $responseMessage->validateStatus()->getRawBody();

// Retrieve cookies or headers returned by server
$myResponseHeader = $responseMessage->getHeader('some-interesting-header');
$myImportantCookie = $responseMessage->getCookie('i_need_this_cookie');
```

### Need to log info about the call itself

Usually you won't need to access the `CBHttpCall` object that links the request with the response.
Unless you're doing some serious logging or profiling or you've asked for some debug info.
In that case, you'll have to break the chain again:

```php
// Now we get the response message instead of the body it wraps
$httpCall =
		// Request Message
		CBHttpMessageRequest::create('POST', $uri)
		// Call
		->createCall()
		->setTimeoutSeconds(5.37)
		->setInDebugMode(true);

// Get response object
$responseObject = $httpCall->exec()
		// Response Message
		->validateStatus()
		->getBodyAsXml();

// Let's see how much time it took the request to complete and any debug info
echo $httpCall->getExecutionSeconds();
print_r($httpCall->getDebugInfo());

```

## Multi-calls

cURL allows you to execute parallel calls. The interface we're using here is the following:
- First construct an array of calls, exactly as you saw before
- Then wrap this array into a `CBHttpMultiCall` and `exec()` it
- After all calls are executed, you're ready to retrieve your responses through `getResponseMessages()`

Here's an example:

```php
//
// Prepare the list of calls. This is a dummy example hitting your localhost index file 100 times.
//
$calls = array();
for ($i = 0; $i < 100; $i++) {

	// Create the call. Set 1 second buffer for timeout
	$calls[] = CBHttpMessageRequest::create('GET', 'http://localhost/')
		->createCall()
		->setTimeoutSeconds(2.0);
}

//
// Perform the multi-call
//
$multiCall = new CBHttpMultiCallCurlParallel($calls);

foreach ($multiCall->exec()->getResponseMessages() as $key=>$responseMessage) {
	/* @var $responseMessage CBHttpMessageResponse */
	$responseObject = $responseMessage->validateStatus()->getRawBody();

	print($responseObject."\n");
}

print("All calls executed in ".$multiCall->getExecutionSeconds()." sec\n");

```