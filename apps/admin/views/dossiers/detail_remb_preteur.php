<script type="text/javascript">
    $(function () {
        jQuery.tablesorter.addParser({
            id: "fancyNumber", is: function (s) {
                return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
            }, format: function (s) {
                return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
            }, type: "numeric"
        });

        $(".tablesorter").tablesorter({headers: {8: {sorter: false}, 9: {sorter: false}}});
        <?php if ($this->nb_lignes != '') { ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php } ?>
    });

    <?php if (isset($_SESSION['freeow'])) : ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php unset($_SESSION['freeow']); ?>
    <?php endif; ?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/dossiers" title="Dossiers">Dossiers</a> -</li>
        <li><a href="<?= $this->lurl ?>/dossiers/remboursements" title="Remboursements">Remboursements</a> -</li>
        <li>
            <a href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->params[0] ?>" title="Detail remboursements">Detail remboursements</a> -
        </li>
        <li>Detail prêteur</li>
    </ul>
    <h1>Liste des <?= count($this->lLenders) ?> derniers remboursements</h1>
    <br/>
    <table style="width:800px;">
        <tr>
            <th style="width:140px;">Nombre de prêteurs :</th>
            <td><?= $this->nbPeteurs ?></td>
            <th style="width:140px;">Remboursement total :</th>
            <td><?= $this->ficelle->formatNumber($this->montant) ?> €</td>
            <th style="width:140px;">Taux moyen :</th>
            <td><?= $this->ficelle->formatNumber($this->tauxMoyen) ?> %</td>
        </tr>
    </table>
    <br/>
    <?php if (count($this->lLenders) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>id prêt</th>
                <th>Montant en €</th>
                <th>Taux en %</th>
                <th>Mensualité de<br/> remboursement</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>id client</th>
                <th>PDF</th>
                <th>Ancien proprietaire</th>
                <th>Echéancier preteur</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($this->lLenders as $loan) :
                $this->projects->get($loan['id_project'], 'id_project');
                $this->loan->get($loan['id_loan']);
                $this->lenders_accounts->get($loan['id_lender'], 'id_lender_account');
                $this->clients->get($this->lenders_accounts->id_client_owner, 'id_client');
                $lesEcheances = $this->echeanciers->select('id_loan = ' . $loan['id_loan']);
                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $loan['id_loan'] ?></td>
                    <td class="right"><?= $this->ficelle->formatNumber($loan['amount'] / 100) ?></td>
                    <td class="right"><?= $this->ficelle->formatNumber($loan['rate'], 1) ?></td>
                    <td class="right"><?= $this->ficelle->formatNumber($lesEcheances[0]['montant'] / 100) ?></td>
                    <td><?= $this->clients->nom ?></td>
                    <td><?= $this->clients->prenom ?></td>
                    <td><?= $this->clients->id_client ?></td>
                    <td>
                        <a href="<?= $this->furl . '/pdf/contrat/' . $this->clients->hash . '/' . $loan['id_loan'] ?>">PDF</a>
                    </td>
                    <td align="center">
                        <a target="_blank" href="<?= $this->lurl ?>/dossiers/detail_echeance_preteur/<?= $loan['id_project'] ?>/<?= $loan['id_loan'] ?>">
                            <img src="<?= $this->surl ?>/images/admin/modif.png" alt="detail"/>
                        </a>
                    </td>
                    <td>
                        <?php if (false === empty($loan['id_transfer'])) :
                        /** @var \lenders_accounts $formerOwner */
                        $formerOwner = $this->loanManager->getFormerOwnerOfLoan($this->loan); ?>
                        <a href="<?= $this->lurl . '/preteurs/edit/' . $formerOwner->id_lender_account ?>"><?= $formerOwner->id_client_owner ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
                $i++;
            endforeach;
            ?>
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
<?php unset($_SESSION['freeow']); ?>
