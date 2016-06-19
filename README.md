HTTP
====

Super simple, very imcomplete, very small wrapper around CURL.

If you need decent cookie handling, use Guzzle etc.

Request
----

**GET**

	use rdx\http\HTTP;

	$request = HTTP::create('https://api.github.com/gists/public');
	$response = $request->request();

**POST**

	use rdx\http\HTTP;

	HTTP::$_agent = 'Some custom user agent string 1.0';

	$request = HTTP::create('https://api.github.com/gists/public', array(
		'method' => 'POST',
		'data' => array('foo' => 'bar'),
		'headers' => array(
			'Authorization: Basic abc',
		),
		'cookies' => array(
			array('name', 'value'),
		),
	));
	$response = $request->request();

Response
----

	var_dump($response->code);   // 200
	var_dump($response->status); // OK

	print_r($response->cookies_by_name);
