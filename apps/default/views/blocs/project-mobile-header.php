<?php

if ($this->projects_status->status == \projects_status::REMBOURSEMENT_ANTICIPE) {
    $this->fireView('../blocs/project-mobile-header/prepayment');
} elseif ($this->projects_status->status == \projects_status::FUNDE || $this->projects_status->status >= \projects_status::REMBOURSEMENT) {
    $this->fireView('../blocs/project-mobile-header/ended');
} elseif ($this->projects_status->status == \projects_status::FUNDING_KO) {
    $this->fireView('../blocs/project-mobile-header/funding-ko');
} elseif ($this->projects_status->status == \projects_status::PRET_REFUSE) {
    $this->fireView('../blocs/project-mobile-header/rejected');
} elseif ($this->bIsConnected && $this->page_attente) {
    $this->fireView('../blocs/project-mobile-header/waiting');
} else {
    $this->fireView('../blocs/project-mobile-header/default');
}
