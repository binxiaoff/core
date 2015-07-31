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
require_once SVETLOZARNET_WEBCLIENTS. 'GoogleLoginClient.php';

class Gmail extends SPContacts
{
	public static $contacts_url 	= "http://www.google.com/m8/feeds/contacts/default/property-email?max-results=9999";

	public function __get($name)
	{
		return parent::__get($name);
	}

	/**
	 * Initializes Gmail
	 * Params are all optional, if provided only the first 4 will be considered in the following order (as array of strings):
	 * @param $username
	 * @param $password
	 * @param $captcha - only if required
	 * @param $state - required together with captcha, must be the string returned by GetState() when ERROR_CAPTCHA_REQUIRED is returned
	 */
	public function __construct()
	{
		$options = func_get_args();
		$this->SetOptions($options);
		$this->client = new GoogleLoginClient();
	}


	public function GetState()
	{
		return $this->client->GetState();
	}

	public function RestoreState($state)
	{
		return $this->client->RestoreState($state);
	}

	protected function GetContactsData()
	{
		$this->client->Authenticate($this->username, $this->password, $this->captcha);
		if (!$this->client->Authenticated())
		{
			switch($this->client->LoginResponse)
			{
				case GoogleAuthResponses::BadAuthentication:
					$this->Error = ContactsResponses::ERROR_INVALID_LOGIN;
					break;
				case GoogleAuthResponses::CaptchaRequired:
					$this->Error = ContactsResponses::ERROR_CAPTCHA_REQUIRED;
					$this->CaptchaUrl = $this->client->CaptchaUrl;
					break;
				default:
					$this->Error = ContactsResponses::ERROR_UNKNOWN;
					break;
			}
			return false;
		}

		if($this->client->get(self::$contacts_url))
		{
			$this->RawSource = $this->client->http_response_body;
			$this->client->Reset();
			return true;
		}
		else
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

	}

	protected function ParseContactsData()
	{
		$parts = explode('<entry>', $this->RawSource);
		foreach($parts as $v)
		{
			if (preg_match("/(?:<title type='text'>)([^<]*)<.*?(?:<gd:email)[^>]*?address='([^']+)'/si", $v, $matches))
			{
				$name = $matches[1];
				$email = $matches[2];

				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}
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