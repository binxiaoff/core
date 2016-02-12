<style>
    .header-autobid {
        border-bottom: 1px solid #e3e4e4;
        padding: 0 10px;
        margin-bottom: 40px;
    }
    .header-autobid ul {
        margin: 0;
        padding: 0;
    }
    .header-autobid li {
        list-style: none;
        display: inline-block;
    }
    .header-autobid li + li {
        margin-left: 20px;
    }
    .header-autobid li a {
        color: #a1a5a7;
        font-size: 18px;
        font-weight: bold;
        display: block;
        padding: 10px 0;
    }
    .header-autobid li a:hover {
        color: #b10366;
        text-decoration: none;
    }
    .header-autobid li.active a {
        color: #b10366;
        border-bottom: 4px solid #b10366;
    }

    .autobid-tab {
        display: none;
    }
    .autobid-tab.visible {
        display: block;
    }

</style>
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
                <li class="active"><a href="#consultation" data-dest="1"><?= $this->lng['autobid']['title-tab-overview'] ?></a></li>
                <li><a href="#parametrage" data-dest="2"><?= $this->lng['autobid']['title-tab-settings'] ?></a></li>
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

