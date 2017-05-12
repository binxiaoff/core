<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({
                container: $("#pager"),
                positionFixed: false,
                size: <?= $this->nb_lignes ?>
            });
        <?php endif; ?>

        $('.manual_repayment_action').click(function(event) {
            event.preventDefault()

            var $popup = $('#popup-content').clone()
            $popup.find('.btn_link.validate').attr('href', event.target.href)

            $.colorbox({html: $popup.html()})
        })
    });
</script>
<style>
    .form th {width: 125px;}
    .form2 th {width: auto;}
    .manual_repayment_action {display: block; margin: auto; width: 225px;}
    #popup-content {display: none;}
</style>
<div id="popup-content">
    <div id="popup">
        <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a><br>
        <h3 style="white-space: nowrap;">Confirmer le remboursement</h3>
        <div style="text-align: center;">
            <button type="button" class="btn btnDisabled" onclick="parent.$.fn.colorbox.close()">Annuler</button>
            <a href="javascript:;" class="btn_link validate">Valider</a>
        </div>
    </div>
</div>
<div id="contenu">
    <h1>Remboursement <?= $this->companies->name ?> - <?= $this->projects->title ?></h1>
    <div class="btnDroite">
        <a style="margin-right:10px;" target="_blank" href="<?= $this->lurl ?>/dossiers/echeancier_emprunteur/<?= $this->projects->id_project ?>" class="btn_link">Echeancier Emprunteur</a>
        <a target="_blank" href="<?= $this->lurl ?>/dossiers/edit/<?= $this->projects->id_project ?>" class="btn_link">Voir le dossier</a>
    </div>
    <table class="form" style="margin: auto;">
        <tr>
            <td colspan="7"><h2>Informations projet</h2></td>
        </tr>
        <tr>
            <td colspan="2"><b><?= $this->companies->name ?> - <?= $this->projects->title ?></b></td>
            <td><?= $this->ficelle->formatNumber($this->projects->amount, 0) ?>&nbsp;€ - <?= $this->projects->period ?> mois</td>
            <th>Risques :</th>
            <td><?= $this->companies->risk ?></td>
            <th>Analyste :</th>
            <td><?= $this->users->firstname ?> <?= $this->users->name ?></td>
        </tr>
        <tr>
            <th>Contact :</th>
            <td><?= $this->clients->nom ?> <?= $this->clients->prenom ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <th>Nombre de prêteur :</th>
            <td><?= $this->nbPeteurs ?></td>
            <td>Fundé depuis le <?= $this->dates->formatDate($this->projects->date_fin, 'd/m/Y') ?></td>
            <td></td>
            <td></td>
            <th>Statut :</th>
            <td><?= $this->projects_status->label ?></td>
        </tr>
        <tr>
            <th>Commission Unilend :</th>
            <td><?= $this->ficelle->formatNumber($this->commissionUnilend / 100) ?> €</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <br /><br />
    <table class="form form2" style="margin: auto;">
        <tr>
            <td colspan="4"><h2>Remboursement</h2></td>
        </tr>
        <tr>
            <th>Remboursements effectué :</th>
            <td><?= $this->nbRembEffet ?></td>
            <th>Montant remboursé :</th>
            <td><?= $this->ficelle->formatNumber($this->totalEffet / 100) ?> €</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <th></th>
            <td>
                <i><?= $this->ficelle->formatNumber($this->interetEffet / 100) ?> € d'intérêts
                    - <?= $this->ficelle->formatNumber($this->capitalEffet / 100) ?> € de capital
                    - <?= $this->ficelle->formatNumber($this->commissionEffet / 100) ?> € de commissions
                    - <?= $this->ficelle->formatNumber($this->tvaEffet / 100) ?> € de TVA</i>
            </td>
        </tr>
        <tr style="height:30px;">
            <td colspan="4"></td>
        </tr>
        <tr>
            <th>Remboursements à venir :</th>
            <td><?= $this->nbRembaVenir ?></td>
            <th>Montant à percevoir :</th>
            <td><?= $this->ficelle->formatNumber($this->totalaVenir / 100) ?> €</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td>
                <i><?= $this->ficelle->formatNumber($this->interetaVenir / 100) ?> € d'intérêts
                    - <?= $this->ficelle->formatNumber($this->capitalaVenir / 100) ?> € de capital
                    - <?= $this->ficelle->formatNumber($this->commissionaVenir / 100) ?> € de commissions
                    - <?= $this->ficelle->formatNumber($this->tvaaVenir / 100) ?> € de TVA</i>
            </td>
        </tr>
        <?php if ($this->projects->status == \projects_status::REMBOURSEMENT_ANTICIPE) : ?>
            <tr>
                <th>Prochain remboursement :</th>
                <td><?= $this->dates->formatDate($this->nextRemb, 'd/m/Y') ?></td>
                <td></td>
                <td></td>
            </tr>
        <?php endif; ?>
    </table>
    <br/><br/>
    <div style="border: 1px solid #b20066; height: 60px; padding: 5px; width: 280px;">
        <form action="" method="post">
            <b>Remboursement automatique : </b>
            <input type="radio" name="remb_auto" value="0"<?= ($this->projects->remb_auto == 0 ? ' checked' : '') ?>>Oui
            <input type="radio" name="remb_auto" value="1"<?= ($this->projects->remb_auto == 1 ? ' checked' : '') ?>>Non
            <br/>
            <input type="hidden" name="send_remb_auto"/>
            <input style="margin-top:5px;" type="submit" value="Valider" name="valider_remb_auto" class="btn"/>
        </form>
    </div>
    <br/><br/>
    <?php if (1 == $this->projects->remb_auto) : ?>
        <a href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->projects->id_project ?>/remb" class="btn_link manual_repayment_action">Rembourser</a><br/>
        <a href="<?= $this->lurl ?>/dossiers/detail_remb/<?= $this->projects->id_project ?>/remb/regul" class="btn_link manual_repayment_action">Régulariser un remboursement en retard</a>
    <?php endif; ?>
    <br/>
    <div class="btnDroite">
        <a style="border-color: #A1A5A7;background-color:#A1A5A7; color:white;"
           href="<?= $this->lurl ?>/dossiers/detail_remb_preteur/<?= $this->projects->id_project ?>" class="btn_link">Voir le détail prêteur</a>
    </div>
    <br/><br/>
    <?php $this->fireView('early_repayment'); ?>
</div>
