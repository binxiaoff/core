<?php

if (! $nocache) {
    $cont = ob_get_contents();
    ob_end_clean();

    $oCache->set($cacheKey, $cont, 300);
    echo $cont;
    echo '<!-- Unilend cache / write to key : "' . $cacheKey . '" -->';
}
