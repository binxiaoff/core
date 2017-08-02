<div class="row">
    <div class="col-md-12">
        <?php if ($this->projects->status >= \projects_status::COMITY_REVIEW || $this->projects_status_history->projectHasHadStatus($this->projects->id_project, \projects_status::COMITY_REVIEW)) : ?>
            <div id="content_etape7">
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
                <h1>Comité risque</h1>
                <div>
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th>Performance financière</th>
                            <td><?= $this->projects_notes->performance_fianciere_comite ?> / 10</td>
                            <th>Marché opéré</label></th>
                            <td><?= $this->projects_notes->marche_opere_comite ?> / 10</td>
                            <th>Dirigeance</th>
                            <td><?= $this->projects_notes->dirigeance_comite ?> / 10</td>
                            <th>Indicateur risque dynamique</th>
                            <td><?= $this->projects_notes->indicateur_risque_dynamique_comite ?> / 10</td>
                        </tr>
                        <tr>
                            <th>Structure</th>
                            <td><?= $this->projects_notes->structure_comite ?> / 10</td>
                            <th>Global</th>
                            <td colspan="5"><?= $this->projects_notes->global_comite ?> / 10</td>
                        </tr>
                        <tr>
                            <th>>Rentabilité</th>
                            <td>
                                <?= $this->projects_notes->rentabilite_comite ?> / 10
                            </td>
                            <th>Individuel</th>
                            <td colspan="5">
                                <?= $this->projects_notes->individuel_comite ?> / 10
                            </td>
                        </tr>
                        <tr>
                            <th>Trésorerie</th>
                            <td colspan="7">
                                <?= $this->projects_notes->tresorerie_comite ?> / 10
                            </td>
                        </tr>
                        <tr class="lanote">
                            <th colspan="8" style="text-align:center;">Note :
                                <span class="moyenneNote_comite"><?= $moyenne ?> / 10 (soit <?= $start ?>)</span></th>
                        </tr>
                        <tr>
                            <td colspan="8">
                                <div><?= $this->projects_notes->avis_comite ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php else : ?>
            Aucune donnée à afficher
        <?php endif; ?>
    </div>
</div>