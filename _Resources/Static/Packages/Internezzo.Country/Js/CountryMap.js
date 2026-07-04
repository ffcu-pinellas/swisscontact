'use strict';

window.addEventListener('nezzo-amcharts-loaded', event => {

    (function ($) {

	am4core.ready(function() {

	    am4core.addLicense("CH235502131");
	    am4core.addLicense("MP235502131");

	    am4core.useTheme(am4themes_animated);

	    $('[data-internezzo-country-map]').each(function (idx, mapElement) {
		let chart, $mapElement;

		$mapElement = $(mapElement);

		chart = am4core.create(mapElement, am4maps.MapChart);
		chart.geodata = am4geodata_worldMoroccoLow;

		chart.projection = new am4maps.projections.Orthographic();
		chart.panBehavior = "rotateLongLat";
		chart.deltaLatitude = -10;
		chart.deltaLongitude = -80;
		chart.padding(20,20,20,20);
		chart.homeZoomLevel = 1.1;

		chart.chartContainer.wheelable = false;
		chart.zoomControl = new am4maps.ZoomControl();

		chart.adapter.add("deltaLatitude", function(delatLatitude){
		    return am4core.math.fitToRange(delatLatitude, -90, 90);
		})

		// globe background
		let backgroundSeries = chart.backgroundSeries.mapPolygons.template;
		backgroundSeries.polygon.fill = am4core.color("#C2BAB0");
		backgroundSeries.polygon.fillOpacity = 0.2;

		// globe lines
		let graticuleSeries = chart.series.push(new am4maps.GraticuleSeries());
		graticuleSeries.mapLines.template.line.stroke = am4core.color("#000000");
		graticuleSeries.mapLines.template.line.strokeOpacity = 0.3;
		graticuleSeries.fitExtent = false;

		let WorldpolygonSeries = new am4maps.MapPolygonSeries();
		WorldpolygonSeries.useGeodata = true;
		chart.series.push(WorldpolygonSeries);

		// default countries
		let polygonTemplate = WorldpolygonSeries.mapPolygons.template;
		polygonTemplate.fill = am4core.color("#00477A");
		polygonTemplate.fillOpacity = 0.4;
		polygonTemplate.stroke = am4core.color("#ffffff");
		polygonTemplate.strokeWidth = 1.0;

		let SCcountriespolygonSeries = new am4maps.MapPolygonSeries();
		SCcountriespolygonSeries.useGeodata = true;
		chart.series.push(SCcountriespolygonSeries);

		SCcountriespolygonSeries.dataSource.url = $mapElement.data('internezzo-country-map-countries-url');
		SCcountriespolygonSeries.dataSource.events.on("done", function(ev) {
		    let SCcountriesInclude = ev.data.map(function(a) {
			return a.id;
		    });
		    SCcountriespolygonSeries.include = SCcountriesInclude;
		});

		// marked countries
		let SCcountriespolygonTemplate = SCcountriespolygonSeries.mapPolygons.template;
		SCcountriespolygonTemplate.fill = am4core.color("#00477A");
		SCcountriespolygonTemplate.stroke = am4core.color("#ffffff");
		SCcountriespolygonTemplate.strokeWidth = 1.0;
		let hs = SCcountriespolygonTemplate.states.create("hover");
		hs.properties.fill = am4core.color("#5A5F61");

		SCcountriespolygonTemplate.url = "{url}";

		SCcountriespolygonSeries.tooltip.label.interactionsEnabled = true;
		SCcountriespolygonSeries.tooltip.keepTargetHover = true;
		SCcountriespolygonSeries.calculateVisualCenter = true;
		SCcountriespolygonSeries.mapPolygons.template.tooltipPosition = "fixed";
		SCcountriespolygonSeries.tooltip.htmlContainer = true;
		SCcountriespolygonSeries.tooltip.background.cornerRadius = 0;
		SCcountriespolygonSeries.tooltip.background.strokeOpacity = 0;
		SCcountriespolygonSeries.tooltip.getFillFromObject = false;
		SCcountriespolygonSeries.tooltip.background.fill = false;
		SCcountriespolygonSeries.tooltip.label.fill = am4core.color("#000000");
		SCcountriespolygonTemplate.tooltipHTML = '<div class="countryTooltip"><div class="countryTooltip-inner"><p class="map-countrylink map-countrylink-arrow"><a href="{url}" target="_self">{name}</a></p></div></div>';

		let animation;

		document.addEventListener('scroll', inViewport);

		function inViewport() {
		    const mapElementTop = $mapElement.get(0).getBoundingClientRect().top;
		    const mapElementBottom = $mapElement.get(0).getBoundingClientRect().bottom;

		    if (window.innerHeight > mapElementTop && mapElementBottom > 0) {
			setTimeout(function() {
			    animation = chart.animate({property: 'deltaLongitude', to:100000}, 20000000);
			}, 300);
		    } else {
			if (animation) {
			    animation.stop();
			}
		    }
		}

		chart.seriesContainer.events.on('down', function() {
		    if (animation) {
			animation.stop();
		    }
		})
	    });
	});

    })(jQuery);

});
