<div class="main">
    <div class="shell">
    <?php
        $sReason = isset($this->params[0]) ? $this->params[0] : null;

        switch ($sReason) {
            case 'pas-3-bilans':
                echo  "<p>".$this->lng['depot-de-dossier-nok']['pas-3-bilans']."</p>";
                break;
            case 'rex-nega':
                echo "<p>".$this->lng['depot-de-dossier-nok']['rex-nega']."</p>";
                break;
            case 'no-rcs':
                echo  "<p>".$this->lng['depot-de-dossier-nok']['no-rcs']."</p>";
                break;
            case 'no-siren':
                echo  "<p>".$this->lng['depot-de-dossier-nok']['no-siren']."</p>";
                break;
            default:
                echo  "<p>".$this->lng['depot-de-dossier-nok']['contenu-non-eligible']."</p>";
                break;
        }
        ?>
    </div>
</div>
