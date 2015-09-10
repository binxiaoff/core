<!--#include virtual="ssi-header.shtml"  -->
<div class="main">
    <div class="shell">

        <?

        $this->fireView('../blocs/depot-de-dossier');

        switch($this->params[0]){
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
        }
        ?>
    </div><!-- /.shell -->
</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->
