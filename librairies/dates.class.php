<?php

class dates
{
    public $today;
    public $tableauJours;
    public $tableauMois;
    public $limitMois;

    public function dates($today = '')
    {
        if ($today == '') {
            $this->today = date('Y-m-d');
        } else {
            $this->today = $today;
        }

        $this->tableauJours['fr']  = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
        $this->tableauJours2['fr'] = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
        // tous les mois commencent pas une minuscule (Note BT 16614)
        $this->tableauMois['fr'] = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
        $this->limitMois         = array(1 => 31, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31);
    }

    // Renvoi d'une date format� comme on le souhaite
    public function formatDate($date, $format = 'Y-m-d')
    {
        return date($format, strtotime($date));
    }

    //-----------------------------------------------------------------------------------------
    // CONVERTISSEUR --------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /*
     * converti une date mysql au format fr
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
     * converti une date mysql au format fr en supprimant l'heure
     **/
    public function formatDateMysqltoFr_HourOut($date)
    {
        $d = explode(' ', $date);
        $d = explode('-', $d[0]);
        return $d[2] . '/' . $d[1] . '/' . $d[0];
    }

    /**
     * converti une date mysql au format fr avec le mois et le jour de la semaine correspondant au format alphabetique
     **/
    public function formatDateComplete($date, $ln = "fr")
    {
        $d   = explode('-', $date);
        $d1  = explode(' ', $d[2]);
        $m   = (int) ($d[1]);
        $day = (int) ($d1[0]);
        $j   = date("w", mktime(0, 0, 0, $m, $day, $d[0]));

        $ladate = $this->tableauJours2[$ln][$j] . ' ' . $day . ' ' . $this->tableauMois[$ln][$m] . ' ' . $d[0];
        return $ladate;
    }

    /**
     * converti une date mysql en timestamp
     **/
    public function formatDateMySqlToTimeStamp($datetime)
    {

        list($date, $time) = explode(' ', $datetime);
        list($year, $month, $day) = explode('-', $date);
        list($hour, $minute, $second) = explode(':', $time);

        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

        return $timestamp;
    }

    //-----------------------------------------------------------------------------------------
    // SELECT HTML ----------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * affiche un selecteur de date dont les ann�es disponible defilent en ordre d�croissant
     * @property year : aujourd'hui � -120
     */
    public function selectDateYearDesc($default = '', $name = 'date', $class = '')
    {

        $var = '';
        $d   = explode('-', $default);

        $var .= '<select class="' . $class . '" name="' . $name . '-jour">';

        for ($i = 1; $i < 32; $i++) {
            $selected = '';
            if ($d[2] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                $var .= '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                $var .= '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        $var .= '</select>';

        $var .= '<select class="' . $class . '" name="' . $name . '-mois">';

        for ($i = 1; $i < 13; $i++) {
            $selected = '';
            if ($d[1] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                $var .= '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                $var .= '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        $var .= '</select>';

        $var .= '<select class="' . $class . '" name="' . $name . '-annee">';

        for ($i = date("Y"); $i > date("Y") - 120; $i--) {
            $selected = '';
            if ($d[0] == $i) {
                $selected = 'selected';
            }
            $var .= '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }
        $var .= '</select>';

        return $var;
    }

    //-----------------------------------------------------------------------------------------
    // RETOUR DE VALEUR -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * retourne le nombre d'ann�e entre aujourdhui et la date donn�e
     * @param $date au format mysql
     */
    public function age($date)
    {
        $d = explode('-', $date);
        $y = date("Y");

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

    /**
     * retourne le nbre de jour entre deux dates donn�es
     * @param $deb_jour
     * @param $deb_mois
     * @param $deb_annee
     * @param $fin_jour
     * @param $fin_mois
     * @param $fin_annee
     * @return $nb_jours
     */
    public function intervalleJours($deb_jour, $deb_mois, $deb_annee, $fin_jour, $fin_mois, $fin_annee)
    {
        $nb_jours = 0;
        for ($annee = $deb_annee; $annee <= $fin_annee; $annee++) {
            if ($annee == $deb_annee) {
                $from_mois = $deb_mois;
            } else {
                $from_mois = 1;
            }

            if ($annee == $fin_annee) {
                $to_mois = $fin_mois;
            } else {
                $to_mois = 12;
            }

            for ($mois = $from_mois; $mois <= $to_mois; $mois++) {
                if (($mois == $deb_mois) && ($annee == $deb_annee)) {
                    $from_jour = $deb_jour;
                } else {
                    $from_jour = 1;
                }

                if (($mois == $fin_mois) && ($annee == $fin_annee)) {
                    $to_jour = $fin_jour;
                } else {
                    $to_jour = $this->nb_jour_dans_mois($mois, $annee);
                }

                $nb_jours += $to_jour - $from_jour + 1;
            }
        }
        return $nb_jours;
    }

    public function nbJours($debut, $fin)
    {
        return round((strtotime($fin) - strtotime($debut)) / (60 * 60 * 24));
    }

    public function dateDiff($date1, $date2)
    {
        $diff   = abs($date1 - $date2);
        $retour = array();

        $tmp              = $diff;
        $retour['second'] = $tmp % 60;

        $tmp              = floor(($tmp - $retour['second']) / 60);
        $retour['minute'] = $tmp % 60;

        $tmp            = floor(($tmp - $retour['minute']) / 60);
        $retour['hour'] = $tmp % 24;

        $tmp           = floor(($tmp - $retour['hour']) / 24);
        $retour['day'] = $tmp;

        return $retour;
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
