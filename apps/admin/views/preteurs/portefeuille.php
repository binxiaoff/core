<?php if (isset($_SESSION['freeow'])): ?>
    <script type="text/javascript">
        $(function() {
            var title = "<?=$_SESSION['freeow']['title']?>",
                message = "<?=$_SESSION['freeow']['message']?>",
                opts = {},
                container;
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
        <?php unset($_SESSION['freeow']); ?>
    </script>
<?php endif; ?>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/preteurs" title="Prêteurs">Prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Gestion prêteurs</a> -</li>
        <li><a href="<?= $this->lurl ?>/preteurs/gestion" title="Gestion prêteurs">Detail prêteur</a> -</li>
        <li>Portefeuille & Performances</li>
    </ul>

    <?php if ($this->clients_status->status == 10): // a controler ?>
        <div class="attention">
            Attention : compte non validé - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
    <?php elseif (in_array($this->clients_status->status, array(20, 30, 40))): // completude ?>
        <div class="attention" style="background-color:#F9B137">
            Attention : compte en complétude - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
    <?php elseif (in_array($this->clients_status->status, array(50))): // modification ?>
        <div class="attention" style="background-color:#F2F258">
            Attention : compte en modification - créé le <?= date('d/m/Y', $this->timeCreate) ?>
        </div>
    <?php endif; ?>

    <h1>Detail prêteur : <?= $this->clients->prenom . ' ' . $this->clients->nom ?></h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/preteurs/edit/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Consulter Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/edit_preteur/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Modifier Prêteur</a>
        <a href="<?= $this->lurl ?>/preteurs/email_history/<?= $this->lenders_accounts->id_lender_account ?>" class="btn_link">Historique des emails</a>
    </div>

    <div>
        <h2>Portefeuille</h2>

        <h3>TRI du portefeuille : <?= empty($this->IRRValue) === false ? $this->IRRValue.'%' : 'non calculable' ?>
            à date <?= empty($this->IRRdate) === false ? $this->dates->formatDateMysqltoShortFR($this->IRRdate): 'd\'aujourd\'hui' ?></h3>

        <h3>Nombre de projets à probleme dans le portefeuille :  <?= $this->problProjects ?></h3>
        <h3>Nombre de projets total dans le portefeuille : <?= $this->totalProjects ?></h3>
        <h3>Nombre de projets mis en ligne depuis son inscription : <?= $this->projectsPublished ?><h2>
    </div>

    <br/>

    <h2>Prêts</h2>
    <div class="table-filter clearfix">
        <p class="left">Historique des projets financés depuis le compte Unilend n°<?= $this->clients->id_client ?></p>
    </div>
    <div>
        <table class="tablesorter">
            <thead>
            <tr>
                <th style="text-align: left">Projet</th>
                <th style="text-align: left">Note</th>
                <th style="text-align: left">Montant prêté</th>
                <th style="text-align: left">Taux d'intérêt</th>
                <th style="text-align: left">Début</th>
                <th style="text-align: left">Prochaine</th>
                <th style="text-align: left">Fin</th>
                <th style="text-align: left">Mensualité</th>
                <th style="text-align: left">Documents <br> à télécharger</th>
            </tr>
            </thead>
            <?php if ($this->lSumLoans != false): ?>
                <?php $i = 1; ?>
                <?php foreach ($this->lSumLoans as $k => $l): ?>
                    <?php
                    $Le_projects = $this->loadData('projects');
                    $Le_projects->get($l['id_project']);
                    $this->projects_status->getLastStatut($l['id_project']);

                    //si un seul loan sur le projet
                    if ($l['nb_loan'] == 1) {
                        ?>
                        <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?>">
                            <td><h5><a href="/dossiers/edit/<?= $l['id_project'] ?>"><?= $l['name'] ?></a></h5></td>
                            <td><?= $l['risk'] ?></td>
                            <td><?= $this->ficelle->formatNumber($l['amount']) ?> €</td>
                            <td><?= $this->ficelle->formatNumber($l['rate']) ?> %</td>
                            <?php if ($l['project_status'] == projects_status::REMBOURSEMENT_ANTICIPE): ?>
                                <td><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></td>
                                <td colspan="3"><p>Remboursé intégralement le <?= $this->dates->formatDate($l['status_change'], 'd/m/Y') ?></p></td>
                            <?php else: ?>
                                <td><?=$this->dates->formatDate($l['debut'], 'd/m/Y')?></td>
                                <td><?=$this->dates->formatDate($l['next_echeance'], 'd/m/Y')?></td>
                                <td><?=$this->dates->formatDate($l['fin'], 'd/m/Y')?></td>
                                <td><?=$this->ficelle->formatNumber($l['mensuel'])?> €/mois</td>
                            <?php endif; ?>
                            <td>
                                <?php if ($this->projects_status->status >=projects_status::REMBOURSEMENT): ?>
                                    <a href="<?= $this->lurl . '/preteurs/contratPdf/' . $this->clients->hash . '/' . $l['id_loan_if_one_loan'] ?>">Contrat PDF</a><br>
                                    <?php if (in_array($l['id_project'], $this->arrayDeclarationCreance)): ?>
                                        <a href="<?= $this->lurl . '/preteurs/creancesPdf/' . $this->clients->hash . '/' . $l['id_loan_if_one_loan'] ?>">Créances PDF</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                           </td>
                        </tr>
                        <?php
                    } else {
                        ?>
                        <tr class="<?= ($i++ % 2 == 1 ? '' : 'odd') ?>">
                            <td><h5><a href="/dossiers/edit/<?= $l['id_project'] ?>"><?= $l['name'] ?></a></h5></td>
                            <td><?= $l['risk'] ?></td>
                            <td><?= $this->ficelle->formatNumber($l['amount']) ?> €</td>
                            <td><?= $this->ficelle->formatNumber($l['rate']) ?> %</td>
                            <?php if ($l['project_status'] == projects_status::REMBOURSEMENT_ANTICIPE): ?>
                                <td><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></td>
                                <td colspan="3"><p>Remboursé intégralement le <?= $this->dates->formatDate($l['status_change'], 'd/m/Y') ?></p></td>
                            <?php else: ?>
                                <td><?= $this->dates->formatDate($l['debut'], 'd/m/Y') ?></td>
                                <td><?= $this->dates->formatDate($l['next_echeance'], 'd/m/Y') ?></td>
                                <td><?= $this->dates->formatDate($l['fin'], 'd/m/Y') ?></td>
                                <td><?= $this->ficelle->formatNumber($l['mensuel']) ?> €/mois</td>
                            <?php endif; ?>
                            <td></td>
                        </tr>
                        <?php
                            $a = 0;
                            $listeLoans = $this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $l['id_project']);

                            foreach ($listeLoans as $loan) {
                                $SumAremb = $this->echeanciers->select('id_loan = '.$loan['id_loan'] . ' AND status = 0', 'ordre ASC', 0, 1);
                                $fiscal   = $SumAremb[0]['prelevements_obligatoires'] + $SumAremb[0]['retenues_source'] + $SumAremb[0]['csg'] + $SumAremb[0]['prelevements_sociaux'] + $SumAremb[0]['contributions_additionnelles'] + $SumAremb[0]['prelevements_solidarite'] + $SumAremb[0]['crds'];
                                $b        = $a + 1;
                                ?>
                                <tr style="background-color: #e3e4e5; color: black;">
                                    <td style="text-align: right; background-color: #e3e4e5; color: black;">Détail loan</td>
                                    <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                    <td style="background-color: #e3e4e5; color: black;"><?= $this->ficelle->formatNumber($loan['amount'] / 100, 0) ?> €</td>
                                    <td style="background-color: #e3e4e5; color: black;"><?= $this->ficelle->formatNumber($loan['rate']) ?>%</td>
                                    <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                    <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                    <td style="text-align: right; background-color: #e3e4e5; color: black;"></td>
                                    <td style="background-color: #e3e4e5; color: black;"><?= $this->ficelle->formatNumber($SumAremb[0]['montant'] / 100 - $fiscal) ?> €/mois</td>
                                    <td>
                                    <?php if ($this->projects_status->status >= \projects_status::REMBOURSEMENT): ?>
                                        <a style="background-color: #e3e4e5; color: black;" href="<?= $this->lurl . '/preteurs/contratPdf/' . $this->clients->hash . '/' . $loan['id_loan'] ?>">Contrat PDF</a><br>
                                        <?php if (in_array($l['id_project'], $this->arrayDeclarationCreance)): ?>
                                            <a style="background-color: #e3e4e5; color: black;" href="<?= $this->lurl . '/pdf/declaration_de_creances/' . $this->clients->hash . '/' . $loan['id_loan'] ?>">Créances PDF</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    </td>
                                </tr>
                                <script type="text/javascript">
                                    $(".btn-detailLoans_<?= $k ?>").click(function() {
                                        $(".loans_<?= $k ?>").slideToggle();

                                        if ($(".btn-detailLoans_<?= $k ?>").hasClass("on_display")) {
                                            $(".btn-detailLoans_<?= $k ?>").html('+');
                                            $(".btn-detailLoans_<?= $k ?>").addClass("off_display");
                                            $(".btn-detailLoans_<?= $k ?>").removeClass("on_display");
                                        } else {
                                            $(".btn-detailLoans_<?= $k ?>").html('-');
                                            $(".btn-detailLoans_<?= $k ?>").addClass("on_display");
                                            $(".btn-detailLoans_<?= $k ?>").removeClass("off_display");
                                        }
                                    });
                                </script>
                                <?php
                                $a++;
                            }
                        ?>
                        </tr>
                        <?php
                        $i++;
                    }
                ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
