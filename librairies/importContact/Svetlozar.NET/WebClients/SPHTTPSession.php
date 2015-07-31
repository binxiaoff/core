<?php
/// -----------------------------------------------------------------------------
/// Copyright 2006-2010, Svetlozar Petrov. All rights reserved.
/// http://svetlozar.net
///
/// Confidential and Proprietary, Not For Public Release.
///
/// No redistribution allowed without prior permission (see licence for details)
/// The above notice must remain unmodified in every source code file
/// -----------------------------------------------------------------------------

require_once('SPHTTPClient.php');
/**
 * @author Svetlozar Petrov http://svetlozar.net
 * A cookie collection wrapper on top of SPHTTPClient, cookie handling is done automatically for each request/redirect
 */
class SPHTTPSession extends SPHTTPClient
{
	//! format will be: array[hosts][paths][secure][cookie_name]=>cookie value
	protected 	$__cookies;

	//! no domain/path/secure info on this one, just plain cookiename => cookievalue array
	protected	$__simple_cookie;

	/**
	 * Initialize cookies and curl options (if provided, both are optional)
	 * @param $cookies
	 * @param $curlopts
	 */
	function __construct($cookies = null, $curlopts = null)
	{
		$this->reset_cookies_state($cookies);
		parent::__construct($curlopts);
	}

	/**
	 * Clear cookies and reset curl options
	 * @see SPHTTPClient#reset_state()
	 */
	function reset_state($cookies = null)
	{
		$this->reset_cookies_state($cookies);
	}

	/**
	 * Return all stored cookies (no serialization done)
	 * @return array
	 */
	function get_all_cookies()
	{
		return $this->__cookies;
	}

	/**
	 * Return the current cookies serialized (can be used to later restore a cookie collection to a previous state)
	 * @return string
	 */
	function get_cookies_state()
	{
		return serialize($this->__cookies);
	}

	/**
	 * Reset cookies to previous or empty state
	 */
	function reset_cookies_state($cookies)
	{
		$this->__cookies = ($cookies && is_array($cookies)) ? $cookies : ((($c = unserialize($cookies)) && $is_array($c)) ? $c : array());
		$this->__simple_cookie = array();

		ksort($this->__cookies, SORT_STRING);

		foreach($this->__cookies as $h => $p)
		{
			ksort($this->__cookies[$h], SORT_STRING);
			foreach($this->__cookies[$h] as $p => $s)
			{
				if (isset($s[true]))
				{
					$this->__simple_cookie = array_merge($cookies, $s[true]);
				}

				if (isset($s[false]))
				{
					$this->__simple_cookie = array_merge($cookies, $s[false]);
				}
			} //!< end for each path
		} //!< end for each host
	}

	/**
	 * Set cookie expects the contents of a Set-Cookie header and the host that returned Set-Cookie
	 * @param string $cookie_header - set-cookie header string to parse
	 * @param string $host - host that returned the set-cookie header
	 * @param bool $https_response - set the flag to true if the Set-Cookie header was received over https
	 */
	function set_cookie($cookie_header, $host, $https_response = false)
	{
		//! parse cookie, add it to the collection
		$cookie = "";
		$value = "";

		if (preg_match("/^([^=]+)=([^; ]*)/si", $cookie_header, $matches))
		{
			$cookie = $matches[1];
			$value = $matches[2];
		}
		else
		{
			return; //!< couldn't find cookie name/value
		}

		$host = preg_match("/domain=([^; ]*)/si", $cookie_header, $matches) ? $matches[1] : $host;
		$host = trim(strrev($host), ". "); //!< internally host will be stored as a reversed string (makes cookie lookup much more efficient)
		$path = preg_match("/path=([^; ]*)/si", $cookie_header, $matches) ? $matches[1] : "/";
		$expired = ((strpos($cookie_header, "Max-Age=0") > 0) ||
						(strpos($cookie_header, "Discard") > 0 ) ||
							(preg_match("/expires=.*?(\d{4})/si", $cookie_header, $matches) && ((int) date("Y") > (int) $matches[1])));
		$secure = (strpos($cookie_header, " secure") > 0);

		if ($expired && ($secure && $https_response || !$secure) && isset($this->__cookies[$host][$path][$secure][$cookie]))
		{
			unset($this->__cookies[$host][$path][$secure][$cookie]);
			if (!$this->__cookies[$host][$path][$secure])
			{
				unset($this->__cookies[$host][$path][$secure]);
				if(!$this->__cookies[$host][$path])
				{
					unset($this->__cookies[$host][$path]);
					if(!$this->__cookies[$host])
					{
						unset($this->__cookies[$host]);
					}
				}
			}

			unset($this->__simple_cookie[$cookie]);
		}
		else if (!$expired)
		{
			$this->__cookies[$host][$path][$secure][$cookie] = $value;
			$this->__simple_cookie[$cookie] = $value;
		}
	}

	/**
	 * Return a simple cookie value
	 * @param $cookie_name
	 * @return string - cookie value
	 */
	function get_simple_cookie($cookie_name)
	{
		if (isset($this->__simple_cookie[$cookie_name]))
		{
			return $this->__simple_cookie[$cookie_name];
		}

		return null;
	}

	/**
	 * Build and return cookie header string for the given url
	 * @param $url
	 */
	function get_cookie($url)
	{
		$url_parts = parse_url($url);
		$host = strrev($url_parts["host"]);
		$path = isset($url_parts["path"]) ? $url_parts["path"] : "/";
		$secure = (strtolower($url_parts["scheme"]) == "https");

		$cookies = array();
		ksort($this->__cookies, SORT_STRING);

		foreach($this->__cookies as $h => $p)
		{
			if (strpos($host, $h) === 0)
			{
				ksort($this->__cookies[$h], SORT_STRING);
				foreach($this->__cookies[$h] as $p => $s)
				{
					if (strpos($path, $p) === 0)
					{
						if ($secure)
						{
							if (isset($s[true]))
							{
								$cookies = array_merge($cookies, $s[true]);
							}
						}

						if (isset($s[false]))
						{
							$cookies = array_merge($cookies, $s[false]);
						}
					}

				} //!< end for each path
			}
		} //!< end for each host

		return SPUtils::join_key_values_assoc("=", "; ", $cookies);
	}

	/**
	 * Parse cookies from the response headers and set cookies directly to the curl resource if redirect is done within curl
	 */
	protected function handle_response_headers($ch)
	{
		parent::handle_response_headers($ch);

		if(($set_cookie_headers = array_keys($this->http_response_headers[0], "Set-Cookie")))
		{
			foreach ($set_cookie_headers as $header_index)
			{
				$this->set_cookie($this->http_response_headers[1][$header_index], strtolower($this->__request_url_parts["host"]), strtolower($this->__request_url_parts["scheme"]) == "https");
			}

			if ($this->__redirect && $this->auto_redirect_curl && ($cookie = $this->get_cookie($this->__next_location)) )
			{
				curl_setopt($ch, CURLOPT_COOKIE, $cookie);
			}
		}
	}

	/**
	 * Intercept set request options to add cookies for the current request
	 */
	protected function set_request_options()
	{
		# __next_location is the effective request url
		if ($cookie = $this->get_cookie($this->__next_location))
			$this->__curl_opts[CURLOPT_COOKIE] = $cookie;
		parent::set_request_options();
	}

}
?>