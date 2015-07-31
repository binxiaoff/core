/*
Name: c2selectbox Advanced v1.1.2 (Major.Minor.Bugfix)
Date: 2012-09-07
*/

function c2preg_quote(str, delimiter) {
    // Quote regular expression characters plus an optional character
    //
    // version: 1107.2516
    // discuss at: http://phpjs.org/functions/preg_quote    // +   original by: booeyOH
    // +   improved by: Ates Goral (http://magnetiq.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Brett Zamir (http://brett-zamir.me)    // *     example 1: preg_quote("$40");
    // *     returns 1: '\$40'
    // *     example 2: preg_quote("*RRRING* Hello?");
    // *     returns 2: '\*RRRING\* Hello\?'
    // *     example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");    // *     returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'
    return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}

(function($){
	$.fn.c2Selectbox = function(action) {
		var c2_animation_speed = 100;

		if (typeof action == 'undefined') {
			$(this).each(function() {
				if ($(this).find('option').length > 0) {
					if (!$(this).hasClass('c2-sb-enabled')) {
						$(this).addClass('c2-sb-enabled');

						var original_select = $(this);
						var html = '<div class="c2-sb-inner-wrap"><div class="c2-sb-text"></div><span class="c2-sb-button"><i class="icon-arrow-down"></i></span></div><div class="c2-sb-list-wrap"><ul class="c2-sb-list"></ul></div>';
						var selectbox = $('<div class="c2-sb-wrap" tabindex="0"></div>');
						selectbox.append(html);
						var first_option = $(this).find('option:eq(0)');
						var found_value = false;
						$(this).find('optgroup, option').each(function(index) {
							if ($(this).is('option')) {
								if ($(this).is(':selected')) { // only FF reports checked for the first option if none is actually checked
									$(selectbox).find('.c2-sb-text').text($(this).text());
									found_value = true;

									if(!$(this).is(':first-child')){
										$(selectbox).addClass('populated');
									}
								}
								$(selectbox).find('.c2-sb-list').append('<li class="c2-sb-list-item"><a href="#" class="c2-sb-list-item-link ' + (($(this).is(':selected')) ? 'c2-sb-list-item-link-active' : '') + ' " data-value="' + $(this).val() + '">' + $(this).text() + '</a></li>');
							} else if ($(this).is('optgroup')) {
								$(selectbox).find('.c2-sb-list').append('<li class="c2-sb-list-item"><a href="#" class="c2-sb-list-item-group">' + $(this).attr('label') + '</a></li>');
							}
						});
						if (!found_value) {
							$(selectbox).find('.c2-sb-text').text($(first_option).text());
							$(selectbox).find('.c2-sb-list-item-link[data-value="' + $(first_option).val() + '"]').addClass('c2-sb-list-item-link-active');
							found_value = true;
						}

						var width = $(this).width() + parseInt($(this).css('padding-left')) + parseInt($(this).css('padding-right')) + parseInt($(this).css('border-left-width')) + parseInt($(this).css('border-right-width'));
						$(original_select).hide().after(selectbox);

						var padding = parseInt($(selectbox).find('.c2-sb-inner-wrap').css('padding-left')) + parseInt($(selectbox).find('.c2-sb-inner-wrap').css('padding-right'));
						var button_width = parseInt($(selectbox).find('.c2-sb-button').css('width')) + parseInt($(selectbox).find('.c2-sb-button').css('padding-left')) + parseInt($(selectbox).find('.c2-sb-button').css('padding-right')) + parseInt($(selectbox).find('.c2-sb-button').css('margin-left')) + parseInt($(selectbox).find('.c2-sb-button').css('margin-right'));
						$(selectbox).find('.c2-sb-text').width(width - padding - button_width);
						list_width = $(selectbox).width() - parseInt($(selectbox).find('.c2-sb-list-wrap').css('border-left-width')) - parseInt($(selectbox).find('.c2-sb-list-wrap').css('border-right-width'));
						$(selectbox).find('.c2-sb-list-wrap').css('min-width', list_width);

						$(selectbox).find('.c2-sb-text, .c2-sb-button').click(function() {
							var sb = $(this).closest('.c2-sb-wrap');
							var list = $(sb).find('.c2-sb-list-wrap');
							$('.c2-sb-wrap').not(sb).c2Selectbox('close');
							$(list).stop(true, true);
							if (!$(list).is(':visible')) {
								$(sb).addClass('c2-sb-open');
							} else {
								$(sb).removeClass('c2-sb-open').addClass('c2-sb-closing');
								$('.c2-sb-focused').removeClass('c2-sb-focused');
								$(sb).addClass('c2-sb-focused');
							}
							$(list).slideToggle(c2_animation_speed, function() {
								if (!$(list).is(':visible')) {
									$(sb).removeClass('c2-sb-closing');
								} else {
									var active = $(sb).find('.c2-sb-list-item-link-active');
									/*
									if ($(active).position().top < $(sb).find('.c2-sb-list-wrap').scrollTop() || $(active).position().top + 1 > $(sb).find('.c2-sb-list-wrap').scrollTop() + $(sb).find('.c2-sb-list-wrap').height()) {
										var new_top = $(active).position().top;
										$(sb).find('.c2-sb-list-wrap').scrollTop(new_top);
									}
									*/
									if (typeof $(sb).find('.c2-sb-list-wrap').jScrollPane != 'undefined') {
										$(sb).find('.c2-sb-list-wrap').jScrollPane({
											verticalGutter: 0,
											showArrows: true
										});
									}
								}
							});
							return false;
						});

						$(selectbox).find('.c2-sb-list-item-link').click(function() {
							$(this).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
							$(this).addClass('c2-sb-list-item-link-active');
							$(this).closest('.c2-sb-wrap').find('.c2-sb-text').text($(this).text());
							$(original_select).val($(this).attr('data-value')).change();
							$(selectbox).c2Selectbox('close');
							if ($(this).attr('data-value') === '' || $(this).attr('data-value') === '0' || $(this).attr('data-value').indexOf('*') > -1) {
								$(selectbox).removeClass('populated').addClass('field-error');
							} else {
								$(selectbox).addClass('populated').removeClass('field-error');
							}
							return false;
						});
						$(selectbox).find('.c2-sb-list-item-group').click(function() {
							return false;
						});

						$(selectbox).focus(function() {
							$('.c2-sb-focused').removeClass('c2-sb-focused');
							$(this).addClass('c2-sb-focused');
						});
						$(selectbox).blur(function(e) {
							$(this).removeClass('c2-sb-focused');
						});
					}
				}
			});

			$(document).keydown(function(e) {
				var sb = $('.c2-sb-open:eq(0)');
				if (sb.length == 0) {
					sb = $('.c2-sb-focused:eq(0)');
				}
				if ($(sb).length == 1) {
					if (e.keyCode == 38) { // up
						var active = $(sb).find('.c2-sb-list-item-link-active');
						var prev = $(active).parent().prev();
						while (prev.find('.c2-sb-list-item-group').length) {
							var prev = prev.prev();
						}
						if (prev.length == 1) {
							var prev = $(prev).find('.c2-sb-list-item-link');

							// Almost duplicated code
							$(prev).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
							$(prev).addClass('c2-sb-list-item-link-active');
							$(prev).closest('.c2-sb-wrap').find('.c2-sb-text').text($(prev).text());

							if ($(prev).attr('data-value') === '' || $(prev).attr('data-value') === '0' || $(prev).attr('data-value').indexOf('*') > -1) {
								$(sb).removeClass('populated').addClass('field-error');
							} else {
								$(sb).addClass('populated').removeClass('field-error');
							}

							$(sb).prev().val($(prev).attr('data-value')).change();

							var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
							if (api) {
								api.scrollToElement($(prev), false);
							}
							/*
							if ($(prev).position().top < $(sb).find('.c2-sb-list-wrap').scrollTop()) {
								var new_top = $(prev).position().top;
								$(sb).find('.c2-sb-list-wrap').scrollTop(new_top);
							}
							*/
						}
						return false;
					} else if (e.keyCode == 40) { // down
						var active = $(sb).find('.c2-sb-list-item-link-active');
						var next = $(active).parent().next();
						while (next.find('.c2-sb-list-item-group').length) {
							var next = next.next();
						}
						if (next.length == 1) {
							var next = $(next).find('.c2-sb-list-item-link');

							// Almost duplicated code
							$(next).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
							$(next).addClass('c2-sb-list-item-link-active');
							$(next).closest('.c2-sb-wrap').find('.c2-sb-text').text($(next).text());
							$(sb).prev().val($(next).attr('data-value')).change();

							if ($(next).attr('data-value') === '' || $(next).attr('data-value') === '0' || $(next).attr('data-value').indexOf('*') > -1) {
								$(sb).removeClass('populated').addClass('field-error');
							} else {
								$(sb).addClass('populated').removeClass('field-error');
							}

							var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
							if (api) {
								api.scrollToElement($(next), false);
							}
							/*
							if ($(next).position().top + 1 > $(sb).find('.c2-sb-list-wrap').scrollTop() + $(sb).find('.c2-sb-list-wrap').height()) {
								var new_top = $(next).position().top - $(sb).find('.c2-sb-list-wrap').height() + $(next).height() + parseInt($(next).css('padding-top')) + parseInt($(next).css('padding-bottom'));;
								$(sb).find('.c2-sb-list-wrap').scrollTop(new_top);
							}
							*/
						}
						return false;
					} else if (e.keyCode == 13 || e.keyCode == 27) { // enter || escape
						$(sb).c2Selectbox('close');
						return false;
					} else if (e.keyCode == 9) { // enter || escape
						$(sb).c2Selectbox('close');
						return true;
					} else if (e.keyCode == 33) { // page up
						if ($(sb).hasClass('c2-sb-open')) {
							var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
							if (api) {
								api.scrollBy(0, -$(sb).find('.c2-sb-list-wrap').height());
							}

							var top = api.getContentPositionY();
							var a = null;
							$(sb).find('.c2-sb-list-item-link').each(function() {
								if ($(this).position().top >= top) {
									a = $(this);
									return false;
								}
							});
							if (!a) {
								a = $(sb).find('.c2-sb-list-item-link:first');
							}
						} else {
							var a = $(sb).find('.c2-sb-list-item-link-active').closest('.c2-sb-list-item');
							prev = $(a).prev();
							for (var i = 0; i < 5; i++) {
								if (prev.length == 0) {
									break;
								}
								if (prev.find('.c2-sb-list-item-group:eq(0)').length != 0) {
									prev = prev.prev();
									i--;
									continue;
								}
								a = prev;
								prev = prev.prev();
							};
							a = $(a).find('.c2-sb-list-item-link:eq(0)');
						}


						// Almost duplicated code
						$(a).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
						$(a).addClass('c2-sb-list-item-link-active');
						$(a).closest('.c2-sb-wrap').find('.c2-sb-text').text($(a).text());
						$(sb).prev().val($(a).attr('data-value')).change();

						return false;
					} else if (e.keyCode == 34) { // page down
						if ($(sb).hasClass('c2-sb-open')) {
							var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
							if (api) {
								api.scrollBy(0, $(sb).find('.c2-sb-list-wrap').height());
							}

							var top = api.getContentPositionY() + $(sb).find('.c2-sb-list-wrap').height();
							var a = null;
							$(sb).find('.c2-sb-list-item-link').each(function() {
								if ($(this).position().top >= top) {
									return false;
								}
								a = $(this);
							});
							if (!a) {
								a = $(sb).find('.c2-sb-list-item-link:last');
							}
						} else {
							var a = $(sb).find('.c2-sb-list-item-link-active').closest('.c2-sb-list-item');
							next = $(a).next();
							for (var i = 0; i < 5; i++) {
								if (next.length == 0) {
									break;
								}
								if (next.find('.c2-sb-list-item-group:eq(0)').length != 0) {
									next = next.next();
									i--;
									continue;
								}
								a = next;
								next = next.next();
							};
							a = $(a).find('.c2-sb-list-item-link:eq(0)');
						}

						// Almost duplicated code
						$(a).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
						$(a).addClass('c2-sb-list-item-link-active');
						$(a).closest('.c2-sb-wrap').find('.c2-sb-text').text($(a).text());
						$(sb).prev().val($(a).attr('data-value')).change();

						return false;
					} else if (e.keyCode == 36) { // home
						var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
						if (api) {
							api.scrollToY(0);
						}

						var a = $(sb).find('.c2-sb-list-item-link:first');
						$(a).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
						$(a).addClass('c2-sb-list-item-link-active');
						$(a).closest('.c2-sb-wrap').find('.c2-sb-text').text($(a).text());
						$(sb).prev().val($(a).attr('data-value')).change();

						return false;
					} else if (e.keyCode == 35) { // end
						var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
						if (api) {
							api.scrollToY($(sb).find('.c2-sb-list').height());
						}

						var a = $(sb).find('.c2-sb-list-item-link:last');
						$(a).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
						$(a).addClass('c2-sb-list-item-link-active');
						$(a).closest('.c2-sb-wrap').find('.c2-sb-text').text($(a).text());
						$(sb).prev().val($(a).attr('data-value')).change();

						return false;
					} else {
						textsearch = function () {
							var items_found = -1;
							var offset = isNaN(parseInt($(sb).data('guess-offset'))) ? 0 : parseInt($(sb).data('guess-offset'));

							if (text.length == 1) {
								if (text[0] == $(sb).data('guess-offset-letter')) {
									offset ++;
								} else {
									offset = 0;
									$(sb).data('guess-offset-letter', text);
								}
							} else {
								var all_the_same = true;
								for (var i = text.length - 1; i >= 0; i--) {
									if (text[i] != text[0]) {
										all_the_same = false;
										break;
									}
								};

								if (all_the_same) {
									text = text[0];
									offset ++;
									$(sb).data('guess-offset-letter', text);
								}
							}

							var first = false;
							var found = false;
							function activate(active) {
								$(active).closest('.c2-sb-list-wrap').find('.c2-sb-list-item-link').removeClass('c2-sb-list-item-link-active');
								$(active).addClass('c2-sb-list-item-link-active');
								$(active).closest('.c2-sb-wrap').find('.c2-sb-text').text($(active).text());
								$(sb).prev().val($(active).attr('data-value')).change();

								var api = $(sb).find('.c2-sb-list-wrap').data('jsp');
								if (api) {
									api.scrollToElement($(active), false);
								}
							}
							$(sb).find('.c2-sb-list-item-link').each(function() {
								var compare_to = $(this).text().toLowerCase().substr(0, text.length);
								if (compare_to == text) {
									if (!first) {
										first = $(this);
									}
									activate($(this));
									
									/*
									if ($(active).position().top < $(sb).find('.c2-sb-list-wrap').scrollTop() || $(active).position().top + 1 > $(sb).find('.c2-sb-list-wrap').scrollTop() + $(sb).find('.c2-sb-list-wrap').height()) {
										var new_top = $(active).position().top;
										$(sb).find('.c2-sb-list-wrap').scrollTop(new_top);
									}
									*/
									items_found ++;
									if (offset == items_found) {
										found = true;
										return false;
									}
								}
							});
							if (!found && first) {
								activate($(first));
								offset = 0;
							}
							$(sb).data('guess-offset', offset);
						}

						var allowed = {'A':'A', 'B':'B', 'C':'C', 'D':'D', 'E':'E', 'F':'F', 'G':'G', 'H':'H', 'I':'I', 'J':'J', 'K':'K', 'L':'L', 'M':'M', 'N':'N', 'O':'O', 'P':'P', 'Q':'Q', 'R':'R', 'S':'S', 'T':'T', 'U':'U', 'V':'V', 'W':'W', 'X':'X', 'Y':'Y', 'Z':'Z', '0':'0', '1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8', '9':'9', ' ':' ', '.':'.', ',':',', '-':'-', '_':'_', '!':'!', '?':'?', '"':'"', "'":"'", '(':'(', ')':')'};

						if (typeof $(sb).data('guess-buffer-text') == 'undefined') {
							$(sb).data('guess-buffer-text', new String());
						}
						var text = $(sb).data('guess-buffer-text');
						var timeout = $(sb).data('guess-buffer-timeout');
						clearTimeout(timeout);
						timeout = setTimeout(function() {
							/*if ( navigator.userAgent.toLowerCase().indexOf('msie') === -1 ) {
								textsearch();
							}*/
							$(sb).data('guess-buffer-text', new String());
						}, 500);
						$(sb).data('guess-buffer-timeout', timeout);

						var character = String.fromCharCode(e.keyCode);

						if ( e.keyCode > 95 && e.keyCode < 106 ) {
							character = String.fromCharCode(e.keyCode - 48);
						}

						if (typeof allowed[character] == 'undefined') {
							return true;
						} else {
							text += character.toLowerCase();
							$(sb).data('guess-buffer-text', text);
							if ( navigator.userAgent.toLowerCase().indexOf('msie') === -1 ) {
								textsearch();
							}
							return false;
						}
					}
				}
				return true;
			});

			$('body').click(function(e) {
				$('.c2-sb-wrap').removeClass('c2-sb-focused');
				if (!$(e.target).is('.c2-sb-wrap *')) {
					$('.c2-sb-wrap').c2Selectbox('close');
				}
				return true;
			});
		} else {
			if (action == 'close') {
				$(this).each(function() {
					var targets = [];
					if ($(this).is('.c2-sb-wrap')) {
						targets.push($(this));
					} else {
						$(this).find('.c2-sb-wrap').each(function() {
							targets.push($(this));
						});
					}
					for (var i = 0; i < targets.length; i++) {
						var t = targets[i]
						if ($(t).find('.c2-sb-list-wrap').is(':visible')) {
							$(t).removeClass('c2-sb-open');
							$(t).addClass('c2-sb-closing');
							$(t).find('.c2-sb-list-wrap').stop(true, true).slideUp(c2_animation_speed, function() {
								$(this).closest('.c2-sb-wrap').removeClass('c2-sb-closing');
							});
						}
					};
				});
			}

			if ( action == 'update' ){
				$(this).each(function(){
					var $the_select = $(this),
						$c2Select = $(this).next('.c2-sb-wrap'),
						currentVal = $the_select.find('option:selected').val();

					if(!currentVal.length){
						$c2Select.removeClass('changed')
					}
					$c2Select.find('.c2-sb-text').text($the_select.find('option:selected').text());
					$c2Select.find('.c2-sb-list-item-link-active').removeClass('c2-sb-list-item-link-active');
					$c2Select.find('.c2-sb-list-item-link[data-value="'+currentVal+'"]').addClass('c2-sb-list-item-link-active');
				});
			}

		}
	};
})(jQuery);
