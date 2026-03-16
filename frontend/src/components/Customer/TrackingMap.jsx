import React, { useState, useEffect, useRef } from "react";
import {
  FaMapMarkerAlt,
  FaShip,
  FaTruck,
  FaWarehouse,
  FaFlag,
  FaClock,
  FaRoute,
} from "react-icons/fa";

// Static coordinates for major shipping hubs used in vehicle shipping
const CITY_COORDS = {
  // East Africa
  nairobi: [-1.2921, 36.8219],
  mombasa: [-4.0435, 39.6682],
  "dar es salaam": [-6.7924, 39.2083],
  kampala: [0.3476, 32.5825],
  kigali: [-1.9441, 30.0619],
  "addis ababa": [9.032, 38.7469],
  // Southern Africa
  durban: [-29.8587, 31.0218],
  "cape town": [-33.9249, 18.4241],
  johannesburg: [-26.2041, 28.0473],
  beira: [-19.8436, 34.8389],
  maputo: [-25.9692, 32.5732],
  lusaka: [-15.4166, 28.2833],
  // West Africa
  lagos: [6.5244, 3.3792],
  accra: [5.6037, -0.187],
  abidjan: [5.3484, -4.0083],
  dakar: [14.7167, -17.4677],
  // Indian Ocean & Transit Hubs
  'colombo': [6.9271, 79.8612], 'sri lanka': [6.9271, 79.8612],
  'mumbai': [19.0760, 72.8777], 'india': [19.0760, 72.8777],
  'chennai': [13.0827, 80.2707], 'port of chennai': [13.0827, 80.2707],
  'salalah': [17.0150, 54.0924], 'oman': [17.0150, 54.0924],
  'aden': [12.7797, 45.0367], 'yemen': [12.7797, 45.0367],
  'djibouti': [11.8251, 42.5903],
  'port sudan': [19.6158, 37.2164], 'sudan': [15.5007, 32.5599],
  tokyo: [35.6762, 139.6503],
  osaka: [34.6937, 135.5023],
  nagoya: [35.1815, 136.9066],
  yokohama: [35.4437, 139.638],
  japan: [35.6762, 139.6503],
  singapore: [1.3521, 103.8198],
  dubai: [25.2048, 55.2708],
  "abu dhabi": [24.4539, 54.3773],
  uae: [25.2048, 55.2708],
  "united arab emirates": [25.2048, 55.2708],
  shanghai: [31.2304, 121.4737],
  china: [31.2304, 121.4737],
  "hong kong": [22.3193, 114.1694],
  busan: [35.1796, 129.0756],
  "south korea": [35.1796, 129.0756],
  // Europe
  london: [51.5074, -0.1278],
  uk: [51.5074, -0.1278],
  "united kingdom": [51.5074, -0.1278],
  hamburg: [53.5753, 9.9969],
  germany: [53.5753, 9.9969],
  rotterdam: [51.9244, 4.4777],
  netherlands: [51.9244, 4.4777],
  antwerp: [51.2454, 4.4153],
  belgium: [51.2454, 4.4153],
  // Americas
  "new york": [40.7128, -74.006],
  "los angeles": [34.0522, -118.2437],
  usa: [33.749, -84.388],
  "united states": [33.749, -84.388],
  toronto: [43.7, -79.4],
  canada: [43.7, -79.4],
  // Australia/Oceania
  sydney: [-33.8688, 151.2093],
  melbourne: [-37.8136, 144.9631],
  australia: [-33.8688, 151.2093],
};

const getCoords = (city, country) => {
  const c = (city || "").toLowerCase().trim();
  const co = (country || "").toLowerCase().trim();
  // Also try extracting city name from port strings like "Port of Yokohama"
  const portCity = c
    .replace(/^port of\s+/i, "")
    .replace(/^port\s+/i, "")
    .trim();
  return CITY_COORDS[c] || CITY_COORDS[co] || CITY_COORDS[portCity] || null;
};

const TrackingMap = ({ shipment, booking }) => {
  const mapRef = useRef(null);
  const leafletMapRef = useRef(null);
  const [mapReady, setMapReady] = useState(false);
  const [mapError, setMapError] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);
  const [trackingSteps, setTrackingSteps] = useState([]);

  // Load Leaflet from CDN
  useEffect(() => {
    if (window.L) {
      setMapReady(true);
      return;
    }

    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
    document.head.appendChild(link);

    const script = document.createElement("script");
    script.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
    script.async = true;
    script.onload = () => setMapReady(true);
    script.onerror = () => setMapError(true);
    document.head.appendChild(script);
  }, []);

  // Initialize map once Leaflet is ready and booking data is available
  useEffect(() => {
    if (!mapReady || !booking || !mapRef.current) return;
    if (leafletMapRef.current) {
      leafletMapRef.current.remove();
      leafletMapRef.current = null;
    }

    const L = window.L;
    // Support both flat fields and nested route/vehicle structure
    const originCity =
      booking.origin_city ||
      booking.route?.origin_city ||
      shipment?.departure_port ||
      "";
    const originCountry =
      booking.origin_country || booking.route?.origin_country || "";
    const destCity =
      booking.destination_city ||
      booking.route?.destination_city ||
      shipment?.arrival_port ||
      "";
    const destCountry =
      booking.destination_country || booking.route?.destination_country || "";

    const origin = getCoords(originCity, originCountry);
    const dest = getCoords(destCity, destCountry);

    if (!origin && !dest) {
      setMapError(true);
      return;
    }

    const center =
      origin && dest
        ? [(origin[0] + dest[0]) / 2, (origin[1] + dest[1]) / 2]
        : origin || dest;

    const map = L.map(mapRef.current, {
      zoomControl: true,
      scrollWheelZoom: false,
    }).setView(center, 3);
    leafletMapRef.current = map;

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 18,
    }).addTo(map);

    const greenIcon = L.divIcon({
      html: '<div style="background:#22c55e;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>',
      iconAnchor: [7, 7],
      className: "",
    });
    const redIcon = L.divIcon({
      html: '<div style="background:#ef4444;width:14px;height:14px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>',
      iconAnchor: [7, 7],
      className: "",
    });
    const blueIcon = L.divIcon({
      html: '<div style="background:#3b82f6;width:18px;height:18px;border-radius:50%;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.5);animation:pulse 2s infinite"></div>',
      iconAnchor: [9, 9],
      className: "",
    });

    const bounds = [];

    if (origin) {
      L.marker(origin, { icon: greenIcon })
        .addTo(map)
        .bindPopup(
          `<b>Origin</b><br>${originCity || originCountry || "Origin"}`,
        );
      bounds.push(origin);
    }

    if (dest) {
      L.marker(dest, { icon: redIcon })
        .addTo(map)
        .bindPopup(
          `<b>Destination</b><br>${destCity || destCountry || "Destination"}`,
        );
      bounds.push(dest);
    }

    // Draw dashed route line between origin and destination
    if (origin && dest) {
      L.polyline([origin, dest], {
        color: "#3b82f6",
        weight: 3,
        opacity: 0.7,
        dashArray: "10, 8",
      }).addTo(map);
    }

    // Show current location from shipment if available
    const currentLoc = shipment?.current_location;
    let currentCoords = null;
    let currentLabel = 'In Transit';

    if (currentLoc && typeof currentLoc === 'object' && currentLoc.lat && currentLoc.lng) {
      // Stored as {lat, lng, name}
      currentCoords = [currentLoc.lat, currentLoc.lng];
      currentLabel = currentLoc.name || 'In Transit';
    } else if (currentLoc && typeof currentLoc === 'string' && currentLoc.trim()) {
      // Stored as a plain string — look it up in the city table
      const locCoords = getCoords(currentLoc.trim(), '');
      if (locCoords) {
        currentCoords = locCoords;
        currentLabel = currentLoc;
      }
    }

    // If no current location but booking is in_transit, interpolate a midpoint
    if (!currentCoords && origin && dest && booking.status === 'in_transit') {
      currentCoords = [(origin[0] + dest[0]) / 2, (origin[1] + dest[1]) / 2];
      currentLabel = 'En Route (estimated)';
    }

    if (currentCoords) {
      L.marker(currentCoords, { icon: blueIcon })
        .addTo(map)
        .bindPopup(`<b>Current Location</b><br>${currentLabel}`);
      bounds.push(currentCoords);
    }

    if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [40, 40] });
    }
  }, [mapReady, booking, shipment]);

  // Build progress steps
  useEffect(() => {
    if (!booking) return;

    const getStepStatus = (stepType) => {
      const s = booking.status;
      if (stepType === "collection")
        return ["collected", "in_transit", "customs", "delivered"].includes(s)
          ? "completed"
          : ["processing", "confirmed"].includes(s)
            ? "current"
            : "pending";
      if (stepType === "port")
        return ["in_transit", "customs", "delivered"].includes(s)
          ? "completed"
          : s === "collected"
            ? "current"
            : "pending";
      if (stepType === "transit")
        return ["customs", "delivered"].includes(s)
          ? "completed"
          : s === "in_transit"
            ? "current"
            : "pending";
      if (stepType === "customs")
        return s === "delivered"
          ? "completed"
          : s === "customs"
            ? "current"
            : "pending";
      if (stepType === "delivered")
        return s === "delivered" ? "completed" : "pending";
      return "pending";
    };

    const originLabel =
      booking.origin_city ||
      booking.route?.origin_city ||
      booking.route?.origin_country ||
      "Origin";
    const destLabel =
      booking.destination_city ||
      booking.route?.destination_city ||
      booking.route?.destination_country ||
      "Destination";

    const steps = [
      {
        id: 1,
        title: "Booking Confirmed",
        icon: FaFlag,
        status: "completed",
        location: originLabel,
      },
      {
        id: 2,
        title: "Vehicle Collection",
        icon: FaTruck,
        status: getStepStatus("collection"),
        location: originLabel,
      },
      {
        id: 3,
        title: "Port Processing",
        icon: FaWarehouse,
        status: getStepStatus("port"),
        location: shipment?.departure_port || "Departure Port",
      },
      {
        id: 4,
        title: "In Transit",
        icon: FaShip,
        status: getStepStatus("transit"),
        location: (typeof shipment?.current_location === 'string' ? shipment.current_location : shipment?.current_location?.name) || "At Sea",
      },
      {
        id: 5,
        title: "Customs Clearance",
        icon: FaWarehouse,
        status: getStepStatus("customs"),
        location: shipment?.arrival_port || destLabel,
      },
      {
        id: 6,
        title: "Delivered",
        icon: FaFlag,
        status: getStepStatus("delivered"),
        location: destLabel,
      },
    ];

    setTrackingSteps(steps);
    const stepMap = {
      confirmed: 1,
      processing: 2,
      collected: 2,
      in_transit: 4,
      customs: 5,
      delivered: 6,
    };
    setCurrentStep(stepMap[booking.status] || 1);
  }, [booking, shipment]);

  const getStepColor = (status) => {
    if (status === "completed")
      return "bg-green-500 text-white border-green-500";
    if (status === "current")
      return "bg-blue-500 text-white border-blue-500 animate-pulse";
    return "bg-gray-200 text-gray-500 border-gray-300";
  };

  const getEstimatedDelivery = () => {
    if (booking?.estimated_delivery)
      return new Date(booking.estimated_delivery).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
      });
    const days = {
      japan: 35,
      uk: 45,
      "united kingdom": 45,
      uae: 25,
      "united states": 50,
      usa: 50,
      germany: 40,
      canada: 45,
    };
    const d =
      days[
        (
          booking?.origin_country ||
          booking?.route?.origin_country ||
          ""
        ).toLowerCase()
      ] || 40;
    const est = new Date(
      new Date(booking?.created_at).getTime() + d * 86400000,
    );
    return est.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  };

  if (!booking) {
    return (
      <div className="text-center py-8">
        <FaMapMarkerAlt className="text-gray-300 text-4xl mx-auto mb-4" />
        <p className="text-gray-500">No tracking information available</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Current Status Bar */}
      <div className="bg-white rounded-lg border p-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="flex items-center gap-3">
            <FaMapMarkerAlt className="text-blue-500 flex-shrink-0" />
            <div>
              <p className="text-xs text-gray-500">Current Status</p>
              <p className="font-semibold capitalize">
                {booking.status?.replace("_", " ") || "Processing"}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <FaClock className="text-blue-500 flex-shrink-0" />
            <div>
              <p className="text-xs text-gray-500">Estimated Delivery</p>
              <p className="font-semibold">{getEstimatedDelivery()}</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <FaRoute className="text-blue-500 flex-shrink-0" />
            <div>
              <p className="text-xs text-gray-500">Route</p>
              <p className="font-semibold text-sm">
                {booking.origin_city ||
                  booking.route?.origin_city ||
                  booking.route?.origin_country ||
                  "—"}{" "}
                →{" "}
                {booking.destination_city ||
                  booking.route?.destination_city ||
                  booking.route?.destination_country ||
                  "—"}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Leaflet Map */}
      <div className="rounded-lg overflow-hidden border">
        {mapError ? (
          <div className="flex items-center justify-center h-64 bg-gray-50">
            <div className="text-center">
              <FaMapMarkerAlt className="text-gray-300 text-4xl mx-auto mb-3" />
              <p className="text-gray-500 text-sm">
                Map unavailable for this route
              </p>
              <p className="text-xs text-gray-400 mt-1">
                {booking.origin_city || booking.route?.origin_country || "—"} →{" "}
                {booking.destination_city ||
                  booking.route?.destination_country ||
                  "—"}
              </p>
            </div>
          </div>
        ) : (
          <div className="relative">
            {!mapReady && (
              <div className="absolute inset-0 flex items-center justify-center bg-gray-50 z-10">
                <div className="text-center">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                  <p className="text-sm text-gray-500">Loading map...</p>
                </div>
              </div>
            )}
            <div ref={mapRef} style={{ height: "380px", width: "100%" }} />
            {/* Legend */}
            <div className="absolute bottom-4 left-4 bg-white rounded-lg shadow-md p-3 z-[1000] text-xs space-y-1">
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-green-500 inline-block"></span>{" "}
                Origin
              </div>
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-red-500 inline-block"></span>{" "}
                Destination
              </div>
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>{" "}
                Current
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Progress Stepper */}
      <div className="bg-white rounded-lg border p-6">
        <h4 className="text-lg font-semibold text-gray-900 mb-6">
          Shipment Progress ({currentStep}/{trackingSteps.length})
        </h4>
        <div className="relative">
          {trackingSteps.map((step, index) => {
            const Icon = step.icon;
            const isLast = index === trackingSteps.length - 1;
            return (
              <div key={step.id} className="relative flex items-start pb-8">
                {!isLast && (
                  <div
                    className={`absolute left-6 top-12 w-0.5 h-16 ${index < currentStep - 1 ? "bg-green-500" : "bg-gray-300"}`}
                  />
                )}
                <div
                  className={`relative flex items-center justify-center w-12 h-12 rounded-full border-2 flex-shrink-0 ${getStepColor(step.status)}`}
                >
                  <Icon className="text-lg" />
                </div>
                <div className="ml-4 flex-1">
                  <p className="font-medium text-gray-900">{step.title}</p>
                  <p className="text-sm text-gray-500 flex items-center gap-1 mt-0.5">
                    <FaMapMarkerAlt className="text-xs" />
                    {step.location}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Shipment Details (if available) */}
      {shipment &&
        (shipment.vessel_name ||
          shipment.container_number ||
          shipment.departure_port) && (
          <div className="bg-white rounded-lg border p-6">
            <h4 className="text-lg font-semibold text-gray-900 mb-4">
              Shipment Details
            </h4>
            <div className="grid grid-cols-2 gap-4 text-sm">
              {shipment.vessel_name && (
                <div>
                  <p className="text-gray-500">Vessel</p>
                  <p className="font-medium">{shipment.vessel_name}</p>
                </div>
              )}
              {shipment.container_number && (
                <div>
                  <p className="text-gray-500">Container</p>
                  <p className="font-medium">{shipment.container_number}</p>
                </div>
              )}
              {shipment.departure_port && (
                <div>
                  <p className="text-gray-500">Departure Port</p>
                  <p className="font-medium">{shipment.departure_port}</p>
                </div>
              )}
              {shipment.arrival_port && (
                <div>
                  <p className="text-gray-500">Arrival Port</p>
                  <p className="font-medium">{shipment.arrival_port}</p>
                </div>
              )}
            </div>
          </div>
        )}
    </div>
  );
};

export default TrackingMap;
