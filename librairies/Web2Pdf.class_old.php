<?
class Web2Pdf {
	
	// le path jusqu'au dossier du fichier
	// le slug de la personne ou avoir le nom du fichier unique
	// urlsite  pour avec la page web a convertire en pdf
	// name nom du fichier pdf (complété par le slug par la suite)
	// $vraisNomPdf
	// un param pour le projet
	// si pdf signé
	// entete
	// pied de page
	function convert($path,$slug,$urlsite,$name,$vraisNomPdf,$param='',$signe='',$entete='',$piedpage='',$display='')
	{
		
		$nom_fichier = $name.'-'.$slug.".pdf";

		if($param != '')$nom_fichier = $name.'-'.$slug."-".$param.".pdf";
		
		
		// si pouvoir et que c'est pas signer (supp le pouvoir car on doit le regénérer a chaque fois)
		if($name == 'pouvoir' && $signe != 1 && file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0 && date("Y-m-d",filemtime($path.$nom_fichier)) != date('Y-m-d'))
		//if($name == 'pouvoir' && $signe != 1 && file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0)
		{
			unlink($path.$nom_fichier);
		}
		
		// on verifie si on a un fichier deja
		if(file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0)
		{
			
				
		}
		else
		{
			
			if($entete != '') $entete = '&HeaderUrl='.$entete.'&HeaderSpacing=10&MarginTop=20';
			
			if($piedpage != '') $piedpage = '&FooterUrl='.$piedpage;
			
			$key = '&ApiKey=234816043';
			//$key = '';
			// récupérer le résultat
			
			$lelienCovertApi = "http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$entete.$piedpage.$key."&LowQuality=true";
			
			$url = false;
			while($url == false)
			{
				$url = file_get_contents ($lelienCovertApi); // PDF
			}
			//$url = fread(fopen($lelienCovertApi, "r"), filesize($lelienCovertApi)); 
			
			//$url = file_get_contents ("http://do.convertapi.com/Web2Pdf?curl=".$urlsite."&ApiKey=234816043"); // PDF
			
			
			/*if($url == false)
			mail("d.courtier@equinoa.com","covertapi","faux ".$name." - ".$signe." < http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$entete.$piedpage.$key."&LowQuality=true");
			else mail("d.courtier@equinoa.com","covertapi","true ".$name." - ".$signe." < http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$entete.$piedpage.$key."&LowQuality=true");*/
			
			file_put_contents($path.$nom_fichier,$url);
					
		}
		
		// si mandat ou pouvoir et que c'est pas signer, faut faire signer
		if($name == 'mandat' && $signe != 1 || $name == 'pouvoir' && $signe != 1)
		{
			
			return 'universign';
		}
		else
		{	
			if($display==''){
		
			//header('Content-type: application/pdf');
			header("Content-disposition: attachment; filename=" . $vraisNomPdf.".pdf");
			header("Content-Type: application/force-download");
			@readfile($path.$nom_fichier);
		
			/*header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($name.".pdf").'";');
			@readfile($url);
			die();*/
			
			}
			
		}
	}
	
	function lecture($path_nom_fichier,$name)
	{
			/*header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($name.".pdf").'";');
			@readfile($path_nom_fichier);
			die();	*/
			
			header("Content-disposition: attachment; filename=UNILEND-" . $name.".pdf");
			header("Content-Type: application/force-download");
			@readfile($path_nom_fichier);
	}
	
	function convertSimple($urlsite,$path,$nom_fichier)
	{
		
		$key = '&ApiKey=234816043';
		//$key = '';
		// récupérer le résultat
		$url = file_get_contents("http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$key); // PDF
		
		// On renregistre
		file_put_contents($path.$nom_fichier,$url);
		
		if(file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0)
		{
			
		}
		else
		{
			$this->convertSimple($urlsite,$path,$nom_fichier);
		}
		
	}
	
	function convert_old($urlsite,$name,$entete='',$piedpage='')
	{

		if($entete != '') $entete = '&HeaderUrl='.$entete.'&HeaderSpacing=10&MarginTop=20';
		
		if($piedpage != '') $piedpage = '&FooterUrl='.$piedpage;
		
		// récupérer le résultat
		$url = file_get_contents ("http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$entete.$piedpage); // PDF
		//$url = file_get_contents ("http://do.convertapi.com/Web2Pdf?curl=".$urlsite."&ApiKey=234816043"); // PDF
	
		//header('Content-type: application/pdf');
		$nom_fichier = $name.".pdf";
		header("Content-disposition: attachment; filename=" . $nom_fichier);
		header("Content-Type: application/force-download"); 
		echo $url;
		
                

		
		
		/*$url = file_get_contents ("http://do.convertapi.com/Web2Image?curl=".$urlsite); // img
		$nom_fichier = $name.".jpg";
		header("Content-disposition: attachment; filename=" . $nom_fichier);
		header("Content-Type: application/force-download"); 
		echo $url;*/
		
	}
	
}