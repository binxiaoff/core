<?php
// page presse
$this->settings->get('id_dossier_presse','type');
$this->id_dossier_presse = $this->settings->value;

if($this->tree->id_tree == $this->id_dossier_presse)
{
    //on va récupérer les enfants en fonction de leur date de publication
    $this->childsContent = $this->tree->select_enfant_presse($this->id_dossier_presse);
}
