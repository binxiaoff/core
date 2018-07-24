//*************************//
// *** FICHIER JS ADMIN ***//
//*************************//

// MEMO COMPONENT
var Memo = function($trigger) {
    var self = this
    self.$elem = $($trigger.data('memo'))

    if (self.$elem.length === 0 || self.$elem[0].hasOwnProperty('Memo')) {
        return false
    }

    self.$elem.addClass('memo-editor')
    self.textarea = self.$elem.attr('id') + '-textarea'
    self.optional = false
    if ($trigger.attr('data-memo-optional') !== undefined) {
        self.optional = true
    }

    self.track = {
        open: false,
        projectId: $trigger.data('memo-project-id'),
        submitUrl: $trigger.data('memo-onsubmit'),
        startupFocus: $trigger.data('memo') !== '#abandon-project-memo'
    }

    var existingHmtl = self.$elem.html()
    if (existingHmtl) self.$elem.html('')

    var html = '<form method="post" action="' + self.track.submitUrl + '">' +
                    '<div class="existing">' + existingHmtl + '</div>' +
                    '<textarea id="' + self.textarea + '" name="comment"></textarea>' +
                    '<input type="hidden" name="projectId" value="' + self.track.projectId + '">' +
                    '<label><input type="radio" name="public" value="0" checked> Privé </label> ' +
                    '<label><input type="radio" name="public" value="1"> Public</label>' +
                    '<div class="controls text-right">' +
                        '<button type="button" data-memo-close class="btn-default">Annuler</button>' +
                        '<button type="submit" data-memo-submit class="btn-primary">Valider</button>' +
                    '</div>' +
                '</form>'

    self.$elem.append(html)

    self.$textarea = $('#' + self.textarea)

    self.$elem[0].Memo = self
}
Memo.prototype.open = function () {
    var self = this

    if (self.track.open) {
        self.close()
        return false
    }

    if ($('.memo-editor').length > 1) {
        $('.memo-editor:not("#'+self.$elem.attr('id')+'")').each(function() {
            $(this)[0].Memo.close()
        })
    }

    // By default, memos are private
    self.$elem.find('.controls input[name="public"]').each(function() {
        if ($(this).val() === '0')
            $(this).attr('checked', true).prop('checked', true)
    })
    // Open the memo textarea
    self.$elem.slideDown(300, function() {
        CKEDITOR.replace(self.textarea, {
            height: 170,
            width: '100%',
            toolbar: 'Basic',
            removePlugins: 'elementspath',
            resize_enabled: false,
            startupFocus: self.track.startupFocus
        })
        // Set focus on abandon reason select instead of memo textarea
        var $abandonReason = $('#reason')
        if ($abandonReason.length) {
            var $select2 = $abandonReason.select2({
                width: 'resolve',
                dropdownParent: $('#abandon-project-memo')
            })
            $select2.select2('open')
        }

        self.track.open = true
    })
}
Memo.prototype.submit = function() {
    var self = this

    var comment = CKEDITOR.instances[self.textarea].getData()

    if (comment === '' && !self.optional) {
        alert('Veuillez écrire un mémo.')
        return false
    }

    self.$textarea.html(comment)

    if (self.track.submitUrl === 'suspensive') {
        valid_rejete_etape7('4', self.track.projectId)
        return false
    } else if (self.track.submitUrl === 'add') {
        $.ajax({
            url: add_url + '/dossiers/memo',
            method: 'POST',
            dataType: 'html',
            data: {
                projectId: self.track.projectId,
                content: comment,
                public: self.$elem.find('[name="public"]:checked').val()
            },
            success: function(response) {
                $('#table_memo').html(response)
                self.close()
            }
        });
    } else if (self.track.submitUrl.indexOf('abandon') > -1) {
        if (!self.$elem.find('.select').val()) {
            alert('Merci de choisir un motif d\'abandon.')
            return false
        } else {
            self.$elem.find('form').submit()
        }
    } else {
        self.$elem.find('form').submit()
    }
}
Memo.prototype.close = function() {
    var self = this
    self.$elem.slideUp(300, function() {
        if (CKEDITOR.instances[self.textarea]) {
            CKEDITOR.instances[self.textarea].destroy(true)
            self.$textarea.val('')
            self.track.open = false
        }
    })
}

/* Elements Jquery */
$(document).ready(function() {
    $('.thickbox').colorbox({fixed: true})

    // Load colorbox in iframe mode
    $('.colorbox-iframe').colorbox({
      // fastIframe: false,
      iframe: true,
      initialWidth: 300,
      initialHeight: 300
    })

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

    $('.extract_rib_btn').colorbox({
        onComplete: function() {
            var tmpImg = new Image()
            tmpImg.src = $('#colorbox').find('img').attr('src')
            tmpImg.onload = function() {
                var origHeight = this.height + 300
                var origWidth = this.width + 50
                if (origWidth > 1080) {
                    origWidth = 1080
                }
                if (origWidth < 600) {
                    origWidth = 600
                }
                $.colorbox.resize({height: origHeight, width: origWidth})
            }
        }
    })

    $('[data-memo]').each(function(){
        var $trigger = $(this)
        new Memo($trigger)
    })

    $(document).on('click', '[data-memo]', function() {
        var $target = $($(this).data('memo'))
        $target[0].Memo.open()
    })
    $(document).on('click', '[data-memo-close]', function() {
        var $target = $(this).parents('.memo-editor')
        $target[0].Memo.close()
    })
    $(document).on('click', '[data-memo-submit]', function(event) {
        var $target = $(this).parents('.memo-editor')
        var submitFunction = $('[data-memo=#' + $target[0].id + ']').data('memo-onsubmit')
        $target[0].Memo.submit(submitFunction)
        event.preventDefault()

    })

    if (typeof $.fn.dataTable !== 'undefined') {
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
                sSearch:         "Rechercher &nbsp;:",
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
    }
})

// Prevent double-click
$(document).on('click', '[data-prevent-doubleclick]', function(e) {
    if ($(this).data('prevent-doubleclick') === 'preventClick') {
        e.preventDefault()
        console.log('preventing')
    } else {
        $(this).data('prevent-doubleclick', 'preventClick')
        window.setTimeout(function(){
            $(this).data('prevent-doubleclick', false)
        }, 1000)
    }
})

/* Changer l'onglet de la langue */
function changeOngletLangue(lng)
{
    var lang_encours = document.getElementById('lng_encours').value;

    document.getElementById('lng_encours').value = lng;
    document.getElementById('lien_'+lang_encours).className = '';
    document.getElementById('lien_'+lng).className = 'active';
    document.getElementById('langue_'+lang_encours).style.display = 'none';
    document.getElementById('langue_'+lng).style.display = 'block';
}

/* Set du nouvel ID parent pour les 2 langues */
function setNewIdParent(id)
{
    document.getElementById('id_parent').value = id;
}

/* Chack formulaire edition user */
function checkFormModifUser()
{
    if(document.getElementById('email').value == '')
    {
        alert("Vous devez indiquer une adresse e-mail !");
        return false;
    }

    return true;
}

/* Check du formulaire d'ajout d'un user */
function checkFormAjoutUser()
{
    if(document.getElementById('email').value == '')
    {
        alert("Vous devez indiquer une adresse e-mail !");
        return false;
    }
    else if(document.getElementById('firstname').value == '')
    {
        alert("Vous devez indiquer un prenom !");
        return false;
    }
    else if(document.getElementById('name').value == '')
    {
        alert("Vous devez indiquer un nom de famille !");
        return false;
    }

    return true;
}

/* Ajout détail produit */
function ajouterDetails(div)
{
    var nb_encours = parseInt(document.getElementById(div).value);

    // Attribution du nouveau nombre
    document.getElementById(div).value = nb_encours + 1;
    var nb_new = parseInt(document.getElementById(div).value);

    // Affichage de la ligne correspondante
    document.getElementById('contenuDetails'+nb_new).style.display = 'block';

    if(nb_new == 10)
    {
        document.getElementById('lienAjoutDetails').style.display = 'none';
    }
}

// check BIC
function check_bic(bic)
{
    var regSWIFT = /^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/;
    if(regSWIFT.test(bic) == false){

        return false;
    }
    else{
        return true
    }
}

// check nombre caractere iban
function check_ibanNB(id,val,nb){
    id = "#"+id;
    if(val.length < nb){$(id).addClass('LV_invalid_field');$(id).removeClass('LV_valid_field');}
    else{$(id).addClass('LV_valid_field');$(id).removeClass('LV_invalid_field');}
}

// check validation iban
function validateIban(iban) {
    var ibanValidationModulo = 97; // On utilise var au lieu de const, qui présente des incompatibilités sur IE8

    // On force les caractères alphabétiques en majuscule
    iban = iban.toUpperCase();
    // on supprime les espaces
    iban = iban.replace(new RegExp(" ", "g"), "");

    // le code iban doit faire plus de 14 caractères
    if (iban.length < 15) {
        return false;
    }

    // puis on transfert les quatre premiers caractères en fin de chaine.
    modifiedIban = iban.substring(4, iban.length)+iban.substr(0,4);

    // On convertit les caractères alphabétiques en valeur numérique
    numericIbanString = "";
    for (var index = 0; index < modifiedIban.length; index ++) {
        currentChar = modifiedIban.charAt(index);
        currentCharCode = modifiedIban.charCodeAt(index);

        // si le caractère est un digit, on le recopie
        if ((currentCharCode > 47) && (currentCharCode <  58)) {
            numericIbanString = numericIbanString + currentChar;
        }
        // si le caractère est une lettre, on le converti en valeur
        else if ((currentCharCode > 64) && (currentCharCode < 91)) {
            value = currentCharCode-65+10;
            numericIbanString = numericIbanString + value;
        }
        // sinon, le code iban est invalide (caractère invalide).
        else {
            return false;
        }
    }

    // On a maintenant le code iban converti en un nombre. Un très gros nombre.
    // Tellement gros que javascript ne peut pas le gérer.
    // Pour calculer le modulo, il faut donc y aller par étapes :
    // on découpe le nombre en blocs de 5 chiffres.
    // Pour chaque bloc, on préfixe avec le modulo du bloc précédent (2 chiffres max,
    // ce qui nous fait un nombre de 7 chiffres max, gérable par javascript).
    var previousModulo = 0;
    for (var index = 0; index < numericIbanString.length; index += 5) {
        subpart = previousModulo+""+numericIbanString.substr(index, 5);
        previousModulo = subpart % ibanValidationModulo;
    }

    return previousModulo == 1;
}

function initAutocompleteCity(elmCity, elmCode)
{
    $([elmCode, elmCity]).each(function() {
        var getBirthPlace= '';
        if (elmCity.data('autocomplete') == 'birth_city') {
            getBirthPlace = 'birthplace/';
        }
        $(this).autocomplete({
            source: add_url + '/ajax/get_cities/' + getBirthPlace,
            minLength: 3,

            search: function( event, ui ) {
                if ($(this).data('autocomplete') == 'birth_city'){
                    elmCode.val('');
                }
            },

            select: function( event, ui ) {
                event.preventDefault();

                var myRegexp = /(.+)\s\((.+)\)/;
                var match = myRegexp.exec(ui.item.label);

                if(match != null) {
                    elmCity.val(match[1]);
                    if ($(this).data('autocomplete') == 'birth_city'){
                        elmCode.val(ui.item.value);
                    } else {
                        elmCode.val(match[2]);
                    }
                }
            }
        });
    });
}
