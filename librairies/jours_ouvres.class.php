<?php


/*
Jour de l'an : 1er janvier
Fête du travail : 1er mai
Victoire des alliés 1945 : 8 mai
Fête nationale, anniversaire de la fête de la fédération : 14 juillet
Assomption, fin de la vie terrestre de Marie : 15 août
Toussaint, fête de tous les saints : 1er novembre
Armistice de 1918 : 11 novembre
Noël : 25 décembre
Lundi de pâques : dimanche de pâques + 1
Ascension, élévation de Jésus : dimanche de pâques + 39
Pentecôte, venue du Saint Esprit : dimanche de pâques + 50
*/


class jours_ouvres
{
    // Liste des jours fériés français
    function getHolidays($year = null)
    {
        if ($year === null) {
            $year = intval(strftime('%Y'));
        }

        $easterDate  = easter_date($year);
        $easterDay   = date('j', $easterDate);
        $easterMonth = date('n', $easterDate);
        $easterYear  = date('Y', $easterDate);

        $holidays = array(
            // Jours feries fixes
            mktime(0, 0, 0, 1, 1, $year),// 1er janvier
            mktime(0, 0, 0, 5, 1, $year),// Fete du travail
            mktime(0, 0, 0, 5, 8, $year),// Victoire des allies
            mktime(0, 0, 0, 7, 14, $year),// Fete nationale
            mktime(0, 0, 0, 8, 15, $year),// Assomption
            mktime(0, 0, 0, 11, 1, $year),// Toussaint
            mktime(0, 0, 0, 11, 11, $year),// Armistice
            mktime(0, 0, 0, 12, 25, $year),// Noel

            // Jour feries qui dependent de paques
            mktime(0, 0, 0, $easterMonth, $easterDay - 2, $easterYear),// vendredi saint (pour 3 departements)
            mktime(0, 0, 0, $easterMonth, $easterDay + 1, $easterYear),// Lundi de paques
            mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear),// Ascension
            mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear), // Pentecote
        );

        sort($holidays);

        return $holidays;
        /* Sortie :
        Array
        (
            [0] => 1325372400
            [1] => 1333922400
            [2] => 1335823200
            [3] => 1336428000
            [4] => 1337205600
            [5] => 1338156000
            [6] => 1342216800
            [7] => 1344981600
            [8] => 1351724400
            [9] => 1352588400
            [10] => 1356390000
        )
        */
    }

    //Tester si une date est un jour férié ou un week end
    // retoune un boolean
    function isHoliday($timestamp)
    {
        $day = date('D', $timestamp);

        $year = date('Y', $timestamp);

        $lFeries = $this->getHolidays($year);
        $weekend = array('Sat', 'Sun');

        $result = true;

        // jour ferié / weekend
        if (in_array($timestamp, $lFeries) || in_array($day, $weekend)) {
            $result = false;
        }

        return $result;

    }

    /**
     * @param int $timestamp starting date
     * @param int $nbJours number of working days
     * @return false|int
     */
    function display_jours_ouvres($timestamp, $nbJours = 6)
    {

        $nbOuvre    = 0;
        $nbNonOuvre = 0;
        // tant qu'on a pas le bon nombre de jours ouvrés on continue
        while ($nbOuvre < $nbJours) {

            $diff = ($nbJours - $nbOuvre);

            for ($i = 1; $i <= $diff; $i++) {
                $date = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp) - $i, date('Y', $timestamp));

                if ($this->isHoliday($date) == false) {
                    $nbNonOuvre += 1;
                } else {
                    $nbOuvre += 1;
                }
            }
            $timestamp = $date;
        }

        return $date;
    }

}
