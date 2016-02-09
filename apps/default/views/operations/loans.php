<table class="table detail-ope finances">
    <thead>
        <tr>
            <th class="col-status<?= 'status' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' asc' : ' desc') : '' ?>" id="loan-order-status">
                <div class="th-wrap">
                    <i class="icon-arrows"></i>
                </div>
            </th>
            <th align="left" class="col1<?= 'title' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' asc' : ' desc') : '' ?>" id="loan-order-title">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-projet'] ?>" class="icon-person tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-projet'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col2<?= 'note' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' asc' : ' desc') : '' ?>" id="loan-order-note">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-note'] ?>" class="icon-gauge tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-note'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col3<?= 'amount' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' asc' : ' desc') : '' ?>" id="loan-order-amount">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-montant'] ?>" class="icon-euro tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-montant'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col4<?= 'interest' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' asc' : ' desc') : '' ?>" id="loan-order-interest">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-interet'] ?>" class="icon-graph tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-interet'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th class="col5">
                <div class="th-wrap th-wrap-v2">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-calendrier'] ?>" class="icon-calendar tooltip-anchor"></i>
                    <div class="calendar-title" style="margin-top: 8.5px;">
                        <span style="width:75px;" id="loan-order-start"<?= 'start' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' class="asc"' : ' class="desc"') : '' ?>><?= $this->lng['preteur-operations-detail']['titre-debut'] ?>&nbsp;<i class="icon-arrows"></i></span>
                        <span style="width:79px;" id="loan-order-next"<?= 'next' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' class="asc"' : ' class="desc"') : '' ?>><?= $this->lng['preteur-operations-detail']['titre-prochaine'] ?>&nbsp;<i class="icon-arrows"></i></span>
                        <span style="width:75px;" id="loan-order-end"<?= 'end' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' class="asc"' : ' class="desc"') : '' ?>><?= $this->lng['preteur-operations-detail']['titre-fin'] ?>&nbsp;<i class="icon-arrows"></i></span>
                    </div>
                </div>
            </th>
            <th class="col6<?= 'repayment' === $this->sOrderField ? ('ASC' === $this->sOrderDirection ? ' asc' : ' desc') : '' ?>" id="loan-order-repayment">
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-titre-mensualite'] ?>" class="icon-bank tooltip-anchor"></i>
                    <div class="title-ope"><?= $this->lng['preteur-operations-detail']['titre-mensualite'] ?>&nbsp;<i class="icon-arrows"></i></div>
                </div>
            </th>
            <th>
                <div class="th-wrap">
                    <i title="<?= $this->lng['preteur-operations-detail']['info-contrat'] ?>" class="icon-bdc tooltip-anchor"></i>
                    <div class="title-ope">Documents</div>
                </div>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($this->lSumLoans as $iLoanIndex => $aProjectLoans): ?>
        <tr class="<?= ($iLoanIndex % 2 ? '' : 'odd') ?>">
            <td class="status">
                <div
                    <?php if (false === empty($this->lng['preteur-operations-detail']['info-status-' . $aProjectLoans['project_status']])): ?>title="<?= $this->lng['preteur-operations-detail']['info-status-' . $aProjectLoans['project_status']] ?>"<?php endif; ?>
                    class="status-color<?= empty($aProjectLoans['status-color']) ? '' : ' status-' . $aProjectLoans['status-color'] ?><?php if (false === empty($this->lng['preteur-operations-detail']['info-status-' . $aProjectLoans['project_status']])): ?> tooltip-status<?php endif; ?>">&nbsp;</div>
                <span class="title"><br/><?php if (false === empty($this->lng['preteur-operations-detail']['info-status-' . $aProjectLoans['project_status']])): ?><?= $this->lng['preteur-operations-detail']['info-status-' . $aProjectLoans['project_status']] ?><?php endif; ?></span>
            </td>
            <td class="description">
                <h5><a href="<?= $this->lurl ?>/projects/detail/<?= $aProjectLoans['slug'] ?>" target="_blank"><?= $aProjectLoans['name'] ?></a></h5>
            </td>
            <td>
                <div class="cadreEtoiles">
                    <div class="etoile <?= $this->lNotes[$aProjectLoans['risk']] ?>">&nbsp;</div>
                </div>
            </td>
            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProjectLoans['amount'], 0) ?> €</td>
            <td style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aProjectLoans['rate'], 1) ?> %</td>
            <?php if (in_array($aProjectLoans['project_status'], array(\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE))) { ?>
                <td colspan="2">
                    <span class="calandar-ech" style="width: 79px;"><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></span>
                    <span class="calandar-ech" style="width: 237px;"><p>Remboursé intégralement <br /> le <?= $this->dates->formatDate($aProjectLoans['status_change'], 'd/m/Y')?></p></span>
                </td>
            <?php } else { ?>
                <td>
                    <span class="calandar-ech"><?= $this->dates->formatDate($aProjectLoans['debut'], 'd/m/Y') ?></span>
                    <span class="calandar-ech"><?= $this->dates->formatDate($aProjectLoans['next_echeance'], 'd/m/Y') ?></span>
                    <span class="calandar-ech"><?= $this->dates->formatDate($aProjectLoans['fin'], 'd/m/Y') ?></span>
                </td>
                <td><?= $this->ficelle->formatNumber($aProjectLoans['mensuel']) ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
            <?php } ?>
            <td class="documents">
                <?php if ($aProjectLoans['nb_loan'] == 1): ?>
                    <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                        <a href="<?= $this->lurl ?>/pdf/contrat/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>" class="btn btn-info btn-small multi"><?= $this->lng['preteur-operations-detail']['contract-type-' . $aProjectLoans['id_type_contract']] ?></a>
                    <?php endif; ?>
                    <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)): ?>
                        <br/><br/>
                        <a href="<?= $this->lurl ?>/pdf/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aProjectLoans['id_loan_if_one_loan'] ?>" class="btn btn-grise grise1 btn-small multi"><?= $this->lng['preteur-operations-detail']['declaration-de-creances'] ?></a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn btn-info btn-small btn-detailLoans override_plus">+</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($aProjectLoans['nb_loan'] > 1): ?>
            <tr class="<?= ($iLoanIndex % 2 ? '' : 'odd') ?>">
                <td colspan="8" style="padding:0;">
                    <div class="detailLoans" style="display:none;">
                        <table class="table" style="margin-bottom:0;">
                        <?php
                            foreach ($this->loans->select('id_lender = ' . $this->lenders_accounts->id_lender_account . ' AND id_project = ' . $aProjectLoans['id_project']) as $aLoan):
                                $SumAremb    = $this->echeanciers->select('id_loan = ' . $aLoan['id_loan'] . ' AND status = 0', 'ordre ASC', 0, 1);
                                $fLoanAmount = round($SumAremb[0]['montant'] / 100, 2) - round($SumAremb[0]['prelevements_obligatoires'] + $SumAremb[0]['retenues_source'] + $SumAremb[0]['csg'] + $SumAremb[0]['prelevements_sociaux'] + $SumAremb[0]['contributions_additionnelles'] + $SumAremb[0]['prelevements_solidarite'] + $SumAremb[0]['crds'], 2);
                                ?>
                                <tr>
                                    <td class="col-status"></td>
                                    <td class="col1"></td>
                                    <td class="col2"></td>
                                    <td class="col3" style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['amount'] / 100, 0) ?> €</td>
                                    <td class="col4" style="white-space: nowrap;"><?= $this->ficelle->formatNumber($aLoan['rate'], 1) ?> %</td>
                                    <td class="col5"></td>
                                    <td class="col6" style="white-space: nowrap;"><?= $this->ficelle->formatNumber($fLoanAmount) ?> <?= $this->lng['preteur-operations-detail']['euros-par-mois'] ?></td>
                                    <td class="documents">
                                        <?php if ($aProjectLoans['project_status'] >= \projects_status::REMBOURSEMENT): ?>
                                            <a href="<?= $this->lurl ?>/pdf/contrat/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>" class="btn btn-info btn-small multi"><?= $this->lng['preteur-operations-detail']['contract-type-' . $aLoan['id_type_contract']] ?></a>
                                        <?php endif; ?>
                                        <?php if (in_array($aProjectLoans['id_project'], $this->aProjectsInDebt)): ?>
                                            <br/><br/>
                                            <a href="<?= $this->lurl ?>/pdf/declaration_de_creances/<?= $this->clients->hash ?>/<?= $aLoan['id_loan'] ?>" class="btn btn-grise grise1 btn-small multi"><?= $this->lng['preteur-operations-detail']['declaration-de-creances'] ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<script type="text/javascript">
    $(function() {
        $(window).on('load resize', function() {
            if ($(window).width() < 768) {
                $('.detail-ope .th-wrap').show();
            } else {
                $('.detail-ope .th-wrap').hide();
                $('.detail-ope th .th-wrap').show();
            }
        });

        /**
         Add icons with tooltips to all table rows
         They will be visible below tablet landscape breakpoint
         and will replace the table head icons
         */
        $('.hp-counter + .main .table tr, #table_tri tr, .vos_prets table.detail-ope tr').each(function() {
            $(this).find('td').each(function(indx) {
                var $icon = $($(this).closest('.table').find('th').eq(indx).html());
                if ($(window).width() >= 768) {
                    $icon.hide();
                }
                $icon.prependTo($(this))
            });
        });

        $('#loan-order-status, #loan-order-title, #loan-order-note, #loan-order-amount, #loan-order-interest, #loan-order-start, #loan-order-next, #loan-order-end, #loan-order-repayment').click(function() {
            console.log($(this));
            console.log($(this).hasClass('asc'));
            console.log($(this).hasClass('desc'));
            loadLoans({
                order: $(this).hasClass('asc') ? 'desc' : 'asc',
                type: $(this).attr('id').substring(11)
            });
        });

        $('.tooltip-status').tooltip({placement: 'right'});

        $('.btn-detailLoans').click(function() {
            $(this).parents('tr').next('tr').find('.detailLoans').slideToggle();

            if ($(this).hasClass('on_display')) {
                $(this).html('+').addClass('off_display').removeClass('on_display');
            } else {
                $(this).html('-').addClass('on_display').removeClass('off_display');
            }
        });
    });
</script>
