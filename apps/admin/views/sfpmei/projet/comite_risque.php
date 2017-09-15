<div class="row">
    <div class="col-md-12">
        <?php if ($this->projects->status >= \projects_status::COMITY_REVIEW || $this->projects_status_history->projectHasHadStatus($this->projects->id_project, \projects_status::COMITY_REVIEW)) : ?>
            <?php
            $moyenne = round($this->projects_notes->performance_fianciere_comite * 0.2 + $this->projects_notes->marche_opere_comite * 0.2 + $this->projects_notes->dirigeance_comite * 0.2 + $this->projects_notes->indicateur_risque_dynamique_comite * 0.4, 1);
            $start   = '';
            if ($moyenne >= 0) {
                $start = '2 étoiles';
            }
            if ($moyenne >= 2) {
                $start = '2,5 étoiles';
            }
            if ($moyenne >= 4) {
                $start = '3 étoiles';
            }
            if ($moyenne >= 5.5) {
                $start = '3,5 étoiles';
            }
            if ($moyenne >= 6.5) {
                $start = '4 étoiles';
            }
            if ($moyenne >= 7.5) {
                $start = '4,5 étoiles';
            }
            if ($moyenne >= 8.5) {
                $start = '5 étoiles';
            }
            ?>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Performance financière</th>
                    <th><?= $this->ficelle->formatNumber($this->projects_notes->performance_fianciere_comite, 1) ?> / 10</th>
                    <th>Marché opéré</label></th>
                    <th><?= $this->ficelle->formatNumber($this->projects_notes->marche_opere_comite, 1) ?> / 10</th>
                    <th>Dirigeance</th>
                    <th><?= $this->ficelle->formatNumber($this->projects_notes->dirigeance_comite, 1) ?> / 10</th>
                    <th>Indicateur risque dynamique</th>
                    <th><?= $this->ficelle->formatNumber($this->projects_notes->indicateur_risque_dynamique_comite, 1) ?> / 10</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th>Structure</th>
                    <td><?= $this->ficelle->formatNumber($this->projects_notes->structure_comite, 1) ?> / 10</td>
                    <th>Global</th>
                    <td><?= $this->ficelle->formatNumber($this->projects_notes->global_comite, 1) ?> / 10</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <th>Rentabilité</th>
                    <td><?= $this->ficelle->formatNumber($this->projects_notes->rentabilite_comite, 1) ?> / 10</td>
                    <th>Individuel</th>
                    <td><?= $this->ficelle->formatNumber($this->projects_notes->individuel_comite, 1) ?> / 10</td>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <th>Trésorerie</th>
                    <td><?= $this->ficelle->formatNumber($this->projects_notes->tresorerie_comite, 1) ?> / 10</td>
                    <td colspan="6"></td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <th colspan="8" class="text-center">
                        Note :
                        <span class="moyenneNote_comite"><?= $this->ficelle->formatNumber($moyenne, 1) ?> / 10 (soit <?= $start ?>)</span>
                    </th>
                </tr>
                </tfoot>
            </table>
            <h3>Note d'analyse</h3>
            <div><?= $this->projects_notes->avis_comite ?></div>
        <?php else : ?>
            Aucune donnée à afficher
        <?php endif; ?>
    </div>
</div>