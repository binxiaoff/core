<script type="text/javascript">
    $(document).ready(function () {
        $(".tablesorter").tablesorter({headers: {6: {sorter: false}}});
        <?php if($this->nb_lignes != '') : ?>
        $(".tablesorter").tablesorterPager({container: $("#pager"), positionFixed: false, size: <?=$this->nb_lignes?>});
        <?php endif;?>

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
        $("#datepik_1").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });
        $("#datepik_2").datepicker({
            showOn: 'both',
            buttonImage: '<?=$this->surl?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 10)?>:<?=(date('Y') + 10)?>'
        });

    });
    <?php if(isset($_SESSION['freeow'])) : ?>
    $(document).ready(function () {
        var title, message, opts, container;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php endif; ?>


</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/stats" title="Stats">Stats</a> -</li>
        <li>Etape d'inscription des utilisateurs</li>
    </ul>
    <h1>Etape d'inscription des utilisateurs</h1>

    <form method="post" name="recupCSV">
        <input type="hidden" name="recup"/>
        <input type="hidden" name="spy_date1" value="<?= $_POST['date1'] ?>"/>
        <input type="hidden" name="spy_date2" value="<?= $_POST['date2'] ?>"/>
    </form>

    <div style="margin-bottom:20px; float:right;">
        <a onClick="document.forms['recupCSV'].submit();" class="btn colorAdd">Recuperation du CSV</a></div>


    <div style="width:500px;margin: auto;margin-bottom:20px;background-color: white;border: 1px solid #A1A5A7;border-radius: 10px 10px 10px 10px;margin: 0 auto 20px;padding:5px;">
        <form method="post" name="date_select">
            <fieldset>
                <table class="formColor">
                    <tr>
                        <td style="padding-top:23px;"><label>Date debut</label><br/><input type="text" name="date1" id="datepik_1" class="input_dp" value="<?= $_POST['date1'] ?>"/>
                        </td>
                        <td style="padding-top:23px;"><label>Date fin</label><br/><input type="text" name="date2" id="datepik_2" class="input_dp" value="<?= $_POST['date2'] ?>"/>
                        </td>

                        <td style="padding-top:23px;">
                            <input type="hidden" name="spy_search" id="spy_search"/>
                            <input type="submit" value="Valider" title="Valider" name="send_dossier" id="send_dossier" class="btn"/>
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
    <?php if (count($this->L_clients) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th>Id</th>
                <th>Nom</th>
                <th>Pr&eacute;nom</th>
                <th>E-mail</th>
                <th>Tel</th>
                <th>Date inscription</th>
                <th>Etape inscription validée</th>
                <th>Source</th>
                <th>Source2</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($this->L_clients as $u) : ?>
                <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                    <td><?= $u['id_client'] ?></td>
                    <td><?= $u['nom'] ?></td>
                    <td><?= $u['prenom'] ?></td>
                    <td><?= $u['email'] ?></td>
                    <td><?= $u['telephone'] . ' ' . $u['mobile'] ?></td>
                    <td><?= $this->dates->formatDate($u['added'], 'd/m/Y') ?></td>
                    <td><?= $u['etape_inscription_preteur2']; ?></td>
                    <td><?= $u['source'] ?></td>
                    <td><?= $u['source2'] ?></td>
                </tr>
                <?php
                $i++;
            endforeach; ?>
            </tbody>
        </table>
        <?php if ($this->nb_lignes != '') : ?>
            <table>
                <tr>
                    <td id="pager">
                        <img src="<?= $this->surl ?>/images/admin/first.png" alt="Première" class="first"/>
                        <img src="<?= $this->surl ?>/images/admin/prev.png" alt="Précédente" class="prev"/>
                        <input type="text" class="pagedisplay"/>
                        <img src="<?= $this->surl ?>/images/admin/next.png" alt="Suivante" class="next"/>
                        <img src="<?= $this->surl ?>/images/admin/last.png" alt="Dernière" class="last"/>
                        <select class="pagesize">
                            <option value="<?= $this->nb_lignes ?>" selected="selected"><?= $this->nb_lignes ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    <?php else : ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
<?php unset($_SESSION['freeow']); ?>