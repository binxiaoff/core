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
</head>
<body>
<div id="page-container" class="sidebar-l side-scroll header-navbar-fixed sidebar-o">
    <nav id="sidebar">
        <div class="slimScrollDiv">
            <div id="sidebar-scroll">
                <div class="sidebar-content">
                    <div class="side-header side-content bg-white-op">
                        <a class="h5 text-white" href="<?= $this->lurl ?>">
                            <img src="<?= $this->surl ?>/styles/default/images/logo.png" alt="Unilend">
                        </a>
                    </div>
                    <div class="side-content side-content-full">
                        <ul class="nav-main">
                            <li>
                                <a href="base_pages_dashboard.php"><i class="si si-speedometer"></i><span class="sidebar-mini-hide">Dashboard</span></a>
                            </li>
                            <li class="nav-main-heading"><span class="sidebar-mini-hide">Prêteurs</span></li>
                            <li>
                                <a href="#"><i class="si si-badge"></i><span class="sidebar-mini-hide">UI Elements</span></a>
                                <a href="#"><i class="si si-badge"></i><span class="sidebar-mini-hide">UI Elements</span></a>
                                <a href="#"><i class="si si-badge"></i><span class="sidebar-mini-hide">UI Elements</span></a>
                            </li>
                            <li class="nav-main-heading"><span class="sidebar-mini-hide">Emprunteurs</span></li>
                            <li>
                                <a href="#"><i class="si si-badge"></i><span class="sidebar-mini-hide">UI Elements</span></a>
                                <a href="#"><i class="si si-badge"></i><span class="sidebar-mini-hide">UI Elements</span></a>
                                <a href="#"><i class="si si-badge"></i><span class="sidebar-mini-hide">UI Elements</span></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="slimScrollBar"></div>
            <div class="slimScrollRail"></div>
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
                        <li class="dropdown-header">Profile</li>
                        <li>
                            <a tabindex="-1" href="base_pages_inbox.php">
                                <i class="si si-envelope-open pull-right"></i>
                                <span class="badge badge-primary pull-right">3</span>Inbox
                            </a>
                        </li>
                        <li>
                            <a tabindex="-1" href="base_pages_profile.php">
                                <i class="si si-user pull-right"></i>
                                <span class="badge badge-success pull-right">1</span>Profile
                            </a>
                        </li>
                        <li>
                            <a tabindex="-1" href="javascript:void(0)">
                                <i class="si si-settings pull-right"></i>Settings
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Actions</li>
                        <li>
                            <a tabindex="-1" href="base_pages_lock.php">
                                <i class="si si-lock pull-right"></i>Lock Account
                            </a>
                        </li>
                        <li>
                            <a tabindex="-1" href="<?= $this->lurl ?>/logout">
                                <i class="si si-logout pull-right"></i>Se déconnecter
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
        <ul class="nav-header pull-left">
            <li class="visible-xs">
                <button class="btn btn-default" data-toggle="class-toggle" data-target=".js-header-search" data-class="header-search-xs-visible" type="button">
                    <i class="fa fa-search"></i>
                </button>
            </li>
            <li class="js-header-search header-search">
                <form class="form-horizontal" action="base_pages_search.php" method="post">
                    <div class="input-group remove-margin-t remove-margin-b">
                        <input class="form-control" type="text" id="quick-search" name="quick-search" placeholder="Recherche ...">
                        <span class="input-group-addon"><i class="si si-magnifier"></i></span>
                    </div>
                </form>
            </li>
        </ul>
    </header>
    <main id="main-container">
        <?php $this->fireView(); ?>
    </main>
</div>

<!-- OneUI Core JS: jQuery, Bootstrap, slimScroll, scrollLock, Placeholder, Cookie and App.js -->
<script src="<?= $this->url ?>/oneui/js/core/jquery.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/bootstrap.min.js"></script>
<script src="<?= $this->url ?>/oneui/js/core/jquery.scrollLock.min.js"></script>
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
        $('.js-validation').validate({
            ignore: [],
            errorClass: 'help-block',
            errorElement: 'div',
            errorPlacement: function(error, e) {
                $(e).parents('.form-group').append(error);
            },
            highlight: function(e) {
                var elem = $(e);

                elem.closest('.form-group').removeClass('has-error').addClass('has-error');
                elem.closest('.help-block').remove();
            },
            success: function(e) {
                var elem = $(e);

                elem.closest('.form-group').removeClass('has-error');
                elem.closest('.help-block').remove();
            },
            rules: {
                'val-name': {
                    required: true,
                    minlength: 3
                },
                'val-email': {
                    required: true,
                    email: true
                },
                'val-message': {
                    required: true,
                    minlength: 50
                },
                'val-select': {
                    required: true
                },
                'val-select2': {
                    required: true
                },
                'val-select2-multiple': {
                    required: true,
                    minlength: 2
                }
            },
            messages: {
                'val-name': {
                    required: 'Please enter a username',
                    minlength: 'Your username must consist of at least 3 characters'
                },
                'val-email': 'Please enter a valid email address',
                'val-message': {
                    required: 'Please write a message',
                    minlength: 'Message should be at least 50 characters'
                },
                'val-select2': 'Please select a value!',
                'val-select': 'Please select a value!',
                'val-select2-multiple': 'Please select at least 2 values!'
            }
        });

        // jQuery AutoComplete example, for more examples you can check out https://github.com/Pixabay/jQuery-autoComplete
        $('.js-autocomplete').autoComplete({
            minChars: 1,
            source: function(term, suggest){
                term = term.toLowerCase();

                var $countriesList  = ['Afghanistan','Albania','Algeria','Andorra','Angola','Anguilla','Antigua &amp; Barbuda','Argentina','Armenia','Aruba','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bermuda','Bhutan','Bolivia','Bosnia &amp; Herzegovina','Botswana','Brazil','British Virgin Islands','Brunei','Bulgaria','Burkina Faso','Burundi','Cambodia','Cameroon','Cape Verde','Cayman Islands','Chad','Chile','China','Colombia','Congo','Cook Islands','Costa Rica','Cote D Ivoire','Croatia','Cruise Ship','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt','El Salvador','Equatorial Guinea','Estonia','Ethiopia','Falkland Islands','Faroe Islands','Fiji','Finland','France','French Polynesia','French West Indies','Gabon','Gambia','Georgia','Germany','Ghana','Gibraltar','Greece','Greenland','Grenada','Guam','Guatemala','Guernsey','Guinea','Guinea Bissau','Guyana','Haiti','Honduras','Hong Kong','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Isle of Man','Israel','Italy','Jamaica','Japan','Jersey','Jordan','Kazakhstan','Kenya','Kuwait','Kyrgyz Republic','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Macau','Macedonia','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Mauritania','Mauritius','Mexico','Moldova','Monaco','Mongolia','Montenegro','Montserrat','Morocco','Mozambique','Namibia','Nepal','Netherlands','Netherlands Antilles','New Caledonia','New Zealand','Nicaragua','Niger','Nigeria','Norway','Oman','Pakistan','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Puerto Rico','Qatar','Reunion','Romania','Russia','Rwanda','Saint Pierre &amp; Miquelon','Samoa','San Marino','Satellite','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','South Africa','South Korea','Spain','Sri Lanka','St Kitts &amp; Nevis','St Lucia','St Vincent','St. Lucia','Sudan','Suriname','Swaziland','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Thailand','Timor L\'Este','Togo','Tonga','Trinidad &amp; Tobago','Tunisia','Turkey','Turkmenistan','Turks &amp; Caicos','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Venezuela','Vietnam','Virgin Islands (US)','Yemen','Zambia','Zimbabwe'];
                var $suggestions    = [];

                for ($i = 0; $i < $countriesList.length; $i++) {
                    if (~ $countriesList[$i].toLowerCase().indexOf(term)) $suggestions.push($countriesList[$i]);
                }

                suggest($suggestions);
            }
        });

        // jQuery MaskedInput example
        // a - Represents an alpha character (A-Z,a-z)
        // 9 - Represents a numeric character (0-9)
        // * - Represents an alphanumeric character (A-Z,a-z,0-9)
        $("#example-masked-iban").mask("aa99 9999 9999 9999 9999 9999 9999");

        // Data Tables
        // DataTables Bootstrap integration
        var bsDataTables = function() {
            var $DataTable = $.fn.dataTable;

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
            });

            // Default class modification
            $.extend($DataTable.ext.classes, {
                sWrapper: "dataTables_wrapper form-inline dt-bootstrap",
                sFilterInput: "form-control",
                sLengthSelect: "form-control"
            });

            // Bootstrap paging button renderer
            $DataTable.ext.renderer.pageButton.bootstrap = function (settings, host, idx, buttons, page, pages) {
                var api     = new $DataTable.Api(settings);
                var classes = settings.oClasses;
                var lang    = settings.oLanguage.oPaginate;
                var btnDisplay, btnClass;

                var attach = function (container, buttons) {
                    var i, ien, node, button;
                    var clickHandler = function (e) {
                        e.preventDefault();
                        if (!jQuery(e.currentTarget).hasClass('disabled')) {
                            api.page(e.data.action).draw(false);
                        }
                    };

                    for (i = 0, ien = buttons.length; i < ien; i++) {
                        button = buttons[i];

                        if ($.isArray(button)) {
                            attach(container, button);
                        }
                        else {
                            btnDisplay = '';
                            btnClass = '';

                            switch (button) {
                                case 'ellipsis':
                                    btnDisplay = '&hellip;';
                                    btnClass = 'disabled';
                                    break;

                                case 'first':
                                    btnDisplay = lang.sFirst;
                                    btnClass = button + (page > 0 ? '' : ' disabled');
                                    break;

                                case 'previous':
                                    btnDisplay = lang.sPrevious;
                                    btnClass = button + (page > 0 ? '' : ' disabled');
                                    break;

                                case 'next':
                                    btnDisplay = lang.sNext;
                                    btnClass = button + (page < pages - 1 ? '' : ' disabled');
                                    break;

                                case 'last':
                                    btnDisplay = lang.sLast;
                                    btnClass = button + (page < pages - 1 ? '' : ' disabled');
                                    break;

                                default:
                                    btnDisplay = button + 1;
                                    btnClass = page === button ?
                                        'active' : '';
                                    break;
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
                                    .appendTo(container);

                                settings.oApi._fnBindAction(
                                    node, {action: button}, clickHandler
                                );
                            }
                        }
                    }
                };

                attach(
                    jQuery(host).empty().html('<ul class="pagination"/>').children('ul'),
                    buttons
                );
            };

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
                });

                // Have the collection use a bootstrap compatible drop down
                $.extend(true, $DataTable.TableTools.DEFAULTS.oTags, {
                    "collection": {
                        "container": "ul",
                        "button": "li",
                        "liner": "a"
                    }
                });
            }
        };
        bsDataTables()

        // Simple
        $('.js-dataTable-simple').dataTable({
            pageLength: 5,
            lengthMenu: [[5, 10], [15, 20]],
            searching: false,
            dom:
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-6'i><'col-sm-6'p>>"
        });

        $('.js-dataTable-advanced').dataTable({
            columnDefs: [ { orderable: false } ],
            pageLength: 4,
            lengthMenu: [[5, 10], [5, 10]]
        });

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
        ];

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
        });

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
        });
    })
</script>
</body>
</html>
