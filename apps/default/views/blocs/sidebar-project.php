<?php

$bIsConnected = $this->clients->checkAccess();

// pour éviter de refaire tous les conditions une autre fois pour le mobile, on place une variable qui sera a true si le client peut prêter
$this->preter_by_mobile_ok = false;

if ($this->projects_status->status == \projects_status::REMBOURSEMENT_ANTICIPE) {
    $this->fireView('../blocs/sidebar-project/prepayment');
} elseif ($this->projects_status->status == \projects_status::FUNDE || $this->projects_status->status >= \projects_status::REMBOURSEMENT) {
    $this->fireView('../blocs/sidebar-project/ended');
} elseif ($this->projects_status->status == \projects_status::FUNDING_KO) {
    $this->fireView('../blocs/sidebar-project/funding-ko');
} elseif ($this->projects_status->status == \projects_status::PRET_REFUSE) {
    $this->fireView('../blocs/sidebar-project/rejected');
} elseif (false === $bIsConnected) {
    $this->fireView('../blocs/sidebar-project/not-connected');
} elseif ($this->page_attente == true) {
    $this->fireView('../blocs/sidebar-project/waiting');
} elseif ($this->clients_status->status < 60) {
    $this->fireView('../blocs/sidebar-project/pending');
} else {
    $this->fireView('../blocs/sidebar-project/default-connected');
    $this->preter_by_mobile_ok = true;
}
