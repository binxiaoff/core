<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?= $this->nb_lignes ?>});
        <?php endif; ?>

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });
        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });
    });
</script>
<style>
    .datepicker_table {
        width: 80%;
        margin: 0 auto 20px;
        background-color: white;
        border: 1px solid #A1A5A7;
        border-radius: 10px 10px 10px 10px;
        padding: 5px;
        padding-bottom: 20px;
    }

    .csv {
        margin-bottom: 20px;
        float: right;
    }

    .search_fields td {
        padding-top: 23px;
        padding-left: 10px;
        /*width: 25%;*/
    }
</style>
<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Sources Emprunteurs</h1>
        </div>
    </div>
    <div class="datepicker_table">
        <form method="post" name="date_select">
            <fieldset>
                <table class="search_fields">
                    <tr>
                        <td width=25%>
                            <label for="datepik_1">Date debut</label><br/>
                            <input type="text" name="dateStart" id="datepik_1" class="input_dp"
                                   value="<?= (false === empty($_POST['dateStart']) ? $_POST['dateStart'] : '' ) ?>"/>
                        </td>
                        <td width=25%>
                            <label for="datepik_2">Date fin</label><br/>
                            <input type="text" name="dateEnd" id="datepik_2" class="input_dp"
                                   value="<?= (false === empty($_POST['dateEnd']) ? $_POST['dateEnd'] : '' ) ?>"/>
                        </td>
                        <td width=30%>
                            <input type="radio" name="queryOptions" value="allLines" id="allLines"
                                   <?= (isset($_POST['queryOptions'])) ? ('allLines' == $_POST['queryOptions']) ? 'checked="checked"' : '' : 'checked="checked"' ?>/>
                            <label for="allLines">Choisir toutes les lignes</label><br/>
                            <input type="radio" name="queryOptions" value="groupBySiren" id="groupBySiren"
                                   <?= (isset($_POST['queryOptions']) && 'groupBySiren' == $_POST['queryOptions']) ? 'checked="checked"' : '' ?>/>
                            <label for="groupBySiren">Siren dédoublonnée</label>
                        </td>
                        <td width=20%>
                            <br>
                            <button type="submit" name="send_query" id="send_query" class="btn-primary">Recuperation du CSV</button>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
    </div>
    <div class="row">
        <div class="col-md-12">
            <p><?= $this->message ?></p>
        </div>
    </div>
</div>
