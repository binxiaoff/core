<?php if (in_array($this->current_projects_status->status, array(\projects_status::A_TRAITER, \projects_status::EN_ATTENTE_PIECES, \projects_status::REJETE, \projects_status::A_FUNDER))) { ?>
    <input style="background-color:#AAACAC;" type="text" name="date_publication" id="date_pub" class="input_dp" value="<?= ($this->projects->date_publication != '0000-00-00' ? $this->dates->formatDate($this->projects->date_publication, 'd/m/Y') : '') ?>"/>
<?php } else { ?>
    <?= $this->dates->formatDate($this->projects->date_publication, 'd/m/Y') ?>
<?php } ?>
