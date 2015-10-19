;(function($, window, document, undefined) {
	// Variables for the current scope
	var $win = $(window);
	var $doc = $(document);
	var $html = $(document.documentElement);
	var pieChart;
	var pieChartOpts;
	var barChart;
	var barChartOpts;

	$doc.ready(function() {
		// Toggle Main Menu
		$('.nav-toggle').on('click', function() {
			$html.toggleClass('show-nav').removeClass('show-login show-search');
		});

		// Toggle Login Form
		$('.login-toggle').on('click', function() {
			$html.toggleClass('show-login').removeClass('show-nav show-search');
		});

		// Toggle Search Form
		$('.search-toggle').on('click', function() {
			$html.toggleClass('show-search').removeClass('show-nav show-login');
		});

		/*
			Add icons with tooltips to all table rows
			They will be visible below tablet landscape breakpoint
			and will replace the table head icons
		*/ 
		$('.hp-counter + .main .table tr, #table_tri tr, .vos_prets table.detail-ope tr').each(function() {
			$(this).find('td').each(function(indx) {
				var $icon = $(this).closest('.table').find('th').eq(indx).html();

				$($icon).prependTo($(this));
			});
		});

		$('.popup-link').colorbox({
			maxWidth: '90%',
			onComplete: function() {
				$('.custom-select').c2Selectbox();
			}
		});

		$(document).on('click', '.popup-open', function(event) {
			event.preventDefault();

			var _href = $(this).data('href');

			$.colorbox({
				href: _href,
				onComplete: function() {
					$('.custom-select').c2Selectbox();
				}
			});
		});

		$win.on('load resize', function() {
			if ( $win.width() < 768 ) {
				if ( $('#pie-chart').length ) {
					pieChart = $('#pie-chart').highcharts();
					pieChartOpts = pieChart.options;

					pieChartOpts.chart.width = undefined;
					pieChartOpts.chart.renderTo = '#pie-chart';
					pieChartOpts.plotOptions.pie.dataLabels.distance = 1;
					pieChartOpts.plotOptions.pie.dataLabels.padding = 0;

					$('#pie-chart').highcharts(pieChartOpts);
				}

				if ( $('#bar-chart').length ) {
					barChart = $('#bar-chart').highcharts();
					barChartOpts = barChart.options;

					barChartOpts.chart.width = undefined;
					barChartOpts.chart.renderTo = '#bar-chart';

					$('#bar-chart').highcharts(barChartOpts);
				}
			}
		});

		$('.table-manage').each(function() {
			$(this).find('tr').each(function() {
				$(this).find('td').each(function(indx) {
					$(this).attr('data-title', $.trim($(this).closest('.form-body').find('table:eq(0) th').eq(indx).text()));
				});
			});
		});

		$doc.on('click touchend', function(event) {
			var $target = $(event.target);

			if (
				!$target.hasClass('login-panel') && 
				!$target.parents('.login-panel').length && 
 				!$target.hasClass('login-toggle') && 
 				!$target.parents('.login-toggle').length
			) {                    
				$html.removeClass('show-login');
			}

			if (
				!$target.hasClass('styled-nav') && 
				!$target.parents('.styled-nav').length && 
				!$target.hasClass('nav-toggle') && 
				!$target.parents('.nav-toggle').length
			) {
				$html.removeClass('show-nav');
			}

			if (
				!$target.hasClass('search') && 
				!$target.parents('.search').length && 
				!$target.hasClass('search-toggle') && 
				!$target.parents('.search-toggle').length
			) {
				$html.removeClass('show-search');
			}
		});

		$('#cboxOverlay').on('click', function() {
			$.colorbox.close();
		});
	});
})(jQuery, window, document);
