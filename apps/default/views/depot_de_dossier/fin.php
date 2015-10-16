<div class="main">
    <div class="shell">
        <p>
        <?php
            switch ($this->params[0]) {
                case 'prospect':
                    echo $this->lng['depot-de-dossier']['contenu-prospect'];
                    $this->fireView(('../templates/contact'));
                    break;
                case 'abandon':
                    echo $this->lng['depot-de-dossier']['abandon'];
                    break;
                case 'analyse':
                    echo $this->lng['depot-de-dossier']['analyse'];
                    break;
                default:
                    echo $this->lng['depot-de-dossier']['contenu'];
                    break;
            }
        ?>
        </p>
        <p><?= $this->sErrorMessage ?></p>
    </div>
</div>
