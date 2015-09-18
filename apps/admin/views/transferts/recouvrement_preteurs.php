<script type="text/javascript">

    $(document).ready(function () {

        $("#datepik").datepicker({showOn: 'both', buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif', buttonImageOnly: true, changeMonth: true, changeYear: true, yearRange: '2005:2025'});

    });
<?
if (isset($_SESSION['freeow'])) {
    ?>
        $(document).ready(function () {
            var title, message, opts, container;
            title = "<?= $_SESSION['freeow']['title'] ?>";
            message = "<?= $_SESSION['freeow']['message'] ?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
    <?
}
?>
</script>
<style type="text/css">
    .first{width: 200px;}
    td{text-align: center;}
</style>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/transferts" >Dépot de fonds</a> - </li>
        <li><a href="<?= $this->lurl ?>/transferts/recouvrement/<?= $this->receptions->id_reception ?>" >Recouvrement projet : <?= $this->projects->id_project ?></a> - </li>
        <li>Recouvrement prêteurs</li>
    </ul>
    <h1>Recouvrement prêteurs</h1>
    <div class="btnDroite submitdossier">
        <a href="<?= $this->lurl ?>/transferts/recouvrement/<?= $this->receptions->id_reception ?>/memory" class="btn_link" >Retour recouvrement</a>
    </div>
    
    <div style="margin: auto;text-align: center;">

        <table class="tablesorter">
            <thead>
                <tr>
                    <th>ID preteur (id client)</th>
                    <th>Email prêteur</th>
                    <th>Capital échus</th>
                    <th>Intérêts échus</th>
                    <th>Intérêts courus</th>
                    <th>Capital restant dû</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($this->preteurs != false) {
                    foreach ($this->preteurs as $p) {
                        
                        // Interets courus
                        $this->interetsCourus = round(($this->diff/$this->nbJourMoisDER)*($p['interets_next'] / 100),2);
                        
                        ?>
                        <tr>
                            <td><?= $p['id_client'] ?></td>
                            <td><?= $p['email'] ?></td>
                            <td><?= number_format($p['capital_echus'] / 100, 2, ',', ' ') ?> €</td>
                            <td><?= number_format($p['interets_echus'] / 100, 2, ',', ' ') ?> €</td>
                            <td><?= number_format($this->interetsCourus, 2, ',', ' ') ?> €</td>
                            <td><?= number_format($p['capital_restant_du'] / 100, 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
        <br><br><br><br>
        <div class="btnDroite submitdossier">
            <a href="<?= $this->lurl ?>/transferts/recouvrement/<?= $this->receptions->id_reception ?>/memory" class="btn_link" >Retour recouvrement</a>
        </div>
    </div>

</div>