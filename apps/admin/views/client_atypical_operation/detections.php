<div id="contenu">
    <h1>Détéctions en attente de traitement</h1>
    <?php if (empty($this->atypicalOperation['pending'])) : ?>
        <h2>Toutes les détéctions ont été traitées</h2>
    <?php else : ?>
        <a href="<?= $this->lurl ?>/client_atypical_operation/export/pending">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="exporter en CSV" title="Exporter en CSV">
        </a>
        <?php $this->atypicalOperations = $this->atypicalOperation['pending'] ?>
        <?php $this->fireView('detections_table'); ?>
    <?php endif; ?>
    <h1>Détéctions en attente d'aquittement de la SFPMEI</h1>
    <?php if (false === empty($this->atypicalOperation['waitingACK'])) : ?>
        <a  href="<?= $this->lurl ?>/client_atypical_operation/export/waiting">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="exporter en CSV" title="Exporter en CSV">
        </a>
        <?php $this->atypicalOperations = $this->atypicalOperation['waitingACK'] ?>
        <?php $this->fireView('detections_table'); ?>
    <?php endif; ?>
    <h1>Détéctions traitées</h1>
    <?php if (false === empty($this->atypicalOperation['treated'])) : ?>
        <a href="<?= $this->lurl ?>/client_atypical_operation/export/treated">
            <img src="<?= $this->surl ?>/images/admin/xls.png" alt="exporter en CSV" title="Exporter en CSV">
        </a>
        <?php
        $this->atypicalOperations = $this->atypicalOperation['treated'];
        $this->showActions        = false;
        $this->fireView('detections_table');
        ?>
    <?php endif; ?>
</div>
