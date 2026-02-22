import React, { useState, useEffect } from 'react';
import { 
  FaTimes, 
  FaShip, 
  FaSave,
  FaMapMarkerAlt,
  FaTruck,
  FaCalendarAlt,
  FaRoute,
  FaInfoCircle
} from 'react-icons/fa';
import { updateShipment, updateShipmentStatus, updateEstimatedArrival } from '../../services/adminService';
import { showAlert } from '../../utils/sweetAlert';

const ShipmentEditModal = ({ shipment, onClose, onSave }) => {
  const [activeTab, setActiveTab] = useState('basic');
  const [formData, setFormData] = useState({
    tracking_number: '',
    carrier_name: '',
    vessel_name: '',
    container_number: '',
    current_location: '',
    status: 'preparing',
    departure_port: '',
    arrival_port: '',
    departure_date: '',
    estimated_arrival: '',
    route_description: '',
    notes: ''
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (shipment) {
      setFormData({
        tracking_number: shipment.tracking_number || '',
        carrier_name: shipment.carrier_name || '',
        vessel_name: shipment.vessel_name || '',
        container_number: shipment.container_number || '',
        current_location: shipment.current_location || '',
        status: shipment.status || 'preparing',
        departure_port: shipment.departure_port || '',
        arrival_port: shipment.arrival_port || '',
        departure_date: shipment.departure_date ? shipment.departure_date.split('T')[0] : '',
        estimated_arrival: shipment.estimated_arrival ? shipment.estimated_arrival.split('T')[0] : '',
        route_description: shipment.route_description || '',
        notes: ''
      });
    }
  }, [shipment]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.status) {
      newErrors.status = 'Status is required';
    }

    if (!formData.tracking_number?.trim()) {
      newErrors.tracking_number = 'Tracking number is required';
    }

    if (formData.departure_date && formData.estimated_arrival) {
      if (new Date(formData.departure_date) >= new Date(formData.estimated_arrival)) {
        newErrors.estimated_arrival = 'Estimated arrival must be after departure date';
      }
    }

    // Validate that current location is provided if status is in_transit
    if (formData.status === 'in_transit' && !formData.current_location?.trim()) {
      newErrors.current_location = 'Current location is required for in-transit shipments';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    try {
      setLoading(true);

      // Prepare update data
      const updateData = {
        tracking_number: formData.tracking_number || null,
        carrier_name: formData.carrier_name || null,
        vessel_name: formData.vessel_name || null,
        container_number: formData.container_number || null,
        current_location: formData.current_location || null,
        departure_port: formData.departure_port || null,
        arrival_port: formData.arrival_port || null,
        departure_date: formData.departure_date || null,
        estimated_arrival: formData.estimated_arrival || null,
        route_description: formData.route_description || null,
      };

      // Update shipment details
      await updateShipment(shipment.id, updateData);

      // Update status if changed
      if (formData.status !== shipment.status) {
        await updateShipmentStatus(
          shipment.id, 
          formData.status, 
          formData.current_location || null,
          formData.notes || 'Status updated via admin panel'
        );
      }

      await showAlert('Success', 'Shipment updated successfully', 'success');
      onSave();
    } catch (error) {
      console.error('Failed to update shipment:', error);
      await showAlert('Error', 'Failed to update shipment. Please try again.', 'error');
    } finally {
      setLoading(false);
    }
  };

  const statusOptions = [
    { value: 'preparing', label: 'Preparing', color: 'text-yellow-400' },
    { value: 'in_transit', label: 'In Transit', color: 'text-blue-400' },
    { value: 'customs', label: 'At Customs', color: 'text-orange-400' },
    { value: 'delivered', label: 'Delivered', color: 'text-green-400' },
    { value: 'delayed', label: 'Delayed', color: 'text-red-400' }
  ];

  const getStatusTransitionMessage = () => {
    if (!shipment || formData.status === shipment.status) return null;
    
    const currentStatus = statusOptions.find(s => s.value === shipment.status)?.label || shipment.status;
    const newStatus = statusOptions.find(s => s.value === formData.status)?.label || formData.status;
    
    return (
      <div className="bg-blue-900/20 border border-blue-700/50 rounded-lg p-3 mb-4">
        <div className="flex items-center gap-2 text-blue-400 text-sm">
          <FaInfoCircle />
          <span>Status will change from <strong>{currentStatus}</strong> to <strong>{newStatus}</strong></span>
        </div>
      </div>
    );
  };

  const tabs = [
    { id: 'basic', label: 'Basic Info', icon: FaInfoCircle },
    { id: 'carrier', label: 'Carrier & Route', icon: FaShip },
    { id: 'status', label: 'Status & Timeline', icon: FaCalendarAlt }
  ];

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-800">
          <div className="flex items-center gap-3">
            <FaShip className="text-blue-500 text-xl" />
            <div>
              <h2 className="text-xl font-bold text-white">Edit Shipment</h2>
              <p className="text-gray-400 text-sm">
                {shipment?.tracking_number || 'Update shipment details'}
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
          >
            <FaTimes />
          </button>
        </div>

        {/* Tabs */}
        <div className="flex border-b border-gray-800">
          {tabs.map((tab) => {
            const IconComponent = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium transition-colors ${
                  activeTab === tab.id
                    ? 'text-blue-400 border-b-2 border-blue-400 bg-blue-900/20'
                    : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                }`}
              >
                <IconComponent className="text-sm" />
                {tab.label}
              </button>
            );
          })}
        </div>

        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-6">
            {/* Basic Info Tab */}
            {activeTab === 'basic' && (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Tracking Number *
                  </label>
                  <input
                    type="text"
                    name="tracking_number"
                    value={formData.tracking_number}
                    onChange={handleInputChange}
                    className={`w-full px-4 py-2 bg-gray-800 border rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 ${
                      errors.tracking_number ? 'border-red-500' : 'border-gray-700'
                    }`}
                    placeholder="Enter tracking number"
                  />
                  {errors.tracking_number && (
                    <p className="text-red-400 text-sm mt-1">{errors.tracking_number}</p>
                  )}
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Departure Port
                    </label>
                    <input
                      type="text"
                      name="departure_port"
                      value={formData.departure_port}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Enter departure port"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Arrival Port
                    </label>
                    <input
                      type="text"
                      name="arrival_port"
                      value={formData.arrival_port}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Enter arrival port"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Current Location
                  </label>
                  <input
                    type="text"
                    name="current_location"
                    value={formData.current_location}
                    onChange={handleInputChange}
                    className={`w-full px-4 py-2 bg-gray-800 border rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500 ${
                      errors.current_location ? 'border-red-500' : 'border-gray-700'
                    }`}
                    placeholder="Enter current location"
                  />
                  {errors.current_location && (
                    <p className="text-red-400 text-sm mt-1">{errors.current_location}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Route Description
                  </label>
                  <textarea
                    name="route_description"
                    value={formData.route_description}
                    onChange={handleInputChange}
                    rows={3}
                    className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                    placeholder="Enter route description"
                  />
                </div>
              </div>
            )}

            {/* Carrier & Route Tab */}
            {activeTab === 'carrier' && (
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Carrier Name
                  </label>
                  <input
                    type="text"
                    name="carrier_name"
                    value={formData.carrier_name}
                    onChange={handleInputChange}
                    className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                    placeholder="Enter carrier name"
                  />
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Vessel Name
                    </label>
                    <input
                      type="text"
                      name="vessel_name"
                      value={formData.vessel_name}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Enter vessel name"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Container Number
                    </label>
                    <input
                      type="text"
                      name="container_number"
                      value={formData.container_number}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Enter container number"
                    />
                  </div>
                </div>
              </div>
            )}

            {/* Status & Timeline Tab */}
            {activeTab === 'status' && (
              <div className="space-y-4">
                {getStatusTransitionMessage()}
                
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Status *
                  </label>
                  <select
                    name="status"
                    value={formData.status}
                    onChange={handleInputChange}
                    className={`w-full px-4 py-2 bg-gray-800 border rounded-lg text-white focus:outline-none focus:border-blue-500 ${
                      errors.status ? 'border-red-500' : 'border-gray-700'
                    }`}
                  >
                    <option value="">Select status</option>
                    {statusOptions.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                  {errors.status && (
                    <p className="text-red-400 text-sm mt-1">{errors.status}</p>
                  )}
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Departure Date
                    </label>
                    <input
                      type="date"
                      name="departure_date"
                      value={formData.departure_date}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Estimated Arrival
                    </label>
                    <input
                      type="date"
                      name="estimated_arrival"
                      value={formData.estimated_arrival}
                      onChange={handleInputChange}
                      className={`w-full px-4 py-2 bg-gray-800 border rounded-lg text-white focus:outline-none focus:border-blue-500 ${
                        errors.estimated_arrival ? 'border-red-500' : 'border-gray-700'
                      }`}
                    />
                    {errors.estimated_arrival && (
                      <p className="text-red-400 text-sm mt-1">{errors.estimated_arrival}</p>
                    )}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Notes
                  </label>
                  <textarea
                    name="notes"
                    value={formData.notes}
                    onChange={handleInputChange}
                    rows={4}
                    className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                    placeholder="Enter any additional notes or comments"
                  />
                </div>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="flex justify-end gap-4 p-6 border-t border-gray-800">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {loading ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                  Saving...
                </>
              ) : (
                <>
                  <FaSave />
                  Save Changes
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ShipmentEditModal;