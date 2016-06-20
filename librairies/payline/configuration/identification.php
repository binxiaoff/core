<?php

define('PRODUCTION', true);

if (PRODUCTION) {
    define('MERCHANT_ID', '26099716932287'); // Merchant ID
    define('ACCESS_KEY', 'JEF5JVYBkGFGS67zoXGt'); // Certificate key
} else {
    define('MERCHANT_ID', '16828263878877' ); // Merchant ID
    define('ACCESS_KEY', 'vtPtmVrGFhFx6OPeg7No' ); // Certificate key
}

define('PROXY_HOST', null); // Proxy URL (optional)
define('PROXY_PORT', null); // Proxy port number without 'quotes' (optional)
define('PROXY_LOGIN', '' ); // Proxy login (optional)
define('PROXY_PASSWORD', '' ); // Proxy password (optional)
