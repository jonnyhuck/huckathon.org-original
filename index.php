<!DOCTYPE html>
<html>
	<head>
		<title>Huckathon Task Manager</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="https://unpkg.com/leaflet@1.2.0/dist/leaflet.css" integrity="sha512-M2wvCLH6DSRazYeZRIm1JnYyh22purTM+FDB5CsyxtQJYeKq83arPe5wgbNmcFXGqiSH2XR8dT/fJISVA1r/zQ==" crossorigin=""/>
		<script src="https://unpkg.com/leaflet@1.2.0/dist/leaflet.js" integrity="sha512-lInM/apFSqyy1o6s89K4iQUKg6ppXEgsVxT35HbzUupEVRh2Eu9Wdl4tHj7dZO0s1uvplcYGmt3498TtHq+log==" crossorigin=""></script>
		<style>
			html, body, #map {
				width: 100%; 
				height: 100%; 
				padding: 0; 
				margin: 0;
			}
			button {	
				font-family: Gill Sans Extrabold, sans-serif;
				color: white;
				padding: 20px;
				font-weight: bold;
				font-size: large;
			}
			#greenBtn {
				background-color: lime;
			}
			#redBtn {
				background-color: red;			
			}
			#redBtn {
				background-color: orange;			
			}
		</style>
	</head>
	<body>
		<div id="map"></div>
		
		<?php
			require('scripts/connection.php');
			$db = new PDO($connstr);
			$stmt = $db->query("select ogc_fid, ST_XMin(b), ST_YMin(b), ST_XMax(b), ST_YMax(b), concat('https://www.openstreetmap.org/edit#map=18/', ST_y(a.c), '/', ST_x(a.c)) from (select ogc_fid, Box2D(ST_Transform(ST_Envelope(wkb_geometry), 4326)) as b, ST_AsText(ST_Transform(ST_Centroid(wkb_geometry), 4326)) as c from grid where status = 0 limit 1) a;");
			while ($row = $stmt->fetch())
			{	
				echo "<script>\n";
				echo "	var id = " . $row['ogc_fid'] . ";\n";
				echo "	var minx = " . $row['st_xmin'] . ";\n";
				echo "	var miny = " . $row['st_ymin'] . ";\n";
				echo "	var maxx = " . $row['st_xmax'] . ";\n";
				echo "	var maxy = " . $row['st_ymax'] . ";\n";
				echo "	var osmurl = \"" . $row['concat'] . "\";\n";
				echo "</script>\n";
			}
		?>
		
		<script>
		
			//flag for if user is mapping or not
			var mapping = false;
		
			//lock the requested square - in essence thing else is a callback to this
    		makeRequest(['scripts/markLocked.php?id=', id].join(''), function(d){
    		
    			console.log(d.rows.toString() + " locked (" + id + ")");

				//make a map
				var map = L.map('map', {maxZoom: 17});
			
				// define rectangle geographical bounds
				var bounds = [[miny, minx], [maxy, maxx]];	// remember latlng is y, x!
				L.rectangle(bounds, {color: "#ff7800", weight: 3, opacity: 0.5, fillOpacity: 0.1}).addTo(map);
				// zoom the map to the rectangle bounds
				map.fitBounds(bounds);
			
				//OpenStreetMap layer
				var basemap0 = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
					attribution: '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors,<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>'
				});
				basemap0.addTo(map);

				//ESRI Satellite Imagery layer
				var basemap1 = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
					attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
					maxZoom: 18
				});
				basemap1.addTo(map);
			
				//add the button panel to the map
				var customControl =  L.Control.extend({
					options: { position: 'topright' },
					onAdd: function (m) {	//construct the button
						var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
							container.style.backgroundColor = 'white';     
							container.innerHTML = '<button id="greenBtn" onclick="greenButton()">Start Mapping Now!</button> <button id="yellowBtn" onclick="yellowButton()">Not Sure, Skip!</button> <button id="redBtn" onclick="redButton()">No Mapping Needed!</button>';
							container.style.padding = "5px";
							container.style.color = "black";
			
						//prevent clicks from being propagated to the map
						L.DomEvent.disableClickPropagation(container);
						return container;
					}
				});
				map.addControl(new customControl());
			
				//setup layers control for the map
				L.control.layers({'OpenStreetMap': basemap0, 'Satellite': basemap1},{},{collapsed: false}).addTo(map);
			
			});
			
			/**
			 * Handlers for the functionality of the red button
			 */
			function redButton() {
			
				// there is nothing to map in the square
				if(!mapping){
				
					//mark the square as empty
    				makeRequest(['scripts/markEmpty.php?id=', id].join(''), function(d){
    				
    					//TODO: Verification
    					console.log(d.rows.toString() + " marked empty (" + id + ")");
    				 					
						//reload the page to get new square
						location.reload(); 
    				});
				
				//set the square as incomplete
				} else {
				
					//mark the square as not mapped
    				makeRequest(['scripts/markNotMapped.php?id=', id].join(''), function(d){ 
    				
						//TODO: Verification
						console.log(d.rows.toString() + " reset (" + id + ")");
    								
						//reload the page to get new square
						location.reload(); 
    				});
				}
				
			}
			
			/**
			 * handlers for the functionality of the green button
			 */
			function greenButton() {
				
				// start mapping
				if(!mapping){
				
					//set the flag to mapping
					mapping = true;
					
					//set the buttons to 'done' or 'cancel'
					document.getElementById('greenBtn').innerHTML = "I've finished mapping this square";
					document.getElementById('yellowBtn').visible = false;
					document.getElementById('redBtn').innerHTML = "I've given up mapping this square";
			
					//open the OSM ID Editor at the correct location
					var redirectWindow = window.open(osmurl, '_blank');
					redirectWindow.location;
    			
    			// end mapping
    			} else {
    			
    				//set the flag to not mapping
					mapping = true;
    			
    				//mark the square as complete
    				makeRequest(['scripts/markComplete.php?id=', id].join(''), function(d){
    				
						//TODO: Verification
						console.log(d.rows.toString() + " marked complete (" + id + ")");
    									
						//reload the page to get new square
						location.reload(); 
    				});
    			}
			}
			
			/**
			 * Handlers for the functionality of the yellow button
			 */
			function yellowButton() {
			
				// there is nothing to map in the square
				if(!mapping){
				
					//mark the square as not mapped
    				makeRequest(['scripts/markNotMapped.php?id=', id].join(''), function(d){ 
    				
						//TODO: Verification
						console.log(d.rows.toString() + " reset (" + id + ")");
    								
						//reload the page to get new square
						location.reload(); 
    				});
				}
			}
			
			/**
			 * Make a request for JSON over HTTP, pass resulting text to callback when ready
			 */
			function makeRequest(url, callback) {

				//initialise the XMLHttpRequest object
				var httpRequest = new XMLHttpRequest();

				//this will happen if the XMLHttpRequest object cannot be created (this can happen in Internet Explorer)
				if (!httpRequest) {
					//warn the user and exit the function by returning false
					alert('Warning: Cannot create an XMLHTTP instance');
					return false;
				}

				//set an event listener for when the HTTP state changes
				httpRequest.onreadystatechange = function () {

					//if it works, parse the JSON and pass to the callback	
					//a successful HTTP request returns a state of DONE and a status of 200
					if (httpRequest.readyState === XMLHttpRequest.DONE) {
						if (httpRequest.status === 200) {
							callback(JSON.parse(httpRequest.responseText));
						}
					}
				};

				//prepare and send the request
				httpRequest.open('GET', url);
				httpRequest.send();
			}
		</script>
	</body>
</html>