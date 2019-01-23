<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{Projects, ProjectsPouvoir, UniversignEntityInterface};

?>
<script type="text/javascript">
    $(function() {
        $(".inline").colorbox({inline: true, width: "50%"});
    });
</script>
<div id="contenu">
    <h1>Liste des fonds non débloqués à contrôler</h1>
    <?php if (empty($this->projects)) : ?>
        <p>Aucun déblocage de fonds à contrôler</p>
    <?php else : ?>
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
                <?php foreach ($this->projects as $projectArray) : ?>
                    <?php /** @var Projects $project */ ?>
                    <?php $project = $projectArray['project']; ?>
                    <tr>
                        <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>"><?= $project->getIdProject() ?></a></td>
                        <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>"><?= $project->getTitle() ?></a></td>
                        <td><?= $this->ficelle->formatNumber($project->getAmount(), 0) . '&nbsp€' ?></td>
                        <td><?= isset($projectArray['bic']) ? $projectArray['bic'] : '' ?></td>
                        <td><?= isset($projectArray['iban']) ? $projectArray['iban'] : '' ?></td>
                        <td>
                            <?php if (false === empty($projectArray['rib'])) : ?>
                                <a href="<?= $this->lurl ?>/attachment/download/id/<?= $projectArray['id_rib'] ?>/file/<?= urlencode($projectArray['rib']) ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="RIB">
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (false === empty($projectArray['kbis'])) : ?>
                                <a href="<?= $this->url ?>/attachment/download/id/<?= $projectArray['id_kbis'] ?>/file/<?= urlencode($projectArray['kbis']) ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="K-BIS">
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (false === empty($projectArray['url_pdf'])) : ?>
                                <a href="<?= $this->lurl ?>/protected/pouvoir_project/<?= $projectArray['url_pdf'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Pouvoir">
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (false === empty($projectArray['mandat'])) : ?>
                                <a href="<?= $this->lurl ?>/protected/mandats/<?= $projectArray['mandat'] ?>">
                                    <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Mandat">
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($projectArray['needsBeneficialOwnerDeclaration']) : ?>
                                <?php if (false === empty($projectArray['beneficial_owner_declaration_status']) && UniversignEntityInterface::STATUS_SIGNED == $projectArray['beneficial_owner_declaration_status']) : ?>
                                    <a href="<?= $this->lurl ?>/protected/beneficiaires_effectifs/<?= $projectArray['beneficial_owner_declaration'] ?>">
                                        <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Bénéficiaires effectifs">
                                    </a>
                                <?php else : ?>
                                    <p>Déclaration pas encore signée</p>
                                <?endif; ?>
                            <?php else : ?>
                                <p>Non demandé pour ce type d'entrepise</p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" name="deblocage" onsubmit="return confirm('Voulez-vous vraiment débloquer les fonds pour le projet <?= addslashes($project->getTitle()) ?> (<?= $project->getIdProject() ?>) ?');">
                                <?php if (
                                    isset($projectArray['status_remb'], $projectArray['status_mandat'], $projectArray['authority_status'], $projectArray['needsBeneficialOwnerDeclaration'])
                                    && $projectArray['status_remb'] == ProjectsPouvoir::STATUS_REPAYMENT_PENDING
                                    && $projectArray['status_mandat'] == UniversignEntityInterface::STATUS_SIGNED
                                    && $projectArray['authority_status'] == UniversignEntityInterface::STATUS_SIGNED
                                    && (
                                        false === $projectArray['needsBeneficialOwnerDeclaration']
                                        || isset($projectArray['beneficial_owner_declaration_status']) && $projectArray['beneficial_owner_declaration_status'] == UniversignEntityInterface::STATUS_SIGNED
                                    )
                                ) : ?>
                                    <input type="submit" name="validateProxy" class="btn-primary" value="Débloquer les fonds">
                                    <input type="hidden" name="id_project" value="<?= $project->getIdProject() ?>">
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
