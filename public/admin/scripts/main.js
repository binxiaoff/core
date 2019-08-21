$(function () {
    $('.thickbox').colorbox({fixed: true})

    $(document).on('cbox_complete', function () {
        // When colorbox is loading iframe, it doesn't autosize itself correctly
        var $cboxIframe = $('#cboxLoadedContent .cboxIframe')

        // If it exists, resize to 90% of window's width/height
        if ($cboxIframe.length) {
            var cboxIframeWin = $cboxIframe[0].contentWindow
            var winWidth = (cboxIframeWin.outerWidth >= window.outerWidth
                ? window.outerWidth * 0.9
                : cboxIframeWin.outerWidth)
            var winHeight = (cboxIframeWin.outerHeight >= window.outerHeight
                ? window.outerHeight * 0.9
                : cboxIframeWin.outerHeight)

            $.colorbox.resize({
                width: winWidth,
                height: winHeight
            })
        }
    })

    if (typeof $.fn.dataTable !== 'undefined') {
        var $DataTable = $.fn.dataTable
        $.extend(true, $DataTable.defaults, {
            dom:
                "<'row'<'col-md-6'l><'col-md-6'f>>" +
                "<'row'<'col-md-12'tr>>" +
                "<'row'<'col-md-6'i><'col-md-6'p>>",
            renderer: 'bootstrap',
            oLanguage: {
                sProcessing: "Traitement en cours...",
                sSearch: "Rechercher &nbsp;:",
                sLengthMenu: "Afficher _MENU_ &eacute;l&eacute;ments",
                sInfo: "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
                sInfoEmpty: "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ment",
                sInfoFiltered: "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
                sInfoPostFix: "",
                sLoadingRecords: "Chargement en cours...",
                sZeroRecords: "Aucun &eacute;l&eacute;ment &agrave; afficher",
                sEmptyTable: "Aucune donn&eacute;e disponible dans le tableau",
                oPaginate: {
                    sFirst: "Premier",
                    sPrevious: '<i class="fa fa-angle-left"></i>',
                    sNext: '<i class="fa fa-angle-right"></i>',
                    sLast: "Dernier",
                },
            }
        })
        $.extend($DataTable.ext.classes, {
            sWrapper: "dataTables_wrapper form-inline dt-bootstrap",
            sFilterInput: "form-control",
            sLengthSelect: "form-control"
        })
        $DataTable.ext.renderer.pageButton.bootstrap = function (settings, host, idx, buttons, page, pages) {
            var api = new $DataTable.Api(settings)
            var classes = settings.oClasses
            var lang = settings.oLanguage.oPaginate
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
                    } else {
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
    }
})

function no_cache() {
    date_object = new Date();
    var param = date_object.getTime();

    return param;
}

function AjaxObject() {
    if (window.XMLHttpRequest) {
        xhr_object = new XMLHttpRequest();
        return xhr_object;
    }
    else if (window.ActiveXObject) {
        xhr_object = new ActiveXObject('Microsoft.XMLHTTP');
        return xhr_object;
    }
    else {
        alert('Votre navigateur ne supporte pas les objets XMLHTTPRequest...');

    }
}

/* Fonction AJAX chargement des noms de la section de traduction */
function loadNomTexte(section) {
    if (section != "") {
        xhr_object = AjaxObject();
        var param = no_cache();

        xhr_object.onreadystatechange = function () {
            if (xhr_object.readyState != 4) {
                document.getElementById('listeNomTraduction').innerHTML = '<img src="' + add_url + '/images/ajax-loader.gif">';
            }
            if (xhr_object.readyState == 4 && xhr_object.status == 200) {
                var reponse = xhr_object.responseText;
                document.getElementById('btnAjouterTraduction').style.display = 'block';
                document.getElementById('btnAjouterTraduction').href = add_url + '/traductions/add/' + section;
                document.getElementById('listeNomTraduction').innerHTML = reponse;
                document.getElementById('elementTraduction').innerHTML = '';
            }
        };
        xhr_object.open('GET', add_url + '/ajax/loadNomTexte/' + section + '/' + param, true);
        xhr_object.send(null);
    }
    else {
        document.getElementById('listeNomTraduction').innerHTML = '';
        document.getElementById('elementTraduction').innerHTML = '';
        document.getElementById('btnAjouterTraduction').style.display = 'none';
    }
}

/* Fonction AJAX chargement des traductions de la section de traduction */
function loadTradTexte(nom, section) {
    if (nom != "") {
        xhr_object = AjaxObject();
        var param = no_cache();

        xhr_object.onreadystatechange = function () {
            if (xhr_object.readyState != 4) {
                document.getElementById('elementTraduction').innerHTML = '<img src="' + add_url + '/images/ajax-loader.gif">';
            }
            if (xhr_object.readyState == 4 && xhr_object.status == 200) {
                var reponse = xhr_object.responseText;
                document.getElementById('elementTraduction').innerHTML = reponse;
            }
        };
        xhr_object.open('GET', add_url + '/ajax/loadTradTexte/' + nom + '/' + section + '/' + param, true);
        xhr_object.send(null);
    }
    else {
        document.getElementById('elementTraduction').innerHTML = '';
    }
}
