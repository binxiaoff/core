<?php

class ficelle
{
    public function generatePassword($nb)
    {
        $liste_chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $maxi         = strlen($liste_chars) - 1;
        $new_password = '';

        for ($i = 1; $i <= $nb; $i++) {
            $new_password .= substr($liste_chars, rand(0, $maxi), 1);
        }

        return $new_password;
    }

    public function generateSlug($string)
    {
        $string = strip_tags(utf8_decode($string));
        $string = strtr($string, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyyNn');
        $string = strtolower($string); // lower-case the string
        $string = preg_replace('/[ ]/', '-', $string); // replace special characters by score
        $string = preg_replace('/[^a-z0-9-.]/', '', $string); // replace all non-alphanumeric characters by void
        $string = preg_replace('/[-]{2,}/', '-', $string); // replace multi '-' by once
        $string = preg_replace('/[-]{1,}$/', '', $string); // replace end '-' by void
        return $string;
    }

    public function isEmail($value)
    {
        return (false !== filter_var($value, FILTER_VALIDATE_EMAIL));
    }

    // Encode les caracteres speciaux
    public function speChar2HtmlEntities($string)
    {
        $str_trans = array(
            'À' => '&Agrave;', 'à' => '&agrave;', 'Á' => '&Aacute;', 'á' => '&aacute;', 'Â' => '&Acirc;',
            'â' => '&acirc;', 'Ã' => '&Atilde;', 'ã' => '&atilde;', 'Ä' => '&Auml;', 'ä' => '&auml;', 'Å' => '&Aring;',
            'å' => '&aring;', 'Æ' => '&AElig;', 'æ' => '&aelig;', 'Ç' => '&Ccedil;', 'ç' => '&ccedil;', 'Ð' => '&ETH;',
            'ð' => '&eth;', 'È' => '&Egrave;', 'è' => '&egrave;', 'É' => '&Eacute;', 'é' => '&eacute;',
            'Ê' => '&Ecirc;', 'ê' => '&ecirc;', 'Ë' => '&Euml;', 'ë' => '&euml;', 'Ì' => '&Igrave;', 'ì' => '&igrave;',
            'Í' => '&Iacute;', 'í' => '&iacute;', 'Î' => '&Icirc;', 'î' => '&icirc;', 'Ï' => '&Iuml;', 'ï' => '&iuml;',
            'Ñ' => '&Ntilde;', 'ñ' => '&ntilde;', 'Ò' => '&Ograve;', 'ò' => '&ograve;', 'Ó' => '&Oacute;',
            'ó' => '&oacute;', 'Ô' => '&Ocirc;', 'ô' => '&ocirc;', 'Õ' => '&Otilde;', 'õ' => '&otilde;',
            'Ö' => '&Ouml;', 'ö' => '&ouml;', 'Ø' => '&Oslash;', 'ø' => '&oslash;', 'Œ' => '&OElig;', 'œ' => '&oelig;',
            'ß' => '&szlig;', 'Þ' => '&THORN;', 'þ' => '&thorn;', 'Ù' => '&Ugrave;', 'ù' => '&ugrave;',
            'Ú' => '&Uacute;', 'ú' => '&uacute;', 'Û' => '&Ucirc;', 'û' => '&ucirc;', 'Ü' => '&Uuml;', 'ü' => '&uuml;',
            'Ý' => '&Yacute;', 'ý' => '&yacute;', 'Ÿ' => '&Yuml;', 'ÿ' => '&yuml;'
        );
        return strtr($string, $str_trans);
    }

    // Enleve les accents
    public function speCharNoAccent($string)
    {
        $str_trans = array(
            'À' => 'A', 'à' => 'a', 'Á' => 'A', 'á' => 'a', 'Â' => 'A', 'â' => 'a', 'Ã' => 'A', 'ã' => 'a', 'Ä' => 'A',
            'ä' => 'a', 'Å' => 'A', 'å' => 'a', 'Æ' => 'A', 'æ' => 'a', 'Ç' => 'C', 'ç' => 'c', 'È' => 'E', 'è' => 'e',
            'É' => 'E', 'é' => 'e', 'Ê' => 'E', 'ê' => 'e', 'Ë' => 'E', 'ë' => 'e', 'Ì' => 'I', 'ì' => 'i', 'Í' => 'I',
            'í' => 'i', 'Î' => 'I', 'î' => 'i', 'Ï' => 'I', 'ï' => 'i', 'Ñ' => 'N', 'ñ' => 'n', 'Ò' => 'O', 'ò' => 'o',
            'Ó' => 'O', 'ó' => 'o', 'Ô' => 'O', 'ô' => 'o', 'Õ' => 'O', 'õ' => 'o', 'Ö' => 'O', 'ö' => 'o', 'Ø' => 'O',
            'ø' => 'o', 'Œ' => 'OE', 'œ' => 'oe', 'ß' => 'B', 'Ù' => 'U', 'ù' => 'u', 'Ú' => 'U', 'ú' => 'u',
            'Û' => 'U', 'û' => 'u', 'Ü' => 'U', 'ü' => 'u', 'Ý' => 'Y', 'ý' => 'y', 'Ÿ' => 'Y', 'ÿ' => 'y'
        );

        return strtr($string, $str_trans);
    }

    public function stripAccents($string)
    {
        return $this->speCharNoAccent($string);
    }

    // Nettoie une chaine pour l'inserer dans un csv
    public function nettoyageCsv($string)
    {
        $new_string = str_replace(";", "-", $string);
        $new_string = str_replace("\n\r", " ", $new_string);
        $new_string = str_replace("\r\n", " ", $new_string);
        $new_string = str_replace("\r", " ", $new_string);
        $new_string = str_replace("\n", " ", $new_string);

        return $new_string;
    }

    // Coupe la chaine $str avec une limite max de $limite, mais sans couper au milieu d'un mot ou d'une entite HTML
    public function subword($str, $limite)
    {
        $str       = trim(strip_tags($str));
        $tableWord = str_word_count($str, 1, '0123456789�&;');

        $nbrlettre = 0;
        foreach ($tableWord as $word) {
            $nbrlettre += strlen($word);
            if ($nbrlettre >= $limite) {
                break;
            } else {
                $nbrlettre++;
            }
        }
        if (strlen($str) > $nbrlettre) {
            return $phrase = substr($str, 0, $nbrlettre) . '...';
        } else {
            return $str;
        }
    }

    // met des majuscules sur les noms composés
    public function majNom($nom)
    {
        $nom = strtolower($nom);

        $pos = strrpos($nom, '-');
        if ($pos === false) {
            return ucwords($nom);
        } else {
            $tabNom = explode('-', $nom);
            $newNom = '';
            $i      = 0;
            foreach ($tabNom as $nom) {
                $newNom .= ($i == 0 ? '' : '-') . ucwords($nom);
                $i++;
            }
            return $newNom;
        }
    }

    public function str_split_unicode($str, $l = 0)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    public function swift_validate($swift)
    {
        if (!eregi("^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$", $swift)) {
            return false;
        } else {
            return true;
        }
    }

    public function isIBAN($iban)
    {
        $charConvert = array(
            "A" => "10", "B" => "11", "C" => "12", "D" => "13", "E" => "14", "F" => "15", "G" => "16", "H" => "17",
            "I" => "18", "J" => "19", "K" => "20", "L" => "21", "M" => "22", "N" => "23", "O" => "24", "P" => "25",
            "Q" => "26", "R" => "27",
            "S" => "28", "T" => "29", "U" => "30", "V" => "31", "W" => "32", "X" => "33", "Y" => "34", "Z" => "35"
        );
        $iban        = str_replace(" ", "", $iban);
        $iban        = strtoupper($iban);
        $iban        = substr($iban, 4) . substr($iban, 0, 4);
        $iban        = strtr($iban, $charConvert);
        return intval(bcmod($iban, 97)) === 1;
    }

    // convertit une chaine en tableau avec key personalisable (mis en place le 04/07/2014)
    // ex : $string = "1=>toto;2=>tata;5=>damien"
    public function explodeStr2array($string)
    {
        $string = explode(';', $string); // on explose aux points virgules
        $array  = array();
        foreach ($string as $c) {
            $tab       = explode('=>', $c); // maintenant on explose aux fleches
            $k         = $tab[0]; // key
            $val       = $tab[1]; // value
            $array[$k] = $val; // on a notre key et valeur
        }
        return $array;
    }


    // fonction qui check la complexité d'un mot de passe
    // 10 caractères mini / 1 chiffre / 1 caractère spécial
    public function password_bo($mdp)    // $mdp le mot de passe passé en paramètre
    {
        // On récupère la longueur du mot de passe
        $longueur = strlen($mdp);

        // On fait une boucle pour lire chaque lettre
        for ($i = 0; $i < $longueur; $i++) {

            // On sélectionne une à une chaque lettre
            // $i étant à 0 lors du premier passage de la boucle
            $lettre = $mdp[$i];

            if ($lettre >= 'a' && $lettre <= 'z') {
                // On ajoute 1 point pour une minuscule
                $point = $point + 1;

                // On rajoute le bonus pour une minuscule
                $point_min = 1;
            } else {
                if ($lettre >= 'A' && $lettre <= 'Z') {
                    // On ajoute 2 points pour une majuscule
                    $point = $point + 2;

                    // On rajoute le bonus pour une majuscule
                    $point_maj = 2;
                } else {
                    if ($lettre >= '0' && $lettre <= '9') {
                        // On ajoute 3 points pour un chiffre
                        $point = $point + 3;

                        // On rajoute le bonus pour un chiffre
                        $point_chiffre = 3;
                    } else {
                        // On ajoute 5 points pour un caractère autre
                        $point = $point + 5;

                        // On rajoute le bonus pour un caractère autre
                        $point_caracteres = 5;
                    }
                }
            }
        }

        // on vérifie que l'on a bien tout les critères nécessaires pour valider le pass
        /* Au moins 10 caratères, 1 chiffre et 1 caractère spécial */
        $valid = false;
        if ($longueur >= 10 && $point_caracteres == 5 && $point_chiffre == 3) {
            $valid = true;
        }


        return $valid;

    }

    //fonction qui check la complexité d'un mot de passe
    public function testpassword($mdp)    // $mdp le mot de passe passé en paramètre
    {
        // On récupère la longueur du mot de passe
        $longueur = strlen($mdp);

        // On fait une boucle pour lire chaque lettre
        for ($i = 0; $i < $longueur; $i++) {

            // On sélectionne une à une chaque lettre
            // $i étant à 0 lors du premier passage de la boucle
            $lettre = $mdp[$i];

            if ($lettre >= 'a' && $lettre <= 'z') {
                // On ajoute 1 point pour une minuscule
                $point = $point + 1;

                // On rajoute le bonus pour une minuscule
                $point_min = 1;
            } else {
                if ($lettre >= 'A' && $lettre <= 'Z') {
                    // On ajoute 2 points pour une majuscule
                    $point = $point + 2;

                    // On rajoute le bonus pour une majuscule
                    $point_maj = 2;
                } else {
                    if ($lettre >= '0' && $lettre <= '9') {
                        // On ajoute 3 points pour un chiffre
                        $point = $point + 3;

                        // On rajoute le bonus pour un chiffre
                        $point_chiffre = 3;
                    } else {
                        // On ajoute 5 points pour un caractère autre
                        $point = $point + 5;

                        // On rajoute le bonus pour un caractère autre
                        $point_caracteres = 5;
                    }
                }
            }
        }

        // on vérifie que l'on a bien tout les critères nécessaires pour valider le pass
        /* Au moins 10 caratères, une majuscule & un caractère spécial */
        $valid = false;
        if ($point_min == 1 && $point_maj == 2 && $longueur >= 10 && ($point_caracteres == 5 || $point_chiffre == 3)) {
            $valid = true;
        }


        // Calcul du coefficient points/longueur
        if ($longueur < 1) {
            $longueur = 1;
        }

        $etape1 = $point / $longueur;

        // Calcul du coefficient de la diversité des types de caractères...
        $etape2 = $point_min + $point_maj + $point_chiffre + $point_caracteres;

        // Multiplication du coefficient de diversité avec celui de la longueur
        $resultat = $etape1 * $etape2;

        // Multiplication du résultat par la longueur de la chaîne
        $final = $resultat * $longueur;


        // tableau de retour
        $tab_result = array("score" => $final, "valid" => $valid);


        return $tab_result;

    }

    // min, maj, caracteres speciaux obligatoire (chiffres obligatoire si true)
    public function password_fo($mdp, $length, $chiffres = false)    // $mdp le mot de passe passé en paramètre
    {
        // On récupère la longueur du mot de passe
        $longueur = strlen($mdp);

        // On fait une boucle pour lire chaque lettre
        for ($i = 0; $i < $longueur; $i++) {

            // On sélectionne une à une chaque lettre
            // $i étant à 0 lors du premier passage de la boucle
            $lettre = $mdp[$i];

            if ($lettre >= 'a' && $lettre <= 'z') {
                // On ajoute 1 point pour une minuscule
                $point = $point + 1;

                // On rajoute le bonus pour une minuscule
                $point_min = 1;
            } else {
                if ($lettre >= 'A' && $lettre <= 'Z') {
                    // On ajoute 2 points pour une majuscule
                    $point = $point + 2;

                    // On rajoute le bonus pour une majuscule
                    $point_maj = 2;
                } else {
                    if ($lettre >= '0' && $lettre <= '9') {
                        // On ajoute 3 points pour un chiffre
                        $point = $point + 3;

                        // On rajoute le bonus pour un chiffre
                        $point_chiffre = 3;
                    } else {
                        // On ajoute 5 points pour un caractère autre
                        $point = $point + 5;

                        // On rajoute le bonus pour un caractère autre
                        $point_caracteres = 5;
                    }
                }
            }
        }

        // on vérifie que l'on a bien tout les critères nécessaires pour valider le pass
        /* Au moins 10 caratères, 1 chiffre et 1 caractère spécial */
        $valid = false;
        if ($chiffres == true) {
            if ($longueur >= $length && $point_caracteres == 5 && $point_chiffre == 3 && $point_maj == 2 && $point_min == 1) {
                $valid = true;
            }
        } else {
            if ($longueur >= $length && $point_maj == 2 && $point_min == 1) {
                $valid = true;
            }
        }


        return $valid;

    }

    // Source
    public function source($utm_source = '', $url = '', $utm_source2 = '')
    {
        // source1
        if ($utm_source != '') {
            $_SESSION['utm_source'] = $utm_source;
        } elseif (!isset($_SESSION['utm_source']) || $_SESSION['utm_source'] == '') {
            if ($utm_source != '') {
                $source = $utm_source;
            } elseif ($url != '') {
                $source = $url;
            } else {
                $source = '';
            }

            $_SESSION['utm_source'] = $source;
        }

        // source2
        if ($utm_source2 != '') {
            $_SESSION['utm_source2'] = $utm_source2;
        } elseif (!isset($_SESSION['utm_source2'])) {
            $_SESSION['utm_source2'] = $utm_source2;
        }
    }

    // Générateur de token avec une clé unique
    public function genere_token($key)
    {
        $key   = md5($key);
        $time  = time();
        $token = base64_encode($key . '-' . $time);

        return $token;
    }

    // token receptionné
    // key = clé unique
    // temps = durée de validité (en secondes)
    public function verifier_token($token, $key, $temps)
    {
        $valide = false;

        // token
        if ($token != '') {

            $decode = base64_decode($token); // Decode token
            $tab    = explode('-', $decode); // sépare les données

            // dans les temps
            if ($tab[1] >= (time() - $temps)) {
                // key ok
                if ($tab[0] == md5($key)) {
                    $valide = true;
                }
            }
        }

        return $valide;
    }


    // mobile ou pas mobile
    public function is_mobile()
    {
        $SymbianOS  = stripos($_SERVER['HTTP_USER_AGENT'], 'SymbianOS');
        $IEMobile   = stripos($_SERVER['HTTP_USER_AGENT'], 'IEMobile');
        $Googlebot  = stripos($_SERVER['HTTP_USER_AGENT'], 'Googlebot-Mobile');
        $iPod       = stripos($_SERVER['HTTP_USER_AGENT'], 'iPod');
        $iPhone     = stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone');
        $android    = stripos($_SERVER['HTTP_USER_AGENT'], 'Android');
        $webOS      = stripos($_SERVER['HTTP_USER_AGENT'], 'webOS');
        $BlackBerry = stripos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry');

        // Condition
        if ($SymbianOS || $IEMobile || $Googlebot || $iPod || $iPhone || $android || $webOS || $BlackBerry) {
            return true;
        } else {
            return false;
        }
    }

    public function is_mobile_v2()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $isMobile  = false;

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            $isMobile = true;
        }

        return $isMobile;
    }

    // Motif mandat emprunteur
    public function motif_mandat($prenom, $nom, $id_project)
    {
        $p          = substr($this->generateSlug(trim($prenom)), 0, 1);
        $nom        = $this->generateSlug(trim($nom));
        $id_project = str_pad($id_project, 6, 0, STR_PAD_LEFT);
        return $motif = mb_strtoupper('UNILEND' . $id_project . 'E' . $p . $nom, 'UTF-8');
    }

    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
