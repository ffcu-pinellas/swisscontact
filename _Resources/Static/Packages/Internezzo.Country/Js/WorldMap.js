'use strict';

window.addEventListener('nezzo-amcharts-loaded', event => {

    (function ($) {

	const ZOOM_DURATION = 500;
	const BBOX = [-180, -180, 180, 180];

	const cluster = new Supercluster({
	    radius: 100,
	    minPoints: 3,
	    maxZoom: 31,
	    minZoom: 1,
	});
	const locationData = [];
	let initialized = false;
	let zooming = false;

	/**
	 * Helper to detect undefined or null values.
	 *
	 * @param value
	 * @returns {boolean}
	 */
	function isNil(value) {
	    return value === null || value === undefined;
	}

	/**
	 * Returns a mapSeries with the given name.
	 *
	 * @param map
	 * @param name
	 * @returns {object}
	 */
	function getNamedSeries(map, name) {
	    const series = Array.from(map?._series?._values);
	    const namedSeries = series.filter((serie) => {
		return serie._id === name;
	    });

	    return namedSeries.pop();
	}

	/**
	 * Returns location data from map element.
	 *
	 * @param mapElement
	 * @returns {[]}
	 */
	function getLocationsFromMapElement(mapElement) {
	    if (locationData.length === 0) {
		$.each(mapElement.data('internezzo-world-map-locations'), function () {
		    locationData.push({
			"address": this.address,
			"projectName": this.project.name,
			"uri": this.project.uri,
			"lat": parseFloat(this.lat),
			"lng": parseFloat(this.lng),
			"hidden": true
		    });
		});
	    }

	    return locationData;
	}

	/**
	 * Converts given location data to a GeoJSON conform dataset.
	 *
	 * @param locations
	 * @returns {[]}
	 */
	function convertLocationsToGeoJSON(locations) {
	    const geoJson = [];
	    Array.from(locations).forEach(location => {
		const latitude = Number.isNaN(location.lat) ? 0 : location.lat;
		const longitude = Number.isNaN(location.lng) ? 0 : location.lng;
		geoJson.push(
		    {
			"type": "Feature",
			"properties": {
			    "address": location.address,
			    "projectName": location.projectName,
			    "uri": location.uri,
			},
			"geometry": {
			    "type": "Point",
			    "coordinates": [
				longitude,
				latitude
			    ]
			}
		    }
		)
	    })

	    return geoJson;
	}

	/**
	 * Returns all existing clusters for each zoom level as array.
	 *
	 * @returns {{}}
	 */
	function getAllClusters() {
	    const clusters = {};

	    cluster.trees.forEach((element, zoomLevel) => {
		const clusteredPoints = cluster.getClusters(BBOX, zoomLevel);
		clusteredPoints.forEach(point => {
		    if (point?.properties?.cluster === true) {
			const clusterId = point?.properties?.cluster_id;
			clusters[clusterId] = {
			    'title': `Cluster with ${point?.properties?.point_count} locations`,
			    'clusterId': clusterId,
			    'amountOfLocations': point?.properties?.point_count,
			    'lat': point?.geometry?.coordinates[1],
			    'lng': point?.geometry?.coordinates[0],
			    'hidden': true
			};
		    }
		})
	    })

	    return Object.values(clusters);
	}

	/**
	 * Iterates over GeoJSON data and returns clusters and locations.
	 *
	 * @param geoJSON
	 * @returns {{locations: [], clusters: []}}
	 */
	function getLocationsAndClustersFromGeoJSON(geoJSON) {
	    const locations = [];
	    const clusters = [];

	    Array.from(geoJSON).forEach(point => {
		if (point?.properties?.cluster === true) {
		    clusters.push({
			'title': `Cluster with ${point?.properties?.point_count} locations`,
			'clusterId': point?.properties?.cluster_id,
			'amountOfLocations': point?.properties?.point_count,
			'lat': point?.geometry?.coordinates[0],
			'lng': point?.geometry?.coordinates[1]
		    });
		} else {
		    locations.push({
			'lat': point?.geometry?.coordinates[0],
			'lng': point?.geometry?.coordinates[1],
			...point.properties
		    });
		}
	    })

	    return {
		locations: locations,
		clusters: clusters,
	    }
	}

	/**
	 * Returns array with included countries based on the map element data attribute.
	 *
	 * @param mapElement
	 * @returns {[]}
	 */
	function getCountriesFromLocations(mapElement) {
	    const includedCountries = [];
	    $.each(mapElement.data('internezzo-world-map-locations'), function () {
		if (this.countries) {
		    Array.from(this.countries).forEach(country => {
			const upperCaseIsoCode = country.trim().toUpperCase();
			if (!includedCountries.includes(upperCaseIsoCode)) {
			    includedCountries.push(upperCaseIsoCode);
			}
		    })
		}
	    });

	    return includedCountries;
	}

	/**
	 * Renders the countries on the map.
	 *
	 * @param map
	 * @param countries
	 */
	function renderCountries(map, countries) {
	    let SCcountriespolygonSeries = new am4maps.MapPolygonSeries();
	    SCcountriespolygonSeries.useGeodata = true;
	    map.series.push(SCcountriespolygonSeries);
	    SCcountriespolygonSeries.include = countries;
	    let SCcountriespolygonTemplate = SCcountriespolygonSeries.mapPolygons.template;
	    SCcountriespolygonTemplate.fill = am4core.color("#00477A");
	    SCcountriespolygonTemplate.stroke = am4core.color("#ffffff");
	    SCcountriespolygonTemplate.strokeWidth = 1.0;
	}

	/**
	 * Renders location markers.
	 *
	 * @param map
	 * @param locations
	 */
	function renderLocations(map, locations) {
	    let locationSeries = getNamedSeries(map, 'locations');
	    if (isNil(locationSeries)) {
		const newLocationSeries = new am4maps.MapImageSeries();
		newLocationSeries.id = 'locations';
		map.series.push(newLocationSeries);
		locationSeries = newLocationSeries;
	    }

	    let locationSeriesTemplate = locationSeries.mapImages.template;
	    let circle = locationSeriesTemplate.createChild(am4core.Circle);
	    circle.radius = 5;
	    circle.x = am4core.percent(50);
	    circle.y = am4core.percent(50);
	    circle.fill = am4core.color("#f85c10");
	    circle.stroke = am4core.color("#393d3e");
	    circle.strokeWidth = 2

	    locationSeriesTemplate.propertyFields.latitude = "lat";
	    locationSeriesTemplate.propertyFields.longitude = "lng";
	    locationSeriesTemplate.horizontalCenter = "middle";
	    locationSeriesTemplate.verticalCenter = "middle";
	    locationSeriesTemplate.align = "center";
	    locationSeriesTemplate.valign = "middle";
	    locationSeriesTemplate.width = 8;
	    locationSeriesTemplate.height = 8;
	    locationSeriesTemplate.nonScaling = true;
	    locationSeriesTemplate.fill = am4core.color("#000");
	    locationSeriesTemplate.background.fillOpacity = 0;
	    locationSeriesTemplate.background.fill = am4core.color("#ffffff");
	    locationSeriesTemplate.setStateOnChildren = true;
	    locationSeriesTemplate.states.create("hover");

	    locationSeries.tooltip.label.interactionsEnabled = true;
	    locationSeries.tooltip.keepTargetHover = true;
	    locationSeries.calculateVisualCenter = true;
	    locationSeries.tooltip.htmlContainer = true;
	    locationSeries.tooltip.background.cornerRadius = 0;
	    locationSeries.tooltip.background.strokeOpacity = 0;
	    locationSeries.tooltip.getFillFromObject = false;
	    locationSeries.tooltip.background.fill = false;
	    locationSeries.tooltip.label.fill = am4core.color("#000000");
	    locationSeriesTemplate.tooltipHTML = '<div class="countryTooltip"><div class="countryTooltip-inner"><p class="map-countrylink"><a href="{uri}" target="_self">{projectName}<br><span class="map-countrylink-address">{address}</span></a></p></div></div>';
	    locationSeriesTemplate.url = '{uri}'
	    locations.forEach((location, index) => {
		let otherLocations = locations.filter(function(item) {
		    return item !== location
		});
		while (hasCloseLocation(location, otherLocations)) {
		    locations[index].lat = location.lat + 0.05;
		    location.lat = location.lat + 0.05;
		}
		if (location.hasOwnProperty('hidden')) {
		    locationSeriesTemplate.hidden = location.hidden;
		}
		locationSeries.data.push(location);
	    })
	}

	function hasCloseLocation(location, otherLocations) {
	    let hasCloseLocation = false;
	    otherLocations.forEach(otherLocation => {
		let longitude = Math.round((otherLocation.lng + Number.EPSILON) * 1000) / 1000;
		let latitude = Math.round((otherLocation.lat + Number.EPSILON) * 1000) / 1000;
		let locLng = Math.round((location.lng + Number.EPSILON) * 1000) / 1000
		let locLat = Math.round((location.lat + Number.EPSILON) * 1000) / 1000
		if (locLng <= (longitude + 0.05) && locLng >= (longitude - 0.05)) {
		    if (locLat <= (latitude + 0.05) && locLat >= (latitude - 0.05)) {
			hasCloseLocation = true;
		    }
		}
	    })
	    return hasCloseLocation;
	}

	/**
	 * Render the cluster marker.
	 *
	 * @param map
	 * @param clusterItems
	 */
	function renderCluster(map, clusterItems) {
	    let clusterSeries = getNamedSeries(map, 'clusters');
	    if (isNil(clusterSeries)) {
		const newClusterSeries = new am4maps.MapImageSeries();
		newClusterSeries.id = 'clusters';
		map.series.push(newClusterSeries);
		clusterSeries = newClusterSeries;
	    }

	    let clusterSeriesTemplate = clusterSeries.mapImages.template;
	    const container = clusterSeriesTemplate.createChild(am4core.Container);
	    container.width = 30;
	    container.height = 30;
	    container.contentAlign = "center";
	    container.contentValign = "middle";
	    container.layout = 'grid';

	    const circle = container.createChild(am4core.Circle);
	    circle.radius = 15;
	    circle.height = am4core.percent(100);
	    circle.width = am4core.percent(100);
	    circle.fill = am4core.color("#f85c10");
	    circle.stroke = am4core.color("#393d3e");
	    circle.strokeWidth = 2
	    circle.isMeasured = false;
	    circle.horizontalCenter = "left";
	    circle.verticalCenter = "top";

	    const label = container.createChild(am4core.Label);
	    label.text = "{amountOfLocations}";
	    label.isMeasured = false;
	    label.wrap = false;
	    label.marginTop = 5;
	    label.textAlign = 'middle';
	    label.height = am4core.percent(100);
	    label.width = am4core.percent(100);

	    clusterSeriesTemplate.propertyFields.latitude = "lat";
	    clusterSeriesTemplate.propertyFields.longitude = "lng";
	    clusterSeriesTemplate.propertyFields.clusterId = "clusterId";
	    clusterSeriesTemplate.propertyFields.isCluster = true;
	    clusterSeriesTemplate.nonScaling = true;
	    clusterSeriesTemplate.fill = am4core.color("#000");
	    clusterSeriesTemplate.background.fillOpacity = 0;
	    clusterSeriesTemplate.background.fill = am4core.color("#ffffff");
	    clusterSeriesTemplate.setStateOnChildren = true;
	    clusterSeriesTemplate.states.create("hover");
	    clusterSeriesTemplate.events.on("hit", function(event) {
		const clusterPoint = event?.target?.dataItem;
		const clusterData = event?.target?.dataItem?._dataContext;
		event.target.series.chart.zoomIn(clusterPoint?._geoPoint);
	    });

	    clusterItems.forEach(point => {
		const hasCluster = clusterSeries.data.find(element => {
		    return element.cluster_id === point.clusterId;
		});

		// render no doubles on the map
		if (isNil(hasCluster)) {
		    if (point.hasOwnProperty('hidden')) {
			clusterSeriesTemplate.hidden = point.hidden;
		    }
		    clusterSeries.data.push(point);
		}
	    })
	}

	/**
	 * Updates the markers on the map for the current zoom level.
	 * We retrieve the visible locations and clusters from the supercluster API.
	 *
	 * @param map
	 */
	function updatePointOfInterests(map) {
	    setTimeout(() => {
		const currentZoomLevel = Number.parseInt(map.zoomLevel);
		const clusteredPoints = cluster.getClusters(BBOX, currentZoomLevel);
		const pointData = getLocationsAndClustersFromGeoJSON(clusteredPoints);

		// update existing marker
		updateLocationMarker(map, pointData.locations);
		updateClusterMarker(map, pointData.clusters);
		zooming = false;
	    }, ZOOM_DURATION);
	}

	/**
	 * Update the visible markers for clusters.
	 * Check based on the given cluster points if we need to hide or show a marker.
	 * As identifier we use the cluster id of the marker.
	 *
	 * @param map
	 * @param clusterPoints
	 */
	function updateClusterMarker(map, clusterPoints) {
	    const clusterSeries = getNamedSeries(map, 'clusters');
	    clusterSeries?._mapImages?._values.forEach(clusterMarker => {
		const clusterId = clusterMarker?._dataItem?._dataContext?.clusterId
		const visibleClusters = clusterPoints.filter((clusterItem) => {
		    return clusterItem.clusterId == clusterId;
		});
		if (clusterId > 0 && visibleClusters.length > 0) {
		    clusterMarker.show();
		} else {
		    clusterMarker.hide();
		}
	    })
	}

	/**
	 * Update the visible markers for locations.
	 * Check based on the given locations points if we need to hide or show a marker.
	 * As identifier we use the uri of the marker.
	 *
	 * @param map
	 * @param locationPoints
	 */
	function updateLocationMarker(map, locationPoints) {
	    const locationSeries = getNamedSeries(map, 'locations');
	    locationSeries?._mapImages?._values.forEach(locationMarker => {
		const uri = locationMarker?._dataItem?._dataContext?.uri
		const visiblePointsWithUri = locationPoints.filter((location) => {
		    return location.uri == uri;
		});
		if (uri.trim() !== '' && visiblePointsWithUri.length > 0) {
		    locationMarker.show();
		} else {
		    locationMarker.hide();
		}
	    })
	}

	const difference = (arr = []) => {
	    const highest = Math.max(...arr);
	    const lowest = Math.min(...arr);
	    return highest - lowest;
	};

	const average = arr => arr.reduce((a,b) => a + b, 0) / arr.length;

	am4core.ready(function () {

	    am4core.addLicense("CH235502131");
	    am4core.addLicense("MP235502131");

	    am4core.useTheme(am4themes_animated);

	    $('[data-internezzo-world-map]').each(function (idx, mapElement) {
		let chart, $mapElement, worldMapCountry, focusCountryKeys;

		worldMapCountry = $('[data-internezzo-world-map-country]').data('internezzo-world-map-country');
		focusCountryKeys = $('[data-internezzo-focus-countries]').data('internezzo-focus-countries');

		$mapElement = $(mapElement);

		chart = am4core.create(mapElement, am4maps.MapChart);
		chart.geodata = am4geodata_worldMoroccoLow;
		if (worldMapCountry) {
		    chart.geodata = am4geodata_worldMoroccoUltra;
		}
		chart.projection = new am4maps.projections.Miller();
		chart.zoomControl = new am4maps.ZoomControl();
		chart.zoomDuration = ZOOM_DURATION;

		// globe background
		let backgroundSeries = chart.backgroundSeries.mapPolygons.template;
		backgroundSeries.polygon.fill = am4core.color("#C2BAB0");
		backgroundSeries.polygon.fillOpacity = 0.2;

		const polygonSeries = chart.series.push(new am4maps.MapPolygonSeries());
		polygonSeries.exclude = ["AQ"];
		polygonSeries.useGeodata = true;

		// Configure series / default countries
		const polygonTemplate = polygonSeries.mapPolygons.template;
		polygonTemplate.fill = am4core.color("#00477A");
		polygonTemplate.fillOpacity = 0.4;

		let SCcountriespolygonSeries = new am4maps.MapPolygonSeries();
		SCcountriespolygonSeries.useGeodata = true;
		chart.series.push(SCcountriespolygonSeries);

		SCcountriespolygonSeries.include = [$('[data-internezzo-world-map-country]').data('internezzo-world-map-country').toUpperCase()];

		let SCcountriespolygonTemplate = SCcountriespolygonSeries.mapPolygons.template;
		SCcountriespolygonTemplate.fill = am4core.color("#00477A");
		SCcountriespolygonTemplate.stroke = am4core.color("#ffffff");
		SCcountriespolygonTemplate.strokeWidth = 1.0;

		// prepare data for rendering of countries and POIs
		const locations = getLocationsFromMapElement($mapElement);
		const geoJSONLocations = convertLocationsToGeoJSON(locations);

		chart.seriesContainer.events.on("inited", function (ev) {
		    if (!initialized) {
			cluster.load(geoJSONLocations);
			const clusters = getAllClusters();
			const includingCountries = getCountriesFromLocations($mapElement, locations);

			renderCountries(chart, includingCountries);
			renderLocations(chart, locations);
			renderCluster(chart, clusters);
			updatePointOfInterests(chart);
			initialized = true;
		    }
		});

		chart.events.on('zoomlevelchanged', function (event) {
		    if (!zooming && initialized) {
			updatePointOfInterests(chart);
		    }
		    if (initialized) {
			zooming = true;
		    }
		});

		if (worldMapCountry) {
		    chart.chartContainer.wheelable = false;
		    SCcountriespolygonSeries.events.on("ready", function(){
			if (SCcountriespolygonSeries.dataItems.length > 0) {
			    chart.zoomToMapObject(SCcountriespolygonSeries.dataItems.first.mapObject);
			}
		    });
		    chart.homeZoomLevel = 5;
		    chart.minZoomLevel = 5;
		} else {
		    if (focusCountryKeys.length > 0) {
			chart.events.on("ready", function(ev) {
			    var north, south, west, east;
			    for(var i = 0; i < focusCountryKeys.length; i++) {
				var country = polygonSeries.getPolygonById(focusCountryKeys[i].toUpperCase());
				if (north === undefined || (country.north > north)) {
				    north = country.north;
				}
				if (south === undefined || (country.south < south)) {
				    south = country.south;
				}
				if (west === undefined || (country.west < west)) {
				    west = country.west;
				}
				if (east === undefined || (country.east > east)) {
				    east = country.east;
				}
				country.isActive = true
			    }
			    chart.zoomToRectangle(north, east, south, west, 1, true);
			});
		    } else {
			const latLocations = locations.map(function (el) {
			    return el.lat
			});
			const lngLocations = locations.map(function (el) {
			    return el.lng
			});
			const latDiff = difference(latLocations);
			const lngDiff = difference(lngLocations);
			const latAverage = average(latLocations);
			const lngAverage = average(lngLocations);
			let homeZoomLevel = 1;
			if (latDiff > 120 || lngDiff > 120) {
			    homeZoomLevel = 1;
			}
			if (latDiff < 120 && lngDiff < 120) {
			    homeZoomLevel = 2;
			}
			if (latDiff < 50 && lngDiff < 50) {
			    homeZoomLevel = 3;
			}
			if (latDiff < 30 && lngDiff < 30) {
			    homeZoomLevel = 4;
			}
			if (latDiff < 10 && lngDiff < 10) {
			    homeZoomLevel = 5;
			}
			if (homeZoomLevel > 1) {
			    chart.homeGeoPoint = {
				latitude: latAverage,
				longitude: lngAverage
			    };
			}
			chart.homeZoomLevel = homeZoomLevel;
		    }
		}
	    });
	});

    })(jQuery);



});
