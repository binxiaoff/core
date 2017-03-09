<div id="contenu">
    <fieldset style="color: black; padding: 10px; border: 1px solid #B10366; width: 280px;">
        <legend style="color: #B20066"><b>Statut de vigilance:</b></legend>
        <span style="padding: 5px; border-radius: 7px; width: 84px; background-color: <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusColor[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_LOW]; ?>">
            <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_LOW] ?>
        </span>
        &nbsp;
        <span style="padding: 5px; border-radius: 7px; width: 84px; background-color: <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusColor[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_MEDIUM]; ?>">
            <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_MEDIUM] ?>
        </span>
        &nbsp;
        <span style="padding: 5px; border-radius: 7px; width: 84px; background-color: <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusColor[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_HIGH]; ?>">
            <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_HIGH] ?>
        </span>
        &nbsp;
        <span style="padding: 5px; border-radius: 7px; width: 84px; background-color: <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusColor[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_REFUSE]; ?>">
            <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel[\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::VIGILANCE_STATUS_REFUSE] ?>
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
