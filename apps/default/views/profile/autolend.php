
<div class="main form-page account-page account-page-personal">
    <div class="shell">
        <nav class="tabs-nav">
            <ul class="navProfile">
                <li><a id="notification" href="#"><?= $this->lng['profile']['titre-4'] ?></a></li>
                <li><a id="securite" href="#"><?= $this->lng['profile']['titre-3'] ?></a></li>
                <li><a id="info_perso" href="#"><?= $this->lng['profile']['titre-1'] ?></a></li>
                <li class="active"><a id="autolend" href="#"><?= $this->lng['profile']['title-tab-autobid'] ?></a></li>
            </ul>
        </nav>
        <header class="header-autobid inner-nav">
            <ul>
                <li id="consult"class="active">
                    <a id="consult_link" href="#consultation" data-dest="1"><?= $this->lng['autobid']['title-tab-overview'] ?></a>
                </li>
                <li id="param" style="<?= ($this->bAutoBidOn) ? '' : 'display:none;'  ?>">
                    <a id="parameter" href="#parametrage" data-dest="2"><?= $this->lng['autobid']['title-tab-settings'] ?></a>
                </li>
            </ul>
        </header>
        <div class="autobid-tabs">
            <div class="autobid-tab" id="tab-1">
                <?php $this->fireview('autolend_overview'); ?>
            </div>
            <div class="autobid-tab" id="tab-2">
                <?php $this->fireview('autolend_settings'); ?>
            </div>
        </div>
    </div>
</div>
<script>
    $(window).load(function () {
        $('#notification').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile");
        });
        $('#securite').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile#securite");
        });
        $('#info_perso').click(function () {
            window.location.replace("<?= $this->lurl ?>/profile#info_perso");
        });
        $('#consult_link').click(function () {
            location.hash = "consultation";
        });
        $('#parameter').click(function () {
            location.hash = "parametrage";
        });

        // Autobid inner nav
        $('.header-autobid a').on('click', function (e) {
            e.preventDefault();
            $('.header-autobid li').removeClass('active');
            $(this).parent().addClass('active');
            $('.autobid-tab').removeClass('visible');
            $('#tab-' + $(this).attr('data-dest')).addClass('visible');
        });

        var tab;
        if (window.location.hash == "#consultation" || window.location.hash == "") {
            tab = $('#consult_link');
        } else if (window.location.hash == "#parametrage") {
            tab = $('#parameter');
        }
        tab.trigger("click");
    });
</script>

