<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientsStatus, Companies, CompanyStatus, ProjectsStatus, UniversignEntityInterface
};

?>
<script>
    $(function () {
        $('#nom, #prenom').on('change keyup', function () {
            var firstName = $('#prenom').val()
            var lastName = $('#nom').val()

            $('#borrower-name').html(firstName + ' ' + lastName)
        })

        $('#societe').on('change keyup', function () {
            var company = $('#societe').val()

            $('#company-name').html(company)
        })

        $('.listeProjets').tablesorter({headers: {4: {sorter: false}, 5: {sorter: false}, 6: {sorter: false}}})
        $('.listeMandats').tablesorter({headers: {3: {sorter: false}}})

        $('#operation-date-form').on('submit', function (e) {
            e.preventDefault()
            var val = {
                id_client: <?= $this->clientEntity->getIdClient() ?>,
                year: $(this).find('select').val()
            };

            $('#filter-button').prop('disabled', true)
            $('.borrower-operation-table').html('<img src="<?= $this->surl ?>/images/admin/ajax-loader.gif">')

            $.post(add_url + '/emprunteurs/loadBorrowerOperationAjax', val).done(function (data) {
                $('#filter-button').prop('disabled', false)
                if (data !== 'nok') {
                    $('.borrower-operation-table').html(data);
                } else {
                    alert('Erreur de chargement')
                }
            });
        });

        $('#status').on('change', function () {
            var status = $(this).val();

            if (status !== '<?= $this->companyStatusInBonis->getId() ?>') {
                $.colorbox({href: '<?= $this->lurl ?>/thickbox/company_status_update/<?= $this->clientEntity->getIdClient() ?>/<?= $this->companyEntity->getIdCompany() ?>/' + status})
            }
        })

        $('.operation-tooltip').tooltip({
            show: false,
            position: {
                at: 'right center',
                my: 'right center',
            },
            content: function () {
                return $(this).attr('title')
            }
        })

        $('#borrower-tabs').tabs()
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
    <?php if (isset($_SESSION['error_email_exist']) && $_SESSION['error_email_exist'] != '') : ?>
        <p class="attention"><?= $_SESSION['error_email_exist'] ?></p>
        <?php unset($_SESSION['error_email_exist']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_company_address']) && false === empty($_SESSION['error_company_address'])) : ?>
        <p class="attention"><?= $_SESSION['error_company_address'] ?></p>
        <?php unset($_SESSION['error_company_address']); ?>
    <?php endif; ?>

    <form method="post" action="<?= $this->lurl ?>/emprunteurs/edit/<?= $this->clientEntity->getIdClient() ?>">
        <input type="hidden" name="form_edit_emprunteur">
        <div class="row">
            <div class="col-md-6">
                <h1>Emprunteur</h1>
                <h2 id="borrower-name"><?= $this->clientEntity->getPrenom() ?> <?= $this->clientEntity->getNom() ?></h2>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="nom">Nom</label>
                        <input type="text" name="nom" id="nom" value="<?= $this->clientEntity->getNom() ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="prenom">Prénom</label>
                        <input type="text" name="prenom" id="prenom" value="<?= $this->clientEntity->getPrenom() ?>" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="text" name="email" id="email" value="<?= $this->clientEntity->getEmail() ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="telephone">Téléphone</label>
                        <input type="text" name="telephone" id="telephone" value="<?= $this->clientEntity->getTelephone() ?>" class="form-control">
                    </div>
                </div>
                <?php if (ClientsStatus::STATUS_VALIDATED === $this->clientEntity->getIdClientStatusHistory()->getIdStatus()->getId()) : ?>
                    <div class="alert alert-success" role="alert">Validé</div>
                <?php else : ?>
                    <div class="alert alert-danger" role="alert">Désactivé</div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h1>Société</h1>
                <h2 id="company-name"><?= $this->companyEntity->getName() ?></h2>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="societe">Raison sociale</label>
                        <input type="text" name="societe" id="societe" value="<?= $this->companyEntity->getName() ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="sector">Secteur d'activité</label>
                        <?php if ($this->companyEntity->getCodeNaf() === Companies::NAF_CODE_NO_ACTIVITY) : ?>
                            <select name="sector" id="sector" class="form-control">
                                <option value="0"></option>
                                <?php foreach ($this->sectors as $sector) : ?>
                                    <option<?= ($this->companyEntity->getSector() == $sector['id_company_sector'] ? ' selected' : '') ?> value="<?= $sector['id_company_sector'] ?>">
                                        <?= $this->translator->trans('company-sector_sector-' . $sector['id_company_sector']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else : ?>
                            <div><?= $this->translator->trans('company-sector_sector-' . $this->companyEntity->getSector()) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="adresse">Adresse</label>
                        <input type="text" name="adresse" id="adresse" value="<?= null !== $this->companyAddress ? $this->companyAddress->getAddress() : '' ?>" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="cp">Code postal</label>
                        <input type="text" name="cp" id="cp" value="<?= null !== $this->companyAddress ? $this->companyAddress->getZip() : '' ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ville">Ville</label>
                        <input type="text" name="ville" id="ville" value="<?= null !== $this->companyAddress ? $this->companyAddress->getCity() : '' ?>" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="email_facture">Email de facturation</label>
                        <input type="text" name="email_facture" id="email_facture" value="<?= $this->companyEntity->getEmailFacture() ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="status">Statut</label>
                        <select id="status" name="status" class="form-control">
                            <?php if (false === empty($this->companyEntity->getIdStatus()) && false === in_array($this->companyEntity->getIdStatus(), $this->possibleCompanyStatus)) : ?>
                                <option selected disabled
                                        value="<?= $this->companyEntity->getIdStatus()->getId() ?>"><?= $this->companyManager->getCompanyStatusNameByLabel($this->companyEntity->getIdStatus()
                                        ->getLabel()) ?></option>
                            <?php endif; ?>
                            <?php /** @var $status CompanyStatus */ ?>
                            <?php foreach ($this->possibleCompanyStatus as $status) : ?>
                                <option <?= $this->companyEntity->getIdStatus()->getId() == $status->getId() ? 'selected' : '' ?>
                                        value="<?= $status->getId() ?>"><?= $this->companyManager->getCompanyStatusNameByLabel($status->getLabel()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Solde</label>
                    <?php if ($this->restFunds > 0) : ?>
                        <a href="<?= $this->lurl ?>/dossiers/add_wire_transfer_out_lightbox/<?= \Unilend\Bundle\CoreBusinessBundle\Service\WireTransferOutManager::TRANSFER_OUT_BY_COMPANY ?>/<?= $this->companyEntity->getIdCompany() ?>"
                           class="thickbox cboxElement"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                    <?php endif; ?>
                    <p><?= $this->currencyFormatter->formatCurrency($this->availableBalance, 'EUR') ?> (dont <?= $this->currencyFormatter->formatCurrency($this->restFunds, 'EUR') ?> disponible)</p>
                    <?php $this->fireView('../dossiers/blocs/wire_transfer_out_list'); ?>
                </div>
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn-primary">Modifier</button>
        </div>
    </form>

    <hr>

    <div>
        <?php if ($this->get('unilend.service.back_office_user_manager')->isGrantedRisk($this->userEntity)) : ?>
            <a role="button" class="btn-primary" href="<?= $this->lurl ?>/societe/notation/<?= $this->companyEntity->getIdCompany() ?>"><i class="fa fa-line-chart"></i>Suivi des notations</a>
        <?php endif; ?>
        <a role="button" class="btn-primary" href="<?= $this->lurl ?>/beneficiaires_effectifs/<?= $this->companyEntity->getIdCompany() ?>">Bénéficiaires effectifs</a>
        <?php if (ClientsStatus::STATUS_VALIDATED === $this->clientEntity->getIdClientStatusHistory()->getIdStatus()->getId()) : ?>
            <?php if (empty($this->clientEntity->getSecreteQuestion()) && empty($this->clientEntity->getSecreteReponse())) : ?>
                <a role="button" class="btn-primary" href="javascript:send_email_borrower_area('<?= $this->clientEntity->getIdClient() ?>', 'open');">Ouvrir l'espace emprunteur</a>
            <?php else : ?>
                <a role="button" class="btn-primary" href="javascript:send_email_borrower_area('<?= $this->clientEntity->getIdClient() ?>', 'initialize')">Réinitialiser l'espace emprunteur</a>
            <?php endif ?>
        <?php endif ?>
    </div>

    <hr>

    <div id="borrower-tabs">
        <ul>
            <li><a href="#projects">Projets</a></li>
            <li><a href="#bank-accounts">RIB</a></li>
            <li><a href="#mandates">Mandats</a></li>
            <li><a href="#operations">Opérations</a></li>
        </ul>
        <div id="projects">
            <?php if (count($this->lprojects) > 0) : ?>
                <table class="tablesorter listeProjets">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Statut</th>
                        <th>Montant</th>
                        <th>PDF</th>
                        <th>Factures</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->lprojects as $index => $project) : ?>
                        <?php $this->projects_status->get($project['status'], 'status'); ?>
                        <tr<?= ($index % 2 === 1 ? '' : ' class="odd"') ?>>
                            <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project['id_project'] ?>"><?= $project['id_project'] ?></a></td>
                            <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project['id_project'] ?>"><?= $project['title'] ?></a></td>
                            <td><?= $this->projects_status->label ?></td>
                            <td class="right"><?= $this->ficelle->formatNumber($project['amount'], 0) ?>&nbsp;€</td>
                            <td>
                                <?php if ($this->projects_pouvoir->get($project['id_project'], 'id_project')) : ?>
                                    <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $this->projects_pouvoir->name ?>">POUVOIR</a>
                                <?php elseif ($project['status'] > ProjectsStatus::FUNDE) : ?>
                                    <a href="/emprunteurs/link_ligthbox/pouvoir/<?= $project['id_project'] ?>" class="thickbox cboxElement">POUVOIR</a>
                                <?php endif; ?>
                                &nbsp;&nbsp;
                                <?php if ($this->clients_mandats->get($this->clientEntity->getIdClient(), 'id_project = ' . $project['id_project'] . ' AND status = ' . UniversignEntityInterface::STATUS_SIGNED . ' AND id_client')) : ?>
                                    <a href="<?= $this->lurl ?>/protected/mandats/<?= $this->clients_mandats->name ?>">MANDAT</a>
                                <?php elseif ($project['status'] > ProjectsStatus::FUNDE) : ?>
                                    <a href="/emprunteurs/link_ligthbox/mandat/<?= $project['id_project'] ?>" class="thickbox cboxElement">MANDAT</a>
                                <?php endif; ?>
                            </td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/emprunteurs/factures/<?= $project['id_project'] ?>" class="thickbox cboxElement" target="_blank">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Factures">
                                </a>
                            </td>
                            <td align="center">
                                <a href="<?= $this->lurl ?>/dossiers/edit/<?= $project['id_project'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Détails">
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                Aucun projet
            <?php endif; ?>
        </div>
        <div id="bank-accounts">
            <?php $this->fireView('../bank_account/blocks/validated_bank_account'); ?>
            <?php $this->fireView('../bank_account/blocks/other_bank_account'); ?>
        </div>
        <div id="mandates">
            <h3>Historique des mandats</h3>
            <table class="tablesorter listeMandats">
                <thead>
                <tr>
                    <th>ID projet</th>
                    <th>IBAN</th>
                    <th>BIC</th>
                    <th>PDF</th>
                    <th>Statut</th>
                    <th>Date d'ajout</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->aMoneyOrders as $index => $aMoneyOrder) : ?>
                    <tr<?= ($index % 2 === 1 ? '' : ' class="odd"') ?>>
                        <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $aMoneyOrder['id_project'] ?>"><?= $aMoneyOrder['id_project'] ?></a></td>
                        <td><?= $aMoneyOrder['iban'] ?></td>
                        <td><?= $aMoneyOrder['bic'] ?></td>
                        <td><a href="<?= $this->lurl ?>/protected/mandats/<?= $aMoneyOrder['name'] ?>">MANDAT</a></td>
                        <td>
                            <?php
                            switch ($aMoneyOrder['status']) {
                                case UniversignEntityInterface::STATUS_PENDING:
                                    echo 'En attente de signature';
                                    break;
                                case UniversignEntityInterface::STATUS_SIGNED:
                                    echo 'Signé';
                                    break;
                                case UniversignEntityInterface::STATUS_CANCELED:
                                    echo 'Annulé';
                                    break;
                                case UniversignEntityInterface::STATUS_FAILED:
                                    echo 'Echec';
                                    break;
                                case UniversignEntityInterface::STATUS_ARCHIVED:
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
        </div>
        <div id="operations">
            <h3>Relevé des opérations</h3>
            <div style="float: right">
                <form method="post" id="operation-date-form" action="#" class="form-inline">
                    <div class="form-group">
                        <select name="operation-date-filter" class="select">
                            <?php foreach (range(date('Y'), $this->clientEntity->getAdded()->format('Y')) as $year) : ?>
                                <option value="<?= $year ?>"><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" value="Filtrer" name="filter" class="btn-primary" id="filter-button">
                    </div>
                </form>
            </div>
            <div class="borrower-operation-table" style="clear: both;">
                <?php $this->fireView('operations'); ?>
            </div>
        </div>
    </div>
</div>
