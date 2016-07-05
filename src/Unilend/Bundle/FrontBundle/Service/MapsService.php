<?php


namespace Unilend\Bundle\FrontBundle\Service;


class MapsService
{
    /**
     * @var array
     */
    public $aFrenchRegions;

    public function __construct()
    {
        $this->aFrenchRegions = [
            'IDF'                                       => '',
            'Auvergne - Rhône - Alpes'                  => '',
            'Nord - Pas de Calais - Picardie'           => '',
            'Poitou - Charentes - Limousin - Aquitaine' => '',
            'Midi-Pyrénées - Languedoc Roussillon'      => '',
            'Champane - Ardenne - Lorraine - Alsace'    => '',
            'Provence - Alpes - Côte d\'Azur'           => '',
            'Pays de la Loire'                          => '',
            'Normandie'                                 => '',
            'Bretagne'                                  => '',
            'Bourgogne - Franche Comté'                 => '',
            'Centre'                                    => '',
            'Corse'                                     => ''
        ];
    }

    public function getFrenchRegions()
    {
        return $this->aFrenchRegions;
    }



}