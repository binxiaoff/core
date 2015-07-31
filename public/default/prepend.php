<?php
// MAINTENANCE
$maint = false;
if($maint)
{
?>
<html>
<head>
<title>UNILEND REVIENT...</title>
</head>
<body>
<br />
<br />
<br />
<br /><center>
<img src="https://www.unilend.fr/images/default/unilogobig.jpg"/><br>

<span style="font-family:Verdana, Geneva, sans-serif">
Pour toujours mieux vous servir, Unilend est actuellement en cours de maintenance.<br>
<br>
Merci de votre patience, vous pourrez rafraichir cette page dans quelques instants.
</span>
</center>
</body>
</html>
<?php
die;	
}
/*
 * Note dev : KLE
 * On veut cacher toutes les pages non connectées, dès que l'utilisateur est loggé on ne cache plus. On ne cache pas les pages qui dès qu'il y a du POST ou du FILE.
 * Le cache à une durée de vie de 5min
 * 
 */


error_reporting(0);
$exp = explode("/", $_SERVER['REQUEST_URI']); 
$uri = $_SERVER['REQUEST_URI'];

$nocache = false;	


// Ajout KLE

// si on est loggé on ne cache pas
if(isset($_SESSION['client']))
{
    $nocache = true;	
}

$currentController = $exp[0];
if (strlen($currentController) <= 1)
	$currentController=$exp[1];

$noCacheControllers = array('ajax','cron','crongeckoboard','pdf','LP_inscription_preteurs','2015','Lp-2015-web','Lp-offre-bienvenue-web','bienvenue','inscription_preteur');

if (in_array($currentController,$noCacheControllers))
	$nocache=true;
/*
// on ne cache pas l'ajax 
if($exp[0]=='ajax' || $exp[0]=='cron' || $exp[0]=='crongeckoboard'|| $exp[0]=='pdf' || $exp[0] == "LP_inscription_preteurs" || $exp[0] == "2015" || $exp[0] == "Lp-2015-web" || $exp[0] == "Lp-offre-bienvenue-web"  || $exp[0] == "bienvenue")
{
	$nocache = true;	
}
*/
//end KLE


// on ne cache pas les POST, FILE etc...
if($_SERVER['REQUEST_METHOD']!='GET')
	$nocache = true;

	
if(!$nocache)
{
	$mc = memcache_connect("unilend.memcache", 11211);
    $keyPartenaireMedia = isset($_SESSION['lexpress'])?'lexpress':'direct';
    $cacheKey = $_SERVER['HTTP_HOST'].'-cache-'.$currentController.'-'.$keyPartenaireMedia.'-'.date('dmY').'-uri-'.str_replace("/", "_", $uri);
    $content = memcache_get($mc, $cacheKey);	
    if ($content !== false){
	   echo $content;
	   //echo '<input type="hidden" name="cached-content" value="$cacheKey">';
       echo '<!-- Unilend cache / load from key : "'.$cacheKey.'">';
	   die;
    }
	ob_start();	
}
