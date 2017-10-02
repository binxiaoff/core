/*
 *  Document   : app.js
 *  Author     : pixelcave
 *  Description: UI Framework Custom Functionality (available to all pages)
 *
 */

var App = function() {
    // Helper variables - set in uiInit()
    var $lHtml, $lBody, $lPage, $lSidebar, $lSidebarScroll, $lSideOverlay, $lSideOverlayScroll, $lHeader, $lSearch, $lMain, $lFooter;

    /*
     ********************************************************************************************
     *
     * BASE UI FUNCTIONALITY
     *
     * Functions which handle vital UI functionality such as main navigation and layout
     * They are auto initialized in every page
     *
     *********************************************************************************************
     */

    // User Interface init
    var uiInit = function() {
        // Set variables
        $lHtml              = jQuery('html');
        $lBody              = jQuery('body');
        $lPage              = jQuery('#page-container');
        $lSidebar           = jQuery('#sidebar');
        $lSidebarScroll     = jQuery('#sidebar-scroll');
        $lSideOverlay       = jQuery('#side-overlay');
        $lSideOverlayScroll = jQuery('#side-overlay-scroll');
        $lHeader            = jQuery('#header-navbar');
        $lSearch            = $lHeader.find('#quick-search');
        $lMain              = jQuery('#main-container');
        $lFooter            = jQuery('#page-footer');

        // Initialize Tooltips
        jQuery('[data-toggle="tooltip"], .js-tooltip').tooltip({
            container: 'body',
            animation: false
        });

        // Initialize Popovers
        jQuery('[data-toggle="popover"], .js-popover').popover({
            container: 'body',
            animation: true,
            trigger: 'hover'
        });

        // Initialize Tabs
        jQuery('[data-toggle="tabs"] a, .js-tabs a').click(function(e){
            e.preventDefault();
            jQuery(this).tab('show');
        });

        // Init form placeholder (for IE9)
        jQuery('.form-control').placeholder();
    };

    // Layout functionality
    var uiLayout = function() {
        // Resizes #main-container min height (push footer to the bottom)
        var $resizeTimeout;

        if ($lMain.length) {
            uiHandleMain();

            jQuery(window).on('resize orientationchange', function(){
                clearTimeout($resizeTimeout);

                $resizeTimeout = setTimeout(function(){
                    uiHandleMain();
                }, 150);
            });
        }

        // Init sidebar and side overlay custom scrolling
        uiHandleScroll('init');

        // Init transparent header functionality (solid on scroll - used in frontend)
        if ($lPage.hasClass('header-navbar-fixed') && $lPage.hasClass('header-navbar-transparent')) {
            jQuery(window).on('scroll', function(){
                if (jQuery(this).scrollTop() > 20) {
                    $lPage.addClass('header-navbar-scroll');
                } else {
                    $lPage.removeClass('header-navbar-scroll');
                }
            });
        }

        // Call layout API on button click
        jQuery('[data-toggle="layout"]').on('click', function(){
            var $btn = jQuery(this);

            uiLayoutApi($btn.data('action'));

            if ($lHtml.hasClass('no-focus')) {
                $btn.blur();
            }
        });
    };

    // Resizes #main-container to fill empty space if exists
    var uiHandleMain = function() {
        var $hWindow     = jQuery(window).height();
        var $hHeader     = $lHeader.outerHeight();
        var $hFooter     = $lFooter.outerHeight();

        if ($lPage.hasClass('header-navbar-fixed')) {
            $lMain.css('min-height', $hWindow - $hFooter);
        } else {
            $lMain.css('min-height', $hWindow - ($hHeader + $hFooter));
        }
    };

    // Handles sidebar and side overlay custom scrolling functionality
    var uiHandleScroll = function($mode) {
        var $windowW = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

        // Init scrolling
        if ($mode === 'init') {
            // Init scrolling only if required the first time
            uiHandleScroll();

            // Handle scrolling on resize or orientation change
            var $sScrollTimeout;

            jQuery(window).on('resize orientationchange', function(){
                clearTimeout($sScrollTimeout);

                $sScrollTimeout = setTimeout(function(){
                    uiHandleScroll();
                }, 150);
            });
        } else {
            // If screen width is greater than 991 pixels and .side-scroll is added to #page-container
            if ($windowW > 991 && $lPage.hasClass('side-scroll')) {
                // Turn scroll lock off (sidebar and side overlay - slimScroll will take care of it)
                jQuery($lSidebar).scrollLock('disable');
                jQuery($lSideOverlay).scrollLock('disable');

                // If sidebar scrolling does not exist init it..
                if ($lSidebarScroll.length && (!$lSidebarScroll.parent('.slimScrollDiv').length)) {

                    $lSidebarScroll.slimScroll({
                        height: $lSidebar.outerHeight(),
                        color: '#fff',
                        size: '5px',
                        opacity : .35,
                        wheelStep : 15,
                        distance : '2px',
                        railVisible: false,
                        railOpacity: 1
                    });
                }
                else { // ..else resize scrolling height
                    $lSidebarScroll
                        .add($lSidebarScroll.parent())
                        .css('height', $lSidebar.outerHeight());
                }

                // If side overlay scrolling does not exist init it..
                if ($lSideOverlayScroll.length && (!$lSideOverlayScroll.parent('.slimScrollDiv').length)) {
                    $lSideOverlayScroll.slimScroll({
                        height: $lSideOverlay.outerHeight(),
                        color: '#000',
                        size: '5px',
                        opacity : .35,
                        wheelStep : 15,
                        distance : '2px',
                        railVisible: false,
                        railOpacity: 1
                    });
                }
                else { // ..else resize scrolling height
                    $lSideOverlayScroll
                        .add($lSideOverlayScroll.parent())
                        .css('height', $lSideOverlay.outerHeight());
                }
            } else {
                // Turn scroll lock on (sidebar and side overlay)
                jQuery($lSidebar).scrollLock('enable');
                jQuery($lSideOverlay).scrollLock('enable');

                // If sidebar scrolling exists destroy it..
                if ($lSidebarScroll.length && $lSidebarScroll.parent('.slimScrollDiv').length) {
                    $lSidebarScroll
                        .slimScroll({destroy: true});
                    $lSidebarScroll
                        .attr('style', '');
                }

                // If side overlay scrolling exists destroy it..
                if ($lSideOverlayScroll.length && $lSideOverlayScroll.parent('.slimScrollDiv').length) {
                    $lSideOverlayScroll
                        .slimScroll({destroy: true});
                    $lSideOverlayScroll
                        .attr('style', '');
                }
            }
        }
    };

    // Layout API
    var uiLayoutApi = function($mode) {
        var $windowW = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

        // Mode selection
        switch($mode) {
            case 'sidebar_pos_toggle':
                $lPage.toggleClass('sidebar-l sidebar-r');
                break;
            case 'sidebar_pos_left':
                $lPage
                    .removeClass('sidebar-r')
                    .addClass('sidebar-l');
                break;
            case 'sidebar_pos_right':
                $lPage
                    .removeClass('sidebar-l')
                    .addClass('sidebar-r');
                break;
            case 'sidebar_toggle':
                if ($windowW > 991) {
                    $lPage.toggleClass('sidebar-o');
                } else {
                    $lPage.toggleClass('sidebar-o-xs');
                }
                break;
            case 'sidebar_open':
                if ($windowW > 991) {
                    $lPage.addClass('sidebar-o');
                } else {
                    $lPage.addClass('sidebar-o-xs');
                }
                break;
            case 'sidebar_close':
                if ($windowW > 991) {
                    $lPage.removeClass('sidebar-o');
                } else {
                    $lPage.removeClass('sidebar-o-xs');
                }
                break;
            case 'sidebar_mini_toggle':
                if ($windowW > 991) {
                    $lPage.toggleClass('sidebar-mini');
                }
                break;
            case 'sidebar_mini_on':
                if ($windowW > 991) {
                    $lPage.addClass('sidebar-mini');
                }
                break;
            case 'sidebar_mini_off':
                if ($windowW > 991) {
                    $lPage.removeClass('sidebar-mini');
                }
                break;
            case 'side_overlay_toggle':
                $lPage.toggleClass('side-overlay-o');
                break;
            case 'side_overlay_open':
                $lPage.addClass('side-overlay-o');
                break;
            case 'side_overlay_close':
                $lPage.removeClass('side-overlay-o');
                break;
            case 'side_overlay_hoverable_toggle':
                $lPage.toggleClass('side-overlay-hover');
                break;
            case 'side_overlay_hoverable_on':
                $lPage.addClass('side-overlay-hover');
                break;
            case 'side_overlay_hoverable_off':
                $lPage.removeClass('side-overlay-hover');
                break;
            case 'header_fixed_toggle':
                $lPage.toggleClass('header-navbar-fixed');
                break;
            case 'header_fixed_on':
                $lPage.addClass('header-navbar-fixed');
                break;
            case 'header_fixed_off':
                $lPage.removeClass('header-navbar-fixed');
                break;
            case 'side_scroll_toggle':
                $lPage.toggleClass('side-scroll');
                uiHandleScroll();
                break;
            case 'side_scroll_on':
                $lPage.addClass('side-scroll');
                uiHandleScroll();
                break;
            case 'side_scroll_off':
                $lPage.removeClass('side-scroll');
                uiHandleScroll();
                break;
            default:
                return false;
        }
    };

    // Main navigation functionality
    var uiNav = function() {
        // When a submenu link is clicked
        jQuery('[data-toggle="nav-submenu"]').on('click', function(e){
            // Get link
            var $link = jQuery(this);

            // Get link's parent
            var $parentLi = $link.parent('li');

            if ($parentLi.hasClass('open')) { // If submenu is open, close it..
                $parentLi.removeClass('open');
            } else { // .. else if submenu is closed, close all other (same level) submenus first before open it
                $link
                    .closest('ul')
                    .find('> li')
                    .removeClass('open');

                $parentLi
                    .addClass('open');
            }

            // Remove focus from submenu link
            if ($lHtml.hasClass('no-focus')) {
                $link.blur();
            }

            return false;
        });
    };

    // Header Search Autocomplete
    var uiQuickSearch = function(){
        $lSearch.autoComplete({
            minChars: 2,
            cache: 0, // Doesn't work with any other value (when using sections in suggestions)
            menuClass: 'header-autocomplete',
            delay: 500,
            source: function(term, suggest){
                $.getJSON('/ajax/quick_search/', {q: term}, function (response){ suggest(response) })
            },
            renderItem: function (item, search, section) {
                var html = ''
                if (section === 'projects') {
                    html += '<div class="autocomplete-suggestion" data-val="' + item.title + '">' +
                        '<p>' + item.title + ' <span class="id">' + item.id + '</span></p>' +
                        '<div class="details">' +
                        '<dl><dt>SIREN</dt> <dd>' + item.siren + '</dd></dl>' +
                        '<dl><dt>Montant</dt> <dd>' + item.amount + ' €</dd></dl>' +
                        '<dl><dt>Durée</dt> <dd>' + item.duration + ' mois</dd></dl>' +
                        '<dl><dt>Statut</dt> <dd>' + item.status + '</dd></dl>' +
                        '</div>'
                }
                if (section === 'lenders') {
                    html += '<div class="autocomplete-suggestion" data-val="' + item.name + '">' +
                        '<p>' + item.name + ' <span class="id">' + item.id + ' </span></p>' +
                        '<div class="details">' +
                        '<dl><dd>' + item.type + '</dd></dl>' +
                        '</div>'
                }
                html += '</div>'
                return html
            },
            onSelect: function(e, term, item){
                var title = item.find('p').text()
                var id = item.find('.id').text()
                var section = item.parent().attr('class')

                $lSearch.val(title.replace(id, ''))

                if (~section.indexOf('projects')) {
                    window.location.replace('/dossiers/edit/' + id.match(/\d+/))
                } else if (~section.indexOf('lenders')) {
                    window.location.replace('/preteurs/edit/' + id.match(/\d+/))
                }
                $('.autocomplete-suggestions').hide()
            },
            sections: true,
            sectionMaxResults: 5,
            sectionShowMore: function(section, term) {
                var btnText = ''
                var html = '<form class="show-more" method="post" action="'
                if (section === 'projects') {
                    btnText  = 'projets'
                    html += '/dossiers">'
                    html += '<input type="hidden" name="form_search_dossier" value="1">'
                    if ($.isNumeric(term)) {
                        html += '<input type="hidden" name="siren" value="' + term + '">'
                    } else {
                        html += '<input type="hidden" name="raison-sociale" value="' + term + '">'
                    }
                }
                if (section === 'lenders') {
                    btnText  = 'prêteurs'
                    html += '/preteurs/gestion">'
                    html += '<input type="hidden" name="form_search_preteur" value="1">'
                    if ($.isNumeric(term)) {
                        html += '<input type="hidden" name="id" value="' + term + '">'
                    } else {
                        html += '<input type="hidden" name="nom" value="' + term + '">'
                    }
                }
                html += '<button type="submit" class="btn btn-default btn-sm"><span class="fa fa-search"></span> Plus de ' + btnText + '</button>'
                html += '</form>'
                return html
            }
        })
    };

    // Blocks options functionality
    var uiBlocks = function() {
        // Init default icons fullscreen and content toggle buttons
        uiBlocksApi(false, 'init');

        // Call blocks API on option button click
        jQuery('[data-toggle="block-option"]').on('click', function(){
            uiBlocksApi(jQuery(this).closest('.block'), jQuery(this).data('action'));
        });
    };

    // Blocks API
    var uiBlocksApi = function($block, $mode) {
        // Set default icons for fullscreen and content toggle buttons
        var $iconFullscreen         = 'si si-size-fullscreen';
        var $iconFullscreenActive   = 'si si-size-actual';
        var $iconContent            = 'si si-arrow-up';
        var $iconContentActive      = 'si si-arrow-down';

        if ($mode === 'init') {
            // Auto add the default toggle icons to fullscreen and content toggle buttons
            jQuery('[data-toggle="block-option"][data-action="fullscreen_toggle"]').each(function(){
                var $this = jQuery(this);

                $this.html('<i class="' + (jQuery(this).closest('.block').hasClass('block-opt-fullscreen') ? $iconFullscreenActive : $iconFullscreen) + '"></i>');
            });

            jQuery('[data-toggle="block-option"][data-action="content_toggle"]').each(function(){
                var $this = jQuery(this);

                $this.html('<i class="' + ($this.closest('.block').hasClass('block-opt-hidden') ? $iconContentActive : $iconContent) + '"></i>');
            });
        } else {
            // Get block element
            var $elBlock = ($block instanceof jQuery) ? $block : jQuery($block);

            // If element exists, procceed with blocks functionality
            if ($elBlock.length) {
                // Get block option buttons if exist (need them to update their icons)
                var $btnFullscreen  = jQuery('[data-toggle="block-option"][data-action="fullscreen_toggle"]', $elBlock);
                var $btnToggle      = jQuery('[data-toggle="block-option"][data-action="content_toggle"]', $elBlock);

                // Mode selection
                switch($mode) {
                    case 'fullscreen_toggle':
                        $elBlock.toggleClass('block-opt-fullscreen');

                        // Enable/disable scroll lock to block
                        if ($elBlock.hasClass('block-opt-fullscreen')) {
                            jQuery($elBlock).scrollLock('enable');
                        } else {
                            jQuery($elBlock).scrollLock('disable');
                        }

                        // Update block option icon
                        if ($btnFullscreen.length) {
                            if ($elBlock.hasClass('block-opt-fullscreen')) {
                                jQuery('i', $btnFullscreen)
                                    .removeClass($iconFullscreen)
                                    .addClass($iconFullscreenActive);
                            } else {
                                jQuery('i', $btnFullscreen)
                                    .removeClass($iconFullscreenActive)
                                    .addClass($iconFullscreen);
                            }
                        }
                        break;
                    case 'fullscreen_on':
                        $elBlock.addClass('block-opt-fullscreen');

                        // Enable scroll lock to block
                        jQuery($elBlock).scrollLock('enable');

                        // Update block option icon
                        if ($btnFullscreen.length) {
                            jQuery('i', $btnFullscreen)
                                .removeClass($iconFullscreen)
                                .addClass($iconFullscreenActive);
                        }
                        break;
                    case 'fullscreen_off':
                        $elBlock.removeClass('block-opt-fullscreen');

                        // Disable scroll lock to block
                        jQuery($elBlock).scrollLock('disable');

                        // Update block option icon
                        if ($btnFullscreen.length) {
                            jQuery('i', $btnFullscreen)
                                .removeClass($iconFullscreenActive)
                                .addClass($iconFullscreen);
                        }
                        break;
                    case 'content_toggle':
                        $elBlock.toggleClass('block-opt-hidden');

                        // Update block option icon
                        if ($btnToggle.length) {
                            if ($elBlock.hasClass('block-opt-hidden')) {
                                jQuery('i', $btnToggle)
                                    .removeClass($iconContent)
                                    .addClass($iconContentActive);
                            } else {
                                jQuery('i', $btnToggle)
                                    .removeClass($iconContentActive)
                                    .addClass($iconContent);
                            }
                        }
                        break;
                    case 'content_hide':
                        $elBlock.addClass('block-opt-hidden');

                        // Update block option icon
                        if ($btnToggle.length) {
                            jQuery('i', $btnToggle)
                                .removeClass($iconContent)
                                .addClass($iconContentActive);
                        }
                        break;
                    case 'content_show':
                        $elBlock.removeClass('block-opt-hidden');

                        // Update block option icon
                        if ($btnToggle.length) {
                            jQuery('i', $btnToggle)
                                .removeClass($iconContentActive)
                                .addClass($iconContent);
                        }
                        break;
                    case 'refresh_toggle':
                        $elBlock.toggleClass('block-opt-refresh');

                        // Return block to normal state if the demostration mode is on in the refresh option button - data-action-mode="demo"
                        if (jQuery('[data-toggle="block-option"][data-action="refresh_toggle"][data-action-mode="demo"]', $elBlock).length) {
                            setTimeout(function(){
                                $elBlock.removeClass('block-opt-refresh');
                            }, 2000);
                        }
                        break;
                    case 'state_loading':
                        $elBlock.addClass('block-opt-refresh');
                        break;
                    case 'state_normal':
                        $elBlock.removeClass('block-opt-refresh');
                        break;
                    case 'close':
                        $elBlock.hide();
                        break;
                    case 'open':
                        $elBlock.show();
                        break;
                    default:
                        return false;
                }
            }
        }
    };

    // Set active color themes functionality
    var uiHandleTheme = function() {
        var $cssTheme = jQuery('#css-theme');
        var $cookies  = $lPage.hasClass('enable-cookies') ? true : false;

        // If cookies are enabled
        if ($cookies) {
            var $theme  = Cookies.get('colorTheme') ? Cookies.get('colorTheme') : false;

            // Update color theme
            if ($theme) {
                if ($theme === 'default') {
                    if ($cssTheme.length) {
                        $cssTheme.remove();
                    }
                } else {
                    if ($cssTheme.length) {
                        $cssTheme.attr('href', $theme);
                    } else {
                        jQuery('#css-main')
                            .after('<link rel="stylesheet" id="css-theme" href="' + $theme + '">');
                    }
                }
            }

            $cssTheme = jQuery('#css-theme');
        }

        // Set the active color theme link as active
        jQuery('[data-toggle="theme"][data-theme="' + ($cssTheme.length ? $cssTheme.attr('href') : 'default') + '"]')
            .parent('li')
            .addClass('active');

        // When a color theme link is clicked
        jQuery('[data-toggle="theme"]').on('click', function(){
            var $this   = jQuery(this);
            var $theme  = $this.data('theme');

            // Set this color theme link as active
            jQuery('[data-toggle="theme"]')
                .parent('li')
                .removeClass('active');

            jQuery('[data-toggle="theme"][data-theme="' + $theme + '"]')
                .parent('li')
                .addClass('active');

            // Update color theme
            if ($theme === 'default') {
                if ($cssTheme.length) {
                    $cssTheme.remove();
                }
            } else {
                if ($cssTheme.length) {
                    $cssTheme.attr('href', $theme);
                } else {
                    jQuery('#css-main')
                        .after('<link rel="stylesheet" id="css-theme" href="' + $theme + '">');
                }
            }

            $cssTheme = jQuery('#css-theme');

            // If cookies are enabled, save the new active color theme
            if ($cookies) {
                Cookies.set('colorTheme', $theme, { expires: 7 });
            }
        });
    };

    // Scroll to element animation helper
    var uiScrollTo = function() {
        jQuery('[data-toggle="scroll-to"]').on('click', function(){
            var $this           = jQuery(this);
            var $target         = $this.data('target');
            var $speed          = $this.data('speed') ? $this.data('speed') : 1000;
            var $headerHeight   = ($lHeader.length && $lPage.hasClass('header-navbar-fixed')) ? $lHeader.outerHeight() : 0;

            jQuery('html, body').animate({
                scrollTop: jQuery($target).offset().top - $headerHeight
            }, $speed);
        });
    };

    // Toggle class helper
    var uiToggleClass = function() {
        jQuery('[data-toggle="class-toggle"]').on('click', function(){
            var $el = jQuery(this);

            jQuery($el.data('target').toString()).toggleClass($el.data('class').toString());

            if ($lHtml.hasClass('no-focus')) {
                $el.blur();
            }
        });
    };

    // Add the correct copyright year
    var uiYearCopy = function() {
        var $date       = new Date();
        var $yearCopy   = jQuery('.js-year-copy');

        if ($date.getFullYear() === 2015) {
            $yearCopy.html('2015');
        } else {
            $yearCopy.html('2015-' + $date.getFullYear().toString().substr(2,2));
        }
    };

    // Manage page loading screen functionality
    var uiLoader = function($mode) {
        var $lpageLoader = jQuery('#page-loader');

        if ($mode === 'show') {
            if ($lpageLoader.length) {
                $lpageLoader.fadeIn(250);
            } else {
                $lBody.prepend('<div id="page-loader"></div>');
            }
        } else if ($mode === 'hide') {
            if ($lpageLoader.length) {
                $lpageLoader.fadeOut(250);
            }
        }

        return false;
    };

    // Collapse details of alerts
    var uiAlertCollapse = function() {
        $alert = jQuery('.alert')
        $alert.each(function(){
            if ($(this).find('ul.hide').length) {
                $(this).addClass('alert-collapse').append('<button></button>').find('ul.hide').removeClass('hide')
                $(this).find('button').click(function(){
                    var $alert = $(this).closest('.alert')
                    var $target = $alert.find('ul')
                    if ($target.is(':visible'))
                        $target.slideUp(150)
                    else
                        $target.slideDown(150)
                })
            }
        })
    };


    /*
     ********************************************************************************************
     *
     * UI HELPERS (ON DEMAND)
     *
     * Third party plugin inits or various custom user interface helpers to extend functionality
     * They need to be called in a page to be initialized. They are included here to be easy to
     * init them on demand on multiple pages (usually repeated init code in common components)
     *
     ********************************************************************************************
     */

    /*
     * Print Page functionality
     *
     * App.initHelper('print-page');
     *
     */
    var uiHelperPrint = function() {
        // Store all #page-container classes
        var $pageCls = $lPage.prop('class');

        // Remove all classes from #page-container
        $lPage.prop('class', '');

        // Print the page
        window.print();

        // Restore all #page-container classes
        $lPage.prop('class', $pageCls);
    };

    /*
     * Custom Table functionality such as section toggling or checkable rows
     *
     * App.initHelper('table-tools');
     *
     */

    // Table sections functionality
    var uiHelperTableToolsSections = function(){
        // For each table
        jQuery('.js-table-sections').each(function(){
            var $table = jQuery(this);

            // When a row is clicked in tbody.js-table-sections-header
            jQuery('.js-table-sections-header > tr', $table).on('click', function(e) {
                var $row    = jQuery(this);
                var $tbody  = $row.parent('tbody');

                if (! $tbody.hasClass('open')) {
                    jQuery('tbody', $table).removeClass('open');
                }

                $tbody.toggleClass('open');
            });
        });
    };

    // Data Tables
    var uiHelperDataTables = function() {
        // DataTables Bootstrap integration
        var $DataTable = $.fn.dataTable
        $.extend( true, $DataTable.defaults, {
            dom:
            "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-6'i><'col-sm-6'p>>",
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
        // Currency sorting
        $.extend($DataTable.ext.oSort, {
            'formatted-num-pre': function (a) {
                a = (a === '-' || a === '') ? 0 : a.replace(',', '.').replace(/[^\d\-\.]/g, '')
                return parseFloat(a)
            },
            'formatted-num-asc': function (a, b) {
                return a - b
            },
            'formatted-num-desc': function (a, b) {
                return b - a
            }
        })
        // Show page of row
        $DataTable.Api.register('row().show()', function() {
            var page_info = this.table().page.info()
            var new_row_index = this.index()
            var row_position = this.table().rows()[0].indexOf(new_row_index)
            if(row_position >= page_info.start && row_position < page_info.end) {
                return this
            }
            var page_to_display = Math.floor(row_position / this.table().page.len())
            this.table().page(page_to_display).draw(false)
            return this
        })

        // Constructor
        function DT(elem, options) {
            var self = this
            self.$elem = $(elem)
            self.options = options

            if (self.$elem[0].DT)
                return false

            // PLUGIN SETTINGS
            // Unsortable actions column
            var unsortableColumns = {}
            self.actions = self.$elem.data('table-actions')
            if (typeof self.actions !== 'undefined' && self.actions !== false) {
                unsortableColumns = {targets: [self.$elem.find('thead td:last-child').index()], orderable: false}
            } else {
                self.actions = self.$elem.data('table-editor-actions')
                if (typeof self.actions !== 'undefined' && self.actions !== false) {
                    self.$elem.find('thead tr').append('<th data-table-actionscolumn>Actions</th>')
                    self.$elem.find('tbody tr').each(function () {
                        var $tr = $(this)
                        var state = null
                        if (~(self.actions.indexOf('toggle'))) {
                            state = $tr.data('state')
                            if (typeof state === 'undefined') {
                                console.log('Missing data-state attr on each row')
                                return false
                            }
                        }
                        $tr.append('<td>' + self.buttons(state) + '</td>')
                    })
                    unsortableColumns = {targets: [self.$elem.find('thead td:last-child').index()], orderable: false}
                }
            }
            // Sortable currency columns
            var currencyColumns = {}
            var $tdEuro = self.$elem.find('td:contains(€)')
            if ($tdEuro.length) {
                var $trEuro = $tdEuro.first().parent()
                var indexes = []
                $trEuro.find('td:contains(€)').each(function(){
                    indexes.push($(this).index())
                })
                currencyColumns = {targets: indexes, type: 'formatted-num'}
            }
            // Rows per page
            var pageLength = self.$elem.data('table-pagelength')
            if (typeof pageLength === 'undefined')
                pageLength = 10
            // Dynamic control (per page)
            var lengthChange = self.$elem.data('table-lengthchange')
            if (typeof pageLength === 'undefined')
                lengthChange = false
            // Search
            var search = self.$elem.data('table-search')
            if (typeof search === 'undefined') {
                search = false
            }

            // BEFORE CALLBACK
            if (typeof options !== 'undefined') {
                if (options.before)
                    options.before()
            }

            // PLUGIN INIT
            self.$elem[0].DT = self
            self.dtInstance = self.$elem.DataTable({
                lengthChange: lengthChange,
                pageLength: pageLength,
                columnDefs: [unsortableColumns, currencyColumns],
                searching: search
            })

            // Stop here if this is not an editable datatable (Editor)
            var editor = self.$elem.data('table-editor')
            if (typeof editor === 'undefined' || editor === false) {
                return false
            }

            // EDITOR
            // Vars
            self.submitUrl = self.$elem.data('table-editor-url')
            self.extraHiddenFields = self.$elem.data('table-editor-hidden')
            self.randomModalId = Math.floor((Math.random() * 10) + 1)
            self.delay = 900
            // Append to wrapper
            self.$wrapper = self.$elem.closest('.dataTables_wrapper')
            if (~(self.actions.indexOf('add')) || ~(self.actions.indexOf('create'))) {
                self.$wrapper.find('.col-sm-6:eq(0)').append('<a role="button" class="btn btn-default add-btn"><span class="fa fa-plus"></span> Ajouter</a>')
            }
            self.$wrapper.prepend('<div class="messages" />')
            // Append Modal
            $('body').append(self.modal())
            self.$modal = $('#modal-editor-' + self.randomModalId)
            // Activate event listeners
            self.events()

            // AFTER CALLBACK
            if (typeof options !== 'undefined') {
                if (options.after)
                    options.after()
            }
            if (typeof self.actions !== 'undefined' && self.actions !== false) {
                self.$elem.find('.btn').tooltip({
                    container: 'body',
                    animation: false
                });
            }
        }
        DT.prototype.buttons = function (state) {
            var self = this
            var html = '<div class="btn-group">'
            if (~(self.actions.indexOf('edit')) || ~(self.actions.indexOf('modify')))
                html += '<a class="btn btn-xs btn-default edit-btn" title="Modifier"><i class="fa fa-pencil"></i></a>'
            if (~(self.actions.indexOf('delete')))
                html += '<a class="btn btn-xs btn-default delete-btn" title="Supprimer"><i class="fa fa-times"></i></a>'
            if (~(self.actions.indexOf('toggle')) && state !== null) {
                var btn = (state === 'inactive') ? 'activate-btn' : 'deactivate-btn'
                var tooltip = (state === 'inactive') ? 'Activer' : 'Désactiver'
                var icon = (state === 'inactive') ? 'off' : 'on'
                html += '<a class="btn btn-xs btn-default ' + btn + '" title="' + tooltip + '"><i class="fa fa-toggle-' + icon + '"></i></a>'
            }
            return html
        }
        DT.prototype.fields = function() {
            var self = this
            var fields = []
            self.$elem.find('th').each(function(){
                var $th = $(this)
                var name = $th.data('editor-name')
                var type = $th.data('editor-type')
                var options = $th.data('editor-options')
                var required = (typeof $th.data('editor-optional') === 'undefined') ? true : false
                var label = $th.text()
                if (typeof name === 'undefined' || typeof type === 'undefined') {
                    if (!$th.is('[data-table-actionscolumn]')) {
                        console.log('Missing data-editor attributes')
                        return false
                    }
                } else {
                    if (typeof options !== 'undefined' && type !== 'multilevel') {
                        var parsedOptions = []
                        options = options.split(',')
                        for (var $i = 0; $i < options.length; $i++) {
                            var option = options[$i].trim().split(':')
                            option.text = option[0]
                            option.id = option[1]
                            parsedOptions.push(option)
                        }
                        options = parsedOptions
                    }
                    fields.push({name: name, type: type, label: label, options: options, required: required})
                }
            })
            return fields
        }
        DT.prototype.form = function(fields) {
            var self = this
            var fields = (typeof fields === 'undefined') ? self.fields() : fields
            var html = ''
            for (var $i=0; $i < fields.length; $i++) {
                var field         = fields[$i]
                var name          = field.name
                var label         = field.label
                var type          = field.type
                var required      = (field.required === true) ? ' required' : ''
                var requiredLabel = (field.required === true) ? '' : ' <span class="optional">(facultatif)</span>'
                var value         = (typeof field.value === 'undefined') ? '' : field.value
                var options       = field.options

                html += '<div class="form-group push-10"><label>' + label + '</label>' + requiredLabel
                // Text / Email
                if (type === 'text' || type === 'email') {
                    html += '<input type="text" name="' + name + '" value="' + value + '" class="form-control' + required + '">'
                // Datepicker
                } else if (type === 'date') {
                    html += '<input type="text" name="' + name + '" value="' + value + '" class="form-control' + required + '" data-date-format="dd/mm/yyyy">'
                // Numerical - currency, number of days, etc.
                } else if (type === 'numerical') {
                    value = (value === '') ? '' : parseFloat(value.replace(',', '.').replace(/[^\d\-\.]/g, ''))
                    html += '<input type="text" name="' + name + '" value="' + value + '" class="form-control' + required + '">'
                // Radio
                } else if (type === 'radio' || type === 'select' || type == 'checkbox') {
                    if (type === 'select')
                        html += '<select class="form-control' + required + '" name="' + name + '"><option value="0">Selectionner</option>'
                    else
                        html += '<br>'
                    for (var $l = 0; $l < options.length; $l++) {
                        var option = options[$l]
                        var choice
                        if (type === 'radio') {
                            choice = (value === option.text) ? 'checked' : ''
                            html += '<label class="css-input css-radio css-radio-sm css-radio-default push-10-r">' +
                                '<input type="radio"  name="' + name + '" value="' + option.id + '" ' + choice + ' class="' + required + '">' +
                                '<span></span> ' + option.text +
                                '</label>'
                        } else if (type === 'select') {
                            choice = (value === option.text) ? 'selected' : ''
                            html += '<option value="' + option.id + '" ' + choice + '>' + option.text + '</option>'
                        } else if (type === 'checkbox') {
                            choice = (~(value.indexOf(option.text))) ? 'checked' : ''
                            html += '<label class="css-input css-checkbox css-checkbox-sm css-checkbox-default push-10-r">' +
                                '<input type="checkbox"  name="' + name + '[]" value="' + option.id + '" ' + choice + ' class="' + required + '">' +
                                '<span></span> ' + option.text +
                                '</label>'
                        }
                    }
                    if (type === 'select') {
                        html += '</select>'
                    }
                } else if (type === 'multilevel') {
                    var optionsHtml = ''
                    var level = 0
                    var selectedLevel = 0
                    function recurseHtml(object) {
                        for (var i in object) {
                            var o = object[i]
                            var selected = (value === o.text) ? 'selected' : ''
                            var spaces = (selected === 'selected') ? '' : new Array(level + 1).join('&nbsp;&nbsp;')
                            var id = (typeof o.id !== 'undefined') ? o.id : o.text
                            optionsHtml += '<option value="' + id + '" ' + selected + ' data-level="' + level + '">' + spaces + o.text + '</option>'
                            if (selected === 'selected')
                                selectedLevel = level
                            if (typeof o.children !== 'undefined') {
                                level++
                                recurseHtml(o.children)
                                level--
                            }
                        }
                        return optionsHtml
                    }
                    html += '<select class="form-control' + required + ' select-multilevel" name="' + name + '"><option value="0">Selectionner</option>'
                    html += recurseHtml(options)
                    html += '</select>'
                // File
                } else if (type === 'file') {
                    if (value === '') {
                        html += '<input type="file" name="' + name + '" value="" class="form-control' + required + '">'
                    } else {
                        html += '<div class="clearfix"><div class="pull-left">' +
                            '<div class="file">' + value + '</div>' +
                            '<input type="hidden" name="' + name + '" value="no_change"></div>' +
                            '<div class="pull-left push-15-l"><a class="btn btn-xs btn-default file-edit-btn edit">Modifier</a></div></div>'
                    }
                // Unknown type
                } else {
                    console.log('Unknown input type')
                    return false
                }
                html += '</div>'
            }
            // Add hidden inputs
            html += '<input type="hidden" name="id" value="">'
            html += '<input type="hidden" name="action" value="">'
            if (typeof self.extraHiddenFields !== 'undefined') {
                var extraHiddenFields = self.extraHiddenFields.split(',')
                for ($i in extraHiddenFields) {
                    var hiddenField = extraHiddenFields[$i].split(':')
                    var hiddenFieldName = hiddenField[0].trim()
                    var hiddenFieldValue = hiddenField[1].trim()
                    html += '<input type="hidden" name="' + hiddenFieldName + '" value="' + hiddenFieldValue + '">'
                }
            }
            return html
        }
        DT.prototype.modal = function() {
            var self = this
            var html = '<div class="modal fade" id="modal-editor-' + self.randomModalId + '" tabindex="-1" role="dialog" aria-hidden="true">' +
                '<form class="modal-dialog validate" action="' + self.submitUrl + '" method="post" enctype="multipart/form-data">' +
                    '<div class="modal-content">' +
                        '<div class="block block-bordered remove-margin-b">' +
                            '<div class="block-header"><h3 class="block-title"></h3></div>' +
                            '<div class="block-content">' + self.form() + '</div>' +
                        '</div>' +
                        '<div class="modal-footer">' +
                            '<button class="btn btn-sm btn-default" type="button" data-dismiss="modal">Annuler</button>' +
                            '<button class="btn btn-sm btn-primary" type="submit">Valider</button>' +
                        '</div>' +
                    '</div>' +
                '</form>' +
            '</div>'
            return(html)
        }
        DT.prototype.getCellValues = function(id) {
            var self = this
            var $row = self.$elem.find('tr[data-id=' + id + ']')
            var fields = self.fields()
            for (var $i=0; $i < fields.length; $i++) {
                var value = $row.find('td:eq(' + $i + ')').text()
                if (fields[$i].type === 'file') {
                    value = $row.find('td:eq(' + $i + ')').html()
                }
                fields[$i].value = value
            }
            return fields
        }
        DT.prototype.openModal = function(type, id) {
            var self = this
            var title, content, hiddenAction, hiddenId
            if (id)
                hiddenId = id
            else
                hiddenId = ''

            if (type === 'create') {
                title = 'Ajouter'
                content = self.form()
                hiddenAction = 'create'
            }
            if (type === 'activate') {
                title = 'Activer'
                content =  '<p>Êtes-vous sûr de vouloir activer l\'élément ?</p>'
                content += '<input type="hidden" name="id">'
                content += '<input type="hidden" name="action">'
                hiddenAction = 'activate'
            }
            if (type === 'deactivate') {
                title = 'Désactiver'
                content =  '<p>Êtes-vous sûr de vouloir désactiver l\'élément ?</p>'
                content += '<input type="hidden" name="id">'
                content += '<input type="hidden" name="action">'
                hiddenAction = 'deactivate'
            }
            if (type === 'modify') {
                title = 'Modifier'
                var fields = self.getCellValues(id)
                content = self.form(fields)
                hiddenAction = 'modify'
            }
            if (type === 'delete') {
                title = 'Supprimer'
                content = '<p>Êtes-vous sûr de vouloir supprimer l\'élément ?</p>'
                content += '<input type="hidden" name="id">'
                content += '<input type="hidden" name="action">'
                hiddenAction = 'delete'
            }
            self.$modal.find('.block').removeClass('block-opt-refresh')
            self.$modal.find('.has-error').removeClass('has-error')
            self.$modal.find('.block-title').html(title)
            self.$modal.find('.block-content').html(content)
            self.$modal.find('input[name=id]').val(hiddenId)
            self.$modal.find('input[name=action]').val(hiddenAction)
            self.$modal.modal('show')
        }
        DT.prototype.submit = function() {
            var self = this
            var $form = self.$modal.find('form')
            if (!$form.find('.has-error').length) {
                self.$modal.find('.block').addClass('block-opt-refresh')
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: new FormData($form[0]),
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            self.update(response.id, response.data)
                        } else {
                            var errors = '<div class="alert alert-danger">'
                            $.each(response.error, function(i, val){
                                errors += '<p>' + val + '</p>'
                            })
                            errors += '</div>'
                            self.$wrapper.find('.messages').html(errors)
                        }
                        self.$modal.modal('hide')
                    }
                })
            }
        }
        DT.prototype.update = function(id, data) {
            var self = this
            var fields = self.fields()
            var tr = self.dtInstance.row('[data-id=' + id + ']')
            var $tr = self.$elem.find('[data-id=' + id + ']')
            // Delete row
            if (data === 'delete') {
                $tr.addClass('animated flash-bg')
                setTimeout(function(){ tr.remove().draw(false) }, self.delay)
                return false
            }
            // Toggle row state
            if (data === 'active' || data === 'inactive') {
                $tr.addClass('animated flash-bg').find('td:last-child').html(self.buttons(data))
                setTimeout(function(){ $tr.removeClass('animated flash-bg') }, self.delay)
                return false
            }
            // Add or modify row
            if (!$.isArray(data)) {
                console.log('response.data must be an array')
                return false
            }
            // Check we have the same number of fields
            if (data.length !== fields.length) {
                console.log('response.data has missing or extra fields')
                return false
            }
            // Replace data values with labels
            for (var $i = 0; $i < fields.length; $i++) {
                var field = fields[$i]
                var options = field.options
                var labels = []
                if (typeof options !== 'undefined') {
                    if (field.type !== 'multilevel') {
                        for (var $l=0; $l < options.length; $l++) {
                            var option = options[$l]
                            if (field.type === 'checkbox') {
                                if (~(data[$i].indexOf(option.id)) || ~(data[$i].indexOf(parseInt(option.id)))) {
                                    labels.push(option.text)
                                }
                            } else {
                                if (option.id === data[$i].id || option.id === data[$i])
                                    labels = option.text
                            }
                        }
                        if (field.type === 'checkbox') {
                            labels = labels.join(', ')
                        }
                        data[$i] = labels
                    } else {
                        function recurseId(options, value) {
                            for (var i in options) {
                                var o = options[i]
                                var id = o.id.toString()
                                if (id.trim() === value.trim()) {
                                    data[$i] = o.text
                                    break
                                } else {
                                    if (typeof o.children !== 'undefined') {
                                        recurseId(o.children, value)
                                    }
                                }
                            }
                        }
                        recurseId(options, data[$i])
                    }
                }
            }
            // Update
            if (!tr.length) {
                // Add new row
                data.push(self.buttons('active'))
                tr = self.dtInstance.row.add(data).draw()
                tr.node().dataset.id = id
            } else {
                // Modify row
                data.push(self.buttons($tr.data('state')))
                tr.data(data)
            }
            // Go to the page on which the row is
            self.page(tr.index(), id)

            // After update callback
            if (typeof self.options !== 'undefined') {
                if (self.options.updated) {
                    self.options.updated(id)
                }
            }
        }
        DT.prototype.page = function(index, id) {
            var self = this
            var position = self.dtInstance.rows()[0].indexOf(index)
            var page = Math.floor(position / self.dtInstance.page.len())
            self.dtInstance.page(page).draw(false)
            var $tr = self.$elem.find('[data-id=' + id + ']')
            $tr.addClass('animated flash-bg')
            setTimeout(function() {$tr.removeClass('animated flash-bg')}, self.delay)
        }
        DT.prototype.events = function() {
            var self = this
            self.$wrapper.on('click', '.add-btn', function(){
                self.openModal('create')
            })
            self.$elem.on('click', '.edit-btn', function(){
                var id = $(this).closest('tr').data('id')
                self.openModal('modify', id)
            })
            self.$elem.on('click', '.delete-btn', function(){
                var id = $(this).closest('tr').data('id')
                self.openModal('delete', id)
            })
            self.$elem.on('click', '.activate-btn', function(){
                var id = $(this).closest('tr').data('id')
                self.openModal('activate', id)
            })
            self.$elem.on('click', '.deactivate-btn', function(){
                var id = $(this).closest('tr').data('id')
                self.openModal('deactivate', id)
            })
            self.$modal.on('shown.bs.modal', function() {
                self.$modal.find('[data-date-format]').datepicker({
                    weekStart: 1,
                    autoclose: true,
                    todayHighlight: false,
                    language: 'fr'
                })

                // MODAL OPEN CALLBACK
                if (typeof self.options !== 'undefined') {
                    if (self.options.modal)
                        self.options.modal(self.$modal)
                }
            })
            uiHelperFormValidate() // Call validation
            self.$modal.find('form').on('submit', function (e) {
                e.preventDefault()
                self.submit()
            })
            function recalculateSpaces($select) {
                $select.children().each(function(){
                    var $option = $(this)
                    var level = $option.data('level')
                    if (typeof level !== 'undefined') {
                        var text = $option.text().trim()
                        $option.html(new Array(level + 1).join('&nbsp;&nbsp;') + text)
                    }
                })
            }
            self.$modal.on('mousedown', '.select-multilevel', function(){
                recalculateSpaces($(this))
            })
            self.$modal.on('change', '.select-multilevel', function(){
                var $selectedOption = $(this).find('option:selected')
                recalculateSpaces($(this))
                $selectedOption.text($selectedOption.text().trim())
            })
            self.$modal.on('change', 'input[type=file]', function () {
                var $input = $(this)
                var file = this.files[0];
                if (file.size > 5*1024) {
                    $input.closest('form-group').addClass('has-error').append('<p class="text-danger">Taille max 5 Mb.</p>')

                } else {
                    $input.closest('form-group').removeClass('has-error').find('.text-danger').remove()
                }
            })
            self.$modal.on('click', '.file-edit-btn', function(){
                var $btn = $(this)
                var $parent = $(this).closest('.form-group')
                var $link = $parent.find('.file')
                var $input = $parent.find('input')
                if ($btn.is('.edit')) {
                    $btn.removeClass('edit').addClass('cancel').text('Annuler')
                    $link.hide()
                    $input.attr('type', 'file').attr('class', 'form-control required')
                } else {
                    $btn.removeClass('cancel').addClass('edit').text('Modifier')
                    $link.show()
                    $parent.removeClass('has-error')
                    $input.attr('type', 'hidden').attr('class', '').val('no_change')
                }
            })
        }
        // jQuery Plugin
        $.fn.DT = function(op) {
            if (typeof op === 'string' && /^(before|after|updated|modal)$/.test(op)) {
                var args = Array.prototype.slice.call(arguments)
                args.shift()
                return this.each(function(i, elem) {
                    if (elem.hasOwnProperty('DT') && typeof elem.DT[op] === 'function') {
                        elem.DT[op].apply(elem.DT, args)
                    }
                })
            } else {
                return this.each(function(i, elem) {
                    if (!elem.hasOwnProperty('DT')) {
                        new DT(elem, op)
                    }
                })
            }
        }
        $('[data-table]').each(function(){
            $(this).DT()
        })
    }

    // Checkable table functionality
    var uiHelperTableToolsCheckable = function() {
        // For each table
        jQuery('.js-table-checkable').each(function(){
            var $table = jQuery(this);

            // When a checkbox is clicked in thead
            jQuery('thead input:checkbox', $table).on('click', function() {
                var $checkedStatus = jQuery(this).prop('checked');

                // Check or uncheck all checkboxes in tbody
                jQuery('tbody input:checkbox', $table).each(function() {
                    var $checkbox = jQuery(this);

                    $checkbox.prop('checked', $checkedStatus);
                    uiHelperTableToolscheckRow($checkbox, $checkedStatus);
                });
            });

            // When a checkbox is clicked in tbody
            jQuery('tbody input:checkbox', $table).on('click', function() {
                var $checkbox = jQuery(this);

                uiHelperTableToolscheckRow($checkbox, $checkbox.prop('checked'));
            });

            // When a row is clicked in tbody
            jQuery('tbody > tr', $table).on('click', function(e) {
                if (e.target.type !== 'checkbox'
                        && e.target.type !== 'button'
                        && e.target.tagName.toLowerCase() !== 'a'
                        && !jQuery(e.target).parent('label').length) {
                    var $checkbox       = jQuery('input:checkbox', this);
                    var $checkedStatus  = $checkbox.prop('checked');

                    $checkbox.prop('checked', ! $checkedStatus);
                    uiHelperTableToolscheckRow($checkbox, ! $checkedStatus);
                }
            });
        });
    };

    // Checkable table functionality helper - Checks or unchecks table row
    var uiHelperTableToolscheckRow = function($checkbox, $checkedStatus) {
        if ($checkedStatus) {
            $checkbox
                .closest('tr')
                .addClass('active');
        } else {
            $checkbox
                .closest('tr')
                .removeClass('active');
        }
    };

    /*
     * jQuery SlimScroll, for more examples you can check out http://rocha.la/jQuery-slimScroll
     *
     * App.initHelper('slimscroll');
     *
     */
    var uiHelperSlimscroll = function(){
        // Init slimScroll functionality
        jQuery('[data-toggle="slimscroll"]').each(function(){
            var $this       = jQuery(this);
            var $height     = $this.data('height') ? $this.data('height') : '200px';
            var $size       = $this.data('size') ? $this.data('size') : '5px';
            var $position   = $this.data('position') ? $this.data('position') : 'right';
            var $color      = $this.data('color') ? $this.data('color') : '#000';
            var $avisible   = $this.data('always-visible') ? true : false;
            var $rvisible   = $this.data('rail-visible') ? true : false;
            var $rcolor     = $this.data('rail-color') ? $this.data('rail-color') : '#999';
            var $ropacity   = $this.data('rail-opacity') ? $this.data('rail-opacity') : .3;

            $this.slimScroll({
                height: $height,
                size: $size,
                position: $position,
                color: $color,
                alwaysVisible: $avisible,
                railVisible: $rvisible,
                railColor: $rcolor,
                railOpacity: $ropacity
            });
        });
    };

    /*
     ********************************************************************************************
     *
     * All the following helpers require each plugin's resources (JS, CSS) to be included in order to work
     *
     ********************************************************************************************
     */

    /*
     * Magnific Popup functionality, for more examples you can check out http://dimsemenov.com/plugins/magnific-popup/
     *
     * App.initHelper('magnific-popup');
     *
     */
    var uiHelperMagnific = function(){
        // Simple Gallery init
        jQuery('.js-gallery').each(function(){
            jQuery(this).magnificPopup({
                delegate: 'a.img-link',
                type: 'image',
                gallery: {
                    enabled: true
                }
            });
        });

        // Advanced Gallery init
        jQuery('.js-gallery-advanced').each(function(){
            jQuery(this).magnificPopup({
                delegate: 'a.img-lightbox',
                type: 'image',
                gallery: {
                    enabled: true
                }
            });
        });
    };

    /*
     * Summernote init, for more examples you can check out http://summernote.org/
     *
     * App.initHelper('summernote');
     *
     */
    var uiHelperSummernote = function(){
        // Init text editor in air mode (inline)
        jQuery('.js-summernote-air').summernote({
            airMode: true
        });

        // Init full text editor
        jQuery('.js-summernote').summernote({
            height: 350,
            minHeight: null,
            maxHeight: null
        });
    };

    /*
     * Slick init, for more examples you can check out http://kenwheeler.github.io/slick/
     *
     * App.initHelper('slick');
     *
     */
    var uiHelperSlick = function(){
        // Get each slider element (with .js-slider class)
        jQuery('.js-slider').each(function(){
            var $slider = jQuery(this);

            // Get each slider's init data
            var $sliderArrows       = $slider.data('slider-arrows') ? $slider.data('slider-arrows') : false;
            var $sliderDots         = $slider.data('slider-dots') ? $slider.data('slider-dots') : false;
            var $sliderNum          = $slider.data('slider-num') ? $slider.data('slider-num') : 1;
            var $sliderAuto         = $slider.data('slider-autoplay') ? $slider.data('slider-autoplay') : false;
            var $sliderAutoSpeed    = $slider.data('slider-autoplay-speed') ? $slider.data('slider-autoplay-speed') : 3000;

            // Init slick slider
            $slider.slick({
                arrows: $sliderArrows,
                dots: $sliderDots,
                slidesToShow: $sliderNum,
                autoplay: $sliderAuto,
                autoplaySpeed: $sliderAutoSpeed
            });
        });
    };

    /*
     * Bootstrap Datepicker init, for more examples you can check out https://github.com/eternicode/bootstrap-datepicker
     *
     * App.initHelper('datepicker');
     *
     */
    var uiHelperDatepicker = function(){
        // Init datepicker (with .js-datepicker and .input-daterange class)
        jQuery('.js-datepicker').add('.input-daterange').datepicker({
            weekStart: 1,
            autoclose: true,
            todayHighlight: true
        });
    };

    /*
     * Bootstrap Colorpicker init, for more examples you can check out http://mjolnic.com/bootstrap-colorpicker/
     *
     * App.initHelper('colorpicker');
     *
     */
    var uiHelperColorpicker = function(){
        // Get each colorpicker element (with .js-colorpicker class)
        jQuery('.js-colorpicker').each(function(){
            var $colorpicker = jQuery(this);

            // Get each colorpicker's init data
            var $colorpickerMode    = $colorpicker.data('colorpicker-mode') ? $colorpicker.data('colorpicker-mode') : 'hex';
            var $colorpickerinline  = $colorpicker.data('colorpicker-inline') ? true : false;

            // Init colorpicker
            $colorpicker.colorpicker({
                'format': $colorpickerMode,
                'inline': $colorpickerinline
            });
        });
    };

    /*
     * Masked Inputs, for more examples you can check out http://digitalbush.com/projects/masked-input-plugin/
     *
     * App.initHelper('masked-inputs');
     *
     */
    var uiHelperMaskedInputs = function(){
        // Init Masked Inputs
        // a - Represents an alpha character (A-Z,a-z)
        // 9 - Represents a numeric character (0-9)
        // * - Represents an alphanumeric character (A-Z,a-z,0-9)
        jQuery('.js-masked-date').mask('99/99/9999');
        jQuery('.js-masked-date-dash').mask('99-99-9999');
        jQuery('.js-masked-phone').mask('(999) 999-9999');
        jQuery('.js-masked-phone-ext').mask('(999) 999-9999? x99999');
        jQuery('.js-masked-taxid').mask('99-9999999');
        jQuery('.js-masked-ssn').mask('999-99-9999');
        jQuery('.js-masked-pkey').mask('a*-999-a999');
        jQuery('.js-masked-time').mask('99:99');
        jQuery('.js-masked-iban').mask('aa99 9999 9999 9999 9999 9999 9999');
    };

    /*
     * Tags Inputs, for more examples you can check out https://github.com/xoxco/jQuery-Tags-Input
     *
     * App.initHelper('tags-inputs');
     *
     */
    var uiHelperTagsInputs = function(){
        // Init Tags Inputs (with .js-tags-input class)
        jQuery('.js-tags-input').tagsInput({
            height: '36px',
            width: '100%',
            defaultText: 'Add tag',
            removeWithBackspace: true,
            delimiter: [',']
        });
    };

    /*
     * Select2, for more examples you can check out https://github.com/select2/select2
     *
     * App.initHelper('select2');
     *
     */
    var uiHelperSelect2 = function(){
        // Init Select2 (with .js-select2 class)
        jQuery('.js-select2').select2();
    };

    /*
     * Highlight.js, for more examples you can check out https://highlightjs.org/usage/
     *
     * App.initHelper('highlightjs');
     *
     */
    var uiHelperHighlightjs = function(){
        // Init Highlight.js
        hljs.initHighlightingOnLoad();
    };

    /*
     * Bootstrap Notify, for more examples you can check out http://bootstrap-growl.remabledesigns.com/
     *
     * App.initHelper('notify');
     *
     */
    var uiHelperNotify = function(){
        // Init notifications (with .js-notify class)
        jQuery('.js-notify').on('click', function(){
            var $notify         = jQuery(this);
            var $notifyMsg      = $notify.data('notify-message');

            jQuery.notify({
                    message: $notifyMsg,
                    url: ''
                },
                {
                    element: 'body',
                    type: 'danger',
                    allow_dismiss: true,
                    newest_on_top: true,
                    showProgressbar: false,
                    placement: {
                        from: 'top',
                        align: 'right'
                    },
                    offset: 100,
                    spacing: 10,
                    z_index: 1033,
                    delay: 0,
                    timer: 1000,
                    animate: {
                        enter: 'animated fadeIn',
                        exit: 'animated fadeOutDown'
                    }
                });
        });
    };

    /*
     * Draggable items with jQuery, for more examples you can check out https://jqueryui.com/sortable/
     *
     * App.initHelper('draggable-items');
     *
     */
    var uiHelperDraggableItems = function(){
        // Init draggable items functionality (with .js-draggable-items class)
        jQuery('.js-draggable-items > .draggable-column').sortable({
            connectWith: '.draggable-column',
            items: '.draggable-item',
            dropOnEmpty: true,
            opacity: .75,
            handle: '.draggable-handler',
            placeholder: 'draggable-placeholder',
            tolerance: 'pointer',
            start: function(e, ui){
                ui.placeholder.css({
                    'height': ui.item.outerHeight(),
                    'margin-bottom': ui.item.css('margin-bottom')
                });
            }
        });
    };

    /*
     * Bootstrap Maxlength, for more examples you can check out https://github.com/mimo84/bootstrap-maxlength
     *
     * App.initHelper('maxlength');
     *
     */
    var uiHelperMaxlength = function(){
        // Init Bootstrap Maxlength (with .js-maxlength class)
        jQuery('.js-maxlength').each(function(){
            var $input = jQuery(this);

            $input.maxlength({
                alwaysShow: $input.data('always-show') ? true : false,
                threshold: $input.data('threshold') ? $input.data('threshold') : 10,
                warningClass: $input.data('warning-class') ? $input.data('warning-class') : 'label label-warning',
                limitReachedClass: $input.data('limit-reached-class') ? $input.data('limit-reached-class') : 'label label-danger',
                placement: $input.data('placement') ? $input.data('placement') : 'bottom',
                preText: $input.data('pre-text') ? $input.data('pre-text') : '',
                separator: $input.data('separator') ? $input.data('separator') : '/',
                postText: $input.data('post-text') ? $input.data('post-text') : ''
            });
        });
    };

    /*
     * Bootstrap Datetimepicker, for more examples you can check out https://github.com/Eonasdan/bootstrap-datetimepicker
     *
     * App.initHelper('datetimepicker');
     *
     */
    var uiHelperDatetimepicker = function(){
        // Init Bootstrap Datetimepicker (with .js-datetimepicker class)
        jQuery('.js-datetimepicker').each(function(){
            var $input = jQuery(this);

            $input.datetimepicker({
                format: $input.data('format') ? $input.data('format') : false,
                useCurrent: $input.data('use-current') ? $input.data('use-current') : false,
                locale: moment.locale('' + ($input.data('locale') ? $input.data('locale') : '') +''),
                showTodayButton: $input.data('show-today-button') ? $input.data('show-today-button') : false,
                showClear: $input.data('show-clear') ? $input.data('show-clear') : false,
                showClose: $input.data('show-close') ? $input.data('show-close') : false,
                sideBySide: $input.data('side-by-side') ? $input.data('side-by-side') : false,
                inline: $input.data('inline') ? $input.data('inline') : false,
                icons: {
                    time: 'si si-clock',
                    date: 'si si-calendar',
                    up: 'si si-arrow-up',
                    down: 'si si-arrow-down',
                    previous: 'si si-arrow-left',
                    next: 'si si-arrow-right',
                    today: 'si si-size-actual',
                    clear: 'si si-trash',
                    close: 'si si-close'
                }
            });
        });
    };

    /*
     * SimpleMDE init, for more examples you can check out https://github.com/NextStepWebs/simplemde-markdown-editor
     *
     * App.initHelper('simplemde');
     *
     */
    var uiHelperSimpleMDE = function(){
        // Init markdown editor (with .js-simplemde class)
        jQuery('.js-simplemde').each(function(){
            var el = jQuery(this);

            new SimpleMDE({ element: el[0] });
        });
    };

    /*
     * Generic form validation init
     *
     * App.initHelper('validation');
     * Requires '.validate' to be added to the form and class '.required' to the form field
     *
     */
    var uiHelperFormValidate = function(){
        $('form.validate').submit(function(e){
            var $form = $(this)
            var valid = true
            $(this).find('.required').each(function(){
                var $input = $(this)
                if ($input.is('input[type=text]') || $input.is('input[type=number]') || $input.is('input[type=email]') || $input.is('textarea') || $input.is('input[type=password]')) {
                    var attrName = $(this).attr('name');
                    if (!$input.val() || $input.val() === '') {
                        if (typeof attrName !== typeof undefined && attrName !== false) {
                            $input.closest('.form-group').removeClass('has-error').addClass('has-error')
                            valid = false
                        }
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
                if ($input.is('input[type=radio]')) {
                    var oneChecked = false
                    $input.closest('.form-group').find('input[name='+ $input.attr('name') +']').each(function(){
                        if ($(this).is(':checked'))
                            oneChecked = true
                    })
                    if (!oneChecked) {
                        $input.closest('.form-group').removeClass('has-error').addClass('has-error')
                        valid = false
                    } else {
                        $input.closest('.form-group').removeClass('has-error')
                        valid = true
                    }
                }
                if (!valid) {
                    console.log('has errors')
                    e.preventDefault()
                    $form.addClass('has-errors')
                } else {
                    $form.removeClass('has-errors')
                }
            })
        })
    };

    var utility = {
        currencyToInteger: function(currency) {
            var number = parseFloat(currency.replace(',', '.').replace('€', '').replace(/\s+/g, ''))
            return number
        }
    };

    return {
        init: function($func) {
            switch ($func) {
                case 'uiInit':
                    uiInit();
                    break;
                case 'uiLayout':
                    uiLayout();
                    break;
                case 'uiNav':
                    uiNav();
                    break;
                case 'uiNav':
                    uiQuickSearch();
                    break;
                case 'uiBlocks':
                    uiBlocks();
                    break;
                case 'uiHandleTheme':
                    uiHandleTheme();
                    break;
                case 'uiToggleClass':
                    uiToggleClass();
                    break;
                case 'uiScrollTo':
                    uiScrollTo();
                    break;
                case 'uiYearCopy':
                    uiYearCopy();
                    break;
                case 'uiLoader':
                    uiLoader('hide');
                    break;
                case 'uiAlertCollapse':
                    uiAlertCollapse();
                    break;
                default:
                    // Init all vital functions
                    uiInit();
                    uiLayout();
                    uiNav();
                    uiQuickSearch();
                    uiBlocks();
                    uiHandleTheme();
                    uiToggleClass();
                    uiScrollTo();
                    uiYearCopy();
                    uiLoader('hide');
                    uiAlertCollapse();
            }
        },
        layout: function($mode) {
            uiLayoutApi($mode);
        },
        loader: function($mode) {
            uiLoader($mode);
        },
        blocks: function($block, $mode) {
            uiBlocksApi($block, $mode);
        },
        initHelper: function($helper) {
            switch ($helper) {
                case 'print-page':
                    uiHelperPrint();
                    break;
                case 'table-tools':
                    uiHelperTableToolsSections();
                    uiHelperTableToolsCheckable();
                    break;
                case 'datatables':
                    uiHelperDataTables()
                    break;
                case 'slimscroll':
                    uiHelperSlimscroll();
                    break;
                case 'magnific-popup':
                    uiHelperMagnific();
                    break;
                case 'summernote':
                    uiHelperSummernote();
                    break;
                case 'slick':
                    uiHelperSlick();
                    break;
                case 'datepicker':
                    uiHelperDatepicker();
                    break;
                case 'colorpicker':
                    uiHelperColorpicker();
                    break;
                case 'tags-inputs':
                    uiHelperTagsInputs();
                    break;
                case 'masked-inputs':
                    uiHelperMaskedInputs();
                    break;
                case 'select2':
                    uiHelperSelect2();
                    break;
                case 'highlightjs':
                    uiHelperHighlightjs();
                    break;
                case 'notify':
                    uiHelperNotify();
                    break;
                case 'draggable-items':
                    uiHelperDraggableItems();
                    break;
                case 'maxlength':
                    uiHelperMaxlength();
                    break;
                case 'datetimepicker':
                    uiHelperDatetimepicker();
                    break;
                case 'simplemde':
                    uiHelperSimpleMDE();
                    break;
                case 'validation':
                    uiHelperFormValidate();
                    break;
                default:
                    return false;
            }
        },
        initHelpers: function($helpers) {
            if ($helpers instanceof Array) {
                for(var $index in $helpers) {
                    App.initHelper($helpers[$index]);
                }
            } else {
                App.initHelper($helpers);
            }
        },
        utility: utility
    };




}();

// Create an alias for App (you can use OneUI in your pages instead of App if you like)
var OneUI = App;

// Initialize app when page loads
jQuery(function(){
    if (typeof angular == 'undefined') {
        App.init();
    }
});