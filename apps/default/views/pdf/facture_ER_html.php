<!DOCTYPE html>
<html lang="fr" class="no-js">
<head>
    <title><?=$this->lng['pdf-facture']['facture-er']?></title>
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Cabin:400,700,400" media="all">
    <link rel="stylesheet" href="<?=$this->surl?>/styles/admin/bootstrap.css"/>
    <link rel="stylesheet" href="<?=$this->surl?>/styles/default/pdf/new-style.css"/>
    <meta name="theme-color" content="#ffffff">
    <meta charset="UTF-8">
</head>
<body class="pdf-invoice">
<div class="container">
    <div class="logo-wrapper">
        <img src="<?=$this->surl?>/styles/default/pdf/images/logo.png">
    </div>
    <h1 id="document-title"><?=$this->lng['pdf-facture']['facture-er']?></h1>
    <div id="header">
        <div class="row">
            <div class="col-xs-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b><?= $this->lng['pdf-facture']['facture'] ?></b>
                    </div>
                    <div class="panel-body">
                        <?= $this->lng['pdf-facture']['facture-num'] ?>: <?= $this->num_facture ?> <br>
                        <?= $this->lng['pdf-facture']['date'] ?>: <?= date('d/m/Y', strtotime($this->date_echeance_reel)) ?> <br>
                        <?= $this->lng['pdf-facture']['id-client'] ?>: <?= $this->clients->id_client ?>
                    </div>
                </div>
            </div>
            <div class="col-xs-4 col-xs-offset-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <b><?= $this->lng['pdf-facture']['facture-a'] ?></b>
                    </div>
                    <div class="panel-body">
                        <?= $this->companies->name ?> <br>
                        <?= $this->companies->adresse1 ?> <br>
                        <?= $this->companies->zip ?>  <?= $this->companies->city ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /#header -->

    <div id="subject">
        <div class="row">
            <div class="col-xs-12">
                <h4><?=$this->lng['pdf-facture']['commission-de-remboursement-du-projet']?> &laquo; <?=$this->projects->nature_project ?> &raquo;</h4>
            </div>
        </div>
    </div><!-- /#subject -->

    <div id="content">
        <table class="table table-bordered">
            <thead>
                <th class="description"><?= $this->lng['pdf-facture']['table-heading-description'] ?></th>
                <th class="value"><?= $this->lng['pdf-facture']['table-heading-value'] ?></th>
            </thead>
            <tbody>
                <tr class="item-commission">
                    <td class="label">
                        <?=$this->lng['pdf-facture']['commission-h.t.']?>
                    </td>
                    <td class="value">
                        <?=$this->ficelle->formatNumber($this->ht)?> €
                    </td>
                </tr>
                <tr class="item-tva">
                    <td class="label">
                        <?= $this->lng['pdf-facture']['tva'] ?>    (<?= $this->ficelle->formatNumber(($this->tva*100)) ?>%)
                    </td>
                    <td class="value">
                        <?= $this->ficelle->formatNumber($this->taxes) ?> €
                    </td>
                </tr>
                <tr class="item-total">
                    <td class="label">
                        <?= $this->lng['pdf-facture']['total-ttc'] ?>
                    </td>
                    <td class="value">
                        <?= $this->ficelle->formatNumber($this->ttc) ?> €
                    </td>
                </tr>
            </tbody>
        </table><!-- /.table -->

        <div id="payment-info">
            <div class="row">
                <div class="col-xs-12 text-right">
                    <p>
                        <?= $this->lng['pdf-facture']['echeance-a-reception'] ?> <br>
                        <b><?= $this->lng['pdf-facture']['regle-par-prelevement-le'] ?> <?= date('d/m/Y', strtotime($this->date_echeance_reel)) ?></b>
                    </p>
                </div>
            </div>
        </div><!-- /#payment-info -->

        <p><?= $this->lng['pdf-facture']['en-cas-de-non-paiement'] ?></p>

        <br>

        <div id="contact-us">
            <h4><?= $this->lng['pdf-facture']['votre-contact'] ?></h4>
            <p>
                <?= $this->lng['pdf-facture']['adresse-contact'] ?>
            </p>
        </div><!-- /#contact-us -->

    </div><!-- /#content -->

    <br><br>

    <?php $this->fireView('footer_facture'); ?>
</div><!-- /.container -->
</body>
</html>
