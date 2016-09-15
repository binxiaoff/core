<?php

class photos
{
    // Repertoire de destination pour les fichiers
    private $upload_dir = '/';

    // Variable du fichier que l'on charge
    private $uploadedFileName;
    private $uploadedFileExtension;

    public function __construct($params)
    {
        $this->spath   = $params[0];
        $this->surl    = $params[1];
        $this->formats = array(
            'bloc_home'                => array('w' => 148, 'h' => 47),
            'img_contact'              => array('w' => 386, 'h' => 238),
            'img_contenu1'             => array('w' => 270, 'h' => 158),
            'img_contenu2'             => array('w' => 226, 'h' => 157),
            'partenaires_landing_page' => array('w' => 97, 'h' => 49),
            'picto_landing_page'       => array('w' => 105, 'h' => 93)
        );
    }

    /**
     * Fonction pour l'affichage de l'image
     * @param string $file   Nom du fichier à afficher
     * @param string $format Fait appel à la liste des formats pour appeler la bonne image dans sa bonne taille
     * @return string
     */
    public function display($file, $format = '')
    {
        if (file_exists($this->spath . 'images/' . ($format != '' ? $format . '_' : '') . $file) && ! is_dir($this->spath . 'images/' . ($format != '' ? $format . '_' : '') . $file)) {
            return $this->surl . '/var/images/' . ($format != '' ? $format . '_' : '') . $file;
        } elseif (file_exists($this->spath . 'images/' . $file) && ! is_dir($this->spath . 'images/' . $file)) {
            $this->uploadedFileName      = $file;
            $this->upload_dir            = $this->spath . 'images/';
            $this->uploadedFileExtension = pathinfo($this->upload_dir . $this->uploadedFileName)['extension'];

            $this->resizeImageWH($format);

            return $this->surl . '/var/images/' . ($format != '' ? $format . '_' : '') . $file;
        } else {
            return $this->surl . '/var/images/' . ($format != '' ? $format . '_' : '') . 'default.png';
        }
    }

    /**
     * Resize image avec une largeur max et une hauteur max
     * @param string $format Type de l'image
     */
    public function resizeImageWH($format = '')
    {
        $w_max        = $this->formats[$format]['w'];
        $h_max        = $this->formats[$format]['h'];
        $newImagePath = $this->uploadedFileName;

        // Si aucun préfixe n'est spécifié, on écrase l'image au lieu d'en créer une nouvelle
        if ($format != '') {
            $newImagePath = $format . '_' . $this->uploadedFileName;
        }

        $imageSize = getimagesize($this->upload_dir . $this->uploadedFileName);

        if (false === $imageSize) {
            trigger_error('Unable to get image size: ' . $this->uploadedFileName, E_USER_WARNING);
            return;
        }

        $w = $imageSize[0];
        $h = $imageSize[1];

        if ($h > $w) {
            $w_tmp = ($h_max / $h) * $w;
            $h_tmp = $h_max;

            if ($h < $h_max) {
                if ($w_tmp > $w_max) {
                    $width  = $w_max;
                    $height = ($w_max / $w_tmp) * $h_tmp;
                } else {
                    $width  = $w;
                    $height = $h;
                }
            } else {
                if ($w_tmp > $w_max) {
                    $width  = $w_max;
                    $height = ($w_max / $w_tmp) * $h_tmp;
                } else {
                    $width  = $w_tmp;
                    $height = $h_tmp;
                }
            }
        } elseif ($w > $h) {
            $w_tmp = $w_max;
            $h_tmp = ($w_max / $w) * $h;

            if ($w < $w_max) {
                if ($h_tmp > $h_max) {
                    $width  = ($h_max / $h_tmp) * $w_tmp;
                    $height = $h_max;
                } else {
                    $width  = $w;
                    $height = $h;
                }
            } else {
                if ($h_tmp > $h_max) {
                    $width  = ($h_max / $h_tmp) * $w_tmp;
                    $height = $h_max;
                } else {
                    $width  = $w_tmp;
                    $height = $h_tmp;
                }
            }
        } else {
            if ($h_max < $w_max) {
                if ($h < $h_max) {
                    $width  = $w;
                    $height = $h;
                } else {
                    $width  = $h_max;
                    $height = $h_max;
                }
            } else {
                if ($w < $w_max) {
                    $width  = $w;
                    $height = $h;
                } else {
                    $width  = $w_max;
                    $height = $w_max;
                }
            }
        }

        if (in_array($this->uploadedFileExtension, array('gif', 'GIF', 'jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG'))) {
            $img_tmp = imagecreatetruecolor($width, $height);

            switch ($this->uploadedFileExtension) {
                case 'gif':
                case 'GIF':
                    $img = imagecreatefromgif($this->upload_dir . $this->uploadedFileName);
                    imagealphablending($img_tmp, false);
                    imagesavealpha($img_tmp, true);
                    $transparent = imagecolorallocatealpha($img_tmp, 255, 255, 255, 127);
                    imagefilledrectangle($img_tmp, 0, 0, $width, $height, $transparent);
                    imagecopyresampled($img_tmp, $img, 0, 0, 0, 0, $width, $height, $w, $h);
                    imagegif($img_tmp, $this->upload_dir . $newImagePath);
                    break;
                case 'jpg':
                case 'JPG':
                case 'jpeg':
                case 'JPEG':
                    $img = imagecreatefromjpeg($this->upload_dir . $this->uploadedFileName);
                    imagecopyresampled($img_tmp, $img, 0, 0, 0, 0, $width, $height, $w, $h);
                    imagejpeg($img_tmp, $this->upload_dir . $newImagePath, 100);
                    break;
                case 'png':
                case 'PNG':
                    $img = imagecreatefrompng($this->upload_dir . $this->uploadedFileName);
                    imagealphablending($img_tmp, false);
                    imagesavealpha($img_tmp, true);
                    $transparent = imagecolorallocatealpha($img_tmp, 255, 255, 255, 127);
                    imagefilledrectangle($img_tmp, 0, 0, $width, $height, $transparent);
                    imagecopyresampled($img_tmp, $img, 0, 0, 0, 0, $width, $height, $w, $h);
                    imagepng($img_tmp, $this->upload_dir . $newImagePath, 0);
                    break;
                default:
                    trigger_error('Invalid image extension: ' . $this->uploadedFileExtension, E_USER_ERROR);
                    return;
            }

            chmod($this->upload_dir . $newImagePath, 0777);
            imagedestroy($img_tmp);
            imagedestroy($img);
        } else {
            trigger_error('Invalid image extension: ' . $this->uploadedFileExtension, E_USER_ERROR);
        }
    }
}
