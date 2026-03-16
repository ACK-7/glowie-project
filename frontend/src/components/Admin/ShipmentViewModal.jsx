import React, { useState, useEffect } from "react";
import {
  FaTimes,
  FaShip,
  FaMapMarkerAlt,
  FaClock,
  FaCheckCircle,
  FaTruck,
  FaUser,
  FaCar,
  FaRoute,
  FaCalendarAlt,
  FaExclamationTriangle,
  FaInfoCircle,
  FaMap,
  FaList,
  FaWarehouse,
} from "react-icons/fa";
import { getShipment } from "../../services/adminService";
import TrackingMap from "../Customer/TrackingMap";

const ShipmentViewModal = ({ shipment, onClose }) => {
  const [detailedShipment, setDetailedShipment] = useState(shipment);
  const [trackingHistory, setTrackingHistory] = useState([]);
  const [delayInfo, setDelayInfo] = useState(null);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState("details");

  useEffect(() => {
    if (shipment?.id) {
      fetchDetailedShipment();
    }
  }, [shipment?.id]);

  const fetchDetailedShipment = async () => {
    try {
      setLoading(true);
      const response = await getShipment(shipment.id);
      // API returns { success, data: { shipment, tracking_history, delay_info, ... } }
      const data = response.data || response;
      if (data.shipment) {
        setDetailedShipment(data.shipment);
        setTrackingHistory(data.tracking_history || []);
        setDelayInfo(data.delay_info || null);
      } else {
        setDetailedShipment(data);
      }
    } catch (error) {
      console.error("Failed to fetch detailed shipment:", error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const config = {
      pending: {
        bg: "bg-yellow-900/30",
        text: "text-yellow-400",
        label: "Pending",
        icon: FaClock,
      },
      confirmed: {
        bg: "bg-blue-900/30",
        text: "text-blue-400",
        label: "Confirmed",
        icon: FaCheckCircle,
      },
      in_transit: {
        bg: "bg-purple-900/30",
        text: "text-purple-400",
        label: "In Transit",
        icon: FaTruck,
      },
      on_route: {
        bg: "bg-blue-900/30",
        text: "text-blue-400",
        label: "On Route",
        icon: FaShip,
      },
      customs: {
        bg: "bg-orange-900/30",
        text: "text-orange-400",
        label: "At Customs",
        icon: FaMapMarkerAlt,
      },
      delivered: {
        bg: "bg-green-900/30",
        text: "text-green-400",
        label: "Delivered",
        icon: FaCheckCircle,
      },
      delayed: {
        bg: "bg-red-900/30",
        text: "text-red-400",
        label: "Delayed",
        icon: FaExclamationTriangle,
      },
    };
    const c = config[status] || config.pending;
    const IconComponent = c.icon;
    return (
      <span
        className={`${c.bg} ${c.text} px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-2`}
      >
        <IconComponent className="text-sm" />
        {c.label}
      </span>
    );
  };

  const getProgressColor = (percentage) => {
    if (percentage >= 80) return "bg-green-500";
    if (percentage >= 50) return "bg-blue-500";
    if (percentage >= 25) return "bg-yellow-500";
    return "bg-red-500";
  };

  const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-800">
          <div className="flex items-center gap-4">
            <FaShip className="text-blue-500 text-2xl" />
            <div>
              <h2 className="text-2xl font-bold text-white">
                {detailedShipment.tracking_number ||
                  `SWG-${detailedShipment.id}`}
              </h2>
              <p className="text-gray-400">
                Shipment Details
                {detailedShipment.booking?.booking_reference && (
                  <span className="ml-2 text-gray-500">· Booking: {detailedShipment.booking.booking_reference}</span>
                )}
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
          >
            <FaTimes className="text-xl" />
          </button>
        </div>

        {/* Tab Navigation */}
        <div className="border-b border-gray-800">
          <nav className="flex px-6">
            <button
              onClick={() => setActiveTab("details")}
              className={`px-6 py-4 text-sm font-medium border-b-2 transition-colors ${
                activeTab === "details"
                  ? "border-blue-500 text-blue-400"
                  : "border-transparent text-gray-400 hover:text-gray-300"
              }`}
            >
              <FaInfoCircle className="inline mr-2" />
              Details
            </button>
            <button
              onClick={() => setActiveTab("map")}
              className={`px-6 py-4 text-sm font-medium border-b-2 transition-colors ${
                activeTab === "map"
                  ? "border-blue-500 text-blue-400"
                  : "border-transparent text-gray-400 hover:text-gray-300"
              }`}
            >
              <FaMap className="inline mr-2" />
              Live Map
            </button>
            <button
              onClick={() => setActiveTab("timeline")}
              className={`px-6 py-4 text-sm font-medium border-b-2 transition-colors ${
                activeTab === "timeline"
                  ? "border-blue-500 text-blue-400"
                  : "border-transparent text-gray-400 hover:text-gray-300"
              }`}
            >
              <FaList className="inline mr-2" />
              Timeline
            </button>
          </nav>
        </div>

        {loading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
            <p className="text-gray-400 mt-4">Loading shipment details...</p>
          </div>
        ) : (
          <div className="p-6">
            {/* Details Tab */}
            {activeTab === "details" && (
              <div className="space-y-6">
                {/* Status and Progress */}
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaInfoCircle className="text-blue-500" />
                      Current Status
                    </h3>
                    <div className="space-y-4">
                      <div className="flex items-center justify-between">
                        <span className="text-gray-400">Status:</span>
                        {getStatusBadge(detailedShipment.status)}
                      </div>
                      <div>
                        <div className="flex items-center justify-between text-sm mb-2">
                          <span className="text-gray-400">Progress</span>
                          <span className="text-white font-semibold">
                            {detailedShipment.progress_percentage || 0}%
                          </span>
                        </div>
                        <div className="w-full bg-gray-700 rounded-full h-3">
                          <div
                            className={`h-3 rounded-full transition-all ${getProgressColor(detailedShipment.progress_percentage || 0)}`}
                            style={{
                              width: `${detailedShipment.progress_percentage || 0}%`,
                            }}
                          ></div>
                        </div>
                      </div>
                      <div>
                        <span className="text-gray-400">Current Location:</span>
                        <p className="text-white font-medium">
                          {detailedShipment.current_location || "N/A"}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaCalendarAlt className="text-green-500" />
                      Timeline
                    </h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Departure Date:</span>
                        <p className="text-white">
                          {formatDate(detailedShipment.departure_date)}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">
                          Estimated Arrival:
                        </span>
                        <p className="text-white">
                          {formatDate(detailedShipment.estimated_arrival)}
                        </p>
                      </div>
                      {detailedShipment.actual_arrival && (
                        <div>
                          <span className="text-gray-400">Actual Arrival:</span>
                          <p className="text-white">
                            {formatDate(detailedShipment.actual_arrival)}
                          </p>
                        </div>
                      )}
                      {detailedShipment.is_delayed && (
                        <div className="bg-red-900/20 border border-red-700/50 rounded-lg p-3">
                          <p className="text-red-400 font-medium">
                            Delayed by {detailedShipment.days_delayed || 0} days
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Customer and Vehicle Info */}
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaUser className="text-purple-500" />
                      Customer Information
                    </h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Name:</span>
                        <p className="text-white font-medium">
                          {detailedShipment.booking?.customer?.full_name ||
                            (detailedShipment.booking?.customer?.first_name
                              ? `${detailedShipment.booking.customer.first_name} ${detailedShipment.booking.customer.last_name || ""}`.trim()
                              : "N/A")}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">Email:</span>
                        <p className="text-white">
                          {detailedShipment.booking?.customer?.email || "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">Phone:</span>
                        <p className="text-white">
                          {detailedShipment.booking?.customer?.phone || "N/A"}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaCar className="text-yellow-500" />
                      Vehicle Information
                    </h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Vehicle:</span>
                        <p className="text-white font-medium">
                          {detailedShipment.booking?.vehicle
                            ? `${detailedShipment.booking.vehicle.year} ${detailedShipment.booking.vehicle.make} ${detailedShipment.booking.vehicle.model}`
                            : "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">VIN:</span>
                        <p className="text-white">
                          {detailedShipment.booking?.vehicle?.vin || "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">Color:</span>
                        <p className="text-white">
                          {detailedShipment.booking?.vehicle?.color || "N/A"}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Route and Carrier Info */}
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaRoute className="text-blue-500" />
                      Route Information
                    </h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Departure Port:</span>
                        <p className="text-white font-medium">
                          {detailedShipment.departure_port || "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">Arrival Port:</span>
                        <p className="text-white font-medium">
                          {detailedShipment.arrival_port || "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">
                          Route Description:
                        </span>
                        <p className="text-white">
                          {detailedShipment.route_description || "N/A"}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaTruck className="text-orange-500" />
                      Carrier Information
                    </h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Carrier:</span>
                        <p className="text-white font-medium">
                          {detailedShipment.carrier_name || "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">Vessel Name:</span>
                        <p className="text-white">
                          {detailedShipment.vessel_name || "N/A"}
                        </p>
                      </div>
                      <div>
                        <span className="text-gray-400">Container Number:</span>
                        <p className="text-white">
                          {detailedShipment.container_number || "N/A"}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Delay Information */}
                {(detailedShipment.is_delayed || delayInfo?.is_delayed) && (
                  <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-red-400 mb-4 flex items-center gap-2">
                      <FaExclamationTriangle />
                      Delay Information
                    </h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Days Delayed:</span>
                        <p className="text-white font-medium">
                          {delayInfo?.days_delayed || detailedShipment.days_delayed || 0} days
                        </p>
                      </div>
                      {delayInfo?.delay_reasons?.length > 0 && (
                        <div>
                          <span className="text-gray-400">Delay Reasons:</span>
                          <ul className="text-white mt-1">
                            {delayInfo.delay_reasons.map((reason, index) => (
                              <li key={index} className="ml-4">• {reason}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                      {delayInfo?.suggested_actions?.length > 0 && (
                        <div>
                          <span className="text-gray-400">Suggested Actions:</span>
                          <ul className="text-white mt-1">
                            {delayInfo.suggested_actions.map((action, index) => (
                              <li key={index} className="ml-4">• {action}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Map Tab */}
            {activeTab === "map" && (
              <div className="space-y-4">
                <div className="bg-gray-800/30 rounded-xl p-4">
                  <TrackingMap
                    shipment={detailedShipment}
                    booking={detailedShipment.booking}
                  />
                </div>
              </div>
            )}

            {/* Timeline Tab */}
            {activeTab === "timeline" && (
              <div className="space-y-6">
                {/* Journey Steps */}
                <div className="bg-gray-800/30 rounded-xl p-6">
                  <h3 className="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                    <FaRoute className="text-blue-500" />
                    Shipment Journey
                  </h3>
                  <div className="flex items-center justify-between mb-8 overflow-x-auto pb-2">
                    {[
                      { key: 'preparing', label: 'Preparing', icon: FaWarehouse },
                      { key: 'in_transit', label: 'In Transit', icon: FaShip },
                      { key: 'customs', label: 'At Customs', icon: FaMapMarkerAlt },
                      { key: 'delivered', label: 'Delivered', icon: FaCheckCircle },
                    ].map((step, idx, arr) => {
                      const statusOrder = ['preparing', 'in_transit', 'customs', 'delivered'];
                      const currentIdx = statusOrder.indexOf(detailedShipment.status === 'delayed' ? 'in_transit' : detailedShipment.status);
                      const stepIdx = statusOrder.indexOf(step.key);
                      const isCompleted = stepIdx < currentIdx;
                      const isActive = stepIdx === currentIdx;
                      const Icon = step.icon;
                      return (
                        <React.Fragment key={step.key}>
                          <div className="flex flex-col items-center min-w-[80px]">
                            <div className={`w-12 h-12 rounded-full flex items-center justify-center border-2 transition-all ${
                              isCompleted ? 'bg-green-600 border-green-500' :
                              isActive ? 'bg-blue-600 border-blue-400 ring-4 ring-blue-900/50' :
                              'bg-gray-700 border-gray-600'
                            }`}>
                              <Icon className={`text-lg ${isCompleted || isActive ? 'text-white' : 'text-gray-400'}`} />
                            </div>
                            <p className={`text-xs mt-2 font-medium text-center ${
                              isCompleted ? 'text-green-400' :
                              isActive ? 'text-blue-400' :
                              'text-gray-500'
                            }`}>{step.label}</p>
                            {isActive && detailedShipment.status === 'delayed' && (
                              <span className="text-xs text-red-400 mt-1">Delayed</span>
                            )}
                          </div>
                          {idx < arr.length - 1 && (
                            <div className={`flex-1 h-1 mx-2 rounded ${stepIdx < currentIdx ? 'bg-green-600' : 'bg-gray-700'}`} />
                          )}
                        </React.Fragment>
                      );
                    })}
                  </div>
                </div>

                {/* History Events */}
                {trackingHistory.length > 0 ? (
                  <div className="bg-gray-800/30 rounded-xl p-6">
                    <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                      <FaClock className="text-green-500" />
                      Event Log
                    </h3>
                    <div className="relative">
                      <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-700"></div>
                      <div className="space-y-6">
                        {[...trackingHistory].reverse().map((event, index) => {
                          const statusColors = {
                            created: 'bg-gray-600',
                            preparing: 'bg-yellow-600',
                            in_transit: 'bg-blue-600',
                            customs: 'bg-orange-600',
                            delivered: 'bg-green-600',
                            delayed: 'bg-red-600',
                          };
                          const dotColor = statusColors[event.status] || 'bg-blue-600';
                          const statusLabels = {
                            created: 'Shipment Created',
                            preparing: 'Preparing for Departure',
                            in_transit: 'In Transit',
                            customs: 'Arrived at Customs',
                            delivered: 'Delivered',
                            delayed: 'Delay Reported',
                          };
                          return (
                            <div key={index} className="flex gap-4 pl-10 relative">
                              <div className={`absolute left-2.5 top-1 w-3 h-3 rounded-full ${dotColor} ring-2 ring-gray-800`}></div>
                              <div className="flex-1 bg-gray-700/30 rounded-lg p-4">
                                <div className="flex items-start justify-between gap-2">
                                  <p className="text-white font-medium">
                                    {statusLabels[event.status] || event.status || 'Status Update'}
                                  </p>
                                  <span className="text-gray-500 text-xs whitespace-nowrap">
                                    {formatDate(event.timestamp || event.created_at)}
                                  </span>
                                </div>
                                {event.location && (
                                  <p className="text-gray-400 text-sm mt-1 flex items-center gap-1">
                                    <FaMapMarkerAlt className="text-xs text-gray-500" />
                                    {event.location}
                                  </p>
                                )}
                                {event.notes && (
                                  <p className="text-gray-400 text-sm mt-1">{event.notes}</p>
                                )}
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="bg-gray-800/30 rounded-xl p-6 text-center">
                    <FaClock className="text-gray-400 text-4xl mx-auto mb-4" />
                    <p className="text-gray-400">No tracking events recorded yet</p>
                  </div>
                )}
              </div>
            )}
          </div>
        )}

        {/* Footer */}
        <div className="flex justify-end gap-4 p-6 border-t border-gray-800">
          <button
            onClick={onClose}
            className="px-6 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default ShipmentViewModal;
