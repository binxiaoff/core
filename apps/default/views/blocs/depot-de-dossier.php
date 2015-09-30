<div class="wrapper">
    <div class="header">
        <div class="shell clearfix">
            <div class="logo">
                <a href="<?= $this->lurl ?>">Unilend</a>
            </div>
            <!-- /.logo -->
        </div>
    </div>
    <!--/.header>-->

    <div class="main">
        <div class="shell">

            <ul style="display:inline; padding-left:0px;">
                <li style="display:inline;color:#A1A5A7;">
                    <a style="color:#A1A5A7;"
                       href="<?= $this->lurl ?>"><?= $this->lng['depot-de-dossier-header']['accueil'] ?>></a>

                </li>

                <li style="display:inline;color:#6B6E70"><?= $this->lng['depot-de-dossier-header']['titre'] ?></li>

            </ul>
            <h1><?= $this->lng['depot-de-dossier-header']['titre' . ($this->page == 2 ? '-etape-2' : '')] ?></h1>
