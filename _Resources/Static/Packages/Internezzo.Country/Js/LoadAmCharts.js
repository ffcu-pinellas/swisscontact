'use strict';

window.addEventListener('ucEvent', event => {
	if (event.detail && event.detail.event === 'consent_status') {
		if (event.detail['amCharts'] === true) {
			let scriptUris = [
			    'https://www.amcharts.com/lib/4/core.js',
			    'https://www.amcharts.com/lib/4/maps.js',
			    'https://www.amcharts.com/lib/4/geodata/worldMoroccoLow.js',
			    'https://www.amcharts.com/lib/4/geodata/worldLow.js',
			    'https://www.amcharts.com/lib/4/geodata/worldMoroccoUltra.js',
			    'https://www.amcharts.com/lib/4/themes/animated.js',
			    'https://unpkg.com/supercluster@7.1.3/dist/supercluster.min.js'
			];

			let lastScriptElement = false;
			while (scriptUris.length > 0) {
			    let scriptUri = scriptUris.shift();
			    let isLast = scriptUris.length === 0;
			    let script = document.createElement('script');
			    script.src = scriptUri;
			    script.async = false;


			    if (lastScriptElement === false) {
				document.body.appendChild(script);
			    } else {
			    	lastScriptElement.addEventListener('load', () => {
				    document.body.appendChild(script);
				    if (isLast) {
				    	script.addEventListener('load', () => {
				    		const loadedEvent = new Event('nezzo-amcharts-loaded');
						window.dispatchEvent(loadedEvent);
					});
				    }
				});
			    }
			    lastScriptElement = script;
			}
		}
	}
});







