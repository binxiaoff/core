<div class="row">
    <div class="col-md-12">
        <h2>
            Mouvements effectués entre le
            <input id="operations-start" type="text" value="<?= $this->startDate->format('d/m/Y') ?>" placeholder="Début" class="datepicker text-center" style="width: 100px">
            et le
            <input id="operations-end" type="text" value="<?= $this->endDate->format('d/m/Y') ?>" placeholder="Fin" class="datepicker text-center" style="width: 100px">
            <button id="operations-filter" class="btn-primary btn-sm">Filtrer</button>
        </h2>
    </div>
</div>
<div id="operations-container" class="row">
    <?php $this->fireView('preteur/mouvements_table'); ?>
</div>

<script>
    $(function () {
        $('.datepicker').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            yearRange: '2013:<?= date('Y') ?>'
        });

        $('#operations-filter').on('click', function () {
            $.ajax({
                url: '<?= $this->lurl ?>/sfpmei/preteur/<?= $this->clients->id_client ?>/mouvements/ajax',
                method: 'POST',
                data: {
                    start: $('#operations-start').val(),
                    end: $('#operations-end').val()
                }
            }).done(function (response) {
                $('#operations-container').html(response);
            });
        })
    })
</script>
