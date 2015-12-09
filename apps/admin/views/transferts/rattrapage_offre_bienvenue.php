<script type="text/javascript">
    $(document).ready(function () {
        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });
        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y')-10)?>:<?=(date('Y')+10)?>'
        });

    });
    <?
    if(isset($_SESSION['freeow']))
    {
    ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?
    }
    ?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/transferts" >Dépot de fonds</a> - </li>
        <li>Rattrapage offre de bienvenue</li>
    </ul>
    <h1>Rattrapage offre de bienvenue</h1>


    <form method="post" name="recupCSV">
        <input type="hidden" name="recup"/>
        <input type="hidden" name="spy_date1" value="<?= $_POST['date1'] ?>"/>
        <input type="hidden" name="spy_date2" value="<?= $_POST['date2'] ?>"/>

    </form>

    <div style="margin-bottom:20px; float:right;"><a onClick="document.forms['recupCSV'].submit();"
                                                     class="btn colorAdd">Recuperation du CSV</a></div>


    <div
        style="width:500px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
        <form method="post" name="date_select">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <td style="padding-top:23px;"><label>Date debut</label><br/><input type="text" name="date1"
                                                                                           id="datepik_1"
                                                                                           class="input_dp"
                                                                                           value="<?= $_POST['date1'] ?>"/>
                        </td>
                        <td style="padding-top:23px;"><label>Date fin</label><br/><input type="text" name="date2"
                                                                                         id="datepik_2" class="input_dp"
                                                                                         value="<?= $_POST['date2'] ?>"/>
                        </td>

                        <td style="padding-top:23px;">
                            <input type="hidden" name="spy_search" id="spy_search"/>
                            <input type="submit" value="Valider" title="Valider" name="send_dossier" id="send_dossier"
                                   class="btn"/>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="8" style="">

                        </th>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>

    <table class="tablesorter">
        <thead>
        <tr>
            <th>Id</th>
            <th>Nom</th>
            <th>Pr&eacute;nom</th>
            <th>Date de création</th>
            <th>Date de validation</th>
            <th></th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

</div>
<?php unset($_SESSION['freeow']); ?>