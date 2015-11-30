<div class="main">
    <div class="shell">
        <div class="section-c tabs-c">
            <nav class="tabs-nav">
                <ul>
                    <li class="active"><a href="#"><?= $this->lng['espace-emprunteur']['operations'] ?></a></li>
                    <li><a href="#"><?= $this->lng['espace-emprunteur']['documents'] ?></a></li>
                </ul>
            </nav>
            <div class="tabs">
                <div class="tab vos_operations">
                    <?= $this->fireView('operations_emprunteur') ?>
                </div>
                <div class="tab vos_documents">
                    <?= $this->fireView('documents') ?>
                </div>
            </div>
        </div>
    </div>
</div>
