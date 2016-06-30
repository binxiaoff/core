<div class="main">
    <div class="shell">
        <h1><?= $this->lng['search']['title'] ?></h1>
        <p><?= $this->lng['search']['resultats'] ?> : <b>"<?= $this->search ?>"</b></p>
        <ul style="padding-left:50px; list-style-type:inherit;">
        <?php if ($this->result == true) : ?>
            <?php foreach ($this->result as $result) : ?>
                <li><a href="<?= $this->lurl ?>/<?= $result['slug'] ?>"><?= $result['title'] ?></a></li>
            <?php endforeach; ?>
        <?php else : ?>
            <?= $this->lng['search']['pas-de-resultats'] ?>
        <?php endif; ?>
        </ul>
    </div>
</div>
