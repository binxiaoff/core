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

class Plaxo extends SPContacts
{
	public $contacts_url 	= "http://www.plaxo.com/pdata/contacts/@me/@all";

	public function __get($name)
	{
		return parent::__get($name);
	}

	protected function GetContactsData()
	{
		$this->client->request_headers["Authorization"] = "Basic ". base64_encode("{$this->username}:{$this->password}");
		if($this->client->get($this->contacts_url))
		{
			$this->RawSource = $this->client->http_response_body;
			return true;
		}
		else
		{
			if ($this->client->http_response_code == 401)
			{
				$this->Error = ContactsResponses::ERROR_INVALID_LOGIN;
			}
			else
			{
				$this->Error = ContactsResponses::ERROR_UNKNOWN;
			}
			return false;
		}

	}

	protected function ParseContactsData()
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

		if ($this->ContactsCount)
		{
			return true;
		}

		$this->Error = ContactsResponses::ERROR_NO_CONTACTS;
		return false;
	}
}
?>