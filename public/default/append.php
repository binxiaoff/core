<?php

if(!$nocache)
{
	
    $cont = ob_get_contents();
    ob_end_clean();
    // Cache pour 5 minutes, sans compression gzip
    memcache_set($mc,$cacheKey,$cont,false,300);
    echo $cont;
    echo '<!-- Unilend cache / write to key : "'.$cacheKey.'" -->';
}