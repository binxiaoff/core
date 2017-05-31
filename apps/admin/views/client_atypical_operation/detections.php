<div id="contenu">
    <fieldset style="color: black; padding: 10px; border: 1px solid #B10366; width: 320px;">
        <legend style="color: #B20066"><b>Statut de vigilance:</b></legend>
        <span class="vigilance-status-0">
            <?= $this->translator->trans('client-vigilance_status-0') ?>
        </span>
        &nbsp;
        <span class="vigilance-status-1">
            <?= $this->translator->trans('client-vigilance_status-1') ?>
        </span>
        &nbsp;
        <span class="vigilance-status-2">
            <?= $this->translator->trans('client-vigilance_status-2') ?>
        </span>
        &nbsp;
        <span class="vigilance-status-3">
            <?= $this->translator->trans('client-vigilance_status-3') ?>
        </span>
    </fieldset>
    <br>
    <h1>Détéctions en attente de traitement</h1>
    <?php if (empty($this->atypicalOperation['pending'])) : ?>
        <h2>Toutes les détéctions ont été traitées</h2>
    <?php else : ?>
        <a href="<?= $this->lurl ?>/client_atypical_operation/export/pending">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="exporter en CSV" title="Exporter en CSV">
        </a>
        <?php
        $this->atypicalOperations = $this->atypicalOperation['pending'];
        $this->fireView('detections_table');
        ?>
    <?php endif; ?>
    <h1>Détéctions en attente d'aquittement de la SFPMEI</h1>
    <?php if (false === empty($this->atypicalOperation['waitingACK'])) : ?>
        <a href="<?= $this->lurl ?>/client_atypical_operation/export/waiting">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="exporter en CSV" title="Exporter en CSV">
        </a>
        <?php
        $this->showUpdated        = true;
        $this->atypicalOperations = $this->atypicalOperation['waitingACK'];
        $this->fireView('detections_table');
        ?>
    <?php endif; ?>
    <h1>Détéctions traitées</h1>
    <?php if (false === empty($this->atypicalOperation['treated'])) : ?>
        <a href="<?= $this->lurl ?>/client_atypical_operation/export/treated">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="exporter en CSV" title="Exporter en CSV">
        </a>
        <?php
        $this->atypicalOperations = $this->atypicalOperation['treated'];
        $this->showUpdated        = true;
        $this->showActions        = false;
        $this->fireView('detections_table');
        ?>
    <?php endif; ?>
</div>
