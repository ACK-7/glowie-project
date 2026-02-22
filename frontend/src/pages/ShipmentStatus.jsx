import React, { useState } from 'react';
import { FaSearch, FaShip, FaAnchor, FaTruck, FaCheckCircle, FaMap } from 'react-icons/fa';
import TrackingMap from '../components/Tracking/TrackingMap';

const ShipmentStatus = () => {
  const [trackingNumber, setTrackingNumber] = useState('');
  const [showStatus, setShowStatus] = useState(false);
  const [activeTab, setActiveTab] = useState('timeline'); // 'timeline' or 'map'

  // Mock data for demonstration
  const trackingStatuses = [
    { status: 'Booked', date: '2024-01-15', completed: true, icon: <FaCheckCircle /> },
    { status: 'On Vessel', date: '2024-01-20', completed: true, icon: <FaShip /> },
    { status: 'In Port (Mombasa)', date: '2024-02-25', completed: true, icon: <FaAnchor /> },
    { status: 'In Transit to Kampala', date: '2024-02-28', completed: false, current: true, icon: <FaTruck /> },
    { status: 'Delivered', date: 'Estimated: 2024-03-02', completed: false, icon: <FaCheckCircle /> }
  ];

  const handleTrack = (e) => {
    e.preventDefault();
    if (trackingNumber.trim()) {
      setShowStatus(true);
    }
  };

  return (
    <div className="bg-gray-50 min-h-screen">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Track Your Shipment</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Enter your Bill of Lading (BOL) or tracking number to see real-time status of your vehicle.
          </p>
        </div>
      </section>

      {/* Tracking Input */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="max-w-2xl mx-auto">
            <div className="bg-white rounded-2xl shadow-lg p-8">
              <form onSubmit={handleTrack} className="space-y-6">
                <div>
                  <label htmlFor="tracking" className="block text-sm font-medium text-gray-700 mb-2">
                    Tracking Number / Bill of Lading (BOL)
                  </label>
                  <div className="flex gap-4">
                    <input
                      type="text"
                      id="tracking"
                      value={trackingNumber}
                      onChange={(e) => setTrackingNumber(e.target.value)}
                      placeholder="e.g., SWG-12345 or BOL-67890"
                      className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                    <button
                      type="submit"
                      className="btn-primary px-8 flex items-center gap-2"
                    >
                      <FaSearch />
                      Track
                    </button>
                  </div>
                </div>
                
                <div className="text-sm text-gray-500">
                  <p>Your tracking number can be found in:</p>
                  <ul className="list-disc list-inside mt-2 space-y-1">
                    <li>Your booking confirmation email</li>
                    <li>The Bill of Lading document</li>
                    <li>Your shipment invoice</li>
                  </ul>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>

      {/* Status Display */}
      {showStatus && (
        <section className="section-padding pt-0">
          <div className="container-custom">
            <div className="max-w-4xl mx-auto">
              <div className="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <div className="flex items-start justify-between mb-8">
                  <div>
                    <h2 className="text-2xl font-bold text-navy-900 mb-2">Shipment #{trackingNumber}</h2>
                    <p className="text-gray-600">2020 Toyota Camry</p>
                    <p className="text-sm text-gray-500 mt-1">Route: Japan → Mombasa → Kampala</p>
                  </div>
                  <div className="text-right">
                    <div className="inline-block bg-yellow-100 text-yellow-800 px-4 py-2 rounded-full font-medium">
                      In Transit
                    </div>
                    <p className="text-sm text-gray-500 mt-2">Est. Delivery: Mar 2, 2024</p>
                  </div>
                </div>

                {/* Tab Navigation */}
                <div className="flex border-b border-gray-200 mb-6">
                  <button
                    onClick={() => setActiveTab('timeline')}
                    className={`px-6 py-3 font-medium text-sm border-b-2 transition-colors ${
                      activeTab === 'timeline'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                    }`}
                  >
                    <FaTruck className="inline mr-2" />
                    Timeline
                  </button>
                  <button
                    onClick={() => setActiveTab('map')}
                    className={`px-6 py-3 font-medium text-sm border-b-2 transition-colors ${
                      activeTab === 'map'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                    }`}
                  >
                    <FaMap className="inline mr-2" />
                    Live Map
                  </button>
                </div>

                {/* Timeline View */}
                {activeTab === 'timeline' && (
                  <div className="space-y-6">
                    {trackingStatuses.map((item, index) => (
                      <div key={index} className="flex gap-6">
                        <div className="flex flex-col items-center">
                          <div className={`w-12 h-12 rounded-full flex items-center justify-center text-xl ${
                            item.completed 
                              ? 'bg-green-500 text-white' 
                              : item.current 
                              ? 'bg-blue-600 text-white animate-pulse' 
                              : 'bg-gray-200 text-gray-400'
                          }`}>
                            {item.icon}
                          </div>
                          {index < trackingStatuses.length - 1 && (
                            <div className={`w-0.5 h-16 ${
                              item.completed ? 'bg-green-500' : 'bg-gray-200'
                            }`}></div>
                          )}
                        </div>
                        <div className="flex-1 pb-8">
                          <h3 className={`text-lg font-bold mb-1 ${
                            item.current ? 'text-blue-600' : 'text-navy-900'
                          }`}>
                            {item.status}
                          </h3>
                          <p className="text-gray-600 text-sm">{item.date}</p>
                          {item.current && (
                            <p className="mt-2 text-sm text-blue-600">
                              Your vehicle is currently being transported from Mombasa to Kampala. 
                              Estimated arrival in 2-3 days.
                            </p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {/* Map View */}
                {activeTab === 'map' && (
                  <div className="mt-4">
                    <TrackingMap 
                      trackingNumber={trackingNumber} 
                      isPublic={true}
                    />
                  </div>
                )}
              </div>

              {/* Contact Support */}
              <div className="bg-blue-50 rounded-xl p-6 text-center">
                <h3 className="font-bold text-navy-900 mb-2">Need Help?</h3>
                <p className="text-gray-600 mb-4">
                  Have questions about your shipment? Our support team is here to help.
                </p>
                <a href="mailto:support@shipwithglowie.com" className="btn-primary inline-block px-8">
                  Contact Support
                </a>
              </div>
            </div>
          </div>
        </section>
      )}
    </div>
  );
};

export default ShipmentStatus;
