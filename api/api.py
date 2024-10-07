
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import ee
import folium
import geehydro
from fastapi.responses import HTMLResponse
import json
import math
from skyfield.api import EarthSatellite, load, wgs84
from datetime import datetime, timedelta
import os

def add_day(date: str, days: int):
    date_obj = datetime.strptime(date, "%Y-%m-%d")
    new_date_obj = date_obj + timedelta(days=1)
    return new_date_obj.strftime("%Y-%m-%d")


def haversine(lat1, lon1, lat2, lon2):
    R = 6371.0  # Earth radius in kilometers
    lat1, lon1, lat2, lon2 = map(math.radians, [lat1, lon1, lat2, lon2])

    dlat = lat2 - lat1
    dlon = lon2 - lon1

    a = math.sin(dlat / 2)**2 + math.cos(lat1) * math.cos(lat2) * math.sin(dlon / 2)**2
    c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))

    distance = R * c  # Distance in kilometers
    return distance

# Applies scaling factors.
def apply_scale_factors(image):
  optical_bands = image.select('SR_B.').multiply(0.0000275).add(-0.2)
  thermal_bands = image.select('ST_B.*').multiply(0.00341802).add(149.0)
  return image.addBands(optical_bands, None, True).addBands(
      thermal_bands, None, True
  )

# Trigger the authentication flow.
ee.Authenticate()

# Initialize the library.
ee.Initialize(project=os.environ.get('GEE_PROJECT_NAME'))

app = FastAPI()

origins = [
    "http://127.0.0.1:8002",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# This endpoint is supposed to be used as a fallback to the USGS aquisition tool (in case the resource would be unavailable), but I'm using it for the time being.
@app.get("/overpass")
def read_root(lat: float, lon: float):
    max_days = 1.0         # download again once 7 days old
    name = 'active.json'  # custom filename, not 'gp.php'
    base = 'https://celestrak.org/NORAD/elements/gp.php'
    url = base + '?GROUP=active&FORMAT=json'

    if not load.exists(name) or load.days_old(name) >= max_days:
        load.download(url, filename=name)

    with load.open('active.json') as f:
        data = json.load(f)

    ts = load.timescale()
    sats = [EarthSatellite.from_omm(ts, fields) for fields in data]

    by_name = {sat.name: sat for sat in sats}
    satellite = by_name['LANDSAT 8']

    # Parameters
    loc = wgs84.latlon(lat, lon)

    t0 = ts.now()

    # Todo: Make this part dynamic. E.g. 2 weeks into the future
    t1 = ts.utc(2024, 10, 30)

    t, events = satellite.find_events(loc, t0, t1, altitude_degrees=0.0)

    eph = load('de421.bsp')
    sunlit = satellite.at(t).is_sunlit(eph)

    data = list(zip(t, events, sunlit))

    # Get all passes
    all_passes = [data[i:i+3] for i in range(0, len(data), 3)]

    # Ignore in-shadow passes
    sunlit_passes = [group for group in all_passes if group[0][2] == True]

    min_distance = 100
    closest_point = None
    time_of_closest_point = None

    for p in sunlit_passes:
        current_time = p[0][0]
        
        while(current_time < p[2][0]):
            geocentric = satellite.at(current_time)
            geographic_pos = wgs84.subpoint_of(geocentric)

            distance = haversine(lat, lon, geographic_pos.latitude.degrees, geographic_pos.longitude.degrees)

            if distance < min_distance:
                min_distance = distance
                closest_point = (geographic_pos.latitude.degrees, geographic_pos.longitude.degrees)
                time_of_closest_point = current_time.utc_datetime()
                
                return {"overpass": time_of_closest_point, "distance": min_distance, "satellite_position": closest_point}

            current_time = current_time + timedelta(seconds=25)


@app.get("/acquire")
def acquire(start_date: str, end_date: str, satellite: str, lng: float, lat: float):
    L8 = ee.ImageCollection("LANDSAT/LC08/C02/T1_L2")
    geometry = ee.Geometry.Point([lng, lat])

    dataset = L8.filterDate(start_date, end_date).filterBounds(geometry)
    image = dataset.sort('system:time_start', False).first()

    pixel_values = image.reduceRegion(
        reducer=ee.Reducer.first(),
        geometry=geometry,
        scale=30,  # Landsat 8 pixel is 30m
        maxPixels=1e9
    )

    return {
        "data": {
            "pixel": pixel_values.getInfo(),
            "scene": image.getInfo()
        }
    }

@app.get("/map", response_class=HTMLResponse)
def pixel(longitude: float, latitude: float, start_date: str, end_date: str = None, zoom: int = 18):
    Map = folium.Map(location=[latitude, longitude])
    
    L8 = ee.ImageCollection("LANDSAT/LC08/C02/T1_L2")
    geometry = ee.Geometry.Point([longitude, latitude])

    if end_date is None:
        end_date = add_day(start_date, 1)

    dataset = L8.filterDate(start_date, end_date).filterBounds(geometry)
    
    image = dataset.sort('system:time_start', False).first()

    vis_params = {
        'bands': ['SR_B4', 'SR_B3', 'SR_B2'],  # Red, Green, Blue bands
        'min': 0.0,
        'max': 0.3
    }

    Map.addLayer(apply_scale_factors(image), vis_params, 'Landsat 8')

    folium.Marker([latitude, longitude], popup='Target Pixel').add_to(Map)

    folium.LayerControl().add_to(Map)

    Map.setCenter(longitude, latitude, zoom)

    return HTMLResponse(content=Map.get_root().render())