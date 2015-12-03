<div class="tab_title" id="title_etape4_1">Etape 4.1 - Notation externe</div>
<div class="tab_content" id="etape4_1">
    <form method="post" name="dossier_etape4_1" id="dossier_etape4_1" enctype="multipart/form-data" action="<?= $this->lurl ?>/dossiers/edit/<?= $this->params[0] ?>" target="_parent">
        <div id="contenu_etape4_1">
            <table class="form" style="width: 100%;">
                <tr class="section-title">
                    <th colspan="4">Déclaration client</th>
                </tr>
                <tr>
                    <th><label for="ca_declara_client">Chiffe d'affaires declaré par client</label></th>
                    <td><input type="text" name="ca_declara_client" id="ca_declara_client" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->ca_declara_client, 0) ?>"/></td>
                    <th><label for="resultat_exploitation_declara_client">Résultat d'exploitation declaré par client</label></th>
                    <td><input type="text" name="resultat_exploitation_declara_client" id="resultat_exploitation_declara_client" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->resultat_exploitation_declara_client, 0) ?>"/></td>
                </tr>
                <tr>
                    <th><label for="fonds_propres_declara_client">Fonds propres declarés par client</label></th>
                    <td colspan="3"><input type="text" name="fonds_propres_declara_client" id="fonds_propres_declara_client" class="input_moy" value="<?= $this->ficelle->formatNumber($this->projects->fonds_propres_declara_client, 0) ?>"/></td>
                </tr>
            </table>
        </div>
        <div id="valid_etape4_1" class="valid_etape"><br/>Données sauvegardées</div>
        <div class="btnDroite">
            <input type="button" class="btn_link" value="Sauvegarder" onclick="valid_etape4_1(<?= $this->projects->id_project ?>)">
        </div>
    </form>
</div>
