<div class="main">
    <div class="shell">

        <?php

        switch ($this->params[0]) {

            case 'prospect':
                echo '<p>' . $this->lng['depot-de-dossier']['contenu-prospect'] . '</p>';
                break;

            case 'procedure-accelere':
                echo '<p>' . $this->lng['depot-de-dossier']['procedure-accelere'] . '</p>';
                break;

            case 'abandon':
                echo '<p>' . $this->lng['depot-de-dossier']['abandon'] . '</p>';
                break;
            case 'analyse':
                echo '<p>' . $this->lng['depot-de-dossier']['analyse'] . '</p>';
                $this->fireView(('../templates/contact'));
                break;
            default:
                echo '<p>' . $this->lng['depot-de-dossier']['contenu'] . '</p>';
                break;
        }
        ?>
    </div>
    <!-- /.shell -->
</div><!-- /.main -->
<!--#include virtual="ssi-footer.shtml"  -->
