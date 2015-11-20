<div class="testimonials">
    <a href="#" class="slide-prev icon-arrow-big-prev"></a>
    <a href="#" class="slide-next icon-arrow-big-next"></a>
    <div class="slides-container">
        <ul class="slides">
        <?php
            $contenu = array('', 'contenu-1-60', 'contenu-2-61', 'contenu-3', 'contenu-4', 'contenu-5-64', 'contenu-6-65', 'contenu-7', 'contenu-8', 'contenu-9', 'contenu-10');
            $lien = array('', 'lien-1-71', 'lien-2-72', 'lien-3-73', 'lien-4-74', 'lien-5', 'lien-6', 'lien-7', 'lien-8', 'lien-9', 'lien-10');
            for ($i = 1; $i <= 10; $i++) {
                if ($this->bloc_content[$contenu[$i]] != '') {
                    ?><li>
                        <?= ($this->bloc_content[$lien[$i]] != '' ? '<a target="_blank" href="' . $this->bloc_content[$lien[$i]] . '">' : '') ?><?
                        if ($this->bloc_content['logo-' . $i] != '') {
                            ?><div class="testimonial-thumb" ><img src="<?= $this->photos->display($this->bloc_content['logo-' . $i], '', 'bloc_home') ?>" alt="<?= $this->bloc_complement['logo-' . $i] ?>"></div><?
                        }
                        ?>
                        <div class="testimonial-body"><?= $this->bloc_content[$contenu[$i]] ?></div>
                        <?= ($this->bloc_content[$lien[$i]] != '' ? '</a>' : '') ?>
                    </li><?
                }
            }
        ?>
        </ul>
    </div>
</div>

<script>
    $('.testimonials .slides').carouFredSel({
        width: '100%',
        height: 100,
        responsive: true,
        auto: 7000,
        prev: '.testimonials .slide-prev',
        next: '.testimonials .slide-next',
        items: {
            visible: 1
        }
    });
</script>
