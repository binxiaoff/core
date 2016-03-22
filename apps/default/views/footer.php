<div class="footer">
    <div class="footer-main">
        <div class="shell">
            <div class="footer-social">
                <div class="social-block block-twitter">
                    <a target="_blank" href="<?= $this->twitter ?>" class="icon"><i class="icon-big-twitter"></i></a>
                    <div class="body">
                        <?= $this->lng['footer']['suivez-nous-sur-twitter'] ?>
                    </div>
                </div>
                <div class="social-block block-facebook">
                    <a target="_blank" href="<?= $this->like_fb ?>" class="icon">
                        <i class="icon-big-facebook"></i>
                    </a>
                    <div class="body">
                        <small><?= $this->lng['footer']['unilend'] ?></small>
                    </div>
                    <div class="foot">
                        <div class="fb-like" data-href="<?= $this->like_fb ?>" data-width="200" data-colorscheme="light" data-layout="standard" data-action="like" data-show-faces="true" data-send="false"></div>
                    </div>
                </div>
            </div>
            <!-- /.footer-social -->

            <ul class="footer-nav">
                <li>
                    <h5><?= $this->lng['footer']['titre-nav-1'] ?></h5>
                    <ul>
                        <?php
                        foreach ($this->navFooter1 as $key => $nf) {
                            ?>
                            <li><a target="<?= $nf['target'] ?>" href="<?= $nf['url'] ?>"><?= $nf['nom'] ?></a></li><?php
                        }
                        ?>
                    </ul>
                </li>
                <li>
                    <h5><?= $this->lng['footer']['titre-nav-2'] ?></h5>
                    <ul>
                        <?
                        foreach ($this->navFooter2 as $key => $nf) {
                            ?>
                            <li><a target="<?= $nf['target'] ?>" href="<?= $nf['url'] ?>"><?= $nf['nom'] ?></a></li><?
                        }
                        ?>
                    </ul>
                </li>
                <li class="wide">
                    <h5><?= $this->lng['footer']['titre-nav-3'] ?></h5>
                    <ul>
                        <?
                        foreach ($this->navFooter3 as $key => $nf) {
                            ?>
                            <li><a target="<?= $nf['target'] ?>" href="<?= $nf['url'] ?>"><?= $nf['nom'] ?></a></li><?
                        }
                        ?>
                    </ul>
                </li>
                <li>
                    <h5><?= $this->lng['footer']['titre-nav-4'] ?></h5>
                    <ul>
                        <?
                        foreach ($this->navFooter4 as $key => $nf) {
                            ?>
                            <li><a target="<?= $nf['target'] ?>" href="<?= $nf['url'] ?>"><?= $nf['nom'] ?></a></li><?
                        }
                        ?>
                    </ul>
                </li>
            </ul>
            <!-- /.footer-nav -->
            <p class="copyrights"><?= $this->lng['footer']['copyrights'] ?>
                <?php
                $i = 0;
                foreach ($this->menuFooter as $key => $f) {
                    ?><?= ($i == 0 ? '' : ' | ') ?>
                    <a target="<?= $f['target'] ?>" href="<?= $f['url'] ?>"><?= $f['nom'] ?></a><?
                    $i++;
                }
                ?>
            </p><!-- /.copyrights -->
        </div>
        <!-- /.shell -->
    </div>
    <!-- /.footer-main -->
    <div class="footer-partners">
        <div class="shell">
            <h6>Nos partenaires</h6>
            <ul>
                <?php
                for ($i = 1; $i <= 4; $i++) {
                    if ($this->bloc_partenaires['image-' . $i] != false) {
                        if ($this->bloc_partenaires['lien-' . $i] != '') {
                            ?>
                            <li>
                            <a target="_blank" href="<?= $this->bloc_partenaires['lien-' . $i] ?>"><img src="<?= $this->surl ?>/var/images/<?= $this->bloc_partenaires['image-' . $i] ?>" alt="<?= $this->bloc_partenairesComplement['image' . $i] ?>"/></a>
                            </li> <?
                        } else {
                            ?>
                            <li>
                            <img class="logo-partner" src="<?= $this->surl ?>/var/images/<?= $this->bloc_partenaires['image-' . $i] ?>" alt="<?= $this->bloc_partenairesComplement['image-' . $i] ?>"/>
                            </li> <?
                        }
                    }
                }
                ?>
                <li>
                    <table width="135" border="0" cellpadding="2" cellspacing="0" title="Cliquez sur Vérifier - Ce site a choisi Symantec SSL pour un e-commerce sûr et des communications confidentielles.">
                        <tr>
                            <td width="135" align="center" valign="top">
                                <script type="text/javascript" src="https://seal.verisign.com/getseal?host_name=www.unilend.fr&amp;size=XS&amp;use_flash=NO&amp;use_transparent=NO&amp;lang=fr"></script>
                            </td>
                        </tr>
                    </table>
                </li>
            </ul>
        </div>
        <!-- /.shell -->
    </div>
    <!-- /.footer-partners -->
    <?php
    if (isset($_SESSION['lexpress'])) {
        if ($_SESSION['lexpress']['id_template'] == 15) {
            ?>
            <iframe name="lexpressfooter" SRC="<?= $_SESSION['lexpress']['footer'] ?>" scrolling="no" height="1000px" width="100%" FRAMEBORDER="no"></iframe>
            <?
        } elseif ($_SESSION['lexpress']['id_template'] == 19) {
            ?>
            <iframe name="lexpressfooter" SRC="<?= $_SESSION['lexpress']['footer'] ?>" scrolling="no" height="1160px" width="100%" FRAMEBORDER="no"></iframe>
            <?
        }
    }
    ?>
</div><!-- /.footer -->
<div class="cookies">
    <div class="content_cookies">
        <div><?= $this->lng['footer']['cookies-content'] ?>
            <a target="_blank" href="<?= $this->lurl ?>/<?= $this->tree->getSlug(381, $this->language) ?>"><?= $this->lng['footer']['cookies-link'] ?></a>
        </div>
    </div>
    <div class="accept_cookies">
        <button onclick="acceptCookies();"><?= $this->lng['footer']['cookies-cta'] ?></button>
    </div>
</div>

<script type="text/javascript">
    if (document.cookie.indexOf("acceptCookie") >= 0) {
        $('.cookies').hide();
    }
</script>
<!--[if lte IE 9]>
<script type="text/javascript" src="<?= $this->surl ?>/scripts/default/placeholders.jquery.min.js"></script>
<![endif]-->
</div><!-- /.wrapper -->

<?php if ($this->lurl == 'http://partenaire.unilend.challenges.fr') { ?>
    <!-- tagging challenges -->
    <script src="http://referentiel.nouvelobs.com/js/nobs;,detect-adblock.js"></script>
    <script>
        xtnv = document;
        xtsd = "http://logi150";
        xtdmc = ".challenges.fr";
        xtsite = "562191";
        xtn2 = "1";
        xtpage = "Services::Unilend";
        xtdi = "";
        xt_multc = "&x1=[Challenges]&x2=1&x4=2&x5=[Autres]&x8=[Unilend]&x15=[Services]";

        (function (win) {
            var doc = win.document,
                fillXtMultc = function (xNumber, value) {
                    var multcVar = '&x' + xNumber + '=' + value;
                    win.xtparam = win.xtparam ? win.xtparam + multcVar : multcVar;
                },
                callXtcore = function () {
                    // appel du script xtcore
                    var script = doc.createElement('script');
                    script.async = 1;
                    script.src = "http://referentiel.nouvelobs.com/scripts/xiti/challenges.fr/xtcore.js";
                    doc.body.appendChild(script);
                },
                deviceDetect = function () {
                    var userAgent = win.navigator.userAgent.toLowerCase(),
                        isMobile = (/iphone|ipod|android|blackberry|opera|mini|windows\sce|palm|smartphone|iemobile/i.test(userAgent)),
                        isTablet = (/ipad|android 3.0|xoom|sch-i800|playbook|tablet|kindle|silk/i.test(userAgent)),
                        deviceDedected = '1';
                    if (isMobile) {
                        if ((userAgent.search("android") > -1) && (userAgent.search("mobile") > -1)) deviceDedected = '2';
                        else if ((userAgent.search("android") > -1) && !(userAgent.search("mobile") > -1)) deviceDedected = '3';
                        else deviceDedected = '2';
                    }
                    if (isTablet) deviceDedected = '3';
                    return deviceDedected;
                },
                handler = function (event) {
                    // aprËs la dÈtection, on remplit l'indicateur x19
                    var detected = event.detail.status || false;
                    fillXtMultc(19, (detected === true ? '1' : '2'));
                    callXtcore();
                };
            try {
                fillXtMultc(3, (deviceDetect() || '1'));
            } catch (e) {
                console.debug('deviceDetect failed!');
            }
            if (doc.addEventListener) {
                doc.addEventListener('detectAdblock', handler, false);
            } else { // IE8-
                callXtcore();
            }
        })(window);
    </script>
    <noscript>
        <img width="1" height="1" alt="" src="http://logi150.xiti.com/hit.xiti?s=562191&amp;s2=1&amp;p=Services::Unilend&amp;di=&amp;"/>
    </noscript>
    <!-- fin tagging challenges -->
<?php } ?>
</body>
</html>
