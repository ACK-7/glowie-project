import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { FaSearch, FaMap, FaList, FaShip, FaTruck, FaCheckCircle, FaInfoCircle, FaCalendar, FaMapMarkerAlt, FaBox, FaCar, FaUser, FaPhone, FaEnvelope } from 'react-icons/fa';
import TrackingMap from '../components/Tracking/TrackingMap';
import TrackingTimeline from '../components/Tracking/TrackingTimeline';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

const TrackShipment = () => {
  const { trackingNumber: urlTrackingNumber } = useParams();
  const [searchParams] = useSearchParams();
  const [trackingNumber, setTrackingNumber] = useState(urlTrackingNumber || searchParams.get('tracking') || '');
  const [activeView, setActiveView] = useState('details'); // 'details', 'map', or 'timeline'
  const [shipmentData, setShipmentData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (trackingNumber) {
      fetchShipmentData();
    }
  }, [trackingNumber]);

  const fetchShipmentData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`${API_BASE_URL}/tracking/${trackingNumber}`);
      if (!response.ok) {
        throw new Error('Shipment not found');
      }
      
      const data = await response.json();
      if (data.success) {
        setShipmentData(data.data);
      } else {
        throw new Error(data.message || 'Failed to load shipment data');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleTrack = (e) => {
    e.preventDefault();
    if (trackingNumber.trim()) {
      fetchShipmentData();
      // Update URL without page reload
      window.history.pushState({}, '', `/track/${trackingNumber}`);
    }
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
        <div className="container mx-auto px-4">
          <div className="text-center mb-8">
            <h1 className="text-4xl md:text-5xl font-bold mb-4">Track Your Vehicle</h1>
            <p className="text-xl text-blue-100 max-w-3xl mx-auto">
              Get real-time updates on your vehicle's location and delivery status
            </p>
          </div>

          {/* Search Form */}
          <div className="max-w-2xl mx-auto">
            <form onSubmit={handleTrack} className="flex gap-4">
              <input
                type="text"
                value={trackingNumber}
                onChange={(e) => setTrackingNumber(e.target.value)}
                placeholder="Enter tracking number (e.g., SWG-12345)"
                className="flex-1 px-6 py-4 rounded-lg text-gray-900 text-lg focus:ring-4 focus:ring-blue-300 focus:outline-none"
                required
              />
              <button
                type="submit"
                disabled={loading}
                className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold px-8 py-4 rounded-lg transition-colors disabled:opacity-50 flex items-center gap-2"
              >
                <FaSearch />
                {loading ? 'Tracking...' : 'Track'}
              </button>
            </form>
          </div>
        </div>
      </section>

      {/* Results Section */}
      {trackingNumber && (
        <section className="py-12">
          <div className="container mx-auto px-4">
            {loading && (
              <div className="text-center py-12">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p className="text-gray-600">Loading shipment data...</p>
              </div>
            )}

            {error && (
              <div className="max-w-2xl mx-auto">
                <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                  <div className="text-red-600 mb-4">
                    <FaShip className="h-12 w-12 mx-auto mb-2" />
                    <h3 className="text-lg font-semibold">Shipment Not Found</h3>
                  </div>
                  <p className="text-red-700 mb-4">{error}</p>
                  <p className="text-sm text-red-600">
                    Please check your tracking number and try again. If you continue to have issues, contact our support team.
                  </p>
                </div>
              </div>
            )}

            {shipmentData && !loading && !error && (
              <div className="max-w-6xl mx-auto">
                {/* Shipment Header */}
                <div className="bg-white rounded-lg shadow-sm border p-6 mb-6">
                  <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                      <h2 className="text-2xl font-bold text-gray-900 mb-2">
                        Tracking #{trackingNumber}
                      </h2>
                      <div className="space-y-1 text-sm text-gray-600">
                        <p><span className="font-medium">Vehicle:</span> {shipmentData.vehicle_info || 'Vehicle details'}</p>
                        <p><span className="font-medium">Route:</span> {shipmentData.route || 'Route information'}</p>
                        {shipmentData.estimated_delivery && (
                          <p><span className="font-medium">Est. Delivery:</span> {new Date(shipmentData.estimated_delivery).toLocaleDateString()}</p>
                        )}
                      </div>
                    </div>
                    <div className="mt-4 md:mt-0 text-right">
                      <div className={`inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${
                        shipmentData.status === 'delivered' ? 'bg-green-100 text-green-800' :
                        shipmentData.status === 'in_transit' ? 'bg-blue-100 text-blue-800' :
                        shipmentData.status === 'delayed' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-gray-100 text-gray-800'
                      }`}>
                        {shipmentData.status === 'delivered' && <FaCheckCircle className="mr-2" />}
                        {shipmentData.status === 'in_transit' && <FaTruck className="mr-2" />}
                        {shipmentData.status_display || shipmentData.status}
                      </div>
                    </div>
                  </div>
                </div>

                {/* View Toggle */}
                <div className="bg-white rounded-lg shadow-sm border mb-6">
                  <div className="border-b border-gray-200">
                    <nav className="flex">
                      <button
                        onClick={() => setActiveView('details')}
                        className={`flex items-center px-6 py-4 text-sm font-medium border-b-2 transition-colors ${
                          activeView === 'details'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                      >
                        <FaInfoCircle className="mr-2" />
                        Details
                      </button>
                      <button
                        onClick={() => setActiveView('map')}
                        className={`flex items-center px-6 py-4 text-sm font-medium border-b-2 transition-colors ${
                          activeView === 'map'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                      >
                        <FaMap className="mr-2" />
                        Live Map
                      </button>
                      <button
                        onClick={() => setActiveView('timeline')}
                        className={`flex items-center px-6 py-4 text-sm font-medium border-b-2 transition-colors ${
                          activeView === 'timeline'
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                      >
                        <FaList className="mr-2" />
                        Timeline
                      </button>
                    </nav>
                  </div>

                  <div className="p-6">
                    {activeView === 'details' && (
                      <div className="space-y-6">
                        {/* Current Status */}
                        <div className="grid md:grid-cols-2 gap-6">
                          <div className="bg-gray-50 rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                              <FaInfoCircle className="text-blue-600" />
                              Current Status
                            </h3>
                            <div className="space-y-3">
                              <div>
                                <span className="text-sm text-gray-600">Status:</span>
                                <div className={`mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                                  shipmentData.status === 'delivered' ? 'bg-green-100 text-green-800' :
                                  shipmentData.status === 'in_transit' ? 'bg-blue-100 text-blue-800' :
                                  shipmentData.status === 'delayed' ? 'bg-yellow-100 text-yellow-800' :
                                  'bg-gray-100 text-gray-800'
                                }`}>
                                  {shipmentData.status_display || shipmentData.status}
                                </div>
                              </div>
                              <div>
                                <span className="text-sm text-gray-600">Progress</span>
                                <div className="mt-2">
                                  <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                      className="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                      style={{ width: `${shipmentData.progress || 0}%` }}
                                    ></div>
                                  </div>
                                  <p className="text-xs text-gray-500 mt-1">{shipmentData.progress || 0}%</p>
                                </div>
                              </div>
                              <div>
                                <span className="text-sm text-gray-600">Current Location:</span>
                                <p className="text-gray-900 font-medium">{shipmentData.current_location || 'N/A'}</p>
                              </div>
                            </div>
                          </div>

                          <div className="bg-gray-50 rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                              <FaCalendar className="text-green-600" />
                              Timeline
                            </h3>
                            <div className="space-y-3">
                              <div>
                                <span className="text-sm text-gray-600">Departure Date:</span>
                                <p className="text-gray-900 font-medium">
                                  {shipmentData.departure_date ? new Date(shipmentData.departure_date).toLocaleDateString() : 'N/A'}
                                </p>
                              </div>
                              <div>
                                <span className="text-sm text-gray-600">Estimated Arrival:</span>
                                <p className="text-gray-900 font-medium">
                                  {shipmentData.estimated_delivery ? new Date(shipmentData.estimated_delivery).toLocaleDateString() : 'N/A'}
                                </p>
                              </div>
                              {shipmentData.actual_delivery && (
                                <div>
                                  <span className="text-sm text-gray-600">Actual Delivery:</span>
                                  <p className="text-gray-900 font-medium">
                                    {new Date(shipmentData.actual_delivery).toLocaleDateString()}
                                  </p>
                                </div>
                              )}
                            </div>
                          </div>
                        </div>

                        {/* Customer Information */}
                        <div className="bg-gray-50 rounded-lg p-6">
                          <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <FaUser className="text-purple-600" />
                            Customer Information
                          </h3>
                          <div className="grid md:grid-cols-2 gap-4">
                            <div>
                              <span className="text-sm text-gray-600">Name:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.customer_name || 'N/A'}</p>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">Email:</span>
                              <p className="text-gray-900 font-medium flex items-center gap-2">
                                <FaEnvelope className="text-gray-400 text-xs" />
                                {shipmentData.customer_email || 'N/A'}
                              </p>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">Phone:</span>
                              <p className="text-gray-900 font-medium flex items-center gap-2">
                                <FaPhone className="text-gray-400 text-xs" />
                                {shipmentData.customer_phone || 'N/A'}
                              </p>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">Booking Reference:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.booking_reference || 'N/A'}</p>
                            </div>
                          </div>
                        </div>

                        {/* Vehicle Information */}
                        <div className="bg-gray-50 rounded-lg p-6">
                          <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <FaCar className="text-orange-600" />
                            Vehicle Information
                          </h3>
                          <div className="grid md:grid-cols-2 gap-4">
                            <div>
                              <span className="text-sm text-gray-600">Vehicle:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.vehicle_info || 'N/A'}</p>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">VIN:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.vin || 'N/A'}</p>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">Make/Model:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.make_model || 'N/A'}</p>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">Year:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.year || 'N/A'}</p>
                            </div>
                          </div>
                        </div>

                        {/* Route Information */}
                        <div className="bg-gray-50 rounded-lg p-6">
                          <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <FaMapMarkerAlt className="text-red-600" />
                            Route Information
                          </h3>
                          <div className="space-y-4">
                            <div className="flex items-start gap-4">
                              <div className="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <FaMapMarkerAlt className="text-green-600 text-sm" />
                              </div>
                              <div className="flex-1">
                                <span className="text-sm text-gray-600">Origin:</span>
                                <p className="text-gray-900 font-medium">{shipmentData.origin || 'N/A'}</p>
                              </div>
                            </div>
                            <div className="flex items-start gap-4">
                              <div className="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <FaMapMarkerAlt className="text-red-600 text-sm" />
                              </div>
                              <div className="flex-1">
                                <span className="text-sm text-gray-600">Destination:</span>
                                <p className="text-gray-900 font-medium">{shipmentData.destination || 'N/A'}</p>
                              </div>
                            </div>
                            <div>
                              <span className="text-sm text-gray-600">Route:</span>
                              <p className="text-gray-900 font-medium">{shipmentData.route || 'N/A'}</p>
                            </div>
                            {shipmentData.distance && (
                              <div>
                                <span className="text-sm text-gray-600">Distance:</span>
                                <p className="text-gray-900 font-medium">{shipmentData.distance}</p>
                              </div>
                            )}
                          </div>
                        </div>

                        {/* Additional Notes */}
                        {shipmentData.notes && (
                          <div className="bg-blue-50 rounded-lg p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                              <FaBox className="text-blue-600" />
                              Additional Notes
                            </h3>
                            <p className="text-gray-700">{shipmentData.notes}</p>
                          </div>
                        )}
                      </div>
                    )}

                    {activeView === 'map' && (
                      <TrackingMap 
                        trackingNumber={trackingNumber}
                        isPublic={true}
                      />
                    )}

                    {activeView === 'timeline' && (
                      <TrackingTimeline 
                        trackingNumber={trackingNumber}
                        isPublic={true}
                      />
                    )}
                  </div>
                </div>

                {/* Additional Information */}
                <div className="grid md:grid-cols-2 gap-6">
                  {/* Contact Support */}
                  <div className="bg-blue-50 rounded-lg p-6">
                    <h3 className="font-bold text-gray-900 mb-2">Need Help?</h3>
                    <p className="text-gray-600 mb-4 text-sm">
                      Have questions about your shipment? Our support team is available 24/7.
                    </p>
                    <div className="space-y-2 text-sm">
                      <p><span className="font-medium">Email:</span> support@shipwithglowie.com</p>
                      <p><span className="font-medium">Phone:</span> +256 700 123 456</p>
                      <p><span className="font-medium">WhatsApp:</span> +256 700 123 456</p>
                    </div>
                  </div>

                  {/* Delivery Instructions */}
                  <div className="bg-green-50 rounded-lg p-6">
                    <h3 className="font-bold text-gray-900 mb-2">Delivery Information</h3>
                    <p className="text-gray-600 mb-4 text-sm">
                      Important information about your vehicle delivery.
                    </p>
                    <div className="space-y-2 text-sm">
                      <p>• Ensure someone is available to receive the vehicle</p>
                      <p>• Have your ID and shipping documents ready</p>
                      <p>• Inspect the vehicle before signing delivery receipt</p>
                      <p>• Contact us immediately if there are any issues</p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </section>
      )}

      {/* How to Track Section */}
      {!trackingNumber && (
        <section className="py-12 bg-white">
          <div className="container mx-auto px-4">
            <div className="max-w-4xl mx-auto text-center">
              <h2 className="text-3xl font-bold text-gray-900 mb-8">How to Track Your Vehicle</h2>
              <div className="grid md:grid-cols-3 gap-8">
                <div className="text-center">
                  <div className="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <FaSearch className="h-8 w-8 text-blue-600" />
                  </div>
                  <h3 className="font-bold text-gray-900 mb-2">Enter Tracking Number</h3>
                  <p className="text-gray-600 text-sm">
                    Use the tracking number from your booking confirmation or shipping documents
                  </p>
                </div>
                <div className="text-center">
                  <div className="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <FaMap className="h-8 w-8 text-blue-600" />
                  </div>
                  <h3 className="font-bold text-gray-900 mb-2">View Live Location</h3>
                  <p className="text-gray-600 text-sm">
                    See your vehicle's current location and route on an interactive map
                  </p>
                </div>
                <div className="text-center">
                  <div className="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <FaTruck className="h-8 w-8 text-blue-600" />
                  </div>
                  <h3 className="font-bold text-gray-900 mb-2">Get Updates</h3>
                  <p className="text-gray-600 text-sm">
                    Receive real-time notifications about your vehicle's journey and delivery
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      )}
    </div>
  );
};

export default TrackShipment;