<?php

namespace rdx\http;

class HTTP {

	static public $_agent = 'Super ultra fast super HTTP browser';

	public $curl;
	public $url = '';
	public $method = 'GET';
	public $data = array();
	public $headers = array();
	public $auth;
	public $cookies = null;
	public $agent = '';
	public $redirects = 0;

	public function __construct( $url = '' ) {
		$this->curl = curl_init();
		$this->url = $url;
	}

	public function request() {
		$ch = $this->curl;

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		if ( $this->headers ) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
		}

		if ( $this->agent ?: static::$_agent ) {
			curl_setopt($ch, CURLOPT_USERAGENT, $this->agent ?: static::$_agent);
		}

		if ( $this->redirects ) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $this->redirects);
			curl_setopt($ch, CURLOPT_COOKIEFILE, '');
		}

		if ( $this->cookies ) {
			$cookies = $this->cookies;
			if ( is_array($cookies) ) {
				$cookies = implode('; ', array_map(function($cookie) {
					return urlencode($cookie[0]) . '=' . urlencode($cookie[1]);
				}, $cookies));
			}
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		}

		if ( $this->auth ) {
			list($user, $pass) = $this->auth;
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
		}

		if ( $this->data ) {
			$data = $this->data;
			is_string($data) or $data = http_build_query($this->data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		$raw = curl_exec($ch);
		$this->info = curl_getinfo($ch);
		$response = $this->createResponse($raw, $this->info);
		curl_close($ch);

		return $response;
	}

	public function createResponse($raw, $info) {
		return new HTTPResponse($raw, $info);
	}

	static public function create($url, $options = array()) {
		if ( is_array($url) ) {
			$options = $url;
			$url = @$options['url'];
		}

		isset($url) && $options['url'] = $url;

		$http = new static;

		foreach ( $options AS $name => $value ) {
			$http->$name = $value;
		}

		return $http;
	}

}
