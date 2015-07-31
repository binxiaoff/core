<?php

$email = (isset($_POST["email"])) ? $_POST["email"] : NULL;
$source3 = (isset($_POST["source3"])) ? $_POST["source3"] : NULL;
$utm_source3_autre = (isset($_POST["utm_source3_autre"])) ? $_POST["utm_source3_autre"] : NULL;

if($email) {
    //On créé une variable pour donner un nom au fichier 
    $fichier = "source3.txt";   
    //On ouvre le fichier. Si il n'existe pas il sera créé automatiquement 
    $fichier_a_ouvrir = fopen ($fichier, "a+"); 
    //On écrit dans le fichier 
    fwrite($fichier_a_ouvrir, $email.'/'.$source3); 
    if($utm_source3_autre) {
        fwrite($fichier_a_ouvrir, '/'.$utm_source3_autre); 
    }
    fwrite($fichier_a_ouvrir, "\r\n"); 
    //On ferme la connexion 
    fclose ($fichier_a_ouvrir);
}

?>