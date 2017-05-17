<?php use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers; ?>
<script type="text/javascript">
  $(function () {
    jQuery.tablesorter.addParser({
      id: "fancyNumber", is: function (s) {
        return /[\-\+]?\s*[0-9]{1,3}(\.[0-9]{3})*,[0-9]+/.test(s);
      }, format: function (s) {
        return jQuery.tablesorter.formatFloat(s.replace(/,/g, '').replace(' €', '').replace(' ', ''));
      }, type: "numeric"
    });

    $(".tablesorter").tablesorter({headers: {9: {sorter: false}}});

      <?php if ($this->nb_lignes != '') : ?>
    $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
      <?php endif; ?>
  });
</script>
<div id="contenu">
    <h1>Liste des <?= count($this->lRemb) ?> derniers remboursements</h1>
    <?php if (false === empty($this->loan->id_transfer)) : ?>
        <div style="background-color: #4fa8b0; padding: 3px; margin-bottom: 5px;"><h2>Attention ce prêt a changé de propriétaire</h2></div>
    <?php endif; ?>
    <?php if (count($this->lRemb) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Montant</th>
                <th>Capital</th>
                <th>Capital remboursé</th>
                <th>Interets</th>
                <th>Interets remboursés</th>
                <th>Tax</th>
                <th>Date théorique</th>
                <th>Date réel</th>
                <th>Statut</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers $repayment */ ?>
            <?php foreach ($this->lRemb as $repayment) : ?>
                <?php
                $client       = $this->client;
                $loanTransfer = $this->loan->getIdTransfer();

                if ($loanTransfer) {
                    $paymentDate  = new \DateTime($repayment['date_echeance_reel']);
                    if ($repayment->getDateEcheanceReel() !== '0000-00-00 00:00:00' && $repayment->getDateEcheanceReel() <= $loanTransfer->getAdded()) {
                        /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $formerOwner */
                        $client = $this->loanManager->getFormerOwner($this->loan);
                    }
                }
                ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $client->getNom()  ?></td>
                    <td><?= $client->getPrenom()?></td>
                    <td><?= $this->numberFormatter->format($repayment->getMontant() / 100) ?></td>
                    <td><?= $this->numberFormatter->format($repayment->getCapital() / 100) ?></td>
                    <td><?= $this->numberFormatter->format($repayment->getCapitalRembourse() / 100) ?></td>
                    <td><?= $this->numberFormatter->format($repayment->getInterets() / 100) ?></td>
                    <td><?= $this->numberFormatter->format($repayment->getInteretsRembourses() / 100) ?></td>
                    <td><?= $this->numberFormatter->format($this->operationRepository->getTaxAmountByRepaymentScheduleId($repayment)) ?></td>
                    <td><?= $repayment->getDateEcheance()->format('d/m/Y') ?></td>
                    <td><?= $repayment->getStatus() == Echeanciers::STATUS_REPAID ? $repayment->getDateEcheanceReel()->format('d/m/Y')  : '' ?></td>
                    <td><?php switch ($repayment->getStatus()) {
                            case Echeanciers::STATUS_PENDING:
                                echo 'A venir';
                                break;
                            case Echeanciers::STATUS_PARTIALLY_REPAID:
                                echo 'Partiellement remboursé';
                                break;
                            case Echeanciers::STATUS_REPAID:
                                echo 'Remboursé';
                                break;
                        } ?>
                    </td>
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            <?php if ($this->montant_ra > 0) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $this->client->getNom() ?></td>
                    <td><?= $this->client->getPrenom() ?></td>
                    <td><?= $this->numberFormatter->format($this->montant_ra) ?></td>
                    <td>0</td>
                    <td><?= $this->numberFormatter->format($this->montant_ra) ?></td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td></td>
                    <td><?= $this->date_ra->format('d/m/Y') ?></td>
                    <td>
                        <?php
                        switch ($repayment->getStatus()) {
                            case Echeanciers::STATUS_PENDING:
                                echo 'A venir';
                                break;
                            case Echeanciers::STATUS_PARTIALLY_REPAID:
                                echo 'Partiellement remboursé';
                                break;
                            case Echeanciers::STATUS_REPAID:
                                echo 'Remboursé';
                                break;
                        }
                        ?>
                    </td>
                </tr>
            <?php endif; ?>
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
