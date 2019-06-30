<?php

namespace rdx\http;

class HTTPResponse {

	public $code = 0;
	public $status = '';

	public $raw = '';
	public $info = null;

	public $pre_plain;

	static public $_headRegex = '#HTTP/\d+\.\d+\s+(\d+)\s+(.+)#i';
	static public $_response = array(
		'application/json' => '_parseJson',
		'text/json' => '_parseJson',
	);

	public $head;
	public $body;

	// public $head = '';
	// public $code = 0;
	// public $status = '';
	// public $headers = null;
	// public $cookies = null;

	// public $body = '';
	// public $plain = '';
	// public $response = null;

	public function __construct( $raw, $info = null ) {
		$this->raw = $raw;
		$this->info = $info;

		$this->code = $this->getCode();
		$this->status = $this->getStatus();
	}

	public function &__get( $name ) {
		$this->$name = null;

		$function = 'get' . str_replace('_', '', $name);
		if (method_exists($this, $function)) {
			$this->$name = call_user_func(array($this, $function));
		}

		return $this->$name;
	}



	protected function makeComponents() {
		if ($this->head !== null) return;

		$lines = explode("\n", $this->raw);
		$lastHeadStart = 0;
		$firstBreak = 0;
		foreach ($lines as $n => &$line) {
			$line = trim($line);

			if (strpos($line, 'HTTP/') === 0) {
				$lastHeadStart = $n;
				$firstBreak = 0;
			}

			if ($firstBreak == 0 && $line == '') {
				$firstBreak = $n;
			}

			unset($line);
		}

		$this->head = trim(implode("\n", array_slice($lines, $lastHeadStart, $firstBreak - $lastHeadStart)));
		$this->body = trim(implode("\n", array_slice($lines, $firstBreak)));
	}

	public function getHead() {
		$this->makeComponents();
		return $this->head;
	}

	public function getBody() {
		$this->makeComponents();
		return $this->body;
	}



	public function getStatus() {
		if ( preg_match(static::$_headRegex, $this->head, $match)) {
			return trim($match[2]);
		}
	}

	public function getCode() {
		if ( preg_match(static::$_headRegex, $this->head, $match)) {
			return (int)$match[1];
		}
	}

	public function getHeaders() {
		$lines = array_slice(explode("\n", $this->head), 1);

		$headers = array();
		foreach ( $lines AS $i => $line ) {
			$x = explode(':', trim($line), 2);
			$headers[strtolower($x[0])][] = trim($x[1]);
		}

		return $headers;
	}

	public function getCookies() {
		$cookies = array();
		if ( isset($this->headers['set-cookie']) ) {
			foreach ( $this->headers['set-cookie'] as $cookie ) {
				if ( preg_match('/([^=]+)=([^;]*)[;\s]*(.+)/', trim($cookie), $match) ) {
					$properties = array();
					if ( $propsplit = preg_split('#\s*;\s*#', $match[3]) ) {
						$properties = array_reduce($propsplit, function($list, $prop) {
							list($name, $value) = explode('=', $prop . '=');
							return $list += array($name => $value);
						}, array());
					}

					if ( !isset($properties['expires']) || strtotime($properties['expires']) > time() + 5 ) {
						list(, $name, $value) = $match;
						$cookies[] = array($name, urldecode($value));
					}
				}
			}
		}

		return $cookies;
	}

	public function getCookiesByName() {
		$cookies = array();
		foreach ( $this->cookies as $cookie ) {
			$cookies[$cookie[0]][] = $cookie[1];
		}

		return $cookies;
	}



	public function getResponse() {
		$response = (string) $this->getBody();

		if ( isset($this->headers['content-type'][0]) ) {
			$x = explode(';', $this->headers['content-type'][0]);

			if ( isset(static::$_response[$x[0]]) ) {
				$functions = (array)static::$_response[$x[0]];
				foreach ($functions as $function) {
					$response = call_user_func(array($this, $function));
					if ( $response !== false ) {
						return $response;
					}
				}
			}
		}

		return false;
	}

	public function _parseJson() {
		return @json_decode(trim($this->getBody()), true);
	}



	public function prePlain( $callback ) {
		$this->pre_plain = $callback;
	}

	public function getPlain() {
		$body = $this->getBody();

		// Only what's inside <body>
		if ( preg_match('#<body[^>]*>([\s\S]+)</body>#', $body, $match) ) {
			$body = $match[1];
		}

		// Remove <script> and <style>
		$body = preg_replace('#<(script|style)[^>]*>([\s\S]*?)</\1>#', '', $body);

		// Optional one-time pre_plain
		if ( $this->pre_plain && is_callable($this->pre_plain) ) {
			$body = call_user_func($this->pre_plain, $body);
		}

		return trim(str_replace('&nbsp;', ' ', strip_tags($body)));
	}



	public function __tostring() {
		return $this->raw;
	}

}
