<?php

use Unilend\Entity\UnderlyingContract;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en-US" xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
    <title>declaration de creances</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/style.css" type="text/css" media="all"/>
    <link rel="stylesheet" href="<?= $this->surl ?>/styles/default/pdf/styleClaims.css" type="text/css" media="all"/>
</head>
<body style="text-align:center;">
<div class="img">
    <div class="creancier">
        <?php if (in_array($this->clients->type, [\Unilend\Entity\Clients::TYPE_PERSON, \Unilend\Entity\Clients::TYPE_PERSON_FOREIGNER])) : ?>
            <?= $this->clients->prenom ?> <?= $this->clients->nom ?><br/>
        <?php else :  ?>
            <?= $this->lenderCompany->getName() ?><br/>
        <?php endif; ?>
        <?= $this->lenderAddress->getAddress() ?><br/>
        <?= $this->lenderAddress->getZip() ?> <?= $this->lenderAddress->getCity() ?><br/>
        <?= $this->lenderAddress->getIdCountry()->getFr() ?>
        <br/><br/>
        n°de <?= $this->translator->trans('contract-type-label_' . $this->contract->getLabel()); ?> :  <?= $this->loan->getIdLoan() ?>
    </div>
    <div class="mandataire_du_creancier">
        <?= $this->mandataires_var ?>
    </div>
    <div class="debiteur">
        <?= $this->borrowerCompany->getForme() ?> <?= $this->borrowerCompany->getName() ?>
        <br/>
        <?= $this->borrowerCompanyAddress->getAddress() ?>
        <br/>
        <?= $this->borrowerCompanyAddress->getZip() ?> <?= $this->borrowerCompanyAddress->getCity() ?>
        <br/>
        <?= $this->borrowerCompany->getSiren() ?>
    </div>
    <div class="procedure">
        <?= $this->nature_var ?>
        <br/>
        <div style="margin-top:55px;"><?= $this->date->format('d/m/Y') ?></div>
    </div>
    <div style="clear:both;"></div>
    <div class="creance_declaree">
        <div class="case1">
            <?= $this->ficelle->formatNumber($this->echu) ?>
        </div>
        <div class="case2">
        </div>
        <div class="case3">
            <?php if ($this->contract->getLabel() === UnderlyingContract::CONTRACT_IFP) : ?>
                Contrat de prêt émis le <?= $this->loan->getAdded()->format('d/m/Y') ?>, échéance au <?= $this->lastEcheance ?>, d’un montant de <?= $this->ficelle->formatNumber(($this->loan->getAmount() / 100)) ?>€ assorti d’un taux d’intérêt annuel de <?= $this->ficelle->formatNumber($this->loan->getRate(), 1) ?>%, amortissable mensuellement.
            <?php else : ?>
                Bon de caisse à ordre, émis le <?= $this->loan->getAdded()->format('d/m/Y') ?>, échéance au <?= $this->lastEcheance ?>, d’un montant de <?= $this->ficelle->formatNumber(($this->loan->getAmount() / 100)) ?>€ assorti d’un taux d’intérêt annuel de <?= $this->ficelle->formatNumber($this->loan->getRate(), 1) ?>%, amortissable mensuellement.
            <?php endif; ?>

        </div>
        <div class="case4">
            <?= $this->ficelle->formatNumber($this->echoir) ?>
        </div>
        <div class="case5">
        </div>
        <div class="case6">
            <?= $this->ficelle->formatNumber($this->total) ?>
        </div>
        <div class="case7"></div>
    </div>
    <div style="clear:both;"></div>
    <div class="fait_a">
        <?= $this->lenderAddress->getCity() ?>
    </div>
    <div class="fait_le">
        <?= date('d/m/Y') ?>
    </div>
    <div style="clear:both;"></div>
    <div class="signataire">
        <?php if (in_array($this->clients->type, [\Unilend\Entity\Clients::TYPE_PERSON, \Unilend\Entity\Clients::TYPE_PERSON_FOREIGNER])) : ?>
            <?= $this->clients->prenom ?> <?= $this->clients->nom ?>
        <?php else : ?>
            <?php if ($this->lenderCompany->getStatusClient() != \Unilend\Entity\Companies::CLIENT_STATUS_MANAGER) : ?>
                <?= $this->lenderCompany->getPrenomDirigeant() ?> <?= $this->lenderCompany->getNomDirigeant() ?><br> Fonction : <?= $this->lenderCompany->getFonctionDirigeant() ?>
            <?php else : ?>
                <?= $this->clients->prenom ?> <?= $this->clients->nom ?><br> Fonction : <?= $this->clients->fonction ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
    <div class="montant_total">
        <?= $this->ficelle->formatNumber($this->total) ?>
    </div>
</div>
</body>
</html>
