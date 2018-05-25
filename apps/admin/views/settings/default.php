<script type="text/javascript">
    $(function() {
        $('.tablesorter').tablesorter({headers: {2: {sorter: false}}})
    });
</script>
<div id="contenu">
    <h1>Liste des paramètres globaux</h1>
    <?php if (count($this->settings) > 0) : ?>
        <table class="tablesorter table-striped">
            <thead>
                <tr>
                    <th style="width: 340px">Type</th>
                    <th>Valeur</th>
                    <th style="width: 35px">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Settings $setting */ ?>
                <?php foreach ($this->settings as $setting) : ?>
                    <tr>
                        <td><?= $setting->getType() ?></td>
                        <td style="overflow: auto; max-width: 700px;"><?= $setting->getValue() ?></td>
                        <td class="center">
                            <a href="<?= $this->lurl ?>/settings/edit/<?= $setting->getIdSetting() ?>" class="thickbox">
                                <img src="<?= $this->surl ?>/images/admin/edit.png" alt="Modifier">
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>Il n'y a aucun paramètre pour le moment.</p>
    <?php endif; ?>
</div>
