<?php

class dates
{
    public $today;
    public $tableauJours = [
        'fr' => [
            'Dimanche',
            'Lundi',
            'Mardi',
            'Mercredi',
            'Jeudi',
            'Vendredi',
            'Samedi'
        ]
    ];
    public $tableauMois = [
        'fr' => [
            '',
            'janvier',
            'février',
            'mars',
            'avril',
            'mai',
            'juin',
            'juillet',
            'août',
            'septembre',
            'octobre',
            'novembre',
            'décembre'
        ]
    ];

    public function __construct($today = '')
    {
        if ($today == '') {
            $this->today = date('Y-m-d');
        } else {
            $this->today = $today;
        }
    }

    // Renvoi d'une date formatée comme on le souhaite
    public function formatDate($date, $format = 'Y-m-d')
    {
        return date($format, strtotime($date));
    }

    //-----------------------------------------------------------------------------------------
    // CONVERTISSEUR --------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * Convertit une date SQL au format fr
     */
    public function formatDateMysqltoFr($date)
    {
        $d = explode('-', $date);
        return $d[2] . '/' . $d[1] . '/' . $d[0];
    }

    //-----------------------------------------------------------------------------------------
    // RETOUR DE VALEUR -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    public function intervalDates($date1, $date2)
    {
        $dh1 = explode(' ', $date1);
        $d1  = explode('-', $dh1[0]);
        $h1  = explode(':', $dh1[1]);

        $date1 = mktime($h1[0], $h1[1], $h1[2], $d1[1], $d1[2], $d1[0]);

        $dh2 = explode(' ', $date2);
        $d2  = explode('-', $dh2[0]);
        $h2  = explode(':', $dh2[1]);

        $date2 = mktime($h2[0], $h2[1], $h2[2], $d2[1], $d2[2], $d2[0]);

        $diff_date        = $date2 - $date1;
        $diff             = array();
        $diff['secondes'] = (int) ($diff_date);
        $diff['minutes']  = (int) ($diff_date / (60));
        $diff['heures']   = (int) ($diff_date / (60 * 60));
        $diff['jours']    = (int) ($diff_date / (60 * 60 * 24));
        $diff['mois']     = (int) ($diff_date / (60 * 60 * 24 * 30));

        return $diff;
    }
}
