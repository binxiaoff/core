<?php
$config = array(
    'env' => 'dev',
    'cms' => 'iZicom', //(iZinoa ou iZicom)
    'url' => array(
        'dev' => array(
            'default' => 'https://dev.www.unilend.fr',
            'admin'   => 'https://dev.admin.unilend.fr'
        ),
        'demo' => array(
            'default' => 'https://demo.corp.unilend.fr',
            'admin'   => 'https://admindemo.corp.unilend.fr'
        ),
        'prod' => array(
            'default' => 'https://www.unilend.fr',
            'admin'   => 'https://admin.unilend.fr'
        )
    ),
    'static_url' => array(
        'dev'  => 'https://dev.www.unilend.fr',
        'demo' => 'https://demo.corp.unilend.fr',
        'prod' => 'https://www.unilend.fr'
    ),
    'params' => array(
        'mode'         => 'literal', // Mode de gestion des paramètres (literal = nom et valeur du paramètre dans l'URL, ex. : id_18, default = valeur uniquement, ex. 18)
        'separator'    => '___',     // Séparateur pour le mode literal
        'routage'      => false,     // Utilisation de la table de routage
        'seo_optimize' => false,     // Si true, inclusion des vues : HEAD / VUE COURANTE / HEADER / FOOTER -  Si false, inclusion des vues : HEAD / HEADER / VUE COURANTE / FOOTER
    ),
    'multilanguage' => array(
        'enabled'                  => false,                     // Activation des langues
        'mode'                     => 'literal',                 // Mode de gestion des langues (default : literal = langue dans l'URL, seule config possible à date)
        'allowed_languages'        => array('fr' => 'Francais'), // Liste des langues autorisées, la première est la langue par défaut (array('en'=>'Anglais','fr'=>'Francais') par exemple)
        'domain_default_languages' => array()                    // Liste des langues par défaut pour un domaine, ex:array('aspartam.dev.equinoa.net'=>'fr')
    ),
    'cache' => array(
        'dev'  => array(
            'serverAddress' => '127.0.0.1',
            'serverPort'    => 11211
        ),
        'demo' => array(
            'serverAddress' => 'equinoamutu.memcache',
            'serverPort'    => 11211
        ),
        'prod' => array(
            'serverAddress' => 'unilend.memcache',
            'serverPort'    => 11211
        )
    )
);

if (isset($_SERVER['HTTP_HOST'])) {
    switch ($_SERVER['HTTP_HOST']) {
        case 'prets-entreprises-unilend.capital.fr':
            $config['url']['prod']['default'] = 'http://prets-entreprises-unilend.capital.fr';
            break;
        case 'partenaire.unilend.challenges.fr':
            $config['url']['prod']['default'] = 'http://partenaire.unilend.challenges.fr';
            break;
        case 'lexpress.unilend.fr':
            $config['url']['prod']['default'] = 'http://lexpress.unilend.fr';
            break;
        case 'pret-entreprise.votreargent.lexpress.fr':
            $config['url']['prod']['default'] = 'http://pret-entreprise.votreargent.lexpress.fr';
            break;
        case 'emprunt-entreprise.lentreprise.lexpress.fr':
            $config['url']['prod']['default'] = 'http://emprunt-entreprise.lentreprise.lexpress.fr';
            break;
        case 'financementparticipatifpme.lefigaro.fr':
            $config['url']['prod']['default'] = 'http://financementparticipatifpme.lefigaro.fr';
            break;
    }

    if (in_array($_SERVER['HTTP_HOST'], array('prets-entreprises-unilend.capital.fr', 'partenaire.unilend.challenges.fr', 'lexpress.unilend.fr'))) {
        $config['static_url']['prod'] = 'http://www.unilend.fr';
    }
}

if (false === defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $config['env']);
}
