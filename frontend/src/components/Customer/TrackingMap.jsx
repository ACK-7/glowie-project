import React, { useState, useEffect } from 'react';
import { 
  FaMapMarkerAlt, 
  FaShip, 
  FaPlane, 
  FaTruck,
  FaWarehouse,
  FaFlag,
  FaClock,
  FaRoute
} from 'react-icons/fa';

const TrackingMap = ({ shipment, booking }) => {
  const [currentStep, setCurrentStep] = useState(0);
  const [trackingSteps, setTrackingSteps] = useState([]);

  useEffect(() => {
    if (booking) {
      generateTrackingSteps();
    }
  }, [booking, shipment]);

  const generateTrackingSteps = () => {
    const steps = [
      {
        id: 1,
        title: 'Booking Confirmed',
        description: 'Your booking has been confirmed and is being processed',
        icon: FaFlag,
        status: 'completed',
        date: booking.created_at,
        location: booking.origin_city || 'Origin'
      },
      {
        id: 2,
        title: 'Vehicle Collection',
        description: 'Vehicle collected from seller/location',
        icon: FaTruck,
        status: getStepStatus('collection'),
        date: booking.pickup_date || null,
        location: booking.origin_city || 'Origin'
      },
      {
        id: 3,
        title: 'Port Processing',
        description: 'Vehicle processing at departure port',
        icon: FaWarehouse,
        status: getStepStatus('port_processing'),
        date: shipment?.departure_date || null,
        location: shipment?.departure_port || booking.origin_city || 'Departure Port'
      },
      {
        id: 4,
        title: 'In Transit',
        description: 'Vehicle is being shipped to destination',
        icon: FaShip,
        status: getStepStatus('in_transit'),
        date: shipment?.departure_date || null,
        location: shipment?.current_location || 'At Sea'
      },
      {
        id: 5,
        title: 'Customs Clearance',
        description: 'Vehicle clearing customs at destination',
        icon: FaWarehouse,
        status: getStepStatus('customs'),
        date: null,
        location: shipment?.arrival_port || booking.destination_city || 'Destination Port'
      },
      {
        id: 6,
        title: 'Delivered',
        description: 'Vehicle delivered to final destination',
        icon: FaFlag,
        status: getStepStatus('delivered'),
        date: booking.delivery_date || null,
        location: booking.destination_city || 'Destination'
      }
    ];

    setTrackingSteps(steps);
    
    // Set current step based on booking status
    const statusStepMap = {
      'confirmed': 1,
      'processing': 2,
      'collected': 2,
      'in_transit': 4,
      'customs': 5,
      'delivered': 6
    };
    
    setCurrentStep(statusStepMap[booking.status] || 1);
  };

  const getStepStatus = (stepType) => {
    if (!booking) return 'pending';
    
    const status = booking.status;
    
    switch (stepType) {
      case 'collection':
        return ['collected', 'in_transit', 'customs', 'delivered'].includes(status) ? 'completed' : 
               ['processing', 'confirmed'].includes(status) ? 'current' : 'pending';
      
      case 'port_processing':
        return ['in_transit', 'customs', 'delivered'].includes(status) ? 'completed' : 
               status === 'collected' ? 'current' : 'pending';
      
      case 'in_transit':
        return ['customs', 'delivered'].includes(status) ? 'completed' : 
               status === 'in_transit' ? 'current' : 'pending';
      
      case 'customs':
        return status === 'delivered' ? 'completed' : 
               status === 'customs' ? 'current' : 'pending';
      
      case 'delivered':
        return status === 'delivered' ? 'completed' : 'pending';
      
      default:
        return 'pending';
    }
  };

  const getStepColor = (status) => {
    switch (status) {
      case 'completed':
        return 'bg-green-500 text-white border-green-500';
      case 'current':
        return 'bg-blue-500 text-white border-blue-500 animate-pulse';
      default:
        return 'bg-gray-200 text-gray-500 border-gray-300';
    }
  };

  const getConnectorColor = (index) => {
    return index < currentStep - 1 ? 'bg-green-500' : 'bg-gray-300';
  };

  const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
  };

  const getEstimatedDelivery = () => {
    if (booking.estimated_delivery) {
      return formatDate(booking.estimated_delivery);
    }
    
    // Calculate estimated delivery based on route
    const createdDate = new Date(booking.created_at);
    const estimatedDays = getEstimatedDays();
    const estimatedDate = new Date(createdDate.getTime() + (estimatedDays * 24 * 60 * 60 * 1000));
    
    return formatDate(estimatedDate.toISOString());
  };

  const getEstimatedDays = () => {
    const origin = booking.origin_country?.toLowerCase();
    const estimatedDays = {
      'japan': 35,
      'uk': 45,
      'united kingdom': 45,
      'uae': 25,
      'united states': 50,
      'usa': 50,
      'germany': 40,
      'canada': 45
    };
    
    return estimatedDays[origin] || 40;
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
      {/* Shipment Overview */}
      <div className="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-xl font-bold mb-2">
              {booking.vehicle_make} {booking.vehicle_model} ({booking.vehicle_year})
            </h3>
            <p className="text-blue-100">
              Booking: {booking.booking_reference}
            </p>
            <p className="text-blue-100">
              {booking.origin_city}, {booking.origin_country} → {booking.destination_city}
            </p>
          </div>
          <div className="text-right">
            <div className="text-2xl font-bold">
              {currentStep}/{trackingSteps.length}
            </div>
            <div className="text-blue-100 text-sm">Steps Complete</div>
          </div>
        </div>
      </div>

      {/* Current Status */}
      <div className="bg-white rounded-lg border p-6">
        <div className="flex items-center justify-between mb-4">
          <h4 className="text-lg font-semibold text-gray-900">Current Status</h4>
          <span className={`px-3 py-1 rounded-full text-sm font-medium ${
            booking.status === 'delivered' ? 'bg-green-100 text-green-800' :
            booking.status === 'in_transit' ? 'bg-blue-100 text-blue-800' :
            booking.status === 'customs' ? 'bg-orange-100 text-orange-800' :
            'bg-yellow-100 text-yellow-800'
          }`}>
            {booking.status?.toUpperCase() || 'PROCESSING'}
          </span>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="flex items-center space-x-3">
            <FaMapMarkerAlt className="text-blue-500" />
            <div>
              <p className="text-sm text-gray-600">Current Location</p>
              <p className="font-medium">
                {shipment?.current_location || booking.origin_city || 'Processing'}
              </p>
            </div>
          </div>
          
          <div className="flex items-center space-x-3">
            <FaClock className="text-blue-500" />
            <div>
              <p className="text-sm text-gray-600">Estimated Delivery</p>
              <p className="font-medium">{getEstimatedDelivery()}</p>
            </div>
          </div>
          
          <div className="flex items-center space-x-3">
            <FaRoute className="text-blue-500" />
            <div>
              <p className="text-sm text-gray-600">Transit Time</p>
              <p className="font-medium">{getEstimatedDays()} days</p>
            </div>
          </div>
        </div>
      </div>

      {/* Tracking Timeline */}
      <div className="bg-white rounded-lg border p-6">
        <h4 className="text-lg font-semibold text-gray-900 mb-6">Shipment Progress</h4>
        
        <div className="relative">
          {trackingSteps.map((step, index) => {
            const IconComponent = step.icon;
            const isLast = index === trackingSteps.length - 1;
            
            return (
              <div key={step.id} className="relative flex items-start pb-8">
                {/* Connector Line */}
                {!isLast && (
                  <div 
                    className={`absolute left-6 top-12 w-0.5 h-16 ${getConnectorColor(index)}`}
                  />
                )}
                
                {/* Step Icon */}
                <div className={`relative flex items-center justify-center w-12 h-12 rounded-full border-2 ${getStepColor(step.status)}`}>
                  <IconComponent className="text-lg" />
                </div>
                
                {/* Step Content */}
                <div className="ml-6 flex-1">
                  <div className="flex items-center justify-between">
                    <h5 className="text-lg font-medium text-gray-900">{step.title}</h5>
                    {step.date && (
                      <span className="text-sm text-gray-500">
                        {formatDate(step.date)}
                      </span>
                    )}
                  </div>
                  <p className="text-gray-600 mt-1">{step.description}</p>
                  <p className="text-sm text-gray-500 mt-1 flex items-center">
                    <FaMapMarkerAlt className="mr-1" />
                    {step.location}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Additional Information */}
      {shipment && (
        <div className="bg-white rounded-lg border p-6">
          <h4 className="text-lg font-semibold text-gray-900 mb-4">Shipment Details</h4>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {shipment.vessel_name && (
              <div>
                <p className="text-sm text-gray-600">Vessel</p>
                <p className="font-medium">{shipment.vessel_name}</p>
              </div>
            )}
            
            {shipment.container_number && (
              <div>
                <p className="text-sm text-gray-600">Container</p>
                <p className="font-medium">{shipment.container_number}</p>
              </div>
            )}
            
            {shipment.departure_port && (
              <div>
                <p className="text-sm text-gray-600">Departure Port</p>
                <p className="font-medium">{shipment.departure_port}</p>
              </div>
            )}
            
            {shipment.arrival_port && (
              <div>
                <p className="text-sm text-gray-600">Arrival Port</p>
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