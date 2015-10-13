<div class="main">
    <div class="shell">
        <p>
        <?php
            switch ($this->params[0]) {
                case 'prospect':
                    echo $this->lng['depot-de-dossier']['contenu-prospect'];
                    $this->fireView(('../templates/contact'));
                    break;
                case 'procedure-accelere':
                    echo $this->lng['depot-de-dossier']['procedure-accelere'];
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
    </div>
</div>
