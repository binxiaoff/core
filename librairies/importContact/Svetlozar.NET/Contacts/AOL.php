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

require_once 'SPContacts.base.php';

class AOL extends SPContacts
{
	protected function GetContactsData()
	{
		$this->client->curl_user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";

		if (strpos($this->username, "@aol.com") || strpos($this->username, "@aim.com"))
		{
			$this->username = current(explode("@", $this->username));
		}

		if ((isset($this->username) && trim($this->username)=="") || (isset($this->password) && trim($this->password)==""))
		{
			$this->Error = ContactsResponses::ERROR_NO_USERPASSWORD;
			return false;
		}

		//! attempt login
		if(!$this->client->get("https://my.screenname.aol.com/_cqr/login/login.psp?mcState=initialized&seamless=novl&sitedomain=sns.webmail.aol.com&lang=en&locale=us&authLev=2&siteState=ver%3a2%7cac%3aWS%7cat%3aSNS%7cld%3awebmail.aol.com%7cuv%3aAOL%7clc%3aen-us"))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		$url = "https://my.screenname.aol.com/_cqr/login/login.psp";

		if (preg_match('/<form name="AOLLoginForm".*?action="([^"]*).*?<\/form>/si', $this->client->http_response_body, $matches))
		{
			$url = $matches[1];
			preg_match_all('/<input type="hidden" name="([^"]*)" value="([^"]*)".*?>/si', $matches[0], $matches);
			$params = array_combine($matches[1], $matches[2]);
		}
		else
		{
			//! ok not quite unknown, could not find the login form and without the login parms the login won't work and will return misleading error
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		$params['loginId'] = $this->username;
		$params['password'] = $this->password;

		if (!$this->client->post($url, SPUtils::join_key_values_assoc("=", "&", SPUtils::array_map_assoc("rawurlencode", $params))))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		# check if login passed
		if(!preg_match("/'loginForm', 'false', '([^']*)'/si", $this->client->http_response_body, $matches))
		{
			#return error if it's not
			$this->Error = ContactsResponses::ERROR_INVALID_LOGIN;
			return false;
		}

		$url = $matches[1];

		if (!$this->client->get($url))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		if (preg_match('/gTargetHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si',  $this->client->http_response_body, $matches) || preg_match('/gPreferredHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si', $this->client->http_response_body, $matches))
		{
			$url = "http://$matches[1]$matches[2]";
		}
		else
		{
			if(preg_match("/'loginForm', 'false', '([^']*)'/si",  $this->client->http_response_body, $matches))
			{
				$this->client->get($matches[1]);
				$url = $this->client->http_response_location;
			}

			if (preg_match('/gTargetHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si',  $this->client->http_response_body, $matches) || preg_match('/gPreferredHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si',  $this->client->http_response_body, $matches))
			{
				$url = "http://$matches[1]$matches[2]";
			}

                        if(preg_match('/gSuccessURL\s?=\s?"([^"]+)/si',  $this->client->http_response_body, $matches))
			{
				$url = $matches[1];
			}
		}

		$opturl = $url;

		//!get settings:
		$opturl = explode("/", $opturl);
		$opturl[count($opturl)-1] = "common/settings.js.aspx";
		$opturl = implode("/", $opturl);

		$this->client->get($opturl);

		$opturl = explode("/", $url);
		$opturl[count($opturl)-1]="AB";
		$opturl = implode("/", $opturl);

		$version = $this->client->get_simple_cookie("Version");
		$auth = $this->client->get_simple_cookie("Auth");
		$usr = "";

		if (preg_match('/"UserUID":"([^"]*)/si', $this->client->http_response_body, $matches) || preg_match('/uid:([^&]*)/si', $auth, $matches))
		{
			$usr = $matches[1];
		}

		#get the address book:
		$opturl .= "/addresslist-print.aspx?command=all&undefined&sort=LastFirstNick&sortDir=Ascending&nameFormat=FirstLastNick&version=$version&user=$usr";

		if (!$this->client->get($opturl))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}
		else
		{
			$this->RawSource = $this->client->http_response_body;
			return true;
		}
	}

	protected function ParseContactsData()
	{
		$m = explode("contactSeparator", $this->RawSource);

		$data = array_map(null, array_map(array("AOL", "parse_emails"), $m), array_map(array("AOL", "parse_names"), $m));

		while (list($email, $name) = current($data))
		{
			if ($email != "")
			{
				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}

			next($data);
		}

		if (!$this->ContactsCount)
		{
			$this->Error = ContactsResponses::ERROR_NO_CONTACTS;
			return false;
		}

		return true;
	}

	static function parse_emails($str)
	{
		if(preg_match('/(?>Email).*?([^@<>]+@[^<]+)/si', $str, $matches))
			return trim($matches[1]);
		else
			return "";
	}

	static function parse_names($str)
	{
		if( preg_match('/fullName[^>]*>(.*?)<[^>]*>([^<]*)/si', $str, $matches) )
			return trim($matches[1]);
		else
			return "";
	}
}
?>