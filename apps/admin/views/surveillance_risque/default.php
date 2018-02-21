<script>
    $('body').on('click', '.siren-line', function() {
        $(this).toggleClass('expand').nextUntil('.siren-line').slideToggle(0)
    })
</script>
<style>
    .block {
        margin-bottom: 30px;
    }
    .block-content {
        height: 800px;
        border-bottom: 1px solid #ddd;
        overflow-y: scroll;
        overflow-x: hidden;
    }
    .table-events {
        color: #4a4a4a;
    }
    .table-events tbody > tr > td {
        vertical-align: middle!important;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .e-date, .e-id, .e-action {
         width: 8%;
    }
    .e-remaining-capital {
        width: 12%;
    }
    .e-change {
        width: 32%;
    }
    .e-statut, .e-raison {
        width: 16%;
    }
    .details {
        list-style: none;
        padding: 0; margin: 0;
    }
    .details td {
        color: #777;
    }
    .details td.label {
        color: #bdbdbd;
        font-size: 10px;
        text-transform: uppercase;
    }
    .details td.label:after {
        content: ': '
    }
    .details td.label, .details td.value {
        padding-right: 10px;
    }
    .positive .details td.value:nth-child(2) {
        color: #00a453;
    }
    .negative .details td.value:nth-child(2) {
        color: #a30a09;
    }
    .siren-line td {
        font-weight: bold;
        color: #6d1f4f;
        background: #fdf4fa;
    }
    .siren-line .sign:after{
        content: '+';
        display: inline-block;
    }
    .siren-line.expand .sign:after{
        content: '-';
    }
</style>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Surveillance de l'eligibilité</h1>
        </div>
    </div>

    <?php if (false === empty($this->eligibilityEvents)) : ?>
        <div class="row">
            <div class="col-md-6">
                <h2>Projets avec des modifications de l'eligibilité </h2>
            </div>
        </div>
        <section id="company-eligibility-events">
            <div>
                <table class="table table-striped table-events">
                    <thead>
                    <tr>
                        <th class="e-date header">Date</th>
                        <th class="e-id header">ID Projet</th>
                        <th class="e-raison header">Raison Sociale</th>
                        <th class="e-remaining-capital">CRD</th>
                        <th class="e-statut header">Statut</th>
                        <th class="e-change header">Changement</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->eligibilityEvents as $siren => $events): ?>
                        <?php foreach ($events['events'] as $project) : ?>
                            <tr>
                                <td class="e-date"><?= $this->dateFormatter->format(strtotime($project['added'])) ?></td>
                                <td class="e-id">
                                    <a href="/dossiers/edit/<?= $project['id_project'] ?>"><?= $project['id_project'] ?></a>
                                </td>
                                <td class="e-raison">
                                    <a href="/emprunteurs/edit/<?= $project['id_client_owner'] ?>"><?= $project['name'] ?></a>
                                </td>
                                <td class="e-remaining-capital">
                                    <?= isset($project['remainingDueCapital']) ? $this->currencyFormatter->format($project['remainingDueCapital']) : '' ?>
                                </td>
                                <td class="e-statut">
                                    <?= $project['label'] ?>
                                </td>
                                <td class="value"><?= $project['value']?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <h2>Projets avec des modifications des notations (derniers 3 mois)</h2>
        </div>
    </div>
    <section id="company-rating-events">
        <article class="block">
            <div class="block-content">
                <table class="table table-striped table-events">
                    <thead>
                    <tr>
                        <th class="e-date header">Date</th>
                        <th class="e-id header">ID Projet</th>
                        <th class="e-raison header">Raison Sociale</th>
                        <th class="e-remaining-capital">CRD</th>
                        <th class="e-statut header">Statut</th>
                        <th class="e-change header">Changement</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->companyRatingEvents as $siren => $events): ?>
                        <tr class="siren-line<?php if ($events['activeSiren']) : ?> expand<?php endif; ?>">
                            <td colspan="6"><span class="sign"></span> <?= $siren . ' ' . $events['label'] ?> (<?= $events['count'] ?>)</td>
                        </tr>
                            <?php foreach ($events['events'] as $project) : ?>
                                <tr <?php if (false === $events['activeSiren']) : ?> style="display: none;"<?php endif; ?>>
                                    <td class="e-date"><?= $this->dateFormatter->format(strtotime($project['added'])) ?></td>
                                    <td class="e-id">
                                        <a href="/dossiers/edit/<?= $project['id_project'] ?>"><?= $project['id_project'] ?></a>
                                    </td>
                                    <td class="e-raison">
                                        <a href="/emprunteurs/edit/<?= $project['id_client_owner'] ?>"><?= $project['name'] ?></a>
                                    </td>
                                    <td class="e-remaining-capital">
                                        <?= isset($project['remainingDueCapital']) ? $this->currencyFormatter->format($project['remainingDueCapital']) : '' ?>
                                    </td>
                                    <td class="e-statut">
                                        <?= $project['label'] ?>
                                    </td>
                                    <td class="e-change negative" data-change="0">
                                        <table class="details">
                                            <tr>
                                                <td class="label"><?= $this->translator->trans('company-rating_' . $project['company_rating']) ?></td>
                                                <td class="value"><?= false === empty($project['previous_value']) ? $project['value'] < $project['previous_value'] ? '↓' : '↑'  : ''?>
                                                    <?= $project['value'] ?></td>
                                                <td class="label">Précédent</td>
                                                <td class="value"> <?= $project['previous_value'] ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</div>
