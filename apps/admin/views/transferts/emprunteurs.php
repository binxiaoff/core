<link href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css" type="text/css" rel="stylesheet">
<link href="<?= $this->lurl ?>/oneui/css/font-awesome.css" type="text/css" rel="stylesheet">
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
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script>
    $(function() {
        var dt = $('#receptions-table').DataTable({
            serverSide: true,
            processing: true,
            columnDefs: [
                {orderable: false, targets: [1, 3, 6]},
                {searchable: false, targets: [1, 2, 3, 5, 6]},
                {visible: false, targets: [7, 8]},
                {name: "idReception", "targets": 0},
                {name: "motif", "targets": 1},
                {name: "montant", "targets": 2},
                {name: "attribution", "targets": 3},
                {name: "idProject", "targets": 4},
                {name: "added", "targets": 5}
            ],
            order: [[ 0, "desc" ]],
            ajax: '/transferts/emprunteurs_attribues',
            language: {
                url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
            },
            createdRow: function ( row, data, index ) {
                var surl = '<?= $this->surl ?>'
                var $row = $(row)
                var receptionId = data[0]
                var comment = data[7]
                var line = data[8]
                var amount = data[2]
                var negative = (amount.replace(',', '.').replace(/[^\d\-\.]/g, '') < 0) ? true : false

                var addCommentBtn = '<a class="add-comment table-action" data-reception-id=' + receptionId + '" data-comment="' + comment + '" title="Commenter l\'opération">' +
                    '<span class="fa fa-pencil"></span>' +
                    '</a>'
                var showReceptionBtn = '<a class="show-reception table-action" data-reception-id="' + receptionId + '" data-line="' + line + '" title="Afficher l\'opération">' +
                    '<span class="fa fa-eye"></span>' +
                    '</a>'

                if (negative) {
                    $row.css('background', '#f9adb3')
                }

                if (comment !== null) {
                    addCommentBtn = '<a class="add-comment modify-comment table-action" data-reception-id=' + receptionId + '" data-comment="' + comment + '" title="Modifier le commentaire">' +
                    '<span class="fa fa-pencil-square"></span>' +
                    '</a>'
                    if (!negative)
                        $row.css('background', '#fdeec6')
                }
                $row.find('td:last-child').append(showReceptionBtn + addCommentBtn)
            }
        })
        dt.on('preDraw', function() {
            var $filter = $('#receptions-table_filter input')
            $filter.attr('placeholder', 'ID projet ou reception')
        })
        dt.on('draw', function() {
            $('.table-action').tooltip({
                position: {my: 'left top', at: 'right top'},
                content: function () {
                    return $(this).prop('title')
                }
            })
            var $filter = $('#receptions-table_filter')
            var $filterInput = $filter.find('input')
            var label = '<label>Rechercher par ID Projet ou ID reception<label>'
            $filter.html(label)
            $filter.append($filterInput)
        })

        $(document).on('click', '.table-action', function(){
            var $modal
            if ($(this).is('.add-comment') || $(this).is('.modify-comment')) {
                $modal = $('#modal-comment')
                var receptionId = $(this).data('reception-id')
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
            $.colorbox({html:$modal.html(), width: '50%'});
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
    <table id="receptions-table" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th style="width:50px">ID</th>
            <th>Motif</th>
            <th style="width:150px">Montant</th>
            <th style="width:150px">Attribution</th>
            <th style="width:100px">ID projet</th>
            <th style="width:100px">Date</th>
            <th style="width:100px">&nbsp;</th>
            <th style="width:100px">&nbsp;</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th style="width:50px">ID</th>
            <th>Motif</th>
            <th style="width:150px">Montant</th>
            <th style="width:150px">Attribution</th>
            <th style="width:100px">ID projet</th>
            <th style="width:100px">Date</th>
            <th style="width:100px">&nbsp;</th>
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
    </div>
</div>
