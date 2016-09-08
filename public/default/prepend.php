<?php

$exp    = explode('/', $_SERVER['REQUEST_URI']);
$cached = true;

if (isset($_SESSION['client'])) {
    $nocache = true;
}

$currentController = $exp[0];
if (strlen($currentController) <= 1) {
    $currentController = $exp[1];
}

if (
    isset($_SESSION['client'])
    || $_SERVER['REQUEST_METHOD'] !== 'GET'
    || false === in_array($currentController, ['', 'projets-a-financer', 'projects'])
    || $currentController === 'projects' && isset($exp[2]) && $exp[2] === 'bidsExport'
) {
    $cached = false;
}

if ($cached) {
    $params = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . '/../../app/config/parameters.yml'));

    if (file_exists(__DIR__ . '/../../app/config/parameters_extended.yml')) {
        $params['parameters'] = array_merge($params['parameters'], \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . '/../../app/config/parameters_extended.yml'))['parameters']);
    }

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
