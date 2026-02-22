import React, { useState, useEffect, useRef } from 'react';
import { FaMapMarkerAlt, FaRoute, FaShip, FaTruck, FaFlag } from 'react-icons/fa';

const TrackingMap = ({ trackingNumber, shipmentId, isPublic = false }) => {
  const [mapData, setMapData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const mapRef = useRef(null);
  const googleMapRef = useRef(null);
  const markersRef = useRef([]);
  const polylineRef = useRef(null);

  useEffect(() => {
    if (trackingNumber || shipmentId) {
      loadTrackingData();
    }
  }, [trackingNumber, shipmentId]);

  useEffect(() => {
    // Load Google Maps script
    if (!window.google) {
      loadGoogleMapsScript();
    } else if (mapData) {
      initializeMap();
    }
  }, [mapData]);

  const loadGoogleMapsScript = () => {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&libraries=geometry`;
    script.async = true;
    script.defer = true;
    script.onload = () => {
      if (mapData) {
        initializeMap();
      }
    };
    script.onerror = () => {
      setError('Failed to load Google Maps');
      setLoading(false);
    };
    document.head.appendChild(script);
  };

  const loadTrackingData = async () => {
    try {
      setLoading(true);
      setError(null);

      let response;
      if (isPublic && trackingNumber) {
        // Public tracking endpoint
        response = await fetch(`/api/tracking/${trackingNumber}/map`);
      } else if (shipmentId) {
        // Admin tracking endpoint
        const token = localStorage.getItem('token');
        response = await fetch(`/api/admin/crud/shipments/${shipmentId}/map`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
      }

      if (!response.ok) {
        throw new Error('Failed to load tracking data');
      }

      const data = await response.json();
      if (data.success) {
        setMapData(data.data);
      } else {
        throw new Error(data.message || 'Failed to load tracking data');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const initializeMap = () => {
    if (!window.google || !mapData || !mapRef.current) return;

    const { map_config, locations, route } = mapData;
    
    // Initialize map
    const map = new window.google.maps.Map(mapRef.current, {
      center: map_config.center,
      zoom: map_config.zoom,
      mapTypeId: map_config.map_type || 'roadmap',
      styles: [
        {
          featureType: 'water',
          elementType: 'geometry',
          stylers: [{ color: '#e9e9e9' }, { lightness: 17 }]
        },
        {
          featureType: 'landscape',
          elementType: 'geometry',
          stylers: [{ color: '#f5f5f5' }, { lightness: 20 }]
        }
      ]
    });

    googleMapRef.current = map;

    // Clear existing markers and polylines
    clearMapElements();

    // Add markers for origin, destination, and current location
    addLocationMarkers(map, locations);

    // Add route polyline if available
    if (route && route.polyline) {
      addRoutePolyline(map, route.polyline);
    }

    // Fit map to show all markers
    if (locations && locations.length > 0) {
      const bounds = new window.google.maps.LatLngBounds();
      locations.forEach(location => {
        if (location.lat && location.lng) {
          bounds.extend(new window.google.maps.LatLng(location.lat, location.lng));
        }
      });
      map.fitBounds(bounds);
    }
  };

  const clearMapElements = () => {
    // Clear existing markers
    markersRef.current.forEach(marker => marker.setMap(null));
    markersRef.current = [];

    // Clear existing polyline
    if (polylineRef.current) {
      polylineRef.current.setMap(null);
      polylineRef.current = null;
    }
  };

  const addLocationMarkers = (map, locations) => {
    if (!locations || locations.length === 0) return;

    locations.forEach((location, index) => {
      const marker = new window.google.maps.Marker({
        position: { lat: location.lat, lng: location.lng },
        map: map,
        title: location.name || location.address,
        icon: getMarkerIcon(location.type),
        animation: location.type === 'current' ? window.google.maps.Animation.BOUNCE : null
      });

      // Add info window
      const infoWindow = new window.google.maps.InfoWindow({
        content: createInfoWindowContent(location)
      });

      marker.addListener('click', () => {
        infoWindow.open(map, marker);
      });

      markersRef.current.push(marker);
    });
  };

  const getMarkerIcon = (type) => {
    const iconBase = 'https://maps.google.com/mapfiles/kml/shapes/';
    
    switch (type) {
      case 'origin':
        return {
          url: iconBase + 'placemark_circle_highlight.png',
          scaledSize: new window.google.maps.Size(32, 32)
        };
      case 'destination':
        return {
          url: iconBase + 'flag.png',
          scaledSize: new window.google.maps.Size(32, 32)
        };
      case 'current':
        return {
          url: iconBase + 'truck.png',
          scaledSize: new window.google.maps.Size(40, 40)
        };
      case 'checkpoint':
        return {
          url: iconBase + 'placemark_square_highlight.png',
          scaledSize: new window.google.maps.Size(24, 24)
        };
      default:
        return null;
    }
  };

  const createInfoWindowContent = (location) => {
    const statusColor = getStatusColor(location.status);
    
    return `
      <div style="max-width: 250px; padding: 10px;">
        <h4 style="margin: 0 0 8px 0; color: #333;">${location.name || 'Location'}</h4>
        <p style="margin: 0 0 5px 0; font-size: 14px; color: #666;">${location.address}</p>
        ${location.status ? `<p style="margin: 0 0 5px 0; font-size: 12px; color: ${statusColor}; font-weight: bold;">${location.status}</p>` : ''}
        ${location.timestamp ? `<p style="margin: 0; font-size: 12px; color: #888;">${new Date(location.timestamp).toLocaleString()}</p>` : ''}
        ${location.notes ? `<p style="margin: 5px 0 0 0; font-size: 12px; color: #555;">${location.notes}</p>` : ''}
      </div>
    `;
  };

  const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
      case 'delivered':
      case 'completed':
        return '#10b981';
      case 'in_transit':
      case 'shipped':
        return '#3b82f6';
      case 'delayed':
      case 'pending':
        return '#f59e0b';
      case 'cancelled':
      case 'failed':
        return '#ef4444';
      default:
        return '#6b7280';
    }
  };

  const addRoutePolyline = (map, polylineData) => {
    if (!polylineData) return;

    let path = [];
    
    if (typeof polylineData === 'string') {
      // Decode polyline string
      path = window.google.maps.geometry.encoding.decodePath(polylineData);
    } else if (Array.isArray(polylineData)) {
      // Array of coordinates
      path = polylineData.map(point => ({
        lat: point.lat || point.latitude,
        lng: point.lng || point.longitude
      }));
    }

    if (path.length > 0) {
      polylineRef.current = new window.google.maps.Polyline({
        path: path,
        geodesic: true,
        strokeColor: '#3b82f6',
        strokeOpacity: 0.8,
        strokeWeight: 4
      });

      polylineRef.current.setMap(map);
    }
  };

  const refreshTracking = () => {
    loadTrackingData();
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-50 rounded-lg">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading tracking map...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-50 rounded-lg">
        <div className="text-center">
          <FaMapMarkerAlt className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600 mb-4">{error}</p>
          <button
            onClick={refreshTracking}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  if (!mapData) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-50 rounded-lg">
        <div className="text-center">
          <FaMapMarkerAlt className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600">No tracking data available</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-sm border">
      {/* Map Header */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <FaRoute className="h-5 w-5 text-blue-600" />
            <h3 className="text-lg font-semibold text-gray-900">
              Shipment Tracking Map
            </h3>
          </div>
          <button
            onClick={refreshTracking}
            className="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors"
          >
            Refresh
          </button>
        </div>
        
        {mapData.shipment_info && (
          <div className="mt-2 flex items-center space-x-4 text-sm text-gray-600">
            <span>Tracking: {mapData.shipment_info.tracking_number}</span>
            <span>Status: {mapData.shipment_info.status}</span>
            {mapData.shipment_info.estimated_delivery && (
              <span>ETA: {new Date(mapData.shipment_info.estimated_delivery).toLocaleDateString()}</span>
            )}
          </div>
        )}
      </div>

      {/* Map Container */}
      <div className="relative">
        <div
          ref={mapRef}
          className="w-full h-96"
          style={{ minHeight: '400px' }}
        />
        
        {/* Map Legend */}
        <div className="absolute top-4 right-4 bg-white rounded-lg shadow-lg p-3 max-w-xs">
          <h4 className="text-sm font-semibold text-gray-900 mb-2">Legend</h4>
          <div className="space-y-1 text-xs">
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-green-500 rounded-full"></div>
              <span>Origin</span>
            </div>
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-red-500 rounded-full"></div>
              <span>Destination</span>
            </div>
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
              <span>Current Location</span>
            </div>
            <div className="flex items-center space-x-2">
              <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
              <span>Checkpoint</span>
            </div>
          </div>
        </div>
      </div>

      {/* Location Details */}
      {mapData.locations && mapData.locations.length > 0 && (
        <div className="p-4 border-t border-gray-200">
          <h4 className="text-sm font-semibold text-gray-900 mb-3">Route Details</h4>
          <div className="space-y-2">
            {mapData.locations.map((location, index) => (
              <div key={index} className="flex items-start space-x-3 p-2 bg-gray-50 rounded-lg">
                <div className="flex-shrink-0 mt-1">
                  {location.type === 'origin' && <FaFlag className="h-4 w-4 text-green-600" />}
                  {location.type === 'destination' && <FaFlag className="h-4 w-4 text-red-600" />}
                  {location.type === 'current' && <FaTruck className="h-4 w-4 text-blue-600" />}
                  {location.type === 'checkpoint' && <FaMapMarkerAlt className="h-4 w-4 text-yellow-600" />}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900">{location.name}</p>
                  <p className="text-xs text-gray-600">{location.address}</p>
                  {location.timestamp && (
                    <p className="text-xs text-gray-500 mt-1">
                      {new Date(location.timestamp).toLocaleString()}
                    </p>
                  )}
                </div>
                {location.status && (
                  <div className="flex-shrink-0">
                    <span 
                      className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                      style={{ 
                        backgroundColor: `${getStatusColor(location.status)}20`,
                        color: getStatusColor(location.status)
                      }}
                    >
                      {location.status}
                    </span>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default TrackingMap;