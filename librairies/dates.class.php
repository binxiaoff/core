<?php

class dates
{
    var $today;
    var $tableauJours;
    var $tableauMois;
    var $limitMois;

    function dates($today = '')
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
    function formatDate($date, $format = 'Y-m-d')
    {
        return date($format, strtotime($date));
    }

    //-----------------------------------------------------------------------------------------
    // ACCESSEUR ------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * des tableaux de jours/mois gérant les langues ...
     * ajout d'un tableau de mois dans une langue specifique
     */
    function addLnMois($ln = 'en', $mois = array())
    {
        $this->tableauMois[$ln] = $mois;
    }

    /**
     * des tableaux de jours/mois gérant les langues ...
     * ajout d'un tableau de jours dans une langue specifique
     */
    function addLnJours($ln = 'en', $jours = array())
    {
        $this->tableauJours[$ln] = $jours;
    }

    /**
     * des tableaux de jours/mois gérant les langues ...
     * ajout d'un mois spécifique dans une langue specifique
     */
    function addLnMoisNum($ln = 'en', $num = 1, $mois = "January")
    {
        $this->tableauMois[$ln][$num] = $mois;
    }

    /**
     * des tableaux de jours/mois gérant les langues ...
     * ajout d'un jour spécifique dans une langue specifique
     */
    function addLnJourNum($ln = 'en', $num = 0, $jours = "Monday")
    {
        $this->tableauJours[$ln][$num] = $jours;
    }

    //-----------------------------------------------------------------------------------------
    // CONVERTISSEUR --------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * converti un nombre de secondes pour l'affichage
     */
    function formatTimestampToHour($time)
    {
        if ($time >= 86400) /* 86400 = 3600*24 c'est � dire le nombre de secondes dans un seul jour ! donc l� on v�rifie si le nombre de secondes donn� contient des jours ou pas */ {
            // Si c'est le cas on commence nos calculs en incluant les jours

            // on divise le nombre de seconde par 86400 (=3600*24)
            // puis on utilise la fonction floor() pour arrondir au plus petit
            $jour = floor($time / 86400);
            // On extrait le nombre de jours
            $reste = $time % 86400;

            $heure = floor($reste / 3600);
            // puis le nombre d'heures
            $reste = $reste % 3600;

            $minute = floor($reste / 60);
            // puis les minutes

            $seconde = $reste % 60;
            // et le reste en secondes

            // on rassemble les r�sultats en forme de date
            $result = $jour . 'j ' . $heure . 'h ' . $minute . 'min ' . $seconde . 's';
        } elseif ($time < 86400 AND $time >= 3600) // si le nombre de secondes ne contient pas de jours mais contient des heures
        {
            // on refait la m�me op�ration sans calculer les jours
            $heure = floor($time / 3600);
            $reste = $time % 3600;

            $minute = floor($reste / 60);

            $seconde = $reste % 60;

            $result = $heure . 'h ' . $minute . 'min ' . $seconde . ' s';
        } elseif ($time < 3600 AND $time >= 60) {
            // si le nombre de secondes ne contient pas d'heures mais contient des minutes
            $minute  = floor($time / 60);
            $seconde = $time % 60;
            $result  = $minute . 'min ' . $seconde . 's';
        } elseif ($time < 60) // si le nombre de secondes ne contient aucune minutes
        {
            $result = $time . 's';
        }

        return $result;
    }

    /*
     * converti une date mysql au format fr
     */
    function formatDateMysqltoFr($date)
    {
        $d = explode('-', $date);
        return $d[2] . '/' . $d[1] . '/' . $d[0];
    }


    function formatDateMysqltoShortFR($date)
    {
        $d = explode(' ', $date);
        $d = explode('-', $d[0]);

        $date_fr = $d[2] . '/' . $d[1] . '/' . $d[0];
        return $date_fr;
    }

    /*
     * converti une date mysql au format fr avec le mois au format alphabetique
     */
    function formatDateMysqltoFrTxtMonth($date, $ln = "fr")
    {
        $d = explode(' ', $date);
        $d = explode('-', $d[0]);
        $m = (int)($d[1]);
        $d = (int)($d[2]) . ' ' . $this->tableauMois[$ln][$m] . ' ' . $d[0];
        return $d;
    }

    /**
     * converti une date mysql au format fr en supprimant l'heure
     **/
    function formatDateMysqltoFr_HourOut($date)
    {
        $d = explode(' ', $date);
        $d = explode('-', $d[0]);
        return $d[2] . '/' . $d[1] . '/' . $d[0];
    }

    /**
     * converti une date mysql au format fr en integrant l'heure
     * /*/
    function formatDateMysqltoFr_HourIn($date)
    {
        $h = explode(' ', $date);
        $d = explode('-', $h[0]);

        $date_fr = $d[2] . '/' . $d[1] . '/' . $d[0] . ' ' . $h[1];
        return $date_fr;
    }

    /*
     * extrait l'heur d'une date mysql
     */
    function formatDateMysqltoHeure($date)
    {
        $d = explode(' ', $date);
        $d = explode(':', $d[1]);
        return $d[0] . 'h' . $d[1];
    }

    /**
     * converti une date mysql au format fr avec le mois et le jour de la semaine correspondant au format alphabetique
     **/
    function formatDateComplete($date, $ln = "fr")
    {
        $d   = explode('-', $date);
        $d1  = explode(' ', $d[2]);
        $m   = (int)($d[1]);
        $day = (int)($d1[0]);
        $j   = date("w", mktime(0, 0, 0, $m, $day, $d[0]));

        $ladate = $this->tableauJours2[$ln][$j] . ' ' . $day . ' ' . $this->tableauMois[$ln][$m] . ' ' . $d[0];
        return $ladate;
    }

    /**
     * converti une date mysql en timestamp
     **/
    function formatDateMySqlToTimeStamp($datetime)
    {

        list($date, $time) = explode(' ', $datetime);
        list($year, $month, $day) = explode('-', $date);
        list($hour, $minute, $second) = explode(':', $time);

        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

        return $timestamp;
    }

    /**
     * converti une date fr au format mysql
     **/
    function formatDateFrToMysql($date)
    {
        $d = explode('/', $date);
        return $d[2] . '-' . $d[1] . '-' . $d[0];
    }

    //-----------------------------------------------------------------------------------------
    // SELECT HTML ----------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * affiche un selecteur de date dont les ann�es disponible defilent en ordre croissant
     * @property year : aujourd'hui � +120
     */
    function selectDateYearAsc($default = '', $name = 'date', $class = '')
    {

        $d = explode('-', $default);

        echo '<select class="' . $class . '" name="' . $name . '-jour">';
        echo '<option value="">--</option>';

        for ($i = 1; $i < 32; $i++) {
            $selected = '';
            if ($d[2] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        echo '</select>';

        echo '<select class="' . $class . '" name="' . $name . '-mois">';
        echo '<option value="">--</option>';

        for ($i = 1; $i < 13; $i++) {
            $selected = '';
            if ($d[1] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        echo '</select>';

        echo '<select class="' . $class . '" name="' . $name . '-annee">';
        echo '<option value="">----</option>';

        for ($i = date("Y"); $i > date("Y") + 120; $i++) {
            $selected = '';
            if ($d[0] == $i) {
                $selected = 'selected';
            }
            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }
        echo '</select>';
    }

    /**
     * affiche un selecteur de date dont les ann�es disponible defilent en ordre d�croissant
     * @property year : aujourd'hui � -120
     */
    function selectDateYearDesc($default = '', $name = 'date', $class = '')
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

    /**
     * affiche un selecteur de date(jour/mois)
     */
    function selectDateMonth($default = '', $name = 'date', $class = '')
    {

        $d = explode('-', $default);

        echo '<select class="' . $class . '" name="' . $name . '-jour">';
        echo '<option value="">--</option>';

        for ($i = 1; $i < 32; $i++) {
            $selected = '';
            if ($d[2] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        echo '</select>';

        echo '<select class="' . $class . '" name="' . $name . '-mois">';
        echo '<option value="">--</option>';

        for ($i = 1; $i < 13; $i++) {
            $selected = '';
            if ($d[1] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        echo '</select>';
    }

    /**
     * affiche un selecteur de date(mois/ann�es) dont les ann�es disponible defilent en ordre croissant
     * module de paiement cb
     * @property year : aujourd'hui � +120
     */
    function selectionMonthYearAsc($default = '', $name = 'annee', $class = '')
    {

        echo '<select class="' . $class . '" name="' . $name . '-mois">';

        for ($i = 1; $i < 13; $i++) {
            $selected = '';
            if ($d[1] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        echo '</select>';

        echo '<select name="' . $name . '" class="' . $class . '">';

        echo '<option value="">S&eacute;lectionner</option>';

        for ($i = date("Y"); $i < date("Y") + 120; $i++) {
            $selected = '';

            if ($default == $i) {
                $selected = 'selected';
            }

            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }

        echo '</select>';
    }

    /**
     * affiche un selecteur de date(mois/ann�es) dont les ann�es disponible defilent en ordre d�croissant
     * module de paiement cb
     * @property year : aujourd'hui � -120
     * @deprecated
     */
    function selectionMonthYearDesc($default = '', $name = 'annee', $class = '')
    {
        echo '<select class="' . $class . '" name="' . $name . '-mois">';

        for ($i = 1; $i < 13; $i++) {
            $selected = '';
            if ($d[1] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }
        echo '</select>';

        echo '<select name="' . $name . '" class="' . $class . '">';

        echo '<option value="">S&eacute;lectionner</option>';

        for ($i = date("Y"); $i < date("Y") - 120; $i--) {
            $selected = '';

            if ($default == $i) {
                $selected = 'selected';
            }

            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }

        echo '</select>';
    }

    /**
     * affiche un selecteur de date(ann�e) dont les ann�es disponible defilent en ordre croissant
     * @property year : aujourd'hui � +120
     */
    function selectionAnneeAsc($default = '', $name = 'annee', $class = '')
    {
        echo '<select name="' . $name . '" class="' . $class . '" >';

        echo '<option value="">S&eacute;lectionner</option>';

        for ($i = date("Y"); $i < date("Y") + 120; $i++) {
            $selected = '';

            if ($default == $i) {
                $selected = 'selected';
            }

            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }

        echo '</select>';
    }

    /**
     * affiche un selecteur de date(ann�e) dont les ann�es disponible defilent en ordre d�croissant
     * @property year : aujourd'hui � -120
     */
    function selectionAnneeDesc($default = '', $name = 'annee', $class = '')
    {
        echo '<select name="' . $name . '" class="' . $class . '">';

        echo '<option value="">S&eacute;lectionner</option>';

        for ($i = date("Y"); $i < date("Y") - 120; $i--) {
            $selected = '';

            if ($default == $i) {
                $selected = 'selected';
            }

            echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
        }

        echo '</select>';
    }

    /**
     * affiche un selecteur d'heure
     */
    function SelectionHeure($default = '', $name = 'date', $class = '')
    {
        $d = explode(':', $default);

        // SELECT DE L'HEURE
        echo '<select class="' . $class . '" name="' . $name . '-heure" id="' . $name . '-heure">';

        for ($i = 7; $i < 23; $i++) {
            $selected = '';

            if ($d[0] == $i) {
                $selected = 'selected';
            }
            if ($i < 10) {
                echo '<option value="0' . $i . '" ' . $selected . '>0' . $i . '</option>';
            } else {
                echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
            }
        }

        echo '</select>';

        // SELECT DES MINUTES
        echo '<select class="' . $class . '" name="' . $name . '-minute" id="' . $name . '-minute">';

        if ($d[1] == "00") {
            $selected0 = 'selected';
        } else {
            $selected0 = '';
        }
        if ($d[1] == "15") {
            $selected15 = 'selected';
        } else {
            $selected15 = '';
        }
        if ($d[1] == "30") {
            $selected30 = 'selected';
        } else {
            $selected30 = '';
        }
        if ($d[1] == "45") {
            $selected45 = 'selected';
        } else {
            $selected45 = '';
        }

        echo '<option value="00" ' . $selected0 . '>00</option>';
        echo '<option value="15" ' . $selected15 . '>15</option>';
        echo '<option value="30" ' . $selected30 . '>30</option>';
        echo '<option value="45" ' . $selected45 . '>45</option>';

        echo '</select>';
    }

    //-----------------------------------------------------------------------------------------
    // RETOUR DE VALEUR -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------

    /**
     * recupere les valeur du selecteur de date et les renvoie au format Mysql
     * @param nom du selecteur
     * @param method d'envoie du formulaire
     */
    function handleSelectedDate($nom, $method = 'post')
    {
        if ($method == 'post') {
            return $_POST[$nom . '-annee'] . '-' . str_pad($_POST[$nom . '-mois'], 2, 0, STR_PAD_LEFT) . '-' . str_pad($_POST[$nom . '-jour'], 2, 0, STR_PAD_LEFT);
        } else {
            return $_GET[$nom . '-annee'] . '-' . str_pad($_GET[$nom . '-mois'], 2, 0, STR_PAD_LEFT) . '-' . str_pad($_GET[$nom . '-jour'], 2, 0, STR_PAD_LEFT);
        }
    }

    /**
     * retourne le nombre d'ann�e entre aujourdhui et la date donn�e
     * @param $date au format mysql
     */
    function age($date)
    {
        $d = explode('-', $date);
        $y = date("Y");

        return $y - $d[0];
    }

    // verif age +18 en prenant en compte jour/mois/ann�e
    function ageplus18($date)
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
     * retournne si $date1 est inferieur � $date2
     * @param $date1
     * @param $date2
     */
    function compareDatetime($date1, $date2)
    {
        return ($this->formatMyToTimeStamp($date1) < $this->formatMyToTimeStamp($date2));
    }

    /**
     * retourne le dernier jour du mois don�e dans une ann�e donn�
     */
    function nb_jour_dans_mois($mois, $annee)
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
                if (($annee % 4) == 0) return 29;
                else return 28;
        }
        return 31;
    }

    function intervalDates($date1, $date2)
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
        $diff['secondes'] = (int)($diff_date);
        $diff['minutes']  = (int)($diff_date / (60));
        $diff['heures']   = (int)($diff_date / (60 * 60));
        $diff['jours']    = (int)($diff_date / (60 * 60 * 24));
        $diff['mois']     = (int)($diff_date / (60 * 60 * 24 * 30));

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
    function intervalleJours($deb_jour, $deb_mois, $deb_annee, $fin_jour, $fin_mois, $fin_annee)
    {
        $nb_jours = 0;
        for ($annee = $deb_annee; $annee <= $fin_annee; $annee++) {
            if ($annee == $deb_annee) $from_mois = $deb_mois;
            else $from_mois = 1;

            if ($annee == $fin_annee) $to_mois = $fin_mois;
            else $to_mois = 12;

            for ($mois = $from_mois; $mois <= $to_mois; $mois++) {
                if (($mois == $deb_mois) && ($annee == $deb_annee)) $from_jour = $deb_jour;
                else $from_jour = 1;

                if (($mois == $fin_mois) && ($annee == $fin_annee)) $to_jour = $fin_jour;
                else $to_jour = $this->nb_jour_dans_mois($mois, $annee);

                $nb_jours += $to_jour - $from_jour + 1;
            }
        }
        return $nb_jours;
    }

    function dateDiff($date1, $date2)
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

    // date au format sql
    // retourne date format timestamp
    function dateAddMoisJours($date, $nbMois = 0, $nbJours = 0)
    {
        $date = strtotime($date);
        $date = mktime(date("H", $date), date("i", $date), date("s", $date), date("m", $date) + $nbMois, date("d", $date) + $nbJours, date("Y", $date));
        return $date;
    }

    // add mois a partir du nombre de jour dans chaque mois
    function dateAddMoisJoursV2($date, $nbMois = 0, $nbJours = 0)
    {
        $date = strtotime($date);


        $datePlusMois    = mktime(date("H", $date), date("i", $date), date("s", $date), date("m", $date) + $nbMois, date("d", $date), date("Y", $date));
        $nbJoursdansMois = date('t', $datePlusMois);

        $date = mktime(date("h", $date), date("i", $date), date("s", $date), date("m", $date) + ($nbMois - 1), date("d", $date) + $nbJours + $nbJoursdansMois, date("Y", $date));
        return $date;
    }


    // add mois a partir du nombre de jour dans chaque mois
    function dateAddMoisJoursV3($date, $nbMois = 0, $nbJours = 0)
    {
        $nbMois  = max(0, intval($nbMois));
        $nbJours = max(0, intval($nbJours));
        $date    = strtotime($date);

        $date_start_of_month = mktime(date("h", $date), date("i", $date), date("s", $date), date("m", $date), 1, date("Y", $date));
        $date_plus_months    = strtotime("+$nbMois month", $date_start_of_month);
        $date                = strtotime('+' . (min(date('t', $date_plus_months), date("d", $date)) + $nbJours - 1) . ' day', $date_plus_months);

        return $date;
    }
}

?>