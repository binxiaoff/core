<?php
// ****************************************************************************** //
// **************************** CLASSE PHOTO EQUINOA **************************** //
// ****************************************************************************** //
//
//
// Version 1.0
//
// C'est le complement de la classe upload pour les images.
// Elle permet de gérer l'affichage, le recadrage et le retaillage des images.
//
//
// ********************************************************************************* //
// ********************************************************************************* //

class photos
{
    // Repertoire de destination pour les fichiers
    private $upload_dir = '/';

    // Variable du fichier que l'on charge
    private $uploadedFileName;
    private $uploadedFileExtension;

    public function photos($params)
    {
        $this->spath   = $params[0];
        $this->surl    = $params[1];
        $this->formats = array(
            'admin_comp'                => array('w' => 50, 'h' => 50),
            'admin_imgs'                => array('w' => 100, 'h' => 60),
            'img_contenu2'              => array('w' => 226, 'h' => 157),
            'img_contenu1'              => array('w' => 270, 'h' => 158),
            'img_contact'               => array('w' => 386, 'h' => 238),
            'bloc_home'                 => array('w' => 148, 'h' => 47),
            'photo_projet_min'          => array('w' => 109, 'h' => 75),
            'photo_projet_moy'          => array('w' => 300, 'h' => 169),
            'picto_landing_page'        => array('w' => 105, 'h' => 93),
            'img_carousel_landing_page' => array('w' => 109, 'h' => 72),
            'partenaires_landing_page'  => array('w' => 97, 'h' => 49)
        );
    }

    // ************************************************************************************************************ //
    // *********************************** Fonction pour l'affichage de l'image *********************************** //
    // ************************************************************************************************************ //
    // $file -> Nom du fichier a afficher (obligatoire)
    // $type -> type du fichier (defini le dossier dans lequel se situe le fichier (ex: $type = maison -> images/maison)
    // $format -> fait appel a la liste des formats pour appeler la bonne image dans sa bonne taille
    // $w -> largeur max que l'on souhaite si pas dans format pour du one shot
    // $h -> hauteur max que l'on souhaite si pas dans format pour du one shot
    // $url -> utile si on a les tempaltes dans une appli mais qu'on appelle les pages dans une autre appli

    function display($file, $type = '', $format = '', $w = '', $h = '', $url = '', $cadre_fixe = false)
    {
        if ($url == '') {
            $url = $this->surl;
        }

        // Si le fichier appelé existe déjà
        if (file_exists($this->spath . 'images/' . ($type != '' ? $type . '/' : '') . ($format != '' ? $format . '_' : '') . $file) && ! is_dir($this->spath . 'images/' . ($type != '' ? $type . '/' : '') . ($format != '' ? $format . '_' : '') . $file)) {
            return $url . '/var/images/' . ($type != '' ? $type . '/' : '') . ($format != '' ? $format . '_' : '') . $file;
        } // Si le fichier original existe mais pas le format demandé alors on le créé
        elseif (file_exists($this->spath . 'images/' . ($type != '' ? $type . '/' : '') . $file) && ! is_dir($this->spath . 'images/' . ($type != '' ? $type . '/' : '') . $file)) {
            $this->uploadedFileName = $file;
            $this->upload_dir       = $this->spath . 'images/' . ($type != '' ? $type . '/' : '');

            // Recuperation de l'extension
            $extension                   = pathinfo($this->upload_dir . $this->uploadedFileName);
            $extension                   = $extension['extension'];
            $this->uploadedFileExtension = $extension;

            $w = ($w == '' ? $this->formats[$format]['w'] : $w);
            $h = ($h == '' ? $this->formats[$format]['h'] : $h);
            $this->resizeImageWH($w, $h, $format, $cadre_fixe);

            return $url . '/var/images/' . ($type != '' ? $type . '/' : '') . ($format != '' ? $format . '_' : '') . $file;
        } // Sinon on recupere l'image par defaut
        else {
            return $url . '/var/images/' . ($type != '' ? $type . '/' : '') . ($format != '' ? $format . '_' : '') . 'default.png';
        }
    }

    // **************************************************** //
    // RESIZE IMAGE AVEC UNE LARGEUR MAX ET UNE HAUTEUR MAX //
    // **************************************************** //

    // $w_max -> Largeur Max de la nouvelle Image
    // $h_max -> Hauteur Max de la nouvelle Image
    // $type -> Type de l'image (préfixe) -> facultatif

    public function resizeImageWH($w_max, $h_max, $type = '', $cadre_fixe = false)
    {
        $fichier = $this->upload_dir . $this->uploadedFileName;
        $dossier = $this->upload_dir;
        $nom     = $this->uploadedFileName;

        if ($cadre_fixe) {
            $w_cadre = $w_max;
            $h_cadre = $h_max;
        }

        // SI AUCUN TYPE SPECIFIE ON ECRASE L'IMAGE AU LIEU D'EN CREER UNE NOUVELLE
        if ($type != '') {
            $nom = $type . '_' . $nom;
        }

        // RECUPERATION TAILLE IMAGE ORIGINALE
        $taille_image = @getimagesize($fichier);

        $w = $taille_image[0];
        $h = $taille_image[1];

        // SI HAUTEUR IMG > LARGEUR IMG
        if ($h > $w) {
            $w_tmp = ($h_max / $h) * $w;    // LARGEUR TEMP
            $h_tmp = $h_max;                // HAUTEUR TEMP

            // SI HAUTEUR IMG < HAUTEUR MAX
            if ($h < $h_max) {
                // SI LARGEUR TEMP > LARGEUR MAX
                if ($w_tmp > $w_max) {
                    $width  = $w_max;                        // LARGEUR FINALE
                    $height = ($w_max / $w_tmp) * $h_tmp;    // HAUTEUR FINALE
                } else {
                    $width  = $w;    // LARGEUR FINALE
                    $height = $h;    // HAUTEUR FINALE
                }
            } else {
                // SI LARGEUR TEMP > LARGEUR MAX
                if ($w_tmp > $w_max) {
                    $width  = $w_max;                        // LARGEUR FINALE
                    $height = ($w_max / $w_tmp) * $h_tmp;    // HAUTEUR FINALE
                } else {
                    $width  = $w_tmp;    // LARGEUR FINALE
                    $height = $h_tmp;    // HAUTEUR FINALE
                }
            }
        } // SI LARGEUR IMG > HAUTEUR IMG
        elseif ($w > $h) {
            $w_tmp = $w_max;                // LARGEUR TEMP
            $h_tmp = ($w_max / $w) * $h;    // LARGEUR TEMP

            // SI LARGEUR IMG < LARGEUR MAX
            if ($w < $w_max) {
                // SI HAUTEUR TEMP > HAUTEUR MAX
                if ($h_tmp > $h_max) {
                    $width  = ($h_max / $h_tmp) * $w_tmp;    // LARGEUR FINALE
                    $height = $h_max;                        // HAUTEUR FINALE
                } else {
                    $width  = $w;    // LARGEUR FINALE
                    $height = $h;    // HAUTEUR FINALE
                }
            } else {
                // SI HAUTEUR TEMP > HAUTEUR MAX
                if ($h_tmp > $h_max) {
                    $width  = ($h_max / $h_tmp) * $w_tmp;    // LARGEUR FINALE
                    $height = $h_max;                        // HAUTEUR FINALE
                } else {
                    $width  = $w_tmp;    // LARGEUR FINALE
                    $height = $h_tmp;    // HAUTEUR FINALE
                }
            }
        } // SI LARGEUR IMG = HAUTEUR IMG
        elseif ($w == $h) {
            // SI HAUTEUR MAX < LARGEUR MAX
            if ($h_max < $w_max) {
                // SI HAUTEUR IMG < HAUTEUR MAX
                if ($h < $h_max) {
                    $width  = $w;    // LARGEUR FINALE
                    $height = $h;    // HAUTEUR FINALE
                } else {
                    $width  = $h_max;    // LARGEUR FINALE
                    $height = $h_max;    // HAUTEUR FINALE
                }
            } else {
                // SI LARGEUR IMG < LARGEUR MAX
                if ($w < $w_max) {
                    $width  = $w;    // LARGEUR FINALE
                    $height = $h;    // HAUTEUR FINALE
                } else {
                    $width  = $w_max;    // LARGEUR FINALE
                    $height = $w_max;    // HAUTEUR FINALE
                }
            }
        }

        // CAS D'UN FICHIER GIF
        if ($this->uploadedFileExtension == 'gif' || $this->uploadedFileExtension == 'GIF') {
            if ($cadre_fixe) {
                // PREPARATION DU FICHIER DE L'IMAGE FINALE
                $img_tmp = imagecreatetruecolor($w_cadre, $h_cadre);
                $color   = imagecolorallocate($img_tmp, 255, 255, 255);
                imagefilledrectangle($img_tmp, 0, 0, $w_cadre, $h_cadre, $color);

                // DEFINITION DE L'IMAGE QUE L'ON VA RETAILLER
                $img = imagecreatefromgif($fichier);

                // ON REDIMENSIONNE L'IMAGE DANS LE FICHIER IMAGE FINALE
                $x = ($w_cadre - $width) / 2;
                $y = ($h_cadre - $height) / 2;
                imagecopyresampled($img_tmp, $img, $x, $y, 0, 0, $width, $height, $w, $h);
            } else {
                // PREPARATION DU FICHIER DE L'IMAGE FINALE
                $img_tmp = imagecreatetruecolor($width, $height);

                // DEFINITION DE L'IMAGE QUE L'ON VA RETAILLER
                $img = imagecreatefromgif($fichier);

                // RESTITUTION DES EVENTUELLES TRANSPARENCES
                imagealphablending($img_tmp, false);
                imagesavealpha($img_tmp, true);
                $transparent = imagecolorallocatealpha($img_tmp, 255, 255, 255, 127);
                imagefilledrectangle($img_tmp, 0, 0, $width, $height, $transparent);

                // ON REDIMENSIONNE L'IMAGE DANS LE FICHIER IMAGE FINALE
                imagecopyresampled($img_tmp, $img, 0, 0, 0, 0, $width, $height, $w, $h);
            }

            // ON CREE LE FICHIER RETAILLE FINAL
            imagegif($img_tmp, $dossier . $nom);

            // ON DONNE UN ACCES TOTAL SUR LE FICHIER
            chmod($dossier . $nom, 0777);

            // ON DETRUIT LES FICHIERS TEMPORAIRES
            imagedestroy($img_tmp);
            imagedestroy($img);
        }

        // CAS D'UN FICHIER JPEG/JPG/PJPEG
        if (in_array($this->uploadedFileExtension, array('jpg', 'JPG', 'jpeg', 'JPEG'))) {
            if ($cadre_fixe) {
                // PREPARATION DU FICHIER DE L'IMAGE FINALE
                $img_tmp = imagecreatetruecolor($w_cadre, $h_cadre);
                $color   = imagecolorallocate($img_tmp, 255, 255, 255);
                imagefilledrectangle($img_tmp, 0, 0, $w_cadre, $h_cadre, $color);

                // DEFINITION DE L'IMAGE QUE L'ON VA RETAILLER
                $img = imagecreatefromjpeg($fichier);

                // ON REDIMENSIONNE L'IMAGE DANS LE FICHIER IMAGE FINALE
                $x = ($w_cadre - $width) / 2;
                $y = ($h_cadre - $height) / 2;
                imagecopyresampled($img_tmp, $img, $x, $y, 0, 0, $width, $height, $w, $h);
            } else {
                // PREPARATION DU FICHIER DE L'IMAGE FINALE
                $img_tmp = imagecreatetruecolor($width, $height);

                // DEFINITION DE L'IMAGE QUE L'ON VA RETAILLER
                $img = imagecreatefromjpeg($fichier);

                // ON REDIMENSIONNE L'IMAGE DANS LE FICHIER IMAGE FINALE
                imagecopyresampled($img_tmp, $img, 0, 0, 0, 0, $width, $height, $w, $h);
            }

            // ON CREE LE FICHIER RETAILLE FINAL
            imagejpeg($img_tmp, $dossier . $nom, 100);

            // ON DONNE UN ACCES TOTAL SUR LE FICHIER
            chmod($dossier . $nom, 0777);

            // ON DETRUIT LES FICHIERS TEMPORAIRES
            imagedestroy($img_tmp);
            imagedestroy($img);
        }

        // CAS D'UN FICHIER PNG/X-PNG
        if ($this->uploadedFileExtension == 'png' || $this->uploadedFileExtension == 'PNG') {
            if ($cadre_fixe) {
                // PREPARATION DU FICHIER DE L'IMAGE FINALE
                $img_tmp = imagecreatetruecolor($w_cadre, $h_cadre);
                $color   = imagecolorallocate($img_tmp, 255, 255, 255);
                imagefilledrectangle($img_tmp, 0, 0, $w_cadre, $h_cadre, $color);

                // DEFINITION DE L'IMAGE QUE L'ON VA RETAILLER
                $img = imagecreatefrompng($fichier);

                // ON REDIMENSIONNE L'IMAGE DANS LE FICHIER IMAGE FINALE
                $x = ($w_cadre - $width) / 2;
                $y = ($h_cadre - $height) / 2;
                imagecopyresampled($img_tmp, $img, $x, $y, 0, 0, $width, $height, $w, $h);
            } else {
                // PREPARATION DU FICHIER DE L'IMAGE FINALE
                $img_tmp = imagecreatetruecolor($width, $height);

                // DEFINITION DE L'IMAGE QUE L'ON VA RETAILLER
                $img = imagecreatefrompng($fichier);

                // RESTITUTION DES EVENTUELLES TRANSPARENCES
                imagealphablending($img_tmp, false);
                imagesavealpha($img_tmp, true);
                $transparent = imagecolorallocatealpha($img_tmp, 255, 255, 255, 127);
                imagefilledrectangle($img_tmp, 0, 0, $width, $height, $transparent);

                // ON REDIMENSIONNE L'IMAGE DANS LE FICHIER IMAGE FINALE
                imagecopyresampled($img_tmp, $img, 0, 0, 0, 0, $width, $height, $w, $h);
            }

            // ON CREE LE FICHIER RETAILLE FINAL
            imagepng($img_tmp, $dossier . $nom, 0);

            // ON DONNE UN ACCES TOTAL SUR LE FICHIER
            chmod($dossier . $nom, 0777);

            // ON DETRUIT LES FICHIERS TEMPORAIRES
            imagedestroy($img_tmp);
            imagedestroy($img);
        }
    }
}
