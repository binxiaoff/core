<?php

if ($this->projects_status->status == \projects_status::REMBOURSEMENT_ANTICIPE) {
    $this->fireView('../blocs/sidebar-project/prepayment');
} elseif ($this->projects_status->status == \projects_status::FUNDE || $this->projects_status->status >= \projects_status::REMBOURSEMENT) {
    $this->fireView('../blocs/sidebar-project/ended');
} elseif ($this->projects_status->status == \projects_status::FUNDING_KO) {
    $this->fireView('../blocs/sidebar-project/funding-ko');
} elseif ($this->projects_status->status == \projects_status::PRET_REFUSE) {
    $this->fireView('../blocs/sidebar-project/rejected');
} elseif (false === $this->bIsConnected) {
    $this->fireView('../blocs/sidebar-project/not-connected');
} elseif ($this->page_attente) {
    $this->fireView('../blocs/sidebar-project/waiting');
} elseif ($this->clients_status->status < 60) {
    $this->fireView('../blocs/sidebar-project/pending');
} else {
    $this->fireView('../blocs/sidebar-project/default-connected');
}
