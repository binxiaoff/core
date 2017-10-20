<link href="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css" type="text/css" rel="stylesheet">
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
    .dataTables_paginate ul.pagination {
        margin: 12px 0;
        white-space: nowrap;
        float: right;
    }
    .dataTables_paginate ul.pagination > li {
        display: block;
        float: left;
        margin: 0 0 5px 5px;
    }
    .pagination>.active>a, .pagination>.active>span, .pagination>.active>a:hover, .pagination>.active>span:hover, .pagination>.active>a:focus, .pagination>.active>span:focus {
        color: #b20066;
        -webkit-box-shadow: 0 2px #b20066;
        box-shadow: 0 2px #b20066;
    }
</style>
<script src="<?= $this->lurl ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script>
    $(function() {
        var $DataTable = $.fn.dataTable
        $.extend( true, $DataTable.defaults, {
            dom:
            "<'row'<'col-md-6'l><'col-md-6'f>>" +
            "<'row'<'col-md-12'tr>>" +
            "<'row'<'col-md-6'i><'col-md-6'p>>",
            renderer: 'bootstrap',
            oLanguage: {
                sLengthMenu: "_MENU_",
                sProcessing:     "Traitement en cours...",
                sSearch:         "Rechercher&nbsp;:",
                sLengthMenu:     "Afficher _MENU_ &eacute;l&eacute;ments",
                sInfo:           "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                sInfoEmpty:      "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
                sInfoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                sInfoPostFix:    "",
                sLoadingRecords: "Chargement en cours...",
                sZeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
                sEmptyTable:     "Aucune donn&eacute;e disponible dans le tableau",
                oPaginate: {
                    sFirst:      "Premier",
                    sPrevious:   "Pr&eacute;c&eacute;dent",
                    sNext:       "Suivant",
                    sLast:       "Dernier"
                },
                oPaginate: {
                    sPrevious: '<i class="fa fa-angle-left"></i>',
                    sNext: '<i class="fa fa-angle-right"></i>'
                }
            }
        })
        $.extend($DataTable.ext.classes, {
            sWrapper: "dataTables_wrapper form-inline dt-bootstrap",
            sFilterInput: "form-control",
            sLengthSelect: "form-control"
        })
        $DataTable.ext.renderer.pageButton.bootstrap = function (settings, host, idx, buttons, page, pages) {
            var api     = new $DataTable.Api(settings)
            var classes = settings.oClasses
            var lang    = settings.oLanguage.oPaginate
            var btnDisplay, btnClass

            var attach = function (container, buttons) {
                var i, ien, node, button
                var clickHandler = function (e) {
                    e.preventDefault()
                    if (!jQuery(e.currentTarget).hasClass('disabled')) {
                        api.page(e.data.action).draw(false)
                    }
                }
                for (i = 0, ien = buttons.length; i < ien; i++) {
                    button = buttons[i]
                    if ($.isArray(button)) {
                        attach(container, button)
                    }
                    else {
                        btnDisplay = ''
                        btnClass = ''
                        switch (button) {
                            case 'ellipsis':
                                btnDisplay = '...'
                                btnClass = 'disabled'
                                break
                            case 'first':
                                btnDisplay = lang.sFirst
                                btnClass = button + (page > 0 ? '' : ' disabled')
                                break
                            case 'previous':
                                btnDisplay = lang.sPrevious
                                btnClass = button + (page > 0 ? '' : ' disabled')
                                break
                            case 'next':
                                btnDisplay = lang.sNext
                                btnClass = button + (page < pages - 1 ? '' : ' disabled')
                                break
                            case 'last':
                                btnDisplay = lang.sLast
                                btnClass = button + (page < pages - 1 ? '' : ' disabled')
                                break
                            default:
                                btnDisplay = button + 1
                                btnClass = page === button ?
                                    'active' : ''
                                break
                        }
                        if (btnDisplay) {
                            node = jQuery('<li>', {
                                'class': classes.sPageButton + ' ' + btnClass,
                                'aria-controls': settings.sTableId,
                                'tabindex': settings.iTabIndex,
                                'id': idx === 0 && typeof button === 'string' ?
                                settings.sTableId + '_' + button :
                                    null
                            })
                                .append(jQuery('<a>', {
                                        'href': '#'
                                    })
                                        .html(btnDisplay)
                                )
                                .appendTo(container)

                            settings.oApi._fnBindAction(
                                node, {action: button}, clickHandler
                            )
                        }
                    }
                }
            }
            attach(
                jQuery(host).empty().html('<ul class="pagination"/>').children('ul'),
                buttons
            )
        }
        $('#receptions-table').DataTable({
            serverSide: true,
            processing: true,
            columnDefs: [
                {orderable: false, targets: [1, 3, 6]},
                {visible: false, targets: 7},
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
                console.log(data)
                //                $('.reception-line').tooltip({
                //                    position: {my: 'left top', at: 'right top'},
                //                    content: function () {
                //                        return $(this).prop('title')
                //                    }
                //                })
                //                $('.inline').tooltip({disabled: true})
                //                $('.inline').colorbox({inline: true, width: '50%'})
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
