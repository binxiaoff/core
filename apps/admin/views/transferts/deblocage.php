<script type="text/javascript">
    $(function() {
        $(".inline").colorbox({inline: true, width: "50%"});
    });
</script>
<div id="contenu">
    <h1>Liste des fonds non débloqués à contrôler</h1>
    <table class="tablesorter">
        <thead>
            <tr>
                <th>ID du dossier</th>
                <th>Nom du projet</th>
                <th>Montant</th>
                <th>BIC</th>
                <th>Iban</th>
                <th>RIB</th>
                <th>Kbis</th>
                <th>Pouvoir</th>
                <th>Mandat</th>
                <th>Bénéficiaires <br>Effectifs</th>
                <th>Déblocage</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->aProjects as $aProject) : ?>
                <tr>
                    <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $aProject['id_project'] ?>"><?= $aProject['id_project'] ?></a></td>
                    <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $aProject['id_project'] ?>"><?= $aProject['title'] ?></a></td>
                    <td><?= $this->ficelle->formatNumber($aProject['amount'], 0) . '&nbsp€' ?></td>
                    <td><?= isset($aProject['bic']) ? $aProject['bic'] : '' ?></td>
                    <td><?= isset($aProject['iban']) ? $aProject['iban'] : '' ?></td>
                    <td>
                        <?php if (false === empty($aProject['rib'])) : ?>
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $aProject['id_rib'] ?>/file/<?= urlencode($aProject['rib']) ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="RIB"/>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (false === empty($aProject['kbis'])) : ?>
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $aProject['id_kbis'] ?>/file/<?= urlencode($aProject['kbis']) ?>">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="KBIS"/>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (false === empty($aProject['url_pdf'])) : ?>
                            <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $aProject['url_pdf'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="POUVOIR"/></a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (false === empty($aProject['mandat'])) : ?>
                            <a href="<?= $this->lurl ?>/protected/mandats/<?= $aProject['mandat'] ?>"><img src="<?= $this->surl ?>/images/admin/modif.png" alt="MANDAT"/></a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (false === empty($aProject['beneficial_owner_declaration'])) : ?>
                            <?php if ($aProject['beneficial_owner_declaration']) : ?>
                                <a href="<?= $this->lurl ?>/protected/beneficiaires_effectifs/<?= $aProject['beneficial_owner_declaration'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="BENEFCIAIRES EFFECTIFS"/>
                                </a>
                            <?php else: ?>
                                <p>Non demandé pour se type d'entrerpise</p>
                            <?endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" name="deblocage" onsubmit="return confirm('Voulez-vous vraiment débloquer les fonds pour le projet <?= $aProject['id_project'] ?> ?');">
                            <?php if (
                                isset($aProject['status_remb'], $aProject['status_mandat'], $aProject['authority_status'])
                                && $aProject['status_remb'] == \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsPouvoir::STATUS_REPAYMENT_PENDING
                                && $aProject['status_mandat'] == \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED
                                && $aProject['authority_status'] == \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED
                                && $aProject['beneficial_owner_declaration_status'] == \Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED
                            ) : ?>
                                <input type="submit" name="validateProxy" class="btn-primary" value="Débloquer les fonds" />
                                <input type="hidden" name="id_project" value="<?= $aProject['id_project'] ?>"/>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
