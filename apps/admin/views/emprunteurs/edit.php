<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;

?>
<script>
    $(function () {
        $(".listeProjets").tablesorter({headers: {4: {sorter: false}, 5: {sorter: false}}});
        $(".listeMandats").tablesorter();
        $(".mandats").tablesorter({headers: {}});

        <?php if ($this->nb_lignes != '') : ?>
        $(".listeProjets").tablesorterPager({
            container: $("#pager"),
            positionFixed: false,
            size: <?= $this->nb_lignes ?>});
        $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $("#operation-date-form").submit(function (e) {
            e.preventDefault()
            var val = {
                id_client: <?= $this->clients->id_client ?>,
                year: $(this).find('select').val()
            };
            $("#filter-button").prop("disabled", true)
            $.post(add_url + '/emprunteurs/loadBorrowerOperationAjax', val).done(function (data) {
                $("#filter-button").prop("disabled", false)
                if (data != 'nok') {
                    $(".borrower-operation-table").html(data);
                }
            });
        });

        $('#status').change(function () {
            var status = $(this).val();

            if (
                status != '<?= $this->companyStatusInBonis->getId() ?>'
            ) {
                $.colorbox({href: "<?= $this->lurl ?>/thickbox/company_status_update/<?= $this->clients->id_client ?>/<?= $this->companies->id_company ?>/" + status});
            }
        });

        $('.operation-tooltip').tooltip({
            show: false,
            position: {
                at: 'right center',
                my: 'right center',
            },
            content: function () {
                var content = $(this).attr('title')
                return content
            }
        })
    });
</script>

<style>
    .operation-tooltip img {
        position: relative;
        top: -1px;
    }
</style>

<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Detail emprunteur : <?= $this->clients->nom . ' ' . $this->clients->prenom ?></h1>
    <?php if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') : ?>
        <p style="color:#c84747;text-align:center;font-size:14px;font-weight:bold;"><?= $_SESSION['error_email_exist'] ?></p>
        <?php unset($_SESSION['error_email_exist']); ?>
    <?php endif; ?>
    <form method="post" name="edit_emprunteur" id="edit_emprunteur" enctype="multipart/form-data" action="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clients->id_client ?>" target="_parent">
        <table class="formColor" style="width: 775px;margin:auto;">
            <tr>
                <th><label for="nom">Nom</label></th>
                <td><input type="text" name="nom" id="nom" class="input_large" value="<?= $this->clients->nom ?>"/></td>
                <th><label for="prenom">Prénom</label></th>
                <td>
                    <input type="text" name="prenom" id="prenom" class="input_large" value="<?= $this->clients->prenom ?>"/>
                </td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td>
                    <input type="text" name="email" id="email" class="input_large" value="<?= $this->clients->email ?>"/>
                </td>
                <th><label for="telephone">Téléphone</label></th>
                <td>
                    <input type="text" name="telephone" id="telephone" class="input_large" value="<?= $this->clients->telephone ?>"/>
                </td>
            </tr>
            <tr>
                <th><label for="societe">Société</label></th>
                <td>
                    <input type="text" name="societe" id="societe" class="input_large" value="<?= $this->companies->name ?>"/>
                </td>
                <th><label for="sector">Secteur</label></th>
                <td>
                    <?php if ($this->companies->code_naf === \Unilend\Bundle\CoreBusinessBundle\Entity\Companies::NAF_CODE_NO_ACTIVITY) : ?>
                        <select name="sector" id="sector" class="select">
                            <option value="0"></option>
                            <?php foreach ($this->sectors as $sector) : ?>
                                <option<?= ($this->companies->sector == $sector['id_company_sector'] ? ' selected' : '') ?> value="<?= $sector['id_company_sector'] ?>">
                                    <?= $this->translator->trans('company-sector_sector-' . $sector['id_company_sector']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <?= $this->translator->trans('company-sector_sector-' . $this->companies->sector) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="adresse">Adresse</label></th>
                <td colspan="3">
                    <input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value="<?= $this->clients_adresses->adresse1 ?>"/>
                </td>
            </tr>
            <tr>
                <th><label for="cp">Code postal</label></th>
                <td>
                    <input type="text" name="cp" id="cp" class="input_large" value="<?= $this->clients_adresses->cp ?>"/>
                </td>
                <th><label for="ville">Ville</label></th>
                <td>
                    <input type="text" name="ville" id="ville" class="input_large" value="<?= $this->clients_adresses->ville ?>"/>
                </td>
            </tr>
            <tr>
                <th><label for="email_facture">Email de facturation :</label></th>
                <td colspan="3">
                    <input type="text" name="email_facture" id="email_facture" class="input_large" value="<?= $this->companies->email_facture ?>"/>
                </td>
            </tr>
            <tr>
                <th></th>
                <td>
                    <input style="font-size: 11px; height: 25px;" type="button" id="initialiser_espace_emprunteur" name="initialiser_espace_emprunteur" value="Reinitialiser Espace Emprunteur" class="btn" onclick="send_email_borrower_area('<?= $this->clients->id_client ?>', 'initialize')"/>
                </td>
                <?php if (empty($this->clients->secrete_question) && empty($this->clients->secrete_reponse)) : ?>
                    <td colspan="2">
                        <input style="font-size: 11px; height: 25px;" type="button" id="ouvrir_espace_emprunteur" name="ouvrir_espace_emprunteur" value="Envoyer Email Ouverture Espace Emprunteur" class="btn" onclick="send_email_borrower_area('<?= $this->clients->id_client ?>', 'open')"/>
                    </td>
                <?php endif ?>
                <td><span style="margin-left:5px;color:green; display:none;" class="reponse_email">Email Envoyé</span>
                </td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="form_edit_emprunteur" id="form_edit_emprunteur"/>
                    <button type="submit" class="btn-primary pull-right">Valider</button>
                </th>
            </tr>
        </table>
    </form>

    <h1>Société : <?= $this->companyEntity->getName() ?></h1>
    <table class="formColor" style="width: 775px; margin:auto;">
        <tr>
            <th style="width: 133px">ID</th>
            <td style="width: 250px"><?= $this->companyEntity->getIdCompany() ?></td>
            <th style="width: 105px">Statut</th>
            <td>
                <select id="status" name="status" class="select" style="width: 250px;">
                    <?php /** @var $status CompanyStatus */ ?>
                    <?php if (false === empty($this->companyEntity->getIdStatus()) && false === in_array($this->companyEntity->getIdStatus(), $this->possibleCompanyStatus)) : ?>
                        <option selected disabled value="<?= $this->companyEntity->getIdStatus()->getId() ?>"><?= $this->companyManager->getCompanyStatusNameByLabel($this->companyEntity->getIdStatus()->getLabel()) ?></option>
                    <?php endif; ?>
                    <?php foreach ($this->possibleCompanyStatus as $status) : ?>
                        <option <?= $this->companyEntity->getIdStatus()->getId() == $status->getId() ? 'selected' : '' ?> value="<?= $status->getId() ?>"><?= $this->companyManager->getCompanyStatusNameByLabel($status->getLabel()) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php if ($this->get('unilend.service.back_office_user_manager')->isGrantedRisk($this->userEntity)) : ?>
        <a class="btn-primary pull-right" href="<?= $this->lurl ?>/societe/notation/<?= $this->companyEntity->getIdCompany() ?>">Suivi des notations</a>
    <?php endif; ?>
    <br/><br/>

    <h2>Bénéficiaires effectifs</h2>
    <a role="button" class="btn btn-default" href="<?= $this->lurl ?>/beneficiaires_effectifs/<?= $this->companies->id_company ?>">Consulter les Bénéficiaires effectifs</a>
    <br/><br/>

    <?php $this->fireView('../bank_account/blocks/validated_bank_account'); ?>
    <?php $this->fireView('../bank_account/blocks/other_bank_account'); ?>

    <h2>Liste des projets</h2>
    <?php if (count($this->lprojects) > 0) : ?>
        <table class="tablesorter listeProjets">
            <thead>
            <tr>
                <th>ID</th>
                <th>Projet</th>
                <th>statut</th>
                <th>Montant</th>
                <th>PDF</th>
                <th>Factures</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($this->lprojects as $iIndex => $aProject) : ?>
                <?php $this->projects_status->get($aProject['status'], 'status'); ?>
                <tr<?= (++$iIndex % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $aProject['id_project'] ?></td>
                    <td><?= $aProject['title'] ?></td>
                    <td><?= $this->projects_status->label ?></td>
                    <td class="right"><?= $this->ficelle->formatNumber($aProject['amount'], 0) ?>&nbsp;€</td>
                    <td>
                        <?php if ($this->projects_pouvoir->get($aProject['id_project'], 'id_project')) : ?>
                            <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>">POUVOIR</a>
                        <?php elseif ($aProject['status'] > \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::FUNDE) : ?>
                            <a href="/emprunteurs/link_ligthbox/pouvoir/<?= $aProject['id_project'] ?>" class="thickbox cboxElement">POUVOIR</a>
                        <?php endif; ?>
                        &nbsp;&nbsp;
                        <?php if ($this->clients_mandats->get($this->clients->id_client, 'id_project = ' . $aProject['id_project'] . ' AND status = ' . \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED . ' AND id_client')) : ?>
                            <a href="<?= $this->lurl ?>/protected/mandats/<?= $this->clients_mandats->name ?>">MANDAT</a>
                        <?php elseif ($aProject['status'] > \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::FUNDE) : ?>
                            <a href="/emprunteurs/link_ligthbox/mandat/<?= $aProject['id_project'] ?>" class="thickbox cboxElement">MANDAT</a>
                        <?php endif; ?>
                    </td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/emprunteurs/factures/<?= $aProject['id_project'] ?>" class="thickbox cboxElement" target="_blank">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Factures"/>
                        </a>
                    </td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/dossiers/edit/<?= $aProject['id_project'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Détails"/>
                        </a>
                    </td>
                </tr>
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

    <h2>Historique des Mandats</h2>
    <table class="tablesorter listeMandats">
        <thead>
        <tr>
            <th>ID Projet</th>
            <th>IBAN</th>
            <th>BIC</th>
            <th>PDF</th>
            <th>Statut</th>
            <th>Date d'ajout</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->aMoneyOrders as $aMoneyOrder) : ?>
            <tr<?= (++$iIndex % 2 == 1 ? '' : ' class="odd"') ?>>
                <td><?= $aMoneyOrder['id_project'] ?></td>
                <td><?= $aMoneyOrder['iban'] ?></td>
                <td><?= $aMoneyOrder['bic'] ?></td>
                <td><a href="<?= $this->lurl ?>/protected/mandats/<?= $aMoneyOrder['name'] ?>">MANDAT</a></td>
                <td>
                    <?php
                    switch ($aMoneyOrder['status']) {
                        case \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_PENDING:
                            echo 'En attente de signature';
                            break;
                        case \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED:
                            echo 'Signé';
                            break;
                        case \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_CANCELED:
                            echo 'Annulé';
                            break;
                        case \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_FAILED:
                            echo 'Echec';
                            break;
                        case \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_ARCHIVED:
                            echo 'Archivé';
                            break;
                        default:
                            echo 'Inconnu';
                            break;
                    }
                    ?>
                </td>
                <td><?= $this->dates->formatDate($aMoneyOrder['added'], 'd/m/Y à H:i:s') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <h2>Relevé des opérations (Solde: <?= $this->currencyFormatter->formatCurrency($this->availableBalance, 'EUR') ?>)</h2>
    <div style="float: right">
        <form method="post" id="operation-date-form" action="" class="form-inline">
            <div class="form-group">
                <select name="operation-date-filter" class="select">
                    <?php for ($i = date('Y'); $i >= substr($this->clients->added, 0, 4); $i--) : ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <input type="submit" value="Filtrer" name="filter" class="btn-primary" id="filter-button">
            </div>
        </form>
    </div>

    <div class="borrower-operation-table">
    <?php if (count($this->operations) > 0) : ?>
        <?php $this->fireView('operations'); ?>
    <?php else : ?>
        <p>Aucune opération pour l'année en cours</p>
    <?php endif; ?>
    </div>
</div>
