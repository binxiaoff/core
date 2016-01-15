<style type="text/css">
    .detail-ope th {
        vertical-align: bottom;
    }

    .detail-ope .th-wrap {
        font-size: 12px;
        text-align: center;
        width: 100px;
    }

    .detail-ope .col-status .th-wrap {
        text-align: right;
        width: 22px;
    }

    .detail-ope tbody > td > .th-wrap {
        display: none;
    }

    .detail-ope .th-wrap .title-ope {
        margin-top: 3px;
        display: block;
    }

    .detail-ope .th-wrap-v2 {
        width: 246px;
    }

    .detail-ope .calendar-title {
        border-top: 1px solid white;
        margin-top: 3px;
    }

    .detail-ope .calendar-title span {
        width: 80px;
        display: inline-block;
        padding-top: 3px;
    }

    .detail-ope .calandar-ech {
        width: 79px;
        display: inline-block;
        padding-top: 3px;
    }

    .detail-ope.table td {
        padding: 15px 0;
        font-size: 14px;
        vertical-align: middle;
    }

    .detail-ope.table td:first-child {
        padding: 0;
    }

    .detail-ope.table td .status-color {
        background-color: #00A000;
        margin: 6px;
        height: 55px;
        width: 8px;
    }

    .detail-ope.table td .status-color.status-warning {
        background-color: #ffd700;
    }

    .detail-ope.table td .status-color.status-problem {
        background-color: #f00;
    }

    .detail-ope.table td .status-color.status-default {
        background-color: #000;
    }

    .detail-ope.table td.description {
        padding: 15px 0 15px 3px;
        text-align: left;
    }

    .detail-ope.table th:first-child {
        padding-left: 0;
    }

    .detail-ope .cadreEtoiles {
        left: 18px;
        top:2px;
    }

    .detail-ope .detailLoans {
        display: none;
        width: 100%;
        border-top: 1px solid #b20066;
    }

    .detail-ope .detailLoans td {
        padding-top: 8px;
        padding-bottom: 3px;
    }

    .detail-ope .col1 {
        width: 214px;
    }

    .detail-ope .col2 {
        width: 100px;
    }

    .detail-ope .col3 {
        width: 100px;
    }

    .detail-ope .col4 {
        width: 100px;
    }

    .detail-ope .col5 {
        width: 246px;
    }

    .detail-ope .col6 {
        width: 100px;
    }

    .detail-ope tr:nth-child(even) td {
        background-color: white;
    }

    .detail-ope tr:hover td {
        background-color: white;
    }

    .detail-ope tr.odd td {
        background: #f4f4f4;
    }

    .detail-ope td.documents .btn-small {
        font-size: 12px;
        vertical-align: middle;
    }

    .detail-ope .icon-arrows {
        cursor: pointer;
    }

    .detail-ope .tooltip {
        max-width: 200px;
    }

    .c2-sb-list-wrap {
        max-height: 228px;
    }

    .load {
        background: none repeat scroll 0 0 white;
        border: 1px solid #b20066;
        border-radius: 5px;
        display: none;
        height: 50px;
        left: 48%;
        margin: 65px auto auto;
        padding: 5px;
        position: absolute;
        text-align: center;
        width: 100px;
    }

    .override_plus {
        line-height: 18px !important;
        height: 15px !important;
        padding: 0 4px !important;
        top: 0 !important;
        width: 10px;
    }

    .title-ope {
        margin-top: 12.5px !important;
    }

    .vos_prets .export {float: right;}
    .vos_prets .print{margin-top: 8px;width:50px;}
    .vos_prets .xls{margin-top: 6px;width:50px;}

    .summary ul {color: #727272; padding-left: 17px;}
</style>
<h2><?= $this->lng['preteur-operations']['titre-3'] ?></h2>
<div class="export">
    <div class="vos_operations_ligne" style="text-align:center;">
        Imprimer<br/>
        <a href="<?= $this->lurl ?>/pdf/loans" target="_blank"><img class="print" src="<?= $this->surl ?>/styles/default/preteurs/images/icon-print.png"/></a>
    </div>
    <div style="width:30px;display:inline-block;"></div>
    <div class="vos_operations_ligne" style="text-align:center;">
        Exporter<br/>
        <a href="<?= $this->lurl ?>/operations/loans_csv" target="_blank"><img class="xls" src="<?= $this->surl ?>/images/default/xls_hd.png"/></a>
    </div>
</div>
<div class="summary">
    <?php if (count($this->lSumLoans) > 1): ?>
        <?= str_replace('[#LOANS_COUNT#]', count($this->lSumLoans), $this->lng['preteur-operations-detail']['loans-title-plural']) ?>
    <?php else: ?>
        <?= str_replace('[#LOANS_COUNT#]', count($this->lSumLoans), $this->lng['preteur-operations-detail']['loans-title-singular']) ?>
    <?php endif; ?>
    <ul>
        <li><?= $this->lng['preteur-operations-detail']['status-no-problem'] ?>: <?= $this->aLoansStatuses['no-problem'] ?></li>
        <li><?= $this->lng['preteur-operations-detail']['status-late-repayment'] ?>: <?= $this->aLoansStatuses['late-repayment'] ?></li>
        <li><?= $this->lng['preteur-operations-detail']['status-recovery'] ?>: <?= $this->aLoansStatuses['recovery'] ?></li>
        <li><?= $this->lng['preteur-operations-detail']['status-collective-proceeding'] ?>: <?= $this->aLoansStatuses['collective-proceeding'] ?></li>
        <li><?= $this->lng['preteur-operations-detail']['status-default'] ?>: <?= $this->aLoansStatuses['default'] ?></li>
        <li><?= $this->lng['preteur-operations-detail']['status-refund-finished'] ?>: <?= $this->aLoansStatuses['refund-finished'] ?></li>
    </ul>
</div>
<?php if (false === empty($this->lSumLoans)): ?>
<p><?= $this->lng['profile']['contenu-partie-4'] ?></p>
<div class="table-filter clearfix">
    <?php if (count($this->aLoansYears) > 1): ?>
        <div class="right">
            <select id="filter-year" class="custom-select field-small">
                <option value=""><?= $this->lng['preteur-operations-detail']['filter-placeholder-date'] ?></option>
                <?php foreach ($this->aLoansYears as $iYear => $iLoansByYear): ?>
                    <option value="<?= $iYear ?>"><?= $this->lng['profile']['annee'] ?> <?= $iYear ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="left">
        <select id="filter-status" class="custom-select field-small">
            <option value=""><?= $this->lng['preteur-operations-detail']['filter-placeholder-status'] ?></option>
            <?php foreach ($this->aFilterStatuses as $aStatus): ?>
                <?php if (isset($this->lng['preteur-operations-detail']['filter-status-' . $aStatus['status']])): ?>
                    <option value="<?= $aStatus['status'] ?>"><?= $this->lng['preteur-operations-detail']['filter-status-' . $aStatus['status']] ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="load">
    <img src="<?= $this->surl ?>/styles/default/images/loading.gif"/>
    Chargement...
</div>

<div class="loadDetailOp">
    <?php $this->fireView('loans'); ?>
</div>
<?php endif; ?>

<script type="text/javascript">
    $(function() {
        $('#filter-status, #filter-year').change(function() {
            loadLoans();
        });
    });

    function loadLoans(data) {
        $('.load').fadeIn();

        data = $.extend({}, data, {status: $('#filter-status').val(), year: $('#filter-year').val()});

        $.post(add_url + '/operations/loans', data).done(function(data) {
            if (data != 'nok') {
                $('.loadDetailOp').html(data);
                $('.load').fadeOut();
            }
        });
    }
</script>
