<script type="text/javascript">
    $(function() {
        $(".listeProjets").tablesorter({headers: {4: {sorter: false}, 5: {sorter: false}}});
        $(".listeMandats").tablesorter();
        $(".mandats").tablesorter({headers: {}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".listeProjets").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
            $(".mandats").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>
    });
</script>
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
                <td><input type="text" name="prenom" id="prenom" class="input_large" value="<?= $this->clients->prenom ?>"/></td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="text" name="email" id="email" class="input_large" value="<?= $this->clients->email ?>"/></td>
                <th><label for="telephone">Téléphone</label></th>
                <td><input type="text" name="telephone" id="telephone" class="input_large" value="<?= $this->clients->telephone ?>"/></td>
            </tr>
            <tr>
                <th><label for="societe">Société</label></th>
                <td><input type="text" name="societe" id="societe" class="input_large" value="<?= $this->companies->name ?>"/></td>
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
                <td colspan="3"><input type="text" name="adresse" id="adresse" style="width: 620px;" class="input_big" value="<?= $this->clients_adresses->adresse1 ?>"/></td>
            </tr>
            <tr>
                <th><label for="cp">Code postal</label></th>
                <td><input type="text" name="cp" id="cp" class="input_large" value="<?= $this->clients_adresses->cp ?>"/></td>
                <th><label for="ville">Ville</label></th>
                <td><input type="text" name="ville" id="ville" class="input_large" value="<?= $this->clients_adresses->ville ?>"/></td>
            </tr>
            <tr>
                <th><label for="email_facture">Email de facturation :</label></th>
                <td colspan="3"><input type="text" name="email_facture" id="email_facture" class="input_large" value="<?= $this->companies->email_facture ?>"/></td>
            </tr>
            <tr>
                <th></th>
                <td><input style="font-size: 11px; height: 25px;" type="button" id="initialiser_espace_emprunteur" name="initialiser_espace_emprunteur" value="Reinitialiser Espace Emprunteur" class="btn" onclick="send_email_borrower_area('<?= $this->clients->id_client ?>', 'initialize')"/></td>
                <?php if (empty($this->clients->secrete_question) && empty($this->clients->secrete_reponse)) : ?>
                    <td colspan="2"><input style="font-size: 11px; height: 25px;" type="button" id="ouvrir_espace_emprunteur" name="ouvrir_espace_emprunteur" value="Envoyer Email Ouverture Espace Emprunteur" class="btn" onclick="send_email_borrower_area('<?= $this->clients->id_client ?>', 'open')"/></td>
                <?php endif ?>
               <td><span style="margin-left:5px;color:green; display:none;" class="reponse_email" >Email Envoyé</span></td>
            </tr>
            <tr>
                <th colspan="4">
                    <input type="hidden" name="form_edit_emprunteur" id="form_edit_emprunteur" />
                    <input type="submit" value="Valider" title="Valider" name="send_edit_emprunteur" id="send_edit_emprunteur" class="btn" />
                </th>
            </tr>
        </table>
    </form>
    <br /><br />

    <h2>RIB emprunteur en vigueur</h2>
    <?php if ($this->bankAccount) : ?>
        <table class="tablesorter" style="width: 775px;margin:auto;">
            <tr>
                <td>Document</td>
                <td>
                    <?php if ($this->bankAccount->getAttachment()) : ?>
                        <a href="<?= $this->url ?>/attachment/download/id/<?= $this->bankAccount->getAttachment()->getId() ?>/file/<?= urlencode($this->bankAccount->getAttachment()->getPath()) ?>"><?= $this->bankAccount->getAttachment()->getPath() ?></a>
                    <?php else : ?>
                        pas de document fourni.
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>IBAN</td>
                <td>
                    <?= chunk_split($this->bankAccount->getIban(), 4, ' '); ?>
                </td>
            </tr>
            <tr>
                <td>BIC</td>
                <td><?= $this->bankAccount->getBic() ?></td>
            </tr>
        </table>
    <?php else : ?>
        Pas de RIB en vigueur.
    <?php endif; ?>
    <br><br>

    <h2>Autre RIBs</h2>
    <table class="formColor" style="width: 775px;">
        <?php use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount; ?>
        <?php if (false === empty($this->bankAccountDocuments)) : ?>
            <?php foreach ($this->bankAccountDocuments as $attachment) : ?>
                <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount $bankAccount */ ?>
                <?php $bankAccount = $attachment->getBankAccount(); ?>
                <?php if (null === $bankAccount || BankAccount::STATUS_PENDING === $bankAccount->getStatus() || BankAccount::STATUS_ARCHIVED === $bankAccount->getStatus()) :  ?>
        <tr>
            <td style="padding-bottom:20px">
                <a href="<?= $this->url ?>/attachment/download/id/<?= $attachment->getId() ?>/file/<?= urlencode($attachment->getPath()) ?>"><?= $attachment->getPath() ?></a>
            </td>
            <td>
                <?php if ($bankAccount) : ?>
                    <?= chunk_split($bankAccount->getIban(), 4, ' '); ?>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($bankAccount) : ?>
                    <a href="/emprunteurs/validate_rib_lightbox/<?= $bankAccount->getId(); ?>" class="btn_link thickbox cboxElement">Mettre en vigueur</a>
                <?php else : ?>
                    <a href="/emprunteurs/extraction_rib_lightbox/<?= $attachment->getId(); ?>" class="btn_link thickbox cboxElement">Extraire</a>
                <?php endif; ?>
            </td>
        </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <br><br>

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
                        <?php elseif ($aProject['status']  > \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus::FUNDE) : ?>
                            <a href="/emprunteurs/link_ligthbox/mandat/<?= $aProject['id_project'] ?>" class="thickbox cboxElement">MANDAT</a>
                        <?php endif; ?>
                    </td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/emprunteurs/factures/<?= $aProject['id_project'] ?>" class="thickbox cboxElement" target="_blank">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Factures" />
                        </a>
                    </td>
                    <td align="center">
                        <a href="<?= $this->lurl ?>/dossiers/edit/<?= $aProject['id_project'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Détails" />
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
                        <input type="text" class="pagedisplay" />
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
</div>
