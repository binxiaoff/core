<?php

?>
<script type="text/javascript">
    $(function() {
        jQuery.tablesorter.addParser({ id: "fancyNumber", is: function(s) { return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s); }, format: function(s) { return jQuery.tablesorter.formatFloat( s.replace(/,/g,'').replace(' €','').replace(' ','') ); }, type: "numeric" });

        $(".encheres").tablesorter({headers: {6: {sorter: false}}});
        $(".mandats").tablesorter({headers: {}});
        $(".bidsEncours").tablesorter({headers: {6: {sorter: false}}});
        $(".transac").tablesorter({headers: {}});
        $(".favoris").tablesorter({headers: {3: {sorter: false}}});
        <?php if ($this->nb_lignes != '') : ?>
            $(".encheres").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
            $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
        $("#annee").change(function () {
            $('#changeDate').attr('href', "<?= $this->lurl ?>/preteurs/edit/<?=$this->params[0]?>/" + $(this).val());
        });

        // Reject Bid
        $('.deleteBidBtn').click(function () {
            var id_bid = $(this).data('bid')
            if (confirm('Etes vous sur de vouloir rejeter ce bid ?')) {
                var val = {
                    id_bid: id_bid
                };
                $.post(add_url + '/ajax/deleteBidPreteur', val).done(function (data) {
                    if (data != 'nok') {
                        $(".lesbidsEncours").html(data);
                    }
                });
            }
        })

        // Datepickers
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

        // Filter
        $("#anneeMouvTransacForm").submit(function(e) {
            e.preventDefault()
            var val = {
                id_client: <?= $this->clients->id_client ?>,
                year: $(this).find('select').val()
            };
            $.post(add_url + '/ajax/loadMouvTransac', val).done(function(data) {
                if (data != 'nok') {

                    $(".MouvTransac").html(data);
                }
            });
        });
    });
</script>
<link href="<?= $this->lurl ?>/oneui/css/font-awesome.css" type="text/css" rel="stylesheet">
<style>
    @font-face {
        font-family: 'FontAwesome';
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot');
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff2') format('woff2'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff') format('woff'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.ttf') format('truetype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.svg#fontawesomeregular') format('svg');
        font-weight: normal;
        font-style: normal;
    }
    .td-greenPoint-status-valid {
        border-radius: 5px; background-color: #00A000; color: white; width: auto; padding: 5px;
    }
    .td-greenPoint-status-warning {
        border-radius: 5px; background-color: #f79232; color: white; width: auto; padding: 5px;
    }
    .td-greenPoint-status-error {
        border-radius: 5px; background-color: #ff0100; color: white; width: auto; padding: 5px;
    }
    table.attachment-list td, th{
        vertical-align: middle;
    }
    .form-field {
        width: 140px;
        margin-right: 20px;
        float: left;
    }
    .form-field input, .form-field select {
        width: 100%;
        box-sizing: border-box;
        height: 30px;
    }
    .form-field img {
        margin-top: -46px;
    }
</style>
<div id="contenu">
    <?php if (empty($this->clients->id_client)) : ?>
        <div class="attention">Attention : Compte <?= $this->params[0] ?> innconu</div>
    <?php elseif (empty($this->wallet)) : ?>
        <div class="attention">Attention : ce compte n’est pas un compte prêteur</div>
    <?php else : ?>
        <div><?= $this->clientStatusMessage ?></div>
        <?php if ($this->client->isNaturalPerson()) : ?>
            <h1><span class="fa fa-user-o"></span> <?= $this->client->getPrenom() ?> <?= $this->client->getNom() ?></h1>
        <?php else : ?>
            <h1><span class="fa fa-briefcase"></span> <?= $this->companies->name ?></h1>
            <h2>Représentant légal : <?= $this->client->getPrenom() ?> <?= $this->client->getNom() ?></h2>
        <?php endif; ?>
        <div class="btnDroite">
            <a href="<?= $this->lurl ?>/preteurs/bids/<?= $this->clients->id_client ?>" class="btn-primary">Enchères</a>
            <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->clients->id_client ?>" class="btn-primary">Modifier Prêteur</a>
            <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->clients->id_client ?>" class="btn-primary">Historique des emails</a>
            <a href="<?= $this->lurl ?>/preteurs/portefeuille/<?= $this->clients->id_client ?>" class="btn-primary">Portefeuille & Performances</a></div>
        <br>
        <table class="form" style="margin: auto;">
            <tr>
                <th>ID Client</th>
                <td><?= $this->client->getIdClient() ?></td>
                <th>Date de création</th>
                <td><?= $this->client->getAdded()->format('d/m/Y') ?></td>
            </tr>
            <tr>
                <th>Prénom</th>
                <td><?= $this->client->getPrenom() ?></td>
                <th><?php if (false === empty($this->firstValidation)) : ?>Date de 1ère validation<?php endif; ?></th>
                <td><?php if (false === empty($this->firstValidation)) : ?><?= $this->firstValidation->getAdded()->format('d/m/Y') ?><?php endif; ?></td>
            </tr>
            <tr>
                <th>Nom</th>
                <td><?= $this->client->getNom() ?></td>
                <th>Source</th>
                <td><?= $this->client->getSource() ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= $this->client->getEmail() ?></td>
                <th><?php if (false === empty($this->client->getSource2())) : ?>Source secondaire<?php endif; ?></th>
                <td><?php if (false === empty($this->client->getSource2())) : ?><?= $this->client->getSource2() ?><?php endif; ?></td>
            </tr>
            <tr>
                <th>Adresse fiscale validée</th>
                <td><?= null !== $this->validatedAddress ? $this->validatedAddress->getAddress() . '<br>' . $this->validatedAddress->getZip() . ' ' . $this->validatedAddress->getCity() : '' ?></td>
                <?php if (null !== $this->lastModifiedAddress && $this->validatedAddress !== $this->lastModifiedAddress) : ?>
                    <th>Addresse fiscale <br>en attente de validation</th>
                    <td><?= $this->lastModifiedAddress->getAddress() . '<br>' . $this->lastModifiedAddress->getZip() . ' ' . $this->lastModifiedAddress->getCity() ?></td>
                <?php endif; ?>
            </tr>
            <tr>
                <th>Téléphone / Mobile</th>
                <td><?= $this->client->getTelephone() ?> / <?= $this->client->getMobile() ?></td>
            </tr>
        </table>
        <br/><br/>
        <div class="gauche" style="padding:0px;width: 530px;border-right:0px;">
            <table class="form" style="width:340px;">
                <tr>
                    <th>Sommes disponibles</th>
                    <td><?= $this->ficelle->formatNumber($this->solde) ?> €</td>
                </tr>
                <tr>
                    <th>Montant prêté</th>
                    <td><?= $this->ficelle->formatNumber($this->sumPrets) ?> €</td>
                </tr>
                <tr>
                    <th>Fonds retirés</th>
                    <td><?= $this->ficelle->formatNumber($this->soldeRetrait) ?> €</td>
                </tr>
                <tr>
                    <th>Remboursement prochain mois</th>
                    <td><?= $this->ficelle->formatNumber($this->nextRemb) ?> €</td>
                </tr>
                <tr>
                    <th>Enchères moyennes</th>
                    <td><?= $this->ficelle->formatNumber($this->avgPreteur) ?> €</td>
                </tr>
                <tr>
                    <th>Montant des intérêts (brut)</th>
                    <td><?= $this->ficelle->formatNumber($this->sumRembInte) ?> €</td>
                </tr>
                <tr>
                    <th>Défaut</th>
                    <td>Non</td>
                </tr>
                <tr>
                    <th>Exonéré</th>
                    <td>
                        <?php if (empty($this->exemptionYears)) : ?>
                            Non
                        <?php else : ?>
                            <?= implode('<br>', $this->exemptionYears) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="droite" style="padding:0px;width: 530px;">
            <table class="form" style="width:265px;">
                <tr>
                    <th>Total des sommes déposées</th>
                    <td><?= $this->ficelle->formatNumber($this->SumDepot) ?> €</td>
                </tr>
                <tr>
                    <th>Montant encheres en cours</th>
                    <td><?= $this->ficelle->formatNumber($this->sumBidsEncours) ?> €</td>
                </tr>
                <tr>
                    <th>Nombre d'encheres en cours</th>
                    <td><?= $this->NbBids ?></td>
                </tr>
                <tr>
                    <th>Nombre de prêts</th>
                    <td><?= $this->nb_pret ?></td>
                </tr>
                <tr>
                    <th>Montant du 1er versement</th>
                    <td><?= $this->ficelle->formatNumber($this->SumInscription) ?> €</td>
                </tr>
                <tr>
                    <th>Taux moyen pondéré</th>
                    <td><?= $this->ficelle->formatNumber($this->txMoyen) ?> %</td>
                </tr>
                <tr>
                    <th>Remboursement total</th>
                    <td><?= $this->ficelle->formatNumber($this->sumRembMontant) ?> €</td>
                </tr>
            </table>
        </div>

        <div style="clear:both;"></div>
        <br/><br/>
        <h2>Pièces jointes :</h2>
        <table class="attachment-list" style="width: auto; border-collapse: separate; border-spacing: 2px;">
            <tr>
                <th>Type de fichier</th>
                <th>Nom (cliquer pour voir)</th>
                <th>Statut GreenPoint</th>
                <th>&Eacute;tat de validation</th>
            </tr>
            <?php
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
            foreach ($this->attachmentTypes as $attachmentType) :
                $currentAttachment     = null;
                $greenPointAttachment  = null;
                $greenpointLabel       = 'Non Contrôlé par GreenPoint';
                $greenpointColor       = 'error';
                $greenpointFinalStatus = '';
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $attachment */
                foreach ($this->attachments as $attachment) :
                    if ($attachment->getType() === $attachmentType) {
                        $currentAttachment = $attachment;
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment $greenPointAttachment */
                        $greenPointAttachment = $currentAttachment->getGreenpointAttachment();
                        break;
                    }
                    ?>
                <?php
                endforeach;
                if (null === $currentAttachment) {
                    continue;
                }
                if ($greenPointAttachment) {
                    $greenpointLabel = empty($greenPointAttachment->getValidationStatusLabel()) ? 'Erreur d\'appel GreenPoint' : $greenPointAttachment->getValidationStatusLabel();
                    if (\Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
                        $greenpointFinalStatus = 'Statut définitif';
                    } else {
                        $greenpointFinalStatus = 'Statut peut être modifié par un retour asychrone';
                    }

                    if (0 == $greenPointAttachment->getValidationStatus()) {
                        $greenpointColor = 'error';
                    } elseif (8 > $greenPointAttachment->getValidationStatus()) {
                        $greenpointColor = 'warning';
                    } else {
                        $greenpointColor = 'valid';
                    }
                }
                ?>
                <tr style="height: 2em; padding: 2px; ">
                    <th ><?= $attachmentType->getLabel() ?></th>
                    <td>
                        <a href="<?= $this->url ?>/viewer/client/<?= $this->clients->id_client ?>/<?= $attachment->getId() ?>" target="_blank">
                            <?= $attachment->getPath() ?>
                        </a>
                    </td>
                    <td class="td-greenPoint-status-<?= $greenpointColor?>"><?= $greenpointLabel ?></td>
                    <td><?= $greenpointFinalStatus ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br/><br/>
        <?php if (false === empty($this->transfers)) : ?>
            <h2>Document de transfert (en cas de succession)</h2>
            <table class="attachment-list" style="width: auto; border-collapse: separate; border-spacing: 2px;">
                <?php
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Transfer $transfer */
                foreach ($this->transfers as $transfer) :
                    foreach ($transfer->getAttachments() as $transferAttachment) :
                    $attachment = $transferAttachment->getAttachment();
                ?>
                    <tr>
                        <td>
                            <a href="<?= $this->url ?>/viewer/client/<?= $this->clients->id_client ?>/<?= $attachment->getId() ?>" target="_blank">
                                <?= $attachment->getPath() ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2>Mouvements</h2>
        <div class="gauche" style="border: 0; padding-top: 5px;">
            <form method="post" name="date_select" action="<?= $this->lurl ?>/preteurs/operations_export/<?= $this->clients->id_client ?>">
                <div class="form-field">
                    <input type="text" name="dateStart"
                           placeholder="Date debut"
                           id="datepik_1"
                           class="input_dp"
                           value="<?= (empty($_POST['id']) && false === empty($_POST['dateStart'])) ? $_POST['dateStart'] : '' ?>"/>
                </div>
                <div class="form-field">
                    <input type="text"
                           placeholder="Date fin"
                           name="dateEnd"
                           id="datepik_2" class="input_dp"
                           value="<?= (empty($_POST['id']) && false === empty($_POST['dateEnd'])) ? $_POST['dateEnd'] : '' ?>"/>
                </div>
                <input type="submit" value="Exporter" title="Valider" name="export_operations" id="export_operations" class="btn-primary" style="height: 30px" />
            </form>
        </div>
        <div class="droite" style="padding-top: 5px;">
            <form method="post" id="anneeMouvTransacForm" action="">
                <input type="submit" value="Filtrer" name="filter" id="export_operations" class="btn-primary" style="float: right; height: 30px" />
                <div class="form-field" style="float: right;">
                    <select name="anneeMouvTransac" id="anneeMouvTransac" class="select" style="width:100%;">
                        <?php for ($i = date('Y'); $i >= 2013; $i--) : ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="MouvTransac row">
            <div class="col-md-12">
                <?php $this->fireView('transactions'); ?>
            </div>
        </div>

        <br/><br/>

        <div class="lesbidsEncours row">
            <div class="col-md-12">
                <h2>Suivi des enchères en cours</h2>
                <?php if (count($this->lBids) > 0) :?>
                    <table class="tablesorter bidsEncours">
                        <thead>
                        <tr>
                            <th>id bid</th>
                            <th>Projet</th>
                            <th>Date</th>
                            <th>Montant enchere (€)</th>
                            <th>Taux</th>
                            <th>Nbre de mois</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        foreach ($this->lBids as $e) :
                            $this->projects->get($e['id_project'], 'id_project'); ?>
                            <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                                <td align="center"><?= $e['id_bid'] ?></td>
                                <td>
                                    <a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>"><?= $this->projects->title ?></a>
                                </td>
                                <td><?= date('d/m/Y', strtotime($e['added'])) ?></td>
                                <td align="center"><?= number_format($e['amount'] / 100, 2, '.', ' ') ?></td>
                                <td align="center"><?= number_format($e['rate'], 2, '.', ' ') ?> %</td>
                                <td align="center"><?= $this->projects->period ?></td>

                                <td align="center">
                                    <a role="button" class="deleteBidBtn" data-bid="<?= $e['id_bid'] ?>">Rejeter ce bid</a>
                                </td>
                            </tr>
                            <?php
                            $i++;
                        endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($this->nb_lignes != '') : ?>
                        <table>
                            <tr>
                                <td id="pager">
                                    <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                                    <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                                    <input type="text" class="pagedisplay"/>
                                    <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                                    <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                                    <select class="pagesize">
                                        <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <br/><br/>

        <div class="row">
            <div class="col-md-6">
                <h2>Suivi des enchères</h2>
            </div>
            <div class="col-md-6 text-right">
                <select name="annee" id="annee" class="select" style="width:95px; height: 30px">
                    <?php for ($i = date('Y'); $i >= 2008; $i--) : ?>
                        <option <?= (isset($this->params[1]) && $this->params[1] == $i ? 'selected' : '') ?> value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <a id="changeDate" href="<?= $this->lurl ?>/preteurs/edit/<?= $this->params[0] ?>/2013" class="btn-primary">OK</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?php if (count($this->lEncheres) > 0) : ?>
                    <table class="tablesorter encheres">
                        <thead>
                            <tr>
                                <th>Année</th>
                                <th>Projet</th>
                                <th>Montant prêt (€)</th>
                                <th>Pourcentage</th>
                                <th>Nombre de mois</th>
                                <th>Remboursement (€)</th>
                                <th>Contrat</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i = 1;
                        foreach ($this->lEncheres as $e) :
                            $year = $this->formatDate($e['added'], 'Y');
                            $this->projects->get($e['id_project'], 'id_project');
                            $sumMontant = $this->echeanciers->getTotalAmount(array('id_loan' => $e['id_loan']));
                        ?>
                            <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                                <td align="center"><?= $year ?></td>
                                <td>
                                    <a href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>"><?= $this->projects->title ?></a>
                                </td>
                                <td align="center"><?= $this->ficelle->formatNumber($e['amount'] / 100, 0) ?></td>
                                <td align="center"><?= $this->ficelle->formatNumber($e['rate'], 1) ?> %</td>
                                <td align="center"><?= $this->projects->period ?></td>
                                <td align="center"><?= $this->ficelle->formatNumber($sumMontant, 2) ?></td>
                                <td align="center">
                                    <a href="<?= $this->lurl ?>/protected/contrat/<?= $this->clients->hash ?>/<?= $e['id_loan'] ?>">PDF</a>
                                </td>
                            </tr>
                            <?php $i++; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($this->nb_lignes != '') : ?>
                        <table>
                            <tr>
                                <td id="pager">
                                    <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                                    <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                                    <input type="text" class="pagedisplay"/>
                                    <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                                    <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                                    <select class="pagesize">
                                        <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
