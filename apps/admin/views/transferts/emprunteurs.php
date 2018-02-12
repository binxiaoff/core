<link href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css" type="text/css" rel="stylesheet">
<link href="<?= $this->lurl ?>/oneui/css/font-awesome.css" type="text/css" rel="stylesheet">
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<style>
    @font-face {
        font-family: 'FontAwesome';
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot');
        src: url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.eot?#iefix&v=4.7.0') format('embedded-opentype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff2') format('woff2'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.woff') format('woff'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.ttf') format('truetype'),
        url('<?= $this->lurl ?>/oneui/fonts/fontawesome-webfont.svg#fontawesomeregular') format('svg');
        font-weight: normal;
        font-style: normal;
    }
</style>
<script>
    $(function () {
        var dt = $('#receptions-table').DataTable({
            serverSide: true,
            processing: true,
            columnDefs: [
                {orderable: false, targets: [1, 3, 7]},
                {searchable: false, targets: [1, 3, 7, 8, 9, 10]},
                {visible: false, targets: [4, 8, 9, 10]},
                {name: "idReception", "targets": 0},
                {name: "motif", "targets": 1},
                {name: "montant", "targets": 2},
                {name: "attribution", "targets": 3},
                {name: "idProject", "targets": 5},
                {name: "added", "targets": 6}
            ],
            order: [[0, "desc"]],
            ajax: {
                url: '/transferts/attribues/emprunteur',
                "data": function (d) {
                    return $.extend({}, d, {
                        date_from: $('#datepik_from').val(),
                        date_to: $('#datepik_to').val()
                    });
                }
            },
            language: {
                url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
            },
            createdRow: function (row, data, index) {
                var $row = $(row)
                var receptionId = data[0]
                var comment = data[8]
                var line = data[9]
                var rejection = data[10]

                var addCommentBtn = '<a class="add-comment table-action" data-comment="' + comment + '" title="Commenter l\'opération">' +
                    '<span class="fa fa-pencil"></span>' +
                    '</a>'
                var showReceptionBtn = '<a class="show-reception table-action" data-line="' + line + '" title="Afficher l\'opération">' +
                    '<span class="fa fa-eye"></span>' +
                    '</a>'

                if (rejection) {
                    $row.css('background', '#f9adb3')
                }

                if (comment !== null) {
                    addCommentBtn = '<a class="add-comment modify-comment table-action" data-comment="' + comment + '" title="Modifier le commentaire">' +
                        '<span class="fa fa-pencil-square"></span>' +
                        '</a>'

                    if (!rejection) {
                        $row.css('background', '#fdeec6')
                    }
                }
                $row.attr('data-reception-id', receptionId)
                $row.find('td:last-child').append(showReceptionBtn + addCommentBtn)
            }
        })
        dt.on('preDraw', function () {
            var $filter = $('#receptions-table_filter input')
            $filter.attr('placeholder', 'ID reception, projet ou montant')
        })
        dt.on('draw', function () {
            $('.table-action').tooltip({
                position: {my: 'left top', at: 'right top'},
                content: function () {
                    return $(this).prop('title')
                }
            })
        })
        $(document).on('click', '.table-action', function () {
            var $modal
            if ($(this).is('.add-comment') || $(this).is('.modify-comment')) {
                $modal = $('#modal-comment')
                var receptionId = $(this).closest('tr').data('reception-id')
                var comment = $(this).data('comment')
                $modal.find('[name=reception]').val(receptionId)
                $modal.find('[name=comment]').html(comment)
                if ($(this).is('.modify-comment')) {
                    $modal.find('h1').text('Modifier le commentaire')
                } else {
                    $modal.find('h1').text('Ajouter un commentaire')
                }
            }
            if ($(this).is('.show-reception')) {
                $modal = $('#modal-line')
                $modal.find('.line').html($(this).data('line'))
            }
            $.colorbox({html: $modal.html(), width: '50%'})
        })

        $(document).on('submit', '#modal-add-modify-comment-form', function (e) {
            e.preventDefault()
            var $form = $(this)
            if ($form.find('[name=comment]').val() !== '') {
                var $reception = $form.find('[name=reception]')
                $.ajax({
                    url: $form.attr('action'),
                    method: $form.attr('method'),
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function (response) {
                        $.colorbox.close()
                        if (response.success) {
                            var $row = $('[data-reception-id="' + $reception.val() + '"]')
                            $row.find('.add-comment')
                                .removeClass('.add-comment').addClass('modify-comment')
                                .attr('title', 'Modifier le commentaire')
                                .data('comment', response.data.comment)
                                .html('<span class="fa fa-pencil-square"></span>')
                            $row.css('background', '#fdeec6')
                        } else {
                            alert('Une erreur est survenue')
                        }
                    },
                    error: function () {
                        $.colorbox.close()
                        alert('Une erreur est survenue')
                    }
                })
            } else {
                $form.find('[name=comment]').css('border-color', 'red')
            }
        })

        $.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['fr']))

        $('#datepik_from').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            maxDate: '0'
        }).change(function () {
            dt.draw()
        })

        $('#datepik_to').datepicker({
            showOn: 'both',
            buttonImage: '<?= $this->surl ?>/images/admin/calendar.gif',
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            maxDate: '0'
        }).change(function () {
            dt.draw()
        })
    })
</script>

<div id="contenu">
    <div class="row">
        <div class="col-md-6">
            <h1>Opérations emprunteurs</h1>
        </div>
        <div class="col-md-6">
            <a href="<?= $this->lurl ?>/transferts/emprunteurs/csv" class="btn-primary pull-right">Export CSV</a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class="form-group">
                <label for="datepik_from">Date début</label>
                <input type="text" name="date_from" id="datepik_from" class="form-control input-sm">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label for="datepik_to">Date fin</label>
                <input type="text" name="date_to" id="datepik_to" class="form-control input-sm">
            </div>
        </div>
    </div>
    <table id="receptions-table" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th style="width:50px">ID</th>
            <th>Motif</th>
            <th style="width:150px">Montant</th>
            <th style="width:150px">Attribution</th>
            <th style="width:100px">ID Client</th>
            <th style="width:100px">ID projet</th>
            <th style="width:100px">Date</th>
            <th style="width:100px">&nbsp;</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th style="width:50px">ID</th>
            <th>Motif</th>
            <th style="width:150px">Montant</th>
            <th style="width:150px">Attribution</th>
            <th style="width:100px">ID client</th>
            <th style="width:100px">ID projet</th>
            <th style="width:100px">Date</th>
            <th style="width:100px">&nbsp;</th>
        </tr>
        </tfoot>
    </table>
    <div class="hidden">
        <div id="modal-line" style="padding: 10px; min-width: 500px;">
            <div class="line"></div>
            <div class="text-right">
                <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">OK</button>
            </div>
        </div>
    </div>
    <div class="hidden">
        <div id="modal-comment" style="padding: 10px; min-width: 500px;">
            <form id="modal-add-modify-comment-form" method="POST" action="<?= $this->lurl ?>/transferts/comment">
                <input type="hidden" name="reception">
                <h1>Commenter l'opération</h1>
                <div class="form-group">
                    <label class="sr-only">Commentaire</label>
                    <textarea name="comment" rows="5" class="form-control"></textarea>
                </div>
                <div class="text-right">
                    <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">Annuler</button>
                    <button type="submit" class="btn-primary">Valider</button>
                </div>
            </form>
        </div>
    </div>
</div>
