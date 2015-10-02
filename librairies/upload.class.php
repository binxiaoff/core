<?php

// ********************************************************************************* //
// **************************** CLASSE D'UPLOAD EQUINOA **************************** //
// ********************************************************************************* //
//
// Version 4.1
//
// La classe upload désormais se limite a l'upload de fichiers.
// Le check se fait sur les extensions et non plus sur les types.
// La fonction doUpload renvoi true ou false.
// La fonction getErrorType vous permet de retourner le type de l'erreur.
//
// 4.1 : Modification du nettoyage de nom pour enlever l'extension.
// 4.1 : Modification du renommage de fichier avec le time(), ajout d'un sleep pour laisser au time le temps de s'incrementer.
// 4.1 : Ajout de la fonction doRetrieval() qui fait tout comme la doUpload mais a partir d'un fichier sur le grand Internet.
//
// La classe upload est désormais accompagné d'une classe photo pour les images.
//
//
// ********************************************************************************* //
// ********************************************************************************* //

class upload
{
    // Variable contenant le message d'erreur de l'upload
    // Si l'upload se passe bien la fonction doUpload renvoi TRUE
    private $msg_erreur;

    // Variable du fichier que l'on upload
    private $uploadedFileName;
    private $uploadedFile;
    private $uploadedFileSize;
    private $uploadedFileType;
    private $uploadedFileExtension;

    // Repertoire de destination pour les fichiers
    private $upload_dir = '/';

    // Taille max des fichiers pouvant êtres uploadés en octets (rappel : 1Ko -> 1024 octets)
    // Par defaut la valeur est de 8Mo
    private $taille_max = 20971520; // 20Mo

    // Tableau des extensions que l'on autorise à être uploadé
    private $ext_valides = array(
        'jpeg', 'JPEG', 'jpg', 'JPG', 'png', 'PNG', 'gif', 'GIF', 'pdf', 'PDF', 'doc', 'DOC', 'xls', 'XLS', 'ppt',
        'PPT', 'csv', 'CSV', 'swf', 'SWF', 'pptx', 'PPTX', 'docx', 'DOCX', 'xlsx', 'XLSX', 'TXT', 'txt', 'TIFF', 'tiff'
    );

    private $ext_validesVideo = array('ogv', 'mp4', 'webm');

    // Constructeur
    public function __construct()
    {
        // Je préfère définir mes paramètres d'upload dans la méthode doUpload()
        // comme ça je peux construire mon objet Upload() avant même de recevoir un fichier d'un formulaire
        // et balancer l'upload quand je veux avec cette méthode
    }

    // *********************************************** //
    // Fonction pour definir un dossier de destination //
    // *********************************************** //
    public function setUploadDir($path, $upload_dir)
    {
        // On decoupe la definition du dossier de destination pour la creation des dossiers
        // $path est le repertoire du site
        // $upload_dir est le chemin des dossiers dans lequel sera le fichier
        $this->upload_dir = $path . $upload_dir;
        $this->path_dir   = $path;
        $this->file_dir   = $upload_dir;
    }

    // ********************************************* //
    // Fonction pour definir un Poids Max au fichier //
    // ********************************************* //
    public function setPoidsMax($taille_max)
    {
        $this->taille_max = $taille_max;
    }

    // *********************************************** //
    // Fonction pour definir les extensions de fichier //
    // *********************************************** //
    public function setExtValide($ext_valides)
    {
        $this->ext_valides = $ext_valides;
    }

    // ******************************************** //
    // Fonction pour definir le nom fichier uploadé //
    // ******************************************** //
    public function setUploadedFileName($uploadedFileName)
    {
        $this->uploadedFileName = $uploadedFileName;
    }

    // **************************************************************** //
    // Fonction pour nettoyer le nom du fichier si on ne le renomme pas //
    // **************************************************************** //
    private function clean_name($name_file)
    {
        $name_file = strtr($name_file, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyyNn');
        return strtolower(preg_replace('/([^.a-z0-9]+)/i', '-', $name_file));
    }

    // ******************************************* //
    // Fonction qui retourne les messages d'erreur //
    // ******************************************* //
    public function getErrorType()
    {
        return $this->msg_erreur;
    }

    // ************************************************ //
    // Fonction qui récupère le nom du fichier uploader //
    // ************************************************ //
    public function getName()
    {
        return $this->uploadedFileName;
    }

    // ************************************************* //
    // Fonction qui récupère le type du fichier uploader //
    // ************************************************* //
    public function getTypeMine()
    {
        return $this->uploadedFileType;
    }

    // ***************************************************** //
    // Fonction qui récupère l'extension du fichier uploader //
    // ***************************************************** //
    public function getExtension()
    {
        return $this->uploadedFileExtension;
    }

    // ************************************************** //
    // Fonction qui récupère le poids du fichier uploader //
    // ************************************************** //
    public function getFileSize()
    {
        return $this->uploadedFileSize;
    }

    // *********************************************************************************************************** //
    // *********************************** Fonction pour l'upload des fichiers *********************************** //
    // *********************************************************************************************************** //
    // $file_form_name -> Nom du champ file du formulaire (obligatoire)
    // $new_name -> Nouveau nom du fichier sans extension (facultatif)
    // $erase -> true -> Permet de remplacer le fichier sur le serveur s'il existe deja (facultatif)
    public function doUpload($file_form_name, $new_name = '', $erase = false)
    {
        // Recuperation de l'extension du fichier
        $extension = pathinfo($_FILES[$file_form_name]['name']);
        $extension = $extension['extension'];

        // Recuperation du nom sans l'extension
        $nom_tmp = explode('.', $_FILES[$file_form_name]['name']);
        $nom_tmp = $nom_tmp[0];

        // Si l'on a pas donné un nouveau nom au fichier, il garde le nom d'origine
        if ($new_name == '') {
            // On clean le nom d'origine
            $this->uploadedFileName = $this->clean_name($nom_tmp) . '.' . $extension;
        } else {
            // On applique sur le nouveau nom
            $this->uploadedFileName = $new_name . '.' . $extension;
        }

        // Récupération du nom temporaire sur le serveur, de la taille du fichier, son type et son extension
        $this->uploadedFile          = $_FILES[$file_form_name]['tmp_name'];
        $this->uploadedFileSize      = $_FILES[$file_form_name]['size'];
        $this->uploadedFileType      = $_FILES[$file_form_name]['type'];
        $this->uploadedFileExtension = $extension;

        // On commence par verifier que le dossier d'upload existe sinon on le crée
        $this->file_dir = explode('/', $this->file_dir);

        for ($i = 0; $i < count($this->file_dir); $i++) {
            if ($this->file_dir[$i] != '') {
                $this->path_dir .= '/' . $this->file_dir[$i];

                if (!is_dir($this->path_dir)) {
                    if (!mkdir($this->path_dir, 0777)) {
                        $this->msg_erreur = 'Impossible de creer le repertoire : ' . $this->path_dir;
                        return false;
                    }
                }
            }
        }

        // On verifie que le fichier soit bien uploader pour des questions de securite
        if (is_uploaded_file($this->uploadedFile) && !empty($this->uploadedFile)) {
            // On verifie que le fichier n'est pas trop lourd
            if ($this->uploadedFileSize < $this->taille_max) {
                // On verifie que le fichier est bien d'une extension valide
                if (in_array($this->uploadedFileExtension, $this->ext_valides)) {
                    // On vérifie que le nom de fichier n'est pas déjà utilisé sinon on lui rajoute le timestamp a la fin seulement si le erase est a false
                    if (file_exists($this->upload_dir . $this->uploadedFileName) && $erase == false) {
                        sleep(1); // Temporisation du script pour eviter d'avoir une image avec le même nom (time() s'increment toutes les secondes)
                        $this->uploadedFileName = time() . '-' . $this->uploadedFileName;
                    }

                    // Si le fichier est ok, on l'upload sur le serveur
                    if (move_uploaded_file($this->uploadedFile, $this->upload_dir . $this->uploadedFileName)) {
                        // On donne un acces total sur le fichier
                        chmod($this->upload_dir . $this->uploadedFileName, 0777);
                        return true;
                    } else {
                        $this->msg_erreur = 'Un probleme est survenu pendant le chargement du fichier';
                        return false;
                    }
                } else {
                    $this->msg_erreur = 'Le fichier a une mauvaise extension (ext : ' . $this->uploadedFileExtension . ')';
                    return false;
                }
            } else {
                $this->msg_erreur = 'Le fichier est trop lourd (size : ' . $this->uploadedFileSize . ')';
                return false;
            }
        } else {
            $this->msg_erreur = 'Aucun fichier a uploader';
            return false;
        }
    }

    // ************************************************************************************************************************** //
    // *********************************** Fonction pour la recuperation de fichiers distants *********************************** //
    // ************************************************************************************************************************** //
    // $url -> Url complete du fichier distant (obligatoire)
    // $new_name -> Nouveau nom du fichier sans extension (facultatif)
    // $erase -> true -> Permet de remplacer le fichier sur le serveur s'il existe deja (facultatif)
    public function doRetrieval($url, $new_name = '', $erase = false)
    {
        // On explode l'url et on compte le nombre d'elements retournes pour pouvoir recuperer le nom du fichier (=> le dernier element de l'url)
        $tab     = explode('/', $url);
        $taille  = count($tab);
        $nom_tmp = $tab[$taille - 1];

        // Recuperation de l'extension du fichier
        $ext_tmp                     = explode('.', $nom_tmp);
        $this->uploadedFileExtension = $ext_tmp[1];
        $this->uploadedFileName      = $ext_tmp[0];

        // Si l'on a pas donné un nouveau nom au fichier, il garde le nom d'origine
        if ($new_name == '') {
            // On clean le nom d'origine
            $this->uploadedFileName = $this->clean_name($this->uploadedFileName);
        } else {
            // On applique sur le nouveau nom
            $this->uploadedFileName = $new_name . '.' . $this->uploadedFileExtension;
        }

        // On commence par verifier que le dossier d'upload existe sinon on le crée
        $this->file_dir = explode('/', $this->file_dir);

        for ($i = 0; $i < count($this->file_dir); $i++) {
            if ($this->file_dir[$i] != '') {
                $this->path_dir .= '/' . $this->file_dir[$i];

                if (!is_dir($this->path_dir)) {
                    if (!mkdir($this->path_dir, 0777)) {
                        $this->msg_erreur = 'Impossible de creer le repertoire : ' . $this->path_dir;
                        return false;
                    }
                }
            }
        }

        // On verifie que le fichier existe bien
        if (@file_get_contents($url)) {
            // On verifie que le fichier est bien d'une extension valide
            if (in_array($this->uploadedFileExtension, $this->ext_valides)) {
                // On vérifie que le nom de fichier n'est pas déjà utilisé sinon on lui rajoute le timestamp a la fin seulement si le erase est a false
                if (file_exists($this->upload_dir . $this->uploadedFileName) && $erase == false) {
                    sleep(1); // Temporisation du script pour eviter d'avoir une image avec le même nom (time() s'increment toutes les secondes)
                    $this->uploadedFileName = $this->uploadedFileName . '-' . time();
                }

                // Si le fichier est ok, on l'upload sur le serveur
                if (copy($url, $this->upload_dir . $this->uploadedFileName)) {
                    // On donne un acces total sur le fichier
                    chmod($this->upload_dir . $this->uploadedFileName, 0777);
                    return true;
                } else {
                    $this->msg_erreur = 'Un probleme est survenu pendant le chargement du fichier';
                    return false;
                }
            } else {
                $this->msg_erreur = 'Le fichier a une mauvaise extension (ext : ' . $this->uploadedFileExtension . ')';
                return false;
            }
        } else {
            $this->msg_erreur = 'Aucun fichier a uploader';
            return false;
        }
    }
}
