<style type="text/css">
    .tab_title {
        display: block;
        background-color: #b20066;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        padding: 5px;
        margin-top: 15px;
    }
    .tab_title:active,
    .tab_title:focus,
    .tab_title:hover,
    .tab_title:visited {
        color: #fff;
        text-decoration: none;
    }
    .tab_content {border: 2px solid #b20066; padding: 10px;}
    .valid_etape {display: none; text-align: center; font-size: 16px; font-weight: bold; color: #009933;}
    .choose-client {font-weight: bold; text-align: center;}
    #search_result {display: none;}
</style>

<script type="text/javascript">
    $(function() {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));

        $("#date").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
        });

        $('#leclient1').click(function () {
            $('#recherche_client').show();
        });

        $('#leclient2').click(function () {
            $('#recherche_client').hide();
        });
    });

    function valid_create(id_project) {
        $.post(add_url + '/ajax/valid_create', {id_project: id_project}).done(function (data) {
            $(location).attr('href', add_url + '/dossiers/edit/' + id_project);
        });
    }
</script>
<div id="contenu">
    <h1>Création Dossier</h1>
    <?php if (isset($this->params[0]) && $this->params[0] == 'create') : ?>
        <form action="<?= $this->lurl ?>/dossiers/add" method="post" onsubmit="$('#link_search').trigger('click'); return false;">
            <div class="choose-client">
                Client existant ?
                <br/><br/>
                <label><input type="radio" name="leclient" id="leclient1" value="1" checked> Oui</label>
                <label><input type="radio" name="leclient" id="leclient2" value="2"> Non</label>
            </div>
            <br/>
            <br/>
            <div id="recherche_client">
                <table style="width:500px; margin:auto;text-align:center;margin-bottom:10px; border:2px solid;padding:10px;">
                    <tr>
                        <th style="padding:15px;"><label for="search">Prénom / nom / SIREN : </label></th>
                        <td style="padding:15px;">
                            <input id="search" class="input_moy" type="text" name="search">
                        </td>
                        <td style="padding:15px;">
                            <a id="link_search" class="btn_link thickbox" onclick="$(this).attr('href','<?= $this->lurl ?>/dossiers/changeClient/' + encodeURI($('#search').val()))" href="<?= $this->lurl ?>/dossiers/changeClient/">Rechercher</a>
                        </td>
                    </tr>
                </table>
                <table id="search_result" class="tablesorter" style="width:600px;margin:auto;">
                    <thead>
                        <tr>
                            <th>ID client</th>
                            <th>Prénom</th>
                            <th>Nom</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="id_clientHtml"></td>
                            <td id="prenomHtml"></td>
                            <td id="nomHtml"></td>
                        </tr>
                    </tbody>
                </table>
                <br/>
                <br/>
            </div>
        </form>
        <div class="btnDroite">
            <form action="<?= $this->lurl ?>/dossiers/add/create_etape1" method="post">
                <input type="hidden" id="id_client" name="id_client">
                <input type="hidden" name="send_create_etape1">
                <button type="submit" class="btn-primary">Valider</button>
            </form>
        </div>
    <?php elseif (false === empty($this->projects->id_project)) : ?>
        <?php $this->fireView('blocs/etape1'); ?>
    <?php endif; ?>
</div>
