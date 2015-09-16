<?php
class bootstrap extends Controller
{
	var $Command;


	function bootstrap($command,$config,$app,$debug=false)
	{
		parent::__construct($command,$config,$app,$debug);

		// Mise en session de l'url demandée pour un retour si deconnecté sauf pour la fonction login du controller root
		if($this->current_function != 'login') { $_SESSION['request_url'] = $_SERVER['REQUEST_URI']; }


		// Chargements des librairies
		$this->dates = $this->loadLib('dates');
		$this->ficelle = $this->loadLib('ficelle');
		$this->upload = $this->loadLib('upload');
		$this->photos = $this->loadLib('photos',array($this->spath,$this->surl));
		$this->Web2Pdf = $this->loadLib('Web2Pdf');


		// Chargements des datas
		$this->ln = $this->loadData('textes');
		$this->settings = $this->loadData('settings');
		$this->tree_elements = $this->loadData('tree_elements');
		$this->blocs_elements = $this->loadData('blocs_elements');
		$this->blocs = $this->loadData('blocs');
		$this->elements = $this->loadData('elements');
		$this->tree = $this->loadData('tree',array('url'=>$this->lurl,'front'=>$this->Config['url'][$this->Config['env']]['default'],'surl'=>$this->surl,'tree_elements'=>$this->tree_elements,'blocs_elements'=>$this->blocs_elements,'upload'=>$this->upload,'spath'=>$this->spath,'path'=>$this->path));
		$this->users = $this->loadData('users',array('config'=>$this->Config, 'lurl'=>$this->lurl));
		$this->users_zones = $this->loadData('users_zones');
		$this->routages = $this->loadData('routages',array('url'=>$this->lurl));
		$this->users_history = $this->loadData('users_history');


		// Chargements des datas pour la gestion des emailings
		$this->mails_filer = $this->loadData('mails_filer');
		$this->mails_text = $this->loadData('mails_text');
		$this->nmp = $this->loadData('nmp');
		$this->nmp_desabo = $this->loadData('nmp_desabo');


		// Chargements des librairies
		$this->tnmp = $this->loadLib('tnmp',array($this->nmp,$this->nmp_desabo,$this->Config['env']));

		// Recuperation des variables NMP
		$this->settings->get('NMP Server API','type');
		$this->serveur_api = $this->settings->value;

		$this->settings->get('NMP Key','type');
		$this->key_api = $this->settings->value;

		$this->settings->get('NMP Login','type');
		$this->login_api = $this->settings->value;

		$this->settings->get('NMP Password','type');
		$this->pwd_api = $this->settings->value;

		$this->settings->get('NMP Mail','type');
		$this->mail_api = $this->settings->value;

		$this->settings->get('NMP From Mail','type');
		$this->frommail_api = $this->settings->value;

		$this->settings->get('NMP ID Clonage','type');
		$this->id_clone_nmp = $this->settings->value;

		//gestion du captcha
		if(isset($_POST["captcha"])){

			if (isset($_SESSION["captcha"]) && $_SESSION["captcha"]==$_POST["captcha"]){
				$content_captcha = 'ok';
			}
			else{

				$content_captcha = 'ko';
				$this->displayCaptchaError = 'Captcha incorrecte';
			}
		}

		// on log ici toutes les connexions
		if(isset($_POST['connect']) && $_POST["login"] != "" && $_POST["password"] != "")
		{

			if(isset($_POST["captcha"]) && $content_captcha == "ko"){
				$_SESSION['login_user']['displayCaptchaError'] = $this->displayCaptchaError;
			}
			else{

				$this->loggin_connection_admin = $this->loadData('loggin_connection_admin');
				$user = $this->users->login($_POST["login"],$_POST["password"]);
				if($user != false)
				{
					// On enregistre la connexion
					$this->loggin_connection_admin->id_user = $user['id_user'];
					$this->loggin_connection_admin->nom_user = $user['firstname']." ".$user['name'];
					$this->loggin_connection_admin->email = $user['email'];
					$this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
					$this->loggin_connection_admin->ip = $_SERVER["REMOTE_ADDR"];

                                        if (function_exists('geoip_country_code_by_name'))
                                        {
                                            $country_code = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                                        }
                                        else
                                        {
                                            $country_code = "fr";
                                        }

                                        $this->loggin_connection_admin->pays = $country_code;
					//$this->loggin_connection_admin->statut = 0;
					$this->loggin_connection_admin->create();


					// on vide les sessions d'echec
					unset($_SESSION['login_user']);

					// On recupere le formulaire de connexion s'il est passé
					$this->users->handleLogin('connect','login','password');


					die;
				}
				else
				{

					/* A chaque tentative on double le temps d'attente entre 2 demande.

					- tentative 2 = 1seconde d'attente
					- tentative 3 = 2 sec
					- tentative 4 = 4 sec
					- etc...

					Au bout de 10 demandes (avec la même IP) DANS LES 10min
					- Ajout d'un captcha + @ admin

					3 : 4s
					4 : 8s
					5 : 16s
					6 : 32
					*/

					// H - 10min
					$h_moins_dix_min = date('Y-m-d H:i:s', mktime(date('H'), date('i')-10, 0, date('m'), date('d'), date('Y')));

					//on récupère le nombre de tentative déjà faite avec l'ip du user
					$this->nb_tentatives_precedentes = $this->loggin_connection_admin->counter('ip = "'.$_SERVER["REMOTE_ADDR"].'" AND date_connexion >= "'.$h_moins_dix_min.'" AND id_user = 0');

					$this->duree_waiting = 0;

					//parametrage de la boucle de temps
					$coef_multiplicateur = 2;
					$resultat_precedent = 1;

					if($this->nb_tentatives_precedentes > 0 && $this->nb_tentatives_precedentes < 1000) // 1000 pour ne pas bloquer le site
					{
						for($i = 1;$i <= $this->nb_tentatives_precedentes; $i++){
							$this->duree_waiting = $resultat_precedent * $coef_multiplicateur;
							$resultat_precedent = $this->duree_waiting;
						}
					}

					// DEBUG
					//$this->duree_waiting = 1;

					//retour
					$this->error_login = "Le couple d'identifiant n'est pas correct";


					//mise en session
					$_SESSION['login_user']['duree_waiting'] = $this->duree_waiting;
					$_SESSION['login_user']['nb_tentatives_precedentes'] = $this->nb_tentatives_precedentes;
					$_SESSION['login_user']['displayCaptchaError'] = $this->displayCaptchaError;


					//on trace la tentative
					$this->loggin_connection_admin = $this->loadData('loggin_connection_admin');
					$this->loggin_connection_admin->email = $_POST["login"];
					$this->loggin_connection_admin->ip = $_SERVER["REMOTE_ADDR"];
					if (function_exists('geoip_country_code_by_name'))
                                        {
                                            $country_code = strtolower(geoip_country_code_by_name($_SERVER['REMOTE_ADDR']));
                                        }
                                        else
                                        {
                                            $country_code = "fr";
                                        }
					$this->loggin_connection_admin->pays = $country_code;
					$this->loggin_connection_admin->date_connexion = date('Y-m-d H:i:s');
					$this->loggin_connection_admin->statut = 1;
					$this->loggin_connection_admin->create();

				}
			}
		}


		// Chargement des fichiers JS
		$this->loadJs('admin/jquery/jquery-1.5.2.min');
		$this->loadJs('admin/freeow/jquery.freeow.min');
		$this->loadJs('admin/colorbox/jquery.colorbox-min');
		$this->loadJs('admin/treeview/jquery.treeview');
		$this->loadJs('admin/treeview/jquery.cookie');
		$this->loadJs('admin/treeview/tree');
		$this->loadJs('admin/tablesorter/jquery.tablesorter.min');
		$this->loadJs('admin/tablesorter/jquery.tablesorter.pager');
		$this->loadJs('admin/datepicker/jquery-ui-1.7.2.custom.min');
		$this->loadJs('admin/datepicker/ui.datepicker-fr');
		$this->loadJs('admin/ajax');
		$this->loadJs('admin/main');

		// Chargement des fichiers CSS
		$this->loadCss('../scripts/admin/freeow/freeow');
		$this->loadCss('../scripts/admin/colorbox/colorbox');
		$this->loadCss('../scripts/admin/treeview/jquery.treeview');
		$this->loadCss('../scripts/admin/tablesorter/style');
		$this->loadCss('../scripts/admin/datepicker/jquery-ui-1.7.2.custom');
		$this->loadCss('admin/main');

		// Recuperation du code Google Analytics
		$this->settings->get('Google Analytics','type');
		$this->google_analytics = $this->settings->value;

		// Recuperation du mail du compte Google Analytics
		$this->settings->get('Google Mail','type');
		$this->google_mail = $this->settings->value;

		// Recuperation du password cu compte Google Analytics
		$this->settings->get('Google Password','type');
		$this->google_password = $this->settings->value;

		// Recuperation du paging des tableaux
		$this->settings->get('Paging Tableaux','type');
		$this->nb_lignes = $this->settings->value;

		// Recuperation de l'url du front
		$this->urlfront = $this->Config['url'][$this->Config['env']]['default'];

		// Recuperation de la liste des langue disponibles
		$this->lLangues = $this->Config['multilanguage']['allowed_languages'];

		// Recuperation de la langue par defaut
		$array = array_keys($this->Config['multilanguage']['allowed_languages']);
		$this->dLanguage = $array[0];

		// Si user connecté recuperation de la liste des zones autorisée pour affichage du menu
		if(isset($_SESSION['user']) && $_SESSION['user']['id_user'] != '')
		{
			$this->sessionIdUser = $_SESSION['user']['id_user'];
			$this->lZonesHeader = $this->users_zones->selectZonesUser($_SESSION['user']['id_user']);
		}

		if($_SERVER['REMOTE_ADDR'] == '93.26.42.99')
		{
			$this->equinoa = true;
		}

		// on vérifie ici si le mot de passe du user date de moins de 3 mois sinon on le redirige sur la page d'édition de mot de passe.
		if($this->current_function != "edit_password" && $this->current_function != "login" && $this->current_function != "logout" && $this->current_controller != "thickbox" && $_SESSION['user']['id_user'] != "" && $this->current_controller != "ajax")
		{
			// on récupère la date d'il y a 3 mois
			$ilya3mois = mktime(0, 0, 0, date("m")-3, date("d"),  date("Y"));

			// on decoupe la date d'edition du pass pour obtenir son timestamp et la comparer
			$tab_date_pass = explode(' ',$_SESSION['user']['password_edited']);
			$date_pass_edited = $tab_date_pass[0];
			$tab_date_pass2 = explode('-',$date_pass_edited);

			$derniere_edition_pass = mktime(0, 0, 0, $tab_date_pass2[1], $tab_date_pass2[2],  $tab_date_pass2[0]);

			if($derniere_edition_pass < $ilya3mois)
			{
				// Mise en session du message
				$_SESSION['freeow']['title'] = 'Modification de votre mot de passe';
				$_SESSION['freeow']['message'] = 'Votre mot de passe doit &ecirc;tre mis &agrave; jour afin de conserver un niveau de s&eacute;curit&eacute; optimal!';

				// Renvoi sur la liste des utilisateurs
				header('Location:'.$this->lurl.'/edit_password/'.$_SESSION['user']['id_user']);
				die;
			}

		}



	}
}
