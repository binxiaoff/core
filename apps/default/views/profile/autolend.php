
<div class="main form-page account-page account-page-personal">
    <div class="shell">
        <nav class="tabs-nav">
            <ul class="navProfile">
                <li id="notification"><a href="#"><?= $this->lng['profile']['titre-4'] ?></a></li>
                <li id="securite"><a href="<?= $this->lurl ?>/profile#securite"><?= $this->lng['profile']['titre-3'] ?></a></li>
                <li id="info_perso"><a href="<?= $this->lurl ?>/profile#info_perso"><?= $this->lng['profile']['titre-1'] ?></a></li>
                <li class="active" id="autolend"><a href="#"><?= $this->lng['profile']['title-tab-autobid'] ?></a></li>
            </ul>
        </nav>
        <header class="header-autobid inner-nav">
            <ul>
                <li class="active" id="consultation"><a href="#consultation" data-dest="1"><?= $this->lng['autobid']['title-tab-overview'] ?></a></li>
                <li id="parametrage"><a href="#parametrage" data-dest="2"><?= $this->lng['autobid']['title-tab-settings'] ?></a></li>
            </ul>
        </header>
        <div class="autobid-tabs">
            <div class="autobid-tab visible" id="tab-1">
                <?php $this->fireview('autolend_overview'); ?>
            </div>
            <div class="autobid-tab" id="tab-2">
                <?php $this->fireview('autolend_settings'); ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(window).load(function(){
        $(function () {
        $('#notification').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile");
        });
        $('#securite').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile#securite");
        });
        $('#info_perso').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile#info_perso");
        });
    });
        // Autobid inner nav
        $('.header-autobid a').on('click', function(e){
            e.preventDefault();
            $('.header-autobid li').removeClass('active');
            $(this).parent().addClass('active');
            $('.autobid-tab').removeClass('visible');
            $('#tab-'+$(this).attr('data-dest')).addClass('visible');
        });
    });
</script>

