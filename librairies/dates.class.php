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

    public function dates($today = '')
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

    public function formatDateMysqltoShortFR($date)
    {
        $d = explode(' ', $date);
        $d = explode('-', $d[0]);

        $date_fr = $d[2] . '/' . $d[1] . '/' . $d[0];
        return $date_fr;
    }

    /**
     * Convertit une date SQL au format fr en supprimant l'heure
     **/
    public function formatDateMysqltoFr_HourOut($date)
    {
        $d = explode(' ', $date);
        $d = explode('-', $d[0]);
        return $d[2] . '/' . $d[1] . '/' . $d[0];
    }

    /**
     * Convertit une date SQL au format fr avec le mois et le jour de la semaine correspondant au format alphabetique
     * @param string $date
     * @param string $ln
     * @return string
     */
    public function formatDateComplete($date, $ln = 'fr')
    {
        $d   = explode('-', $date);
        $d1  = explode(' ', $d[2]);
        $m   = (int) ($d[1]);
        $day = (int) ($d1[0]);
        $j   = date('w', mktime(0, 0, 0, $m, $day, $d[0]));

        return $this->tableauJours[$ln][$j] . ' ' . $day . ' ' . $this->tableauMois[$ln][$m] . ' ' . $d[0];
    }

    /**
     * Convertit une date SQL en timestamp
     **/
    public function formatDateMySqlToTimeStamp($datetime)
    {
        list($date, $time) = explode(' ', $datetime);
        list($year, $month, $day) = explode('-', $date);
        list($hour, $minute, $second) = explode(':', $time);

        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    //-----------------------------------------------------------------------------------------
    // RETOUR DE VALEUR -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * Retourne le nombre d'années entre aujourd'hui et la date donnée
     * @param string $date au format SQL
     * @return int
     */
    public function age($date)
    {
        $d = explode('-', $date);
        $y = date('Y');

        return $y - $d[0];
    }

    // verif age +18 en prenant en compte jour/mois/ann�e
    public function ageplus18($date)
    {
        $d = explode('-', $date);
        $y = date("Y");

        $age = $y - $d[0];

        if ($d[1] == '00' || $d[0] == '0000' || $d[2] == '00') {
            return false;
        } // si  -18 ans
        elseif ($age < 18) {
            return false;
        } // si 18 ann�es
        elseif ($age == 18) {
            //si le mois est inferieur au mois en cours
            if ($d[1] > date('m')) {
                return false;
            } // si meme mois
            elseif ($d[1] == date('m')) {
                // si 18 ann�es, meme mois et meme jour ou plus
                if ($d[2] <= date('d')) {
                    return true;
                } // Si jour inferieur au jour en cours
                else {
                    return false;
                }
            } // si plus
            else {
                return true;
            }
        } // si plus
        else {
            return true;
        }
    }

    /**
     * retourne le dernier jour du mois don�e dans une ann�e donn�
     */
    public function nb_jour_dans_mois($mois, $annee)
    {
        switch ($mois) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                return 31;
            case 4:
            case 6:
            case 9:
            case 11:
                return 30;
            case 2:
                if (($annee % 4) == 0) {
                    return 29;
                } else {
                    return 28;
                }
        }
        return 31;
    }

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

    // add mois a partir du nombre de jour dans chaque mois
    public function dateAddMoisJoursV3($date, $nbMois = 0, $nbJours = 0)
    {
        $nbMois  = max(0, intval($nbMois));
        $nbJours = max(0, intval($nbJours));
        $date    = strtotime($date);

        $date_start_of_month = mktime(date("H", $date), date("i", $date), date("s", $date), date("m", $date), 1, date("Y", $date));
        $date_plus_months    = strtotime("+$nbMois month", $date_start_of_month);
        $date                = strtotime('+' . (min(date('t', $date_plus_months), date("d", $date)) + $nbJours - 1) . ' day', $date_plus_months);

        return $date;
    }
}
