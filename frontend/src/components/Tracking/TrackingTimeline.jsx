import React, { useState, useEffect } from 'react';
import { FaShip, FaTruck, FaCheckCircle, FaMapMarkerAlt, FaClock, FaExclamationTriangle } from 'react-icons/fa';

const TrackingTimeline = ({ trackingNumber, shipmentId, isPublic = false }) => {
  const [timelineData, setTimelineData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (trackingNumber || shipmentId) {
      loadTimelineData();
    }
  }, [trackingNumber, shipmentId]);

  const loadTimelineData = async () => {
    try {
      setLoading(true);
      setError(null);

      let response;
      if (isPublic && trackingNumber) {
        // Public tracking endpoint
        response = await fetch(`/api/tracking/${trackingNumber}/timeline`);
      } else if (shipmentId) {
        // Admin tracking endpoint
        const token = localStorage.getItem('token');
        response = await fetch(`/api/admin/crud/shipments/${shipmentId}/timeline`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
      }

      if (!response.ok) {
        throw new Error('Failed to load timeline data');
      }

      const data = await response.json();
      if (data.success) {
        setTimelineData(data.data);
      } else {
        throw new Error(data.message || 'Failed to load timeline data');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (status, isCompleted, isCurrent) => {
    if (isCurrent) {
      return <FaTruck className="animate-pulse" />;
    }
    
    switch (status?.toLowerCase()) {
      case 'booked':
      case 'confirmed':
        return <FaCheckCircle />;
      case 'picked_up':
      case 'collected':
        return <FaTruck />;
      case 'in_transit':
      case 'shipped':
      case 'on_vessel':
        return <FaShip />;
      case 'arrived':
      case 'in_port':
        return <FaMapMarkerAlt />;
      case 'delivered':
      case 'completed':
        return <FaCheckCircle />;
      case 'delayed':
      case 'issue':
        return <FaExclamationTriangle />;
      default:
        return <FaClock />;
    }
  };

  const getStatusColor = (status, isCompleted, isCurrent) => {
    if (isCurrent) {
      return 'bg-blue-600 text-white';
    }
    
    if (isCompleted) {
      return 'bg-green-500 text-white';
    }

    switch (status?.toLowerCase()) {
      case 'delayed':
      case 'issue':
        return 'bg-yellow-500 text-white';
      case 'cancelled':
      case 'failed':
        return 'bg-red-500 text-white';
      default:
        return 'bg-gray-200 text-gray-400';
    }
  };

  const getStatusDescription = (event) => {
    const descriptions = {
      'booked': 'Your vehicle booking has been confirmed and is being prepared for shipment.',
      'picked_up': 'Vehicle has been collected from the origin location and is ready for transport.',
      'in_transit': 'Your vehicle is currently being transported to the destination.',
      'on_vessel': 'Vehicle is loaded on the shipping vessel and en route to the destination port.',
      'arrived': 'Vehicle has arrived at the destination port and is being processed.',
      'in_port': 'Vehicle is currently at the port awaiting customs clearance and final transport.',
      'out_for_delivery': 'Vehicle is on the final leg of delivery to your specified location.',
      'delivered': 'Vehicle has been successfully delivered to the destination.',
      'delayed': 'There is a delay in the shipment. We will update you with new timing soon.',
      'issue': 'An issue has been identified with the shipment. Our team is working to resolve it.'
    };

    return event.description || descriptions[event.status?.toLowerCase()] || 'Status update for your shipment.';
  };

  const refreshTimeline = () => {
    loadTimelineData();
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading timeline...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <FaExclamationTriangle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600 mb-4">{error}</p>
          <button
            onClick={refreshTimeline}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  if (!timelineData || !timelineData.events || timelineData.events.length === 0) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <FaClock className="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600">No timeline data available</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white">
      {/* Timeline Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-semibold text-gray-900">Shipment Timeline</h3>
          {timelineData.shipment_info && (
            <p className="text-sm text-gray-600 mt-1">
              Tracking: {timelineData.shipment_info.tracking_number} • 
              Status: {timelineData.shipment_info.status}
            </p>
          )}
        </div>
        <button
          onClick={refreshTimeline}
          className="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors"
        >
          Refresh
        </button>
      </div>

      {/* Timeline Events */}
      <div className="relative">
        {/* Timeline Line */}
        <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
        
        <div className="space-y-8">
          {timelineData.events.map((event, index) => {
            const isCompleted = event.completed || event.status === 'completed';
            const isCurrent = event.current || event.is_current;
            const isLast = index === timelineData.events.length - 1;

            return (
              <div key={index} className="relative flex items-start">
                {/* Timeline Icon */}
                <div className={`relative z-10 flex items-center justify-center w-12 h-12 rounded-full text-xl ${getStatusColor(event.status, isCompleted, isCurrent)}`}>
                  {getStatusIcon(event.status, isCompleted, isCurrent)}
                </div>

                {/* Event Content */}
                <div className="ml-6 flex-1 min-w-0">
                  <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h4 className={`text-lg font-semibold mb-1 ${isCurrent ? 'text-blue-600' : 'text-gray-900'}`}>
                          {event.title || event.status_display || event.status}
                        </h4>
                        
                        {event.location && (
                          <p className="text-sm text-gray-600 mb-2 flex items-center">
                            <FaMapMarkerAlt className="mr-1 h-3 w-3" />
                            {event.location}
                          </p>
                        )}

                        <p className="text-sm text-gray-700 mb-3">
                          {getStatusDescription(event)}
                        </p>

                        {event.notes && (
                          <div className="bg-blue-50 rounded p-3 mb-3">
                            <p className="text-sm text-blue-800">{event.notes}</p>
                          </div>
                        )}

                        {event.estimated_completion && !isCompleted && (
                          <p className="text-xs text-gray-500">
                            Estimated completion: {new Date(event.estimated_completion).toLocaleString()}
                          </p>
                        )}
                      </div>

                      <div className="ml-4 text-right flex-shrink-0">
                        {event.timestamp && (
                          <div className="text-sm text-gray-600">
                            <div>{new Date(event.timestamp).toLocaleDateString()}</div>
                            <div className="text-xs text-gray-500">
                              {new Date(event.timestamp).toLocaleTimeString()}
                            </div>
                          </div>
                        )}
                        
                        {isCurrent && (
                          <div className="mt-2">
                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                              Current
                            </span>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Additional Details */}
                    {event.details && event.details.length > 0 && (
                      <div className="mt-3 pt-3 border-t border-gray-200">
                        <div className="space-y-1">
                          {event.details.map((detail, detailIndex) => (
                            <div key={detailIndex} className="flex justify-between text-xs text-gray-600">
                              <span>{detail.label}:</span>
                              <span className="font-medium">{detail.value}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Summary */}
      {timelineData.summary && (
        <div className="mt-8 p-4 bg-blue-50 rounded-lg">
          <h4 className="font-semibold text-blue-900 mb-2">Journey Summary</h4>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            {timelineData.summary.total_distance && (
              <div>
                <span className="text-blue-600 font-medium">Distance:</span>
                <div className="text-blue-800">{timelineData.summary.total_distance}</div>
              </div>
            )}
            {timelineData.summary.transit_time && (
              <div>
                <span className="text-blue-600 font-medium">Transit Time:</span>
                <div className="text-blue-800">{timelineData.summary.transit_time}</div>
              </div>
            )}
            {timelineData.summary.completed_checkpoints && (
              <div>
                <span className="text-blue-600 font-medium">Checkpoints:</span>
                <div className="text-blue-800">
                  {timelineData.summary.completed_checkpoints}/{timelineData.summary.total_checkpoints}
                </div>
              </div>
            )}
            {timelineData.summary.next_update && (
              <div>
                <span className="text-blue-600 font-medium">Next Update:</span>
                <div className="text-blue-800">{timelineData.summary.next_update}</div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default TrackingTimeline;