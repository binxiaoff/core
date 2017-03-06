<div id="contenu">
    <h1>Détéctions en attente de traitement</h1>
    <?php if (empty($this->atypicalOperation['pending'])) : ?>
        <h2>Toutes les détéctions ont été traitées</h2>
    <?php else : ?>
        <?php $this->atypicalOperations = $this->atypicalOperation['pending'] ?>
        <?php $this->fireView('detections_table'); ?>
    <?php endif; ?>
    <h1>Détéctions en attente d'aquittement de la SFPMEI</h1>
    <?php if (false === empty($this->atypicalOperation['waitingACK'])) : ?>
        <?php $this->atypicalOperations = $this->atypicalOperation['waitingACK'] ?>
        <?php $this->fireView('detections_table'); ?>
    <?php endif; ?>
    <h1>Détéctions traitées</h1>
    <?php if (false === empty($this->atypicalOperation['treated'])) : ?>
        <?php
        $this->atypicalOperations = $this->atypicalOperation['treated'];
        $this->showActions        = false;
        $this->fireView('detections_table');
        ?>
    <?php endif; ?>
</div>
