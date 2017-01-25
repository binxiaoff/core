<?php if (isset($_SESSION['freeow'])) : ?>
    <script type="text/javascript">
        $(function () {
            var title = "<?= $_SESSION['freeow']['title'] ?>",
                message = "<?= $_SESSION['freeow']['message'] ?>",
                opts = {},
                container;

            opts.classes = ['smokey'];
            $('#freeow-tr').freeow(title, message, opts);
        });
    </script>
    <?php unset($_SESSION['freeow']); ?>
<?php endif; ?>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <h1>Arborescence</h1>
    <p id="masstoggler">
        <a title="Tout réduire">Tout réduire</a>&nbsp;|&nbsp;<a title="Tout ouvrir">Tout ouvrir</a>
    </p>
    <div><?= $this->tree->getArbo(1, $this->language) ?></div>
</div>
