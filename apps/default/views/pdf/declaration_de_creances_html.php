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
        <?php if (in_array($this->clients->type, array(1, 4))) { // particulier ?>
            <?= $this->clients->prenom ?> <?= $this->clients->nom ?><br/>
            <?= $this->clients_adresses->adresse_fiscal ?><br/>
            <?= $this->clients_adresses->cp_fiscal ?> <?= $this->clients_adresses->ville_fiscal ?><br/>
            <?= $this->pays_fiscal ?>
        <?php } else { // entreprise ?>
            <?= $this->companies->name ?><br/>
            <?= $this->companies->adresse1 ?><br/>
            <?= $this->companies->zip ?> <?= $this->companies->city ?><br/>
            <?= $this->pays_fiscal ?>
        <?php } ?>
        <br/><br/>
        n°de <?= $this->translator->trans('contract-type-label_' . $this->contract->label); ?> :  <?= $this->oLoans->id_loan ?>
    </div>
    <div class="mandataire_du_creancier">
        <?= $this->mandataires_var ?>
    </div>
    <div class="debiteur">
        <?=$this->companiesEmpr->forme?> <?=$this->companiesEmpr->name?>
        <br />
        <?=$this->companiesEmpr->adresse1?>
        <br />
        <?=$this->companiesEmpr->zip?> <?=$this->companiesEmpr->city?>
        <br />
        <?=$this->companiesEmpr->siren?>
    </div>
    <div class="procedure">
        <?= $this->nature_var ?>
        <br/>
        <div style="margin-top:55px;"><?= $this->date ?></div>
    </div>
    <div style="clear:both;"></div>
    <div class="creance_declaree">
        <div class="case1">
            <?= $this->ficelle->formatNumber($this->echu) ?>
        </div>
        <div class="case2">
        </div>
        <div class="case3">
            <?php if ($this->contract->label == \underlying_contract::CONTRACT_IFP) : ?>
                Contrat de prêt émis le <?= date('d/m/Y', strtotime($this->oLoans->added)) ?>, échéance au <?= $this->lastEcheance ?>, d’un montant de <?= $this->ficelle->formatNumber(($this->oLoans->amount / 100)) ?>€ assorti d’un taux d’intérêt annuel de <?= $this->ficelle->formatNumber($this->oLoans->rate, 1) ?>%, amortissable mensuellement.
            <?php else : ?>
                Bon de caisse à ordre, émis le <?= date('d/m/Y', strtotime($this->oLoans->added)) ?>, échéance au <?= $this->lastEcheance ?>, d’un montant de <?= $this->ficelle->formatNumber(($this->oLoans->amount / 100)) ?>€ assorti d’un taux d’intérêt annuel de <?= $this->ficelle->formatNumber($this->oLoans->rate, 1) ?>%, amortissable mensuellement.
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
        <?php if (in_array($this->clients->type, array(1, 4))) { // particulier
            echo $this->clients_adresses->ville_fiscal;
        } else { // entreprise
            echo $this->companies->city;
        }
        ?>
    </div>
    <div class="fait_le">
        <?= date('d/m/Y') ?>
    </div>
    <div style="clear:both;"></div>
    <div class="signataire">
        <?php if (in_array($this->clients->type, array(1, 4))) { // particulier
            echo $this->clients->prenom . ' ' . $this->clients->nom;
        } else { // entreprise
            // pas le dirigeant
            if ($this->companies->status_client != 1) {
                echo $this->companies->prenom_dirigeant . ' ' . $this->companies->nom_dirigeant . '<br> Fonction : ' . $this->companies->fonction_dirigeant;
            } else {
                echo $this->clients->prenom . ' ' . $this->clients->nom . '<br> Fonction : ' . $this->clients->fonction;
            }
        }
        ?>
    </div>
    <div style="clear:both;"></div>
    <div class="montant_total">
        <?= $this->ficelle->formatNumber($this->total) ?>
    </div>
</div>
</body>
</html>
