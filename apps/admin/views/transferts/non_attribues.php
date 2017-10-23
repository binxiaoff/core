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
      }
    })

    $('.ignore-line-form').on('submit', function (event) {
      event.preventDefault()

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

    $('.reception-line').tooltip({
      position: {my: 'left top', at: 'right top'},
      content: function () {
        return $(this).prop('title')
      }
    })

    $('#receptions-table > thead > tr > th').tooltip({
      position: {my: 'top', at: 'bottom'}
    })

    $('.inline').tooltip({disabled: true})
    $('.inline').colorbox({inline: true})
  });
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
            <th>&nbsp;</th>
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
        <?php foreach ($this->nonAttributedReceptions as $reception) : ?>
            <div id="content-line-<?= $reception->getIdReception() ?>" style="white-space: nowrap; padding: 10px;"><?= $reception->getLigne() ?></div>
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
            <div id="ignore-line-<?= $reception->getIdReception() ?>" style="padding: 10px; min-width: 500px;">
                <form id="ignore-line-form-<?= $reception->getIdReception() ?>" class="ignore-line-form" method="POST" action="<?= $this->lurl ?>/transferts/ignore">
                    <input type="hidden" name="reception" value="<?= $reception->getIdReception() ?>">
                    <h1>Ignorer une opération</h1>
                    <div class="form-group">
                        <label for="ignore-line-comment-<?= $reception->getIdReception() ?>">Commentaire</label>
                        <textarea id="ignore-line-comment-<?= $reception->getIdReception() ?>" name="comment" rows="5" class="form-control"><?= $reception->getComment() ?></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn-default" onclick="$.fn.colorbox.close()">Annuler</button>
                        <button type="submit" class="btn-primary">Valider</button>
                    </div>
                </form>
            </div>
            <div id="comment-<?= $reception->getIdReception() ?>"><?= nl2br($reception->getComment()) ?></div>
        <?php endforeach; ?>
    </div>
</div>
