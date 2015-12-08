<script type="text/javascript">
    $(function() {
        $("#datepik").datepicker({showOn: 'both', buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif', buttonImageOnly: true, changeMonth: true, changeYear: true, yearRange: '2015:2025'});
        $("#datepik").change(function() {
            $.post(add_url + "/ajax/recouvrement", {date: $(this).val(), id_reception: <?= $this->receptions->id_reception ?>}).done(function (data) {
                $('.recouvrement').html(data);
            });
        });

        <?php if (isset($_SESSION['freeow'])):  ?>
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        <?php endif; ?>
    });
</script>
<style type="text/css">
    .first{width: 200px;}
    td{text-align: center;}
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/transferts" >Dépot de fonds</a> - </li>
        <li>Recouvrement projet : <?= $this->projects->id_project ?></li>
    </ul>
    <h1>Recouvrement projet : <?= $this->projects->id_project ?></h1>
    <div class="btnDroite submitdossier">
        <a href="<?= $this->lurl ?>/transferts/recouvrement_preteurs/<?= $this->receptions->id_reception ?>" class="btn_link" >recouvrement par preteurs</a>
    </div>
    <br/><br/>
    <div style="margin: auto;width: 480px;text-align: center;">
        <form action="" method="post" enctype="multipart/form-data">
            <table class="tablesorter">
                <tr class="odd">
                    <td class="first" style="vertical-align: middle;"><label for="datepik">Date d'entrée en recouvrement</label></td>
                    <td><div style="display: inline-block;vertical-align: middle;"><input type="text" name="der" id="datepik" class="input_dp" value="<?= $this->lastDateRecouvrement ?>"></div></td>
                </tr>
            </table>
            <br/><br/>
            <div class="recouvrement"><?= $this->fireView('../ajax/recouvrement') ?></div>
            <br/><br/>
            <?php if ($this->bank_unilend->counter('id_project = ' . $this->projects->id_project . ' AND type = 1 AND status = 0') > 0): ?>
                <input type="hidden" name="send_form_remb_preteurs">
                <button class="btn" type="submit">Rembourser les prêteurs</button>
            <?php endif; ?>
        </form>
    </div>
</div>
