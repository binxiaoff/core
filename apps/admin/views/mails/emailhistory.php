<script type="text/javascript">
    $(function() {
        $(".tablesorter").tablesorter({headers: {4: {sorter: false}}});

        <?php if ($this->nb_lignes != '') : ?>
            $(".tablesorter").tablesorterPager({
                container: $("#pager"),
                positionFixed: false,
                size: <?= $this->nb_lignes ?>}
            );
        <?php endif; ?>

        $(".lightbox").colorbox({
            onComplete: function () {
                $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']));
                $("#datepik_from").datepicker({
                    showOn: 'both',
                    buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                    buttonImageOnly: true,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                });
                $("#datepik_to").datepicker({
                    showOn: 'both',
                    buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
                    buttonImageOnly: true,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '<?= (date('Y') - 10) ?>:<?= (date('Y') + 10) ?>'
                });
            }
        });

        <?php if (false === isset($this->emails)) : ?>
            $('.btnDroite .btn_link.lightbox').click();
        <?php endif; ?>
    });
</script>
<div id="contenu">
    <h1>Historique des emails</h1>
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/mails/recherche" class="btn_link lightbox">Rechercher</a>
    </div>
    <?php if (isset($this->emails) && count($this->emails) > 0) : ?>
        <table class="tablesorter">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>Destinataire</th>
                    <th>Sujet</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($this->emails as $email) : ?>
                    <tr<?= ($i % 2 == 1 ? '' : ' class="odd"') ?>>
                        <td><?= $this->dates->formatDate($email['sent_at'], 'd/m/Y H:i') ?></td>
                        <td><?= $email['sender_name'] ?></td>
                        <td><?= $email['recipient'] ?></td>
                        <td><?= $email['subject'] ?></td>
                        <td align="center">
                            <a href="<?= $this->url ?>/mails/emailpreview/<?= $email['id_queue'] ?>" class="thickbox">
                                <img src="<?= $this->surl ?>/images/admin/modif.png" alt="Voir <?= $email['subject'] ?>"/>
                            </a>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
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
    <?php elseif (isset($this->emails)) : ?>
        <p>Aucun email ne correspond à cette recherche</p>
    <?php endif; ?>
</div>
