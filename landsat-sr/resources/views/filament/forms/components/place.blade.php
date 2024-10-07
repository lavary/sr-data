<x-dynamic-component
	:component="$getFieldWrapperView()"
	:id="$getId()"
	:label="$getLabel()"
	:label-sr-only="$isLabelHidden()"
	:helper-text="$getHelperText()"
	:required="$isRequired()"
	:state-path="$getStatePath()">

	<input id="searchBox" wire:model.defer="{{ $getStatePath() }}" class="border border-gray-200 shadow-sm rounded-md" />
	<div id="map" style="width: 100%; height: 400px; border-radius: 15px; overflow: hidden;"></div>
</x-dynamic-component>

@script
<script>
	let map;
	let marker;
	let latLng = {}; // To store latitude and longitude

	console.log('loaded')

	function initMap() {
		console.log('map initiating ...')

		// Create a map centered on a default location (e.g., initial location).
		map = new google.maps.Map(document.getElementById('map'), {
			center: {
				lat: -33.8688,
				lng: 151.2195
			}, // Default to Sydney
			zoom: 13
		});

		// Create an empty marker that will be updated when the user selects a place
		marker = new google.maps.Marker({
			map: map,
			draggable: true,
			visible: false
		});

		map.addListener('click', function(event) {
			latLng = {
				lat: event.latLng.lat(),
				lng: event.latLng.lng()
			};

			// Place the marker at the clicked location
			marker.setPosition(latLng);
			marker.setVisible(true);

			// Center the map to the clicked location
			//map.setCenter(latLng);

			// Update the output with the clicked coordinates
			//document.getElementById('output').textContent = `Latitude: ${latLng.lat}, Longitude: ${latLng.lng}`;

		});
	}

	const searchBox = document.getElementById('searchBox');
	const output = document.getElementById('output');

	const autocomplete = new google.maps.places.Autocomplete(searchBox);

	autocomplete.addListener('place_changed', function() {
		const place = autocomplete.getPlace();
		if (!place.geometry) {
			output.textContent = "No details available for that place.";
			return;
		}

		// Get the latitude and longitude
		latLng = {
			lat: place.geometry.location.lat(),
			lng: place.geometry.location.lng()
		};

		let r = fetch(`http://127.0.0.1:8000/overpass?lat=${latLng.lat}&lon=${latLng.lng}`)
			.then(res => res.json())
			.then(json => {
				//document.getElementById('schedule').textContent = `Overpass: ${json.overpass}`;
				const otField = document.querySelector('input[id="data.overpass_time"]')

				otField.value = json.overpass;
				otField.dispatchEvent(new Event('input'));

			})

		// Display the lat/long in the paragraph
		const latField = document.querySelector('input[id="data.latitude"]');
		const lngField = document.querySelector('input[id="data.longitude"]');

		latField.value = latLng.lat;
		lngField.value = latLng.lng;

		latField.dispatchEvent(new Event('input'));
		lngField.dispatchEvent(new Event('input'));

		// Center the map to the selected place
		map.setCenter(latLng);
		map.setZoom(15); // You can adjust the zoom level as needed

		// Place the marker at the selected location
		marker.setPosition(latLng);
		marker.setVisible(true);
	});

	// Initialize the map when the page loads
	initMap();
</script>
@endscript