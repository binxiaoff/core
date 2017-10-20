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

    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
        top: 7px !important;
    }
</style>
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script>
    $(function() {
        $('#receptions-table').DataTable({
            serverSide: true,
            processing: true,
            columnDefs: [
                {orderable: false, targets: [0, 1, 6]},
                {searchable: false, targets: 6},
                {visible: false, targets: 7}
            ]
            ajax: '/transferts/emprunteurs_attribues',
            language: {
                url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
            },
           initComplete: function () {
             $('.reception-line').tooltip({
               position: {my: 'left top', at: 'right top'},
               content: function () {
                 return $(this).prop('title')
               }
             })

             $('.inline').tooltip({disabled: true})
             $('.inline').colorbox({inline: true, width: '50%'})
           }

        })
    });
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
        </tr>
        </tfoot>
    </table>
    <div class="hidden">

            <div id="inline-content-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px; background:#fff;"><?= $reception->getLigne() ?></div>
            <div id="comment-line-<?= $reception->getIdReception() ?>" style="padding: 10px; min-width: 500px;">
                <form id="comment-line-form-<?= $reception->getIdReception() ?>" class="comment-line-form" method="POST" action="<?= $this->lurl ?>/transferts/comment">
                    <input type="hidden" name="reception" value="<?= $reception->getIdReception() ?>">
                    <input type="hidden" name="referer" value="<?= $_SERVER['REQUEST_URI'] ?>">
                    <h1>Commenter une opération</h1>
                    <div class="form-group">
                        <label for="comment-line-comment-<?= $reception->getIdReception() ?>" class="sr-only">Commentaire</label>
                        <textarea id="comment-line-comment-<?= $reception->getIdReception() ?>" name="comment" rows="5" class="form-control"><?= $reception->getComment() ?></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">Annuler</button>
                        <button type="submit" class="btn-primary">Valider</button>
                    </div>
                </form>
            </div>
    </div>
</div>
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css">
