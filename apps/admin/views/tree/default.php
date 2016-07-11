<script type="text/javascript">
    <?php if (isset($_SESSION['freeow'])) : ?>
        $(function () {
            var title, message, opts, container;
            title = "<?= $_SESSION['freeow']['title'] ?>";
            message = "<?= $_SESSION['freeow']['message'] ?>";
            opts = {};
            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
        <?php unset($_SESSION['freeow']); ?>
    <?php endif; ?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?= $this->lurl ?>/tree" title="Edition">Edition</a> -</li>
        <li>Arborescence</li>
    </ul>
    <h1>Arborescence</h1>
    <p id="masstoggler">
        <a title="Tout r&eacute;duire">Tout r&eacute;duire</a>&nbsp;|&nbsp;<a title="Tout ouvrir">Tout ouvrir</a></p>
    <div><?= $this->tree->getArbo(($_SESSION['user']['id_tree'] != 0 ? $_SESSION['user']['id_tree'] : 1), $this->language, 0) ?></div>
</div>
