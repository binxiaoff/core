<ul>
    <li>
        <i class="ico-calendar"></i>
        <?= ($this->projects->period == 1000000 ? $this->lng['preteur-projets']['je-ne-sais-pas'] : $this->projects->period . ' ' . $this->lng['preteur-projets']['mois']) ?>
    </li>
    <li>
        <i class="ico-gauge" style="height:14px; top:-5px;"></i>
        <div class="cadreEtoiles" style="display:inherit; top:3px;">
            <div class="etoile <?= $this->lNotes[$this->projects->risk] ?>"></div>
        </div>
    </li>
    <li>
        <i class="ico-chart"></i>
        <?php if ($this->CountEnchere > 0) { ?>
            <span><?= number_format(($this->projects_status->status == 60 || $this->projects_status->status >= 80) ? $this->AvgLoans : $this->avgRate, 1, ',', ' ') . ' %' ?></span>
        <?php } else { ?>
            <span><?= $this->projects->target_rate . ($this->projects->target_rate == '-' ? '' : ' %') ?></span>
        <?php } ?>
    </li>
</ul>
