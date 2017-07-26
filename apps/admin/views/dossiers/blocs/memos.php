<a class="tab_title" id="section-memos" href="#section-memos">Mémos</a>
<div class="tab_content expand" id="tab_memos">
    <p class="text-right">
        <a role="button" data-memo="#tab_memos_memo" data-memo-onsubmit="add" data-memo-project-id="<?= $this->projects->id_project ?>" class="btn btn_link">Ajouter un mémo</a>
    </p>
    <div id="tab_memos_memo" style="margin-top: 0"></div>
    <div id="table_memo">
        <?php $this->fireView('memo/list'); ?>
    </div>
</div>
