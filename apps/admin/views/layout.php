<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Unilend | Administration</title>
    <meta name="description" content="Unilend's administration back office">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="<?= $this->surl ?>/images/admin/favicon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400italic,600,700%7COpen+Sans:300,400,400italic,600,700">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/select2/select2.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/select2/select2-bootstrap.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/jquery-auto-complete/jquery.auto-complete.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/datatables/jquery.dataTables.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/summernote/summernote.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/js/plugins/bootstrap-treeview/bootstrap-treeview.min.css">

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/css/oneui.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/css/unilend.min.css">
    <link rel="stylesheet" href="<?= $this->url ?>/oneui/css/edits.css">
</head>
<body>
<div id="page-container" class="sidebar-l side-scroll header-navbar-fixed sidebar-o">
    <nav id="sidebar">
        <div id="sidebar-scroll">
            <div class="sidebar-content">
                <div class="side-header side-content">
                    <?php if ('prod' !== $this->getParameter('kernel.environment')) : ?>
                        <div class="environment bg-primary text-uppercase">
                            <span class="fa fa-info-circle"></span>
                            <span class="font-w300">Environnement:</span>
                            <span class="font-w600"><?= $this->getParameter('kernel.environment') ?></span>
                        </div>
                    <?php endif; ?>

                    <a class="h5 text-white pull-left" href="<?= $this->lurl ?>">
                        <img src="<?= $this->surl ?>/assets/images/logo/logo-unilend-52x52-purple.png" srcset="<?= $this->surl ?>/assets/images/logo/logo-unilend-52x52-purple@2x.png 2x" alt="Unilend">
                    </a>
                </div>
                <div class="side-content side-content-full">
                    <ul class="nav-main">
                        <?php
                        $menuHtml = '';
                        foreach (static::MENU as $item) {
                            $zone  = $item['zone'];
                            $title = $item['title'];

                            // Item visibility
                            if (in_array($zone, $this->lZonesHeader)) {
                                // Check user and adjust title for Dashboard item
                                if ($title === 'Dashboard') {
                                    if (in_array($_SESSION['user']['id_user_type'], [\users_types::TYPE_RISK, \users_types::TYPE_COMMERCIAL]) || in_array($_SESSION['user']['id_user'], [23, 26])) {
                                        $title = 'Mon flux';
                                    }
                                }
                                $active = $this->menu_admin === $zone ? ' class="open"' : '';
                                $submenu = empty($item['children']) ? '' : ' class="nav-submenu" data-toggle="nav-submenu"';

                                $menuHtml .= '<li'. $active .'>';
                                $menuHtml .= empty($item['uri']) ? '<a'. $submenu .'>' . $title . '</a>' : '<a href="' . $this->lurl . '/' . $item['uri'] . '"'. $submenu .'>' . $title . '</a>';

                                if (false === empty($item['children'])) {
                                    $menuHtml .= '<ul>';
                                    foreach ($item['children'] as $subItem) {
                                        if (false === isset($subItem['zone']) || in_array($subItem['zone'], $this->lZonesHeader)) {
                                            $menuHtml .= '<li><a href="' . $this->lurl . '/' . $subItem['uri'] . '">' . $subItem['title'] . '</a></li>';
                                        }
                                    }
                                    $menuHtml .= '</ul>';
                                }

                                $menuHtml .= '</li>';
                            }
                        }
                        ?>

                        <?= $menuHtml ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <header id="header-navbar" class="content-mini content-mini-full">
        <ul class="nav-header pull-right">
            <li>
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
                        <?= $_SESSION['user']['firstname'] ?> <?= $_SESSION['user']['name'] ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <a data-toggle="modal" data-target="#modal-profile-settings" type="button">
                                <i class="si si-settings pull-right"></i>Profil
                            </a>
                        </li>
                        <li>
                            <a href="https://admin.local.unilend.fr/users/edit_password/">
                                <i class="si si-lock pull-right"></i>Mot de passe
                            </a>
                        </li>
                        <li>
                            <a href="<?= $this->lurl ?>/logout">
                                <i class="si si-logout pull-right"></i>Se déconnecter
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <ul class="nav-header pull-left">
            <li class="header-search">
                <form class="form-horizontal" action="/dossiers" method="post">
                    <div class="input-group remove-margin-t remove-margin-b">
                        <input class="form-control" type="text" id="quick-search" name="quick-search" placeholder="Raison Sociale, Siren ou ID Projet">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                    </div>
                </form>
            </li>
        </ul>
    </header>
    <main id="main-container">
        <?php $this->fireView(); ?>
    </main>
</div>

<!--Modals-->
<div class="modal fade in" id="modal-profile-settings" role="dialog" aria-hidden="true">
    <form class="modal-dialog modal-sm" method="post" name="mod_users" id="mod_users" enctype="multipart/form-data" action="<?= $this->lurl ?>/users/edit_perso_user/<?= $this->users->id_user ?>" target="_parent" data-formvalidate>
        <div class="modal-content">
            <div class="block block-themed block-transparent remove-margin-b">
                <div class="block-header bg-primary-light">
                    <h3 class="block-title">Modifier <?= $this->users->firstname ?> <?= $this->users->name ?></h3>
                </div>
                <div class="block-content">
                    <div class="form-group">
                        <label>Pr&eacute;nom</label>
                        <input type="text" name="firstname" value="<?= $this->users->firstname ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="name" value="<?= $this->users->name ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>T&eacute;l&eacute;phone</label>
                        <input type="text" name="phone" value="<?= $this->users->phone ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?= $this->users->phone ?>" class="form-control required">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="form_mod_users" id="form_mod_users">
                <button class="btn btn-sm btn-default" type="button" data-dismiss="modal">Annuler</button>
                <button class="btn btn-sm btn-primary" type="submit">Valider</button>
            </div>
        </div>
    </form>
</div>

<!-- OneUI Core JS: jQuery, Bootstrap, slimScroll, scrollLock, Placeholder, Cookie and App.js -->
<script src="<?= $this->url ?>/oneui/js/core/jquery.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/bootstrap.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/jquery.scrollLock.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/jquery.slimscroll.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/jquery.placeholder.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/js.cookie.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/app.js"></script>

<!-- OneUI Plugins -->
<script src="<?= $this->url ?>/oneui/js/plugins/select2/select2.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/jquery-validation/jquery.validate.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/select2/select2.full.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/jquery-auto-complete/jquery.auto-complete.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/masked-inputs/jquery.maskedinput.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/summernote/summernote.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/plugins/bootstrap-treeview/bootstrap-treeview.min.js"></script>

<script>
    jQuery(function () {
        // Init page helpers
        App.initHelpers(['select2', 'datepicker', 'autocomplete', 'masked-inputs', 'summernote', 'table-tools', 'easy-pie-chart']);
    });
</script>
<script>
    $(function(){
        // Form Validation
        // Generic form validation - to be further developed
        $('[data-formvalidate]').submit(function(e) {
            var valid = true
            $(this).find('.required').each(function() {
                var $input = $(this)
                if ($input.is('input[type=text]') || $input.is('input[type=number]') || $input.is('input[type=email]') || $input.is('textarea')) {
                    if (!$input.val() || $input.val() === '') {
                        $input.closest('.form-group').removeClass('has-error').addClass('has-error')
                        valid = false
                    } else {
                        $input.closest('.form-group').removeClass('has-error')
                        valid = true
                    }
                }
                if ($input.is('select')) {
                    if ($input.val() === '' || $input.val() === '0' || $input.val() === 'Selectionner') {
                        $input.closest('.form-group').removeClass('has-error').addClass('has-error')
                        valid = false
                    } else {
                        $input.closest('.form-group').removeClass('has-error')
                        valid = true
                    }
                }
                if (!valid) {
                    e.preventDefault()
                }
            })
        })

        // jQuery AutoComplete example, for more examples you can check out https://github.com/Pixabay/jQuery-autoComplete
        $('.js-autocomplete').autoComplete({
            minChars: 1,
            source: function(term, suggest){
                term = term.toLowerCase()

                var $countriesList  = ['Afghanistan','Albania','Algeria','Andorra','Angola','Anguilla','Antigua &amp Barbuda','Argentina','Armenia','Aruba','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bermuda','Bhutan','Bolivia','Bosnia &amp Herzegovina','Botswana','Brazil','British Virgin Islands','Brunei','Bulgaria','Burkina Faso','Burundi','Cambodia','Cameroon','Cape Verde','Cayman Islands','Chad','Chile','China','Colombia','Congo','Cook Islands','Costa Rica','Cote D Ivoire','Croatia','Cruise Ship','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt','El Salvador','Equatorial Guinea','Estonia','Ethiopia','Falkland Islands','Faroe Islands','Fiji','Finland','France','French Polynesia','French West Indies','Gabon','Gambia','Georgia','Germany','Ghana','Gibraltar','Greece','Greenland','Grenada','Guam','Guatemala','Guernsey','Guinea','Guinea Bissau','Guyana','Haiti','Honduras','Hong Kong','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Isle of Man','Israel','Italy','Jamaica','Japan','Jersey','Jordan','Kazakhstan','Kenya','Kuwait','Kyrgyz Republic','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Macau','Macedonia','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Mauritania','Mauritius','Mexico','Moldova','Monaco','Mongolia','Montenegro','Montserrat','Morocco','Mozambique','Namibia','Nepal','Netherlands','Netherlands Antilles','New Caledonia','New Zealand','Nicaragua','Niger','Nigeria','Norway','Oman','Pakistan','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Puerto Rico','Qatar','Reunion','Romania','Russia','Rwanda','Saint Pierre &amp Miquelon','Samoa','San Marino','Satellite','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','South Africa','South Korea','Spain','Sri Lanka','St Kitts &amp Nevis','St Lucia','St Vincent','St. Lucia','Sudan','Suriname','Swaziland','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Thailand','Timor L\'Este','Togo','Tonga','Trinidad &amp Tobago','Tunisia','Turkey','Turkmenistan','Turks &amp Caicos','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Venezuela','Vietnam','Virgin Islands (US)','Yemen','Zambia','Zimbabwe']
                var $suggestions = []

                for (var $i = 0; $i < $countriesList.length; $i++) {
                    if (~ $countriesList[$i].toLowerCase().indexOf(term)) $suggestions.push($countriesList[$i])
                }

                suggest($suggestions)
            }
        })
        
        var $headerSearch = $('#quick-search')
        var $headerSearchForm = $headerSearch.closest('form')
        $headerSearch.autoComplete({
            minChars: 2,
            // Source must be updated later when using an ajax request instead of an array
            // source: function(term, response){
                // $.getJSON('/some/ajax/url/', {q: term}, function(data){ response(data) })
            // },
            source: function(term, suggest) {
                term = term.toLowerCase()
                
                var projects = [
                    {
                        company: 'Truquet Julien',
                        siren: 523774586,
                        projectId: 81663,
                        status: 'Remboursement',
                        amount: 35000,
                        period: 60
                    },
                    {
                        company: 'J.B. Hair Development',
                        siren: 443140587,
                        projectId: 81074,
                        status: 'Remboursement',
                        amount: 40000,
                        period: 36
                    },
                    {
                        company: 'Pharmacie Druel',
                        siren: 507479889,
                        projectId: 80241,
                        status: 'Remboursement Remboursement',
                        amount: 140000,
                        period: 36
                    },
                    {
                        company: 'Pharmacie Druel',
                        siren: 507479889,
                        projectId: 80241,
                        status: 'Remboursement',
                        amount: 60000,
                        period: 36
                    },
                    {
                        company: 'Macmatériel',
                        siren: 789874690,
                        projectId: 79515,
                        status: 'Remboursement',
                        amount: 40000,
                        period: 12
                    }
                ]

                var suggestions = []
                for (var i = 0; i < projects.length; i++) {
                    if ( ~ (projects[i].company + ' ' + projects[i].siren + ' ' + projects[i].projectId).toLowerCase().indexOf(term)) {
                        suggestions.push(projects[i])
                    }
                }
                suggest(suggestions)
            },
            renderItem: function (item, search) {
                return '<div class="autocomplete-suggestion" data-project-id="' + item.projectId + '">' +
                        '<p>' + item.company + '</p>' +
                        '<div class="list">' +
                            '<dl><dt>Siren</dt> <dd>' + item.siren + '</dd></dl>' +
                            '<dl><dt>Amount</dt> <dd>' + item.amount + ' €</dd></dl>' +
                            '<dl><dt>Duration</dt> <dd>' + item.period + ' mois</dd></dl>' +
                            '<dl><dt>Status</dt> <dd>' + item.status + '</dd></dl>' +
                        '</div>' +
                    '</div>'
            },
            onSelect: function(e, term, item){
                var company = item.find('p').text()
                var projectId = item.data('project-id')
                var redirectUrl = 'https://admin.local.unilend.fr' + '/dossiers/edit/' + projectId

                $headerSearch.val(company)
                window.location.href = redirectUrl
            }
        })

        $headerSearchForm.find('.input-group-addon').click(function(){
            $headerSearchForm.submit()
        })

        $headerSearchForm.submit(function() {
            var $form = $(this)
            var search = $headerSearch.val()

            $form.append('<input type="hidden" name="form_search_dossier" value="1">')

            if ($.isNumeric(search)) {
                if (search.length < 9) {
                    window.location.replace('/dossiers/edit/' + search)
                } else {
                    $form.append('<input type="hidden" name="siren" value="' + search + '">')
                }
            } else {
                $form.append('<input type="hidden" name="raison-sociale" value="' + search + '">')
            }
        })


        // jQuery MaskedInput example
        // a - Represents an alpha character (A-Z,a-z)
        // 9 - Represents a numeric character (0-9)
        // * - Represents an alphanumeric character (A-Z,a-z,0-9)
        $("#example-masked-iban").mask("aa99 9999 9999 9999 9999 9999 9999")

        // Data Tables
        // DataTables Bootstrap integration
        var bsDataTables = function() {
            var $DataTable = $.fn.dataTable

            // Set the defaults for DataTables init
            $.extend( true, $DataTable.defaults, {
                dom:
                "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-6'i><'col-sm-6'p>>",
                renderer: 'bootstrap',
                oLanguage: {
                    sLengthMenu: "_MENU_",
                    sInfo: "Showing <strong>_START_</strong>-<strong>_END_</strong> of <strong>_TOTAL_</strong>",
                    oPaginate: {
                        sPrevious: '<i class="fa fa-angle-left"></i>',
                        sNext: '<i class="fa fa-angle-right"></i>'
                    }
                }
            })

            // Default class modification
            $.extend($DataTable.ext.classes, {
                sWrapper: "dataTables_wrapper form-inline dt-bootstrap",
                sFilterInput: "form-control",
                sLengthSelect: "form-control"
            })

            // Bootstrap paging button renderer
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
                                    btnDisplay = '&hellip'
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

            // TableTools Bootstrap compatibility - Required TableTools 2.1+
            if ($DataTable.TableTools) {
                // Set the classes that TableTools uses to something suitable for Bootstrap
                $.extend(true, $DataTable.TableTools.classes, {
                    "container": "DTTT btn-group",
                    "buttons": {
                        "normal": "btn btn-default",
                        "disabled": "disabled"
                    },
                    "collection": {
                        "container": "DTTT_dropdown dropdown-menu",
                        "buttons": {
                            "normal": "",
                            "disabled": "disabled"
                        }
                    },
                    "print": {
                        "info": "DTTT_print_info"
                    },
                    "select": {
                        "row": "active"
                    }
                })

                // Have the collection use a bootstrap compatible drop down
                $.extend(true, $DataTable.TableTools.DEFAULTS.oTags, {
                    "collection": {
                        "container": "ul",
                        "button": "li",
                        "liner": "a"
                    }
                })
            }
        }
        bsDataTables()

        // Simple
        $('.js-dataTable-simple').dataTable({
            pageLength: 5,
            lengthMenu: [[5, 10], [15, 20]],
            searching: false,
            dom:
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-6'i><'col-sm-6'p>>"
        })

        $('.js-dataTable-advanced').dataTable({
            columnDefs: [ { orderable: false } ],
            pageLength: 4,
            lengthMenu: [[5, 10], [5, 10]]
        })

        // TREEVIEW
        var $treeData = [
            {
                text: 'Bootstrap',
                href: '#parent1',
                tags: ['4'],
                nodes: [
                    {
                        text: 'eLearning',
                        href: '#child1',
                        tags: ['2'],
                        nodes: [
                            {
                                text: 'Code',
                                href: '#grandchild1'
                            },
                            {
                                text: 'Tutorials',
                                href: '#grandchild2'
                            }
                        ]
                    },
                    {
                        text: 'Templates',
                        href: '#child2'
                    },
                    {
                        text: 'CSS',
                        href: '#child3',
                        tags: ['2'],
                        nodes: [
                            {
                                text: 'Less',
                                href: '#grandchild3'
                            },
                            {
                                text: 'SaSS',
                                href: '#grandchild4'
                            }
                        ]
                    }
                ]
            },
            {
                text: 'Design',
                href: '#parent3'
            },
            {
                text: 'Coding',
                href: '#parent4'
            },
            {
                text: 'Marketing',
                href: '#parent5'
            }
        ]

        $('.js-tree-simple').treeview({
            data: $treeData,
            color: '#555',
            expandIcon: 'fa fa-plus',
            collapseIcon: 'fa fa-minus',
            onhoverColor: '#f9f9f9',
            selectedColor: '#555',
            selectedBackColor: '#f1f1f1',
            showBorder: false,
            levels: 1
        })

        $('.js-tree-badges').treeview({
            data: $treeData,
            color: '#555',
            expandIcon: 'fa fa-plus',
            collapseIcon: 'fa fa-minus',
            nodeIcon: 'fa fa-folder text-primary',
            onhoverColor: '#f9f9f9',
            selectedColor: '#555',
            selectedBackColor: '#f1f1f1',
            showTags: true,
            levels: 1
        })
    })
</script>
</body>
</html>
