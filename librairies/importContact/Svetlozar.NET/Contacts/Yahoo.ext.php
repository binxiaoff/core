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

/**
 * Most magic moved to SPContactsExtAuth
 * @author Svetlozar Petrov
 */
class YahooExtAuth extends SPContactsExtAuth
{
	public $contacts_url 	= ""; //!< format http://social.yahooapis.com/v1/user/{guid}/contacts

	protected $url_key 		= "yahoo/urls";
	protected $auth_key 	= "yahoo/oauth";

	public function __get($name)
	{
		return parent::__get($name);
	}

	public function GetContacts()
	{
		$result = parent::GetContacts();
		if ($this->client->auth_oauth_response == SPOAuthClient::OAUTH_USER_AUTH)
		{
			$this->UserAuthorizationUrl = $this->client->xoauth_request_auth_url;
		}

		return $result;
	}

	function GetContactsData()
	{
		$this->client->include_in_state[] = "xoauth_yahoo_guid";
		$this->client->include_in_state[] = "oauth_session_handle";

		$result = false;
		$url = "";

		if ($this->client->oauth_token && !$this->client->__oauth_access_token)
		{
			$this->client->get_access_token();
			$this->client->__oauth_access_token = "true";
			$this->client->oauth_expires_in = null;
			$this->client->oauth_authorization_expires_in = null;
		}

		if ($this->client->xoauth_yahoo_guid)
		{
			$url = "http://social.yahooapis.com/v1/user/{$this->client->xoauth_yahoo_guid}/contacts;out=name,nickname,email,yahooid,otherid;bucket=0;maxbucketsize=9999;minbucketcount=1;";
		}

		if ($url)
		{
			$result =  $this->client->execute_request(SPConstants::HTTP_METHOD_GET, $url) ;
		}
		else
		{
			$this->client->get_request_token();
			$this->client->oauth_expires_in = null;
			return false;
		}


		if (!$result || $this->client->auth_oauth_response == SPOAuthClient::OAUTH_USER_AUTH)
		{
			return false;
		}
		else
		{
			$this->RawSource = $this->client->http_response_body;
			return true;
		}
	}

	function ParseContactsData()
	{
		preg_match_all('#<contact[^>]*>(.*?)</contact>#si', $this->RawSource, $contacts);
		foreach($contacts[0] as $c)
		{
			if (preg_match_all('#<fields[^>]*>.*?<type>(.*?)</type>.*?<value>(.*?)</value>.*?</fields>#si', $c, $fields))
			{
				$name = $email = "";
				for($i=0; $i<count($fields[1]); $i++)
				{
					switch($fields[1][$i])
					{
						case "name":
							preg_match_all("#<[^>]*>(.*?)</[^>]*>#si", $fields[2][$i], $name_parts);
							$name = join(" ", $name_parts[1]);
							break;
						case "email":
						case "otherid":
							$email = $email ? $email : (strpos($fields[2][$i], "@") ? $fields[2][$i] : "");
							break;
						case "yahooid":
						case "nickname":
							$name = $name ? $name : $fields[2][$i];
							break;
					}
				}

				$name = SPUtils::decode_html_escaped($name);

				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}
		}

		if ($this->client->auth_url_revoke)
		{
			$this->client->get($this->client->auth_url_revoke);
			$this->client->__oauth_access_token = null;
			$this->client->oauth_token = null;
			$this->client->oauth_token_secret = null;
		}

		if ($this->ContactsCount)
		{
			return true;
		}

		$this->Error = ContactsResponses::ERROR_NO_CONTACTS;
		return false;
	}
}
?>