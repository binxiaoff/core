<?php 

class routagesController extends bootstrap
{
	var $Command;
	
	function routagesController(&$command,$config,$app)
	{
		parent::__construct($command,$config,$app);
		
		$this->catchAll = true;
	}
	
	function _default()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// Formulaire d'ajout d'un routage
		if(isset($_POST['form_add_routages']))
		{
			$this->routages->id_langue = $_POST['id_langue'];
			$this->routages->ctrl_url = $_POST['ctrl_url'];
			$this->routages->fct_url = $_POST['fct_url'];
			$this->routages->ctrl_projet = $_POST['ctrl_projet'];
			$this->routages->fct_projet = $_POST['fct_projet'];
			$this->routages->statut = $_POST['statut'];
			$this->routages->id_routage = $this->routages->create();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Ajout d\'un routage';
			$_SESSION['freeow']['message'] = 'Le routage a bien &eacute;t&eacute; ajout&eacute; !';
			
			// Renvoi sur la liste des routages
			header('Location:'.$this->lurl.'/routages');
			die;
		}
		
		// Formulaire de modification d'un routage
		if(isset($_POST['form_edit_routages']))
		{
			// Recuperation des infos du routage
			$this->routages->get($this->params[0],'id_routage');
			
			$this->routages->id_langue = $_POST['id_langue'];
			$this->routages->ctrl_url = $_POST['ctrl_url'];
			$this->routages->fct_url = $_POST['fct_url'];
			$this->routages->ctrl_projet = $_POST['ctrl_projet'];
			$this->routages->fct_projet = $_POST['fct_projet'];
			$this->routages->statut = $_POST['statut'];
			$this->routages->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Modification d\'un routage';
			$_SESSION['freeow']['message'] = 'Le routage a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la liste des routages
			header('Location:'.$this->lurl.'/routages');
			die;
		}
		
		// Suppression d'un routage
		if(isset($this->params[0]) && $this->params[0] == 'delete')
		{
			// Recuperation des infos du routage
			$this->routages->get($this->params[1],'id_routage');
			
			$this->routages->delete($this->params[1],'id_routage');	
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Suppression d\'un routage';
			$_SESSION['freeow']['message'] = 'Le routage a bien &eacute;t&eacute; supprim&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/routages');
			die;
		}
		
		// Modification du status d'un routages
		if(isset($this->params[0]) && $this->params[0] == 'statut')
		{
			$this->routages->get($this->params[1],'id_routage');
			
			$this->routages->statut = ($this->params[2]==1?0:1);
			$this->routages->update();
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'Statut d\'un routage';
			$_SESSION['freeow']['message'] = 'Le statut a bien &eacute;t&eacute; modifi&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/routages');
			die;
		}
		
		// Generation du fichier
		if(isset($this->params[0]) && $this->params[0] == 'generateFile')
		{
			//On recup les infos
			$lLangue = $this->routages->selectLangues();
			
			// Creation du controller
			$table_routage = "";
			$table_routage.= "<?php"."\r\n";
			$table_routage.= "\t"."\$route_projet = array("."\r\n";
			foreach($lLangue as $l => $langue) 
			{
				$table_routage.= "\t\t"."'".$langue['id_langue']."' => array("."\r\n";
				
				$lCtrlUrl = $this->routages->selectCtrlUrl($langue['id_langue']);
				
				foreach($lCtrlUrl as $c => $ctrl) 
				{
					$table_routage.= "\t\t\t"."'".$ctrl['ctrl_url']."' => array("."\r\n";
					
					$lFctUrl = $this->routages->selectFctUrl($langue['id_langue'],$ctrl['ctrl_url']);
					
					foreach($lFctUrl as $f => $fct) 
					{
						$table_routage.= "\t\t\t\t"."'".$fct['fct_url']."' => array('ctrl' => '".$fct['ctrl_projet']."', 'fct' => '".$fct['fct_projet']."')";
						
						if($lFctUrl[$f+1]['id_routage']!='')
							$table_routage.= ",";
							
						$table_routage.= "\r\n";
					}
					
					$table_routage.= "\t\t\t\t)";
					
					if($lCtrlUrl[$c+1]['ctrl_url']!='')
						$table_routage.= ",";
					
					$table_routage.= "\r\n";
				}
				
				$table_routage.= "\t\t\t)";
					
				if($lLangue[$l+1]['id_langue']!='')
					$table_routage.= ",";
				
				$table_routage.= "\r\n";
			}
			$table_routage.= "\t\t);"."\r\n";
			
			$table_routage.= "\r\n"."\r\n";
			
			$table_routage.= "\t"."\$route_url = array("."\r\n";
			foreach($lLangue as $l => $langue) 
			{
				$table_routage.= "\t\t"."'".$langue['id_langue']."' => array("."\r\n";
				
				$lCtrlProjet = $this->routages->selectCtrlProjet($langue['id_langue']);
				
				foreach($lCtrlProjet as $c => $ctrl) 
				{
					$table_routage.= "\t\t\t"."'".$ctrl['ctrl_projet']."' => array("."\r\n";
					
					$lFctProjet = $this->routages->selectFctProjet($langue['id_langue'],$ctrl['ctrl_projet']);
					
					foreach($lFctProjet as $f => $fct) 
					{
						$table_routage.= "\t\t\t\t"."'".$fct['fct_projet']."' => array('ctrl' => '".$fct['ctrl_url']."', 'fct' => '".$fct['fct_url']."')";
						
						if($lFctProjet[$f+1]['id_routage']!='')
							$table_routage.= ",";
							
						$table_routage.= "\r\n";
					}
					
					$table_routage.= "\t\t\t\t)";
					
					if($lCtrlProjet[$c+1]['ctrl_url']!='')
						$table_routage.= ",";
					
					$table_routage.= "\r\n";
				}
				
				$table_routage.= "\t\t\t)";
					
				if($lLangue[$l+1]['id_langue']!='')
					$table_routage.= ",";
				
				$table_routage.= "\r\n";
			}
			$table_routage.= "\t\t);"."\r\n";
			
			
			unlink($this->path.'route.php');
			$fp = fopen($this->path.'route.php', "wb");
			fputs ($fp, $table_routage);
			fclose($fp);
			
			chmod($this->path.'route.php', 0777);	
			
			// Mise en session du message
			$_SESSION['freeow']['title'] = 'G&eacute;n&eacute;ration du fichier de routage';
			$_SESSION['freeow']['message'] = 'Le fichier a bien &eacute;t&eacute; g&eacute;n&eacute;r&eacute; !';
			
			// Renvoi sur la page de gestion
			header('Location:'.$this->lurl.'/routages');
			die;
		}
		
		// Recuperation de la liste des routages
		$this->lRoutages = $this->routages->select('','ctrl_url ASC, fct_url ASC');
	}
	
	function _edit()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url.'/routages';
		
		// Recuperation des infos de la personne
		$this->routages->get($this->params[0],'id_routage');
	}
	
	function _add()
	{
		// Controle d'acces à la rubrique
		$this->users->checkAccess('configuration');
		
		// Activation du menu
		$this->menu_admin = 'configuration';
		
		// On masque les Head, header et footer originaux plus le debug
		$this->autoFireHeader = false;
		$this->autoFireHead = false;
		$this->autoFireFooter = false;
		$this->autoFireDebug = false;		
		
		// On place le redirect sur la home
		$_SESSION['request_url'] = $this->url.'/routages';
	}
}