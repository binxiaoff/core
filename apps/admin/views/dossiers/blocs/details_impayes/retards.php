<div class="block-content block-content-full">
    <h3 class="h4 push-20">Retards</h3>
    <table class="table table-bordered table-hover table-header-bg" style="margin-bottom: 0;">
        <thead>
        <tr>
            <th style="width: 20%">
                Date
            </th>
            <th style="width: 20%">
                Retards
            </th>
            <th style="width: 20%">
                Montant
            </th>
            <th style="width: 20%">
                Confié au recouvreur
            </th>
            <th style="width: 20%">
                Restant à recouvrer
            </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                01/11/2016
            </td>
            <td>
                Capital Projet
            </td>
            <td>
                1 000 €
            </td>
            <td>
                500 €
            </td>
            <td>
                500 €
            </td>
        </tr>
        <tr>
            <td>
                01/01/2017
            </td>
            <td>
                Écheance Janvier
            </td>
            <td>
                1 000 €
            </td>
            <td>
                N/A
            </td>
            <td>
                1 000 €
            </td>
        </tr>
        </tbody>
    </table>
    <table class="table table-bordered font-w600" style="border-top: 0;">
        <thead>
        <tr>
            <td colspan="2" style="width: 40%;">
                Total:
            </td>
            <td style="width: 20%;">
                2000 €
            </td>
            <td style="width: 20%;">
                500 €
            </td>
            <td style="width: 20%;">
                1500 €
            </td>
        </tr>
        </thead>
    </table>
    <div class="text-right">
        <button type="button" class="btn btn-default push-10-r" data-toggle="modal" data-target="#modal-cancel-term">Déchoir le terme</button>

        <!-- CONDITIONAL If project is in status "Remboursement" : -->
        <button type="button" class="btn btn-primary push-10-r" data-toggle="modal" data-target="#modal-status-problem">Passer en statut problème</button>
        <!-- endif -->

        <!-- CONDITIONAL If project is already in status "Problem" : -->
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-debt-collection">Missionner un recouvreur (1 500 €)</button>
        <!-- endif -->
    </div>

    <!-- Start MODALS -->
    <div class="modal fade in" id="modal-cancel-term">
        <form class="modal-dialog" action="/recouvrements/details/429892" method="post">
            <div class="modal-content">
                <div class="block block-themed remove-margin-b">
                    <div class="block-header bg-primary">
                        <h5 class="block-title">Déchoir le terme</h5>
                    </div>
                    <div class="block-content">
                        <p>Cette action est irrévsersible. Voulez-vous confirmer la déchéance du terme ?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="cancel-term" value="1">
                    <button class="btn btn-sm btn-default" type="button" data-dismiss="modal">Annuler</button>
                    <button class="btn btn-sm btn-primary" type="submit">Valider</button>
                </div>
            </div>
        </form>
    </div>

    <!-- CONDITIONAL If project is in status "Remboursement" : -->
    <div class="modal fade in" id="modal-status-problem">
        <form class="modal-dialog" action="/recouvrements/details/429892" method="post">
            <div class="modal-content">
                <div class="block block-themed remove-margin-b">
                    <div class="block-header bg-primary">
                        <h5 class="block-title">Passer en statut problème</h5>
                    </div>
                    <div class="block-content">
                        <p>Êtes-vous sûr de vouloir passer le projet en statut "problème" ?</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="project-status-change" value="problem">
                    <button class="btn btn-sm btn-default" type="button" data-dismiss="modal">Annuler</button>
                    <button class="btn btn-sm btn-primary" type="submit">Valider</button>
                </div>
            </div>
        </form>
    </div>
    <!-- endif -->

    <!-- CONDITIONAL If project is already in status "Problem" : -->
    <div class="modal fade in" id="modal-debt-collection">
        <form class="modal-dialog js-validation" action="/recouvrements/details/429892" method="post">
            <div class="modal-content">
                <div class="block block-themed remove-margin-b">
                    <div class="block-header bg-primary">
                        <h5 class="block-title">Ajout d'une mission de recouvrement</h5>
                    </div>
                    <div class="block-content">
                        <div class="form-group">
                            <ul>
                                <li>Capital Projet - 500 €</li>
                                <li>Echeance Janvier - 1000 €</li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label>Recouvreur</label>
                            <select class="form-control required" name="debt-collection-agency">
                                <option value="1">Progeris</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Type de mission</label>
                            <select class="form-control required" name="debt-collection-type">
                                <option value="0">Selectionner</option>
                                <option value="1">Amiable</option>
                                <option value="2">Contentieux</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Taux (Pourcentage)</label>
                            <input class="form-control required" name="debt-collection-rate" type="text" placeholder="13">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="recover-amount" value="1500">
                    <button class="btn btn-sm btn-default" type="button" data-dismiss="modal">Annuler</button>
                    <button class="btn btn-sm btn-primary" type="submit">Valider</button>
                </div>
            </div>
        </form>
    </div>
    <!-- endif -->
    <!-- End MODALS -->
</div>