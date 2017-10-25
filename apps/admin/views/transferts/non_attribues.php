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
                {orderable: false, targets: [1, 4, 5]},
                {searchable: false, targets: [1, 2, 3, 4, 5, 6]},
                {visible: false, targets: [6]},
                {name: "idReception", "targets": 0},
                {name: "motif", "targets": 1},
                {name: "montant", "targets": 2},
                {name: "added", "targets": 3},
                {name: "code", "targets": 4}
            ],
            order: [[0, "desc"]],
            ajax: '/transferts/get_non_attribues',
            language: {
                url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
            },
            createdRow: function (row, data, index) {
                console.log(data)
                var $row = $(row)
                var receptionId = data[0]
                var comment = data[5]
                var line = data[6]
                var amount = data[2]
                var negative = (amount.replace(',', '.').replace(/[^\d\-\.]/g, '') < 0) ? true : false

                var attachReceptionBtn = '<a class="attach-reception thickbox" href="<?= $this->lurl ?>/transferts/attribution/'+ receptionId +'" title="Attribuer l\'operation">' +
                    '<span class="fa fa-check"></span>' +
                    '</a>'
                var ignoreReceptionBtn = '<a class="ignore-reception table-action" data-reception-id="' + receptionId + '" title="Ignorer l\'operation">' +
                    '<span class="fa fa-close"></span>' +
                    '</a>'
                var showReceptionBtn = '<a class="show-reception table-action" data-reception-id="' + receptionId + '" data-line="' + line + '" title="Afficher l\'opération">' +
                    '<span class="fa fa-eye"></span>' +
                    '</a>'
                var addCommentBtn = '<a class="add-comment table-action" data-reception-id="' + receptionId + '" data-comment="' + comment + '" title="Commenter l\'opération">' +
                    '<span class="fa fa-pencil"></span>' +
                    '</a>'

                if (negative) {
                    $row.css('background', '#f9adb3')
                }
                if (comment !== null && comment !== '') {
                    addCommentBtn = '<a class="add-comment modify-comment table-action" data-reception-id="' + receptionId + '" data-comment="' + comment + '" title="Modifier le commentaire">' +
                        '<span class="fa fa-pencil-square"></span>' +
                        '</a>'
                    if (!negative)
                        $row.css('background', '#fdeec6')
                }
                $row.attr('data-reception-id', receptionId)
                $row.find('td:last-child').append(attachReceptionBtn + ignoreReceptionBtn + showReceptionBtn + addCommentBtn)
                $row.find('.thickbox').colorbox()
            }
        })

        $(document).on('click', '.table-action', function(){
            var $modal
            var receptionId = $(this).data('reception-id')

            if ($(this).is('.ignore-reception')) {
                $modal = $('#modal-ignore-reception')
                $modal.find('[name=reception]').val(receptionId)
            }

            if ($(this).is('.add-comment') || $(this).is('.modify-comment')) {
                $modal = $('#modal-add-modify-comment')
                var comment = $(this).data('comment')
                $modal.find('[name=comment]').html(comment)
                $modal.find('[name=reception]').val(receptionId)
                if ($(this).is('.modify-comment')) {
                    $modal.find('h1').text('Modifier le commentaire')
                } else {
                    $modal.find('h1').text('Ajouter un commentaire')
                }
            }

            if ($(this).is('.show-reception')) {
                $modal = $('#modal-show-reception')
                $modal.find('.line').html($(this).data('line'))
            }

            $.colorbox({html:$modal.html(), width: '50%'})
        })

        $(document).on('submit', '#modal-ignore-reception-form', function(e){
            e.preventDefault()
            var $form = $(this)
            var $reception = $form.find('[name=reception]')
            $.ajax({
                url: $form.attr('action'),
                method: $form.attr('method'),
                data: $form.serialize(),
                success: function (response) {
                    $.colorbox.close()
                    if (response === 'ok') {
                        $('tr[data-reception-id=' + $reception.val() + ']').fadeOut()
                    } else {
                        alert('Une erreur est survenue')
                    }
                },
                error: function () {
                    $.colorbox.close()
                    alert('Une erreur est survenue')
                }
            })
        })
    })
</script>
<div id="contenu">
    <h1>Opérations non affectées</h1>
    <table id="receptions-table" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>ID</th>
            <th>Motif</th>
            <th>Montant</th>
            <th>Date</th>
            <th title="Code interbancaire (Cfonb120)">Code</th>
            <th style="width: 120px">&nbsp;</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th>ID</th>
            <th>Motif</th>
            <th>Montant</th>
            <th>Date</th>
            <th title="Code interbancaire (Cfonb120)">Code</th>
            <th>&nbsp;</th>
        </tr>
        </tfoot>
    </table>
    <div class="hidden">
        <div id="modal-show-reception" style="padding: 10px; min-width: 500px;">
            <div class="line"></div>
            <div class="text-right">
                <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">OK</button>
            </div>
        </div>
        <div id="modal-add-modify-comment" style="padding: 10px; min-width: 500px;">
            <form method="POST" action="<?= $this->lurl ?>/transferts/comment">
                <input type="hidden" name="reception">
                <input type="hidden" name="referer" value="<?= $_SERVER['REQUEST_URI'] ?>">
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
        <div id="modal-ignore-reception" style="padding: 10px; min-width: 500px;">
            <form id="modal-ignore-reception-form" method="POST" action="<?= $this->lurl ?>/transferts/ignore">
                <input type="hidden" name="reception">
                <h1>Ignorer l'operation</h1>
                <div class="form-group">
                    <label>Commentaire</label>
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

