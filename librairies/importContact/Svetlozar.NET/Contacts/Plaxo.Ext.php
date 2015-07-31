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
class PlaxoExtAuth extends SPContactsExtAuth
{
	public $contacts_url 	= "http://www.plaxo.com/pdata/contacts/@me/@all";

	protected $url_key 		= "plaxo/urls";
	protected $auth_key 	= "plaxo/oauth";

	public function __get($name)
	{
		return parent::__get($name);
	}

	function ParseContactsData()
	{
		$parts = explode('"id"', $this->RawSource);
		foreach($parts as $v)
		{
			if (preg_match('/emails":\[{"value":"([^"]*).*?name":{"formatted":"((?:[^"]*(?:(?<=\\\\)")?)*?)"/si', $v, $matches))
			{
				$name = SPUtils::decode_html_escaped($matches[2]);
				$email = $matches[1];

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