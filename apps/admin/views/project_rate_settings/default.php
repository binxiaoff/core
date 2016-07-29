<script type="text/javascript">
    <?php if(isset($_SESSION['freeow'])) : ?>
    $(document).ready(function(){
        var title, message, opts;
        title = "<?=$_SESSION['freeow']['title']?>";
        message = "<?=$_SESSION['freeow']['message']?>";
        opts = {};
        opts.classes = ['smokey'];
        $('#freeow-tr').freeow(title, message, opts);
    });
    <?php endif ?>
</script>
<div id="freeow-tr" class="freeow freeow-top-right"></div>
<div id="contenu">
    <ul class="breadcrumbs">
        <li><a href="<?=$this->lurl?>/settings" title="Configuration">Configuration</a> -</li>
        <li>Grille de taux</li>
    </ul>
    <h1>Gestion de la grille de taux</h1>
    <div class="btnDroite"><a class="btn_link thickbox cboxElement" href="/project_rate_settings/warn_confirmation_box">Notifier les prêteurs</a></div>
    <?php if(count($this->groupedRate) > 0) : ?>
        <table class="tablesorter">
            <thead>
            <tr>
                <th></th>
                <?php foreach (array_keys(array_values($this->groupedRate)[0]) as $evaluation) : ?>
                    <th><?=constant('\projects::RISK_' . $evaluation)?>* (taux min - taux max)</th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($this->groupedRate as $periodId => $periodRate) : ?>
            <tr<?=($i%2 == 1?'':' class="odd"')?>>

                <td><?=array_values($periodRate)[0]['min']?> - <?=array_values($periodRate)[0]['max']?> mois</td>
                <?php foreach ($periodRate as $riskRate) : ?>
                    <td data-evaluation="<?=$riskRate['evaluation']?>" data-period="<?=$periodId?>">
                        <span class="project-rate" data-rate="min"><?=$riskRate['rate_min']?></span>
                        <input style="display: none;" maxlength="4" size="4" class="project-rate-edit" name="rate_min" type="text" value="<?=$riskRate['rate_min']?>" />
                        %
                        <button style="display: none;" class="project-rate-settings-save">OK</button>
                        <button style="display: none;" class="project-rate-settings-cancel">Annuler</button>
                        -
                        <span class="project-rate" data-rate="max"><?=$riskRate['rate_max']?></span>
                        <input style="display: none;" maxlength="4" size="4" class="project-rate-edit" name="rate_max" type="text" value="<?=$riskRate['rate_max']?>" />
                        %
                        <button style="display: none;" class="project-rate-settings-save">OK</button>
                        <button style="display: none;" class="project-rate-settings-cancel">Annuler</button>
                        <span class="save-button"></span>
                    </td>
                <?php endforeach; ?>
            </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Il n'y a aucun utilisateur pour le moment.</p>
    <?php endif; ?>
</div>
<?php unset($_SESSION['freeow']); ?>
<script>
    $('.project-rate').click(function() {
        $(this).hide();
        var input = $(this).next('.project-rate-edit');
        input.show();
        input.next('.project-rate-settings-save').show()
            .next('.project-rate-settings-cancel').show();
        input.focus();
    });

    $(".project-rate-edit").keyup(function (e) {
        if (e.keyCode == 13) {
            $(this).next('.project-rate-settings-save').click();
        }
        if (e.keyCode == 27) {
            $('.project-rate-settings-cancel').click();
        }
    });

    $('.project-rate-settings-cancel').click(function() {
        $(this).hide();
        $(this).prev('.project-rate-settings-save').hide()
            .prev('.project-rate-edit').hide()
            .prev('.project-rate').show();
    });

    $('.project-rate-settings-save').click(function() {
        $('.project-rate-edit').off('blur');
        var evaluation = $(this).parent().data('evaluation');
        var periodId = $(this).parent().data('period');
        var input = $(this).prev('.project-rate-edit');
        var rate_min, rate_max;

        if (input.attr('name') == 'rate_min') {
            rate_min = input.val();
            rate_max = $(this).siblings('span[data-rate=max]').html();
        } else if (input.attr('name') == 'rate_max') {
            rate_max = input.val();
            rate_min = $(this).siblings('span[data-rate=min]').html();
        }

        $.ajax({
            url: 'project_rate_settings/save/' + evaluation + '/' + periodId,
            method: 'POST',
            dataType: 'json',
            data: { rate_min: rate_min, rate_max: rate_max}
        })
        .done(function(response){
            if (response.result == 'OK') {
                input.hide();
                input.next('.project-rate-settings-save').hide()
                    .next('.project-rate-settings-cancel').hide();
                input.prev('.project-rate').html(input.val())
                    .show();
            } else {
                alert(response.message);
            }

        });
    });
</script>
