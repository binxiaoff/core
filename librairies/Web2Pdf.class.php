<?

use Unilend\librairies\ULogger;

class Web2Pdf {

	function Web2Pdf($params)
	{
        $oLogger = new ULogger('Web2Pdf', __DIR__ . '/../log/', 'web2pdf.log');
        $oLogger->addRecord('info', 'Use Web2Pdf the '.date('Y-m-d'), array(__FILE__ . ' at line '.__LINE__));
		$this->convert_api_compteur = $params;
	}


	//ini_set('memory_limit', '1024M');
	// le path jusqu'au dossier du fichier
	// le slug de la personne ou avoir le nom du fichier unique
	// urlsite  pour avec la page web a convertire en pdf
	// name nom du fichier pdf (complété par le slug par la suite)
	// $vraisNomPdf
	// un param pour le projet
	// si pdf signé
	// entete
	// pied de page
	// display => afficher le pdf ou non
	// $paramCovertapi => param a mettre en plus dans url covertapi
	function convert($path,$slug,$urlsite,$name,$vraisNomPdf,$param='',$signe='',$entete='',$piedpage='',$display='',$paramCovertapi='')
	{
		// Nom fichier
		$nom_fichier = $name.'-'.$slug.".pdf";
		// Nom fichier avec le param
		if($param != '')$nom_fichier = $name.'-'.$slug."-".$param.".pdf";


		// Si Pouvoir pas signé
		// qu'il y a un fichier
		// et que la date de creation est différente à la date du jour
		// => dans ce cas on supprime le PDF pour en générer un nouveau
		if($name == 'pouvoir' && $signe != 1 && file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0 && date("Y-m-d",filemtime($path.$nom_fichier)) != date('Y-m-d'))
		//if($name == 'pouvoir' && $signe != 1 && file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0)
		{
			unlink($path.$nom_fichier);
		}

		// on verifie si on a un PDF
		if(file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0){
			// on fait rien
		}
		// si existe pas on créer le pdf
		else{

			// Entete
			if($entete != '') $entete = '&HeaderUrl='.$entete.'&HeaderSpacing=10&MarginTop=20';
			// Pied de page
			if($piedpage != '') $piedpage = '&FooterUrl='.$piedpage;
			// Param convert api
			if($paramCovertapi != '') $paramCovertapi = $paramCovertapi;

			// Api Key
			$key = '&ApiKey=230830799';

			// Lien convertAPI
			$lelienCovertApi = "http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$entete.$piedpage.$paramCovertapi.$key."&LowQuality=true";

			$url = false;
			$i = 0;
			while($url == false){
				$url = file_get_contents($lelienCovertApi); // PDF

				if($i > 10)die; // on restreint a 10 tentatives
				$i++;
			}

			// On enregistre
			file_put_contents($path.$nom_fichier,$url);

		}// Fin check PDF existe

		// si le PDF est un mandat/pouvoir non signé => faut faire signer
		if($name == 'mandat' && $signe != 1 || $name == 'pouvoir' && $signe != 1){
			return 'universign';
		}
		// Si pdf normale ou mandat/pouvoir signé
		else
		{
			// Si aucun critere on affiche sinon on affiche pas
			if($display==''){
				header("Content-disposition: attachment; filename=" . $vraisNomPdf.".pdf");
				header("Content-Type: application/force-download");
				@readfile($path.$nom_fichier);
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

		//$key = '&ApiKey=234816043';
		$key = '&ApiKey=230830799';
		//$key = '';
		// récupérer le résultat

		$i=0;
		$url = false;
		while($url == false){

			// si on fait 5 fois le tour on stop le process
			if($i == 10)die;

			$url = file_get_contents("http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$key); // PDF
			$i++;
		}


		// On renregistre
		file_put_contents($path.$nom_fichier,$url);




		/*if(file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0)
		{

		}
		else
		{
			$this->convertSimple($urlsite,$path,$nom_fichier);
		}*/

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

	function convert_factures($path,$slug,$urlsite,$name,$vraisNomPdf,$param='',$signe='',$entete='',$piedpage='',$display='',$paramCovertapi='')
	{
		// Nom fichier
		$nom_fichier = $name.'-'.$slug.".pdf";
		// Nom fichier avec le param
		if($param != '')$nom_fichier = $name.'-'.$slug."-".$param.".pdf";

		// on verifie si on a un PDF
		if(file_exists($path.$nom_fichier) && filesize($path.$nom_fichier) > 0){
			// on fait rien
		}
		// si existe pas on créer le pdf
		else{

			// Entete
			if($entete != '') $entete = '&HeaderUrl='.$entete.'&HeaderSpacing=10&MarginTop=20';
			// Pied de page
			if($piedpage != '') $piedpage = '&FooterUrl='.$piedpage;
			// Param convert api
			if($paramCovertapi != '') $paramCovertapi = $paramCovertapi;


			//$paramCovertapi="&ConversionDelay=5";
			// Api Key
			$key = '&ApiKey=230830799';

			// Lien convertAPI
			$lelienCovertApi = "http://do.convertapi.com/Web2Pdf?curl=".$urlsite.$entete.$piedpage.$paramCovertapi.$key;


			$url = false;
			$i = 0;
			while($url == false){
				$url = file_get_contents($lelienCovertApi); // PDF

				if($i > 10)die; // on restreint a 10 tentatives
				$i++;
			}

			// On enregistre
			file_put_contents($path.$nom_fichier,$url);

		}// Fin check PDF existe

		// Si aucun critere on affiche sinon on affiche pas
		if($display==''){
			header("Content-disposition: attachment; filename=" . $vraisNomPdf.".pdf");
			header("Content-Type: application/force-download");
			@readfile($path.$nom_fichier);
		}
	}

}