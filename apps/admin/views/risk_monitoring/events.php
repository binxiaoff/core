Evènements Monitoring
<?php if (count($this->saleTeamEvents) > 0) : ?>
    Projects en traitement Commercial
    <?php foreach ($this->saleTeamEvents as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (count($this->upcomingSaleTeamEvents) > 0) : ?>
    Projects en attente de traitement Commercial
    <?php foreach ($this->upcomingSaleTeamEvents as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (count($this->riskTeamEvents) > 0) : ?>
    Projets en traitement Risque
    <?php foreach ($this->riskTeamEvents as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (count($this->runningRepayment) > 0) : ?>

    Projects en cours de remboursement
    <?php foreach ($this->runningRepayment as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>
