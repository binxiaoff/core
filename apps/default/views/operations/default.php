<div class="main">
    <div class="shell">
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul>
                    <li class="active"><a href="#" id="operations"><?= $this->lng['preteur-operations']['titre-1'] ?></a></li>
                    <li><a href="#" id="prets"><?= $this->lng['preteur-operations']['titre-3'] ?></a></li>
                    <li><a href="#" id="docs-fiscaux"><?= $this->lng['preteur-operations']['titre-4'] ?></a></li>
                </ul>
            </nav>
            <div class="tabs">
                <div class="tab vos_operations">
                    <?= $this->fireView('vos_operations'); ?>
                </div>
                <div class="tab vos_prets">
                    <?= $this->fireView('vos_prets'); ?>
                </div>
                <div class="tab doc_fiscaux">
                    <?= $this->fireView('doc_fiscaux'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $('#operations').click(function () {
        location.hash = '';
        history.pushState('', '', location.pathname);
        $("#operations").scrollTop();
    });
    $('#prets').click(function () {
        location.hash = "vosprets";
        $("#prets").scrollTop();
    });
    $('#docs-fiscaux').click(function () {
        location.hash = "documentsfiscaux";
        $("#docs-fiscaux").scrollTop();
    });
</script>
