<div class="tab_title" id="title_tab_email">Mémos</div>
<div class="tab_content expand" id="tab_email">
    <div class="btnDroite">
        <a href="<?= $this->lurl ?>/dossiers/memo/<?= $this->projects->id_project ?>" class="btn_link thickbox">Ajouter un mémo</a>
    </div>
    <br/>
    <div id="table_memo">
        <?php $this->fireView('memo/list'); ?>
    </div>
</div>
