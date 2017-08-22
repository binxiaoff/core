<h2>Remboursement anticipé / Information</h2>
<?php if (true === $this->earlyRepaymentPossible) : ?>
    <table class="form" style="width: 100%; border: 1px solid #b20066;">
        <tr>
            <th>Statut :</th>
            <td>
                <label for="statut"><?= $this->message ?></label>
            </td>
        </tr>
        <?php if ($this->reception) : ?>
            <tr>
                <th>Virement reçu le :</th>
                <td><label for="statut"><?= $this->reception->getAdded()->format('d/m/Y') ?></label></td>
            </tr>
            <tr>
                <th>Identification virement :</th>
                <td><label for="statut"><?= $this->reception->getIdReception() ?></label></td>
            </tr>
            <tr>
                <th>Montant virement :</th>
                <td><label for="statut"><?= ($this->reception->getMontant() / 100) ?> €</label></td>
            </tr>
            <tr>
                <th>Motif du virement :</th>
                <td><label for="statut"><?= $this->reception->getMotif() ?></label></td>
            </tr>
        <?php elseif (isset($this->earlyRepaymentLimitDate)) : ?>
            <tr>
                <th>Virement à émettre avant le :</th>
                <td><label for="statut"><?= $this->earlyRepaymentLimitDate->format('d/m/Y') ?></label></td>
            </tr>
            <tr>
                <th>Montant CRD (*) :</th>
                <td><label for="statut"><?= $this->ficelle->formatNumber($this->lenderOwedCapital) ?>&nbsp;€</label></td>
            </tr>
        <?php endif; ?>

        <?php if ($this->reception instanceof \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions) : ?>
            <?php if ($this->wireTransferAmountOk && $this->displayActionButton) : ?>
                <tr>
                    <th>Actions :</th>
                    <td>
                        <a id="form-trigger" href="#" class="btn_link">Déclencher le remboursement anticipé</a>
                        <div id="form-early-repayment" style="display: none">
                            <div id="popup">
                                <a onclick="parent.$.fn.colorbox.close();" class="closeBtn" title="Fermer"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a><br>
                                <h3 style="white-space: nowrap;">Confirmer le remboursement anticipé</h3>
                                <form action="" method="post" name="action_remb_anticipe">
                                    <input type="hidden" name="id_reception" value="<?= $this->reception->getIdReception() ?>">
                                    <input type="hidden" name="spy_remb_anticipe" value="ok">
                                    <div style="text-align: center;">
                                        <button type="button" class="btn btnDisabled" onclick="parent.$.fn.colorbox.close()">Annuler</button>
                                        <input type="submit" id="btn-early-repayment" value="Valider" class="btn" data-prevent-doubleclick>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        <?php else : ?>
            <tr>
                <th>Motif à indiquer sur le virement :</th>
                <td><label for="statut">RA-<?= $this->projects->id_project ?></label></td>
            </tr>
        <?php endif; ?>
    </table>
    <?php if (false === $this->reception instanceof \Unilend\Bundle\CoreBusinessBundle\Entity\Receptions) : ?>
        <p>* : Le montant correspond aux CRD des échéances restantes après celle du <?= $this->nextScheduledRepaymentDate->format('d/m/Y') ?> qui sera prélevée normalement</p>
    <?php endif; ?>
<?php else: ?>
    <?= $this->message ?>
<?php endif; ?>

<script>
    $(function() {
        $('#form-trigger').click(function(event) {
            event.preventDefault()
            earlyRepaymentForm = $("#form-early-repayment").html()
            $.colorbox({html: earlyRepaymentForm})
        })
    });
</script>
