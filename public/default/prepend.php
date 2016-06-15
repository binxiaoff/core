<?php
// MAINTENANCE
$maint = false;
if ($maint) {
    ?>
    <html>
    <head>
        <title>UNILEND REVIENT...</title>
    </head>
    <body>
    <br/>
    <br/>
    <br/>
    <br/>
    <center>
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

/**
 * Note dev : KLE
 * On veut cacher toutes les pages non connectées, dès que l'utilisateur est loggé on ne cache plus. On ne cache pas les pages qui dès qu'il y a du POST ou du FILE.
 * Le cache à une durée de vie de 5min
 */

error_reporting(0);
$exp     = explode('/', $_SERVER['REQUEST_URI']);
$nocache = false;

if (isset($_SESSION['client'])) {
    $nocache = true;
}

$currentController = $exp[0];
if (strlen($currentController) <= 1) {
    $currentController = $exp[1];
}

$noCacheControllers = array('ajax', 'cron', 'crongeckoboard', 'pdf', 'LP_inscription_preteurs', '2015', 'Lp-2015-web', 'Lp-offre-bienvenue-web', 'bienvenue', 'inscription_preteur');

if (in_array($currentController, $noCacheControllers)) {
    $nocache = true;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $nocache = true;
}

if (! $nocache) {
    $params = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . '/../../app/config/parameters.yml'));
    $oCache = new Memcache;
    $oCache->connect($params['parameters']['server1.memcache_host'], $params['parameters']['server1.memcache_port']);
    
    if (isset($_GET['flushCache']) && $_GET['flushCache'] == 'y') {
        $oCache->flush();
    }

    $keyPartenaireMedia = isset($_SESSION['lexpress']) ? 'lexpress' : 'direct';
    $uri  = trim(str_replace(array('clearCache=y', 'noCache=y', 'flushCache=y'), '', $_SERVER['REQUEST_URI']), '?/');
    $sKey = 'prod' . '_' . $_SERVER['HTTP_HOST'] . '_cache_' . $currentController . '_' . $keyPartenaireMedia . '_' . str_replace('/', '_', $uri);
    $cacheKey = (250 < strlen($sKey)) ? md5($sKey) : $sKey;

    $content  = $oCache->get($cacheKey);

    if ($content !== false) {
        echo $content;
        echo '<!-- Unilend cache / load from key : "' . $cacheKey . '" -->';
        die;
    }
    ob_start();
}
