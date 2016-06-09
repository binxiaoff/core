<?php
$config = array(
    'url' => array(
        'dev' => array(
            'default' => 'https://dev.www.unilend.fr',
            'admin'   => 'https://dev.admin.unilend.fr'
        ),
        'demo' => array(
            'default' => 'https://demo.corp.unilend.fr',
            'admin'   => 'https://admindemo.corp.unilend.fr'
        ),
        'preprod' => array(
            'default' => 'https://preprod.corp.unilend.fr',
            'admin'   => 'https://adminpreprod.corp.unilend.fr'
        ),
        'prod' => array(
            'default' => 'https://www.unilend.fr',
            'admin'   => 'https://admin.unilend.fr'
        )
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
);

if (isset($_SERVER['HTTP_HOST'])) {
    switch ($_SERVER['HTTP_HOST']) {
        case 'prets-entreprises-unilend.capital.fr':
            $config['url']['prod']['default'] = 'http://prets-entreprises-unilend.capital.fr';
            break;
        case 'partenaire.unilend.challenges.fr':
            $config['url']['prod']['default'] = 'http://partenaire.unilend.challenges.fr';
            break;
        case 'financementparticipatifpme.lefigaro.fr':
            $config['url']['prod']['default'] = 'http://financementparticipatifpme.lefigaro.fr';
            break;
    }
}
