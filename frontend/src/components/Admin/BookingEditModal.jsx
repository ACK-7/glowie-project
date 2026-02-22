import React, { useState, useEffect } from 'react';
import { FaTimes, FaSave } from 'react-icons/fa';

const BookingEditModal = ({ booking, isOpen, onClose, onSave }) => {
  const [formData, setFormData] = useState({
    status: '',
    pickup_date: '',
    delivery_date: '',
    total_amount: '',
    notes: '',
    recipient_name: '',
    recipient_email: '',
    recipient_phone: '',
    recipient_country: '',
    recipient_city: '',
    recipient_address: ''
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  useEffect(() => {
    if (booking && isOpen) {
      setFormData({
        status: booking.status || '',
        pickup_date: booking.pickup_date ? booking.pickup_date.split('T')[0] : '',
        delivery_date: booking.delivery_date ? booking.delivery_date.split('T')[0] : '',
        total_amount: booking.total_amount || '',
        notes: booking.notes || '',
        recipient_name: booking.recipient_name || '',
        recipient_email: booking.recipient_email || '',
        recipient_phone: booking.recipient_phone || '',
        recipient_country: booking.recipient_country || '',
        recipient_city: booking.recipient_city || '',
        recipient_address: booking.recipient_address || ''
      });
      setErrors({}); // Clear errors when opening modal
    }
  }, [booking, isOpen]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error for this field when user starts typing
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({}); // Clear previous errors
    
    try {
      // Build update data - only include fields that have values or have changed
      const updateData = {};
      
      // Status is required
      if (!formData.status) {
        setErrors({ status: 'Status is required' });
        throw new Error('Status is required');
      }
      updateData.status = formData.status;
      
      // Include other fields only if they have values
      if (formData.total_amount && formData.total_amount !== '') {
        updateData.total_amount = parseFloat(formData.total_amount);
      }
      
      if (formData.notes && formData.notes.trim() !== '') {
        updateData.notes = formData.notes.trim();
      }
      
      // Recipient information
      if (formData.recipient_name && formData.recipient_name.trim() !== '') {
        updateData.recipient_name = formData.recipient_name.trim();
      }
      
      if (formData.recipient_email && formData.recipient_email.trim() !== '') {
        updateData.recipient_email = formData.recipient_email.trim();
      }
      
      if (formData.recipient_phone && formData.recipient_phone.trim() !== '') {
        updateData.recipient_phone = formData.recipient_phone.trim();
      }
      
      if (formData.recipient_country && formData.recipient_country.trim() !== '') {
        updateData.recipient_country = formData.recipient_country.trim();
      }
      
      if (formData.recipient_city && formData.recipient_city.trim() !== '') {
        updateData.recipient_city = formData.recipient_city.trim();
      }
      
      if (formData.recipient_address && formData.recipient_address.trim() !== '') {
        updateData.recipient_address = formData.recipient_address.trim();
      }
      
      // Handle dates with proper validation
      const pickupDate = formData.pickup_date || null;
      const deliveryDate = formData.delivery_date || null;
      
      // Client-side validation: delivery date must be after pickup date
      if (pickupDate && deliveryDate) {
        const pickup = new Date(pickupDate);
        const delivery = new Date(deliveryDate);
        
        if (delivery <= pickup) {
          setErrors({ delivery_date: 'Delivery date must be after pickup date' });
          throw new Error('Delivery date must be after pickup date');
        }
      }
      
      // Include dates in update if they have values
      if (pickupDate) {
        updateData.pickup_date = pickupDate;
      }
      
      if (deliveryDate) {
        updateData.delivery_date = deliveryDate;
      }
      
      console.log('Submitting booking update:', { id: booking.id, updateData });
      
      await onSave(booking.id, updateData);
      // Don't close here - let the parent handle it after success
    } catch (error) {
      console.error('Error in handleSubmit:', error);
      
      // Extract validation errors from backend response if available
      if (error.validationErrors) {
        setErrors(error.validationErrors);
      }
      
      // Don't show alert if we already set field-specific errors
      if (!error.validationErrors && Object.keys(errors).length === 0) {
        alert(error.message || 'Failed to update booking. Please check your input and try again.');
      }
      // Keep modal open on error so user can retry
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen || !booking) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" onClick={onClose}></div>

        <div className="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-[#1a1f28] border border-gray-700 shadow-xl rounded-lg">
          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <div>
              <h3 className="text-2xl font-bold text-white">Edit Booking</h3>
              <p className="text-gray-400">Reference: {booking.booking_reference}</p>
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-white transition-colors"
            >
              <FaTimes className="w-6 h-6" />
            </button>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Status */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Status
              </label>
              <select
                name="status"
                value={formData.status}
                onChange={handleInputChange}
                className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              >
                <option value="">Select Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="processing">Processing</option>
                <option value="in_transit">In Transit</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
                <option value="completed">Completed</option>
              </select>
            </div>

            {/* Dates */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">
                  Pickup Date
                </label>
                <input
                  type="date"
                  name="pickup_date"
                  value={formData.pickup_date}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <p className="text-xs text-gray-400 mt-1">Required if delivery date is set</p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">
                  Delivery Date
                </label>
                <input
                  type="date"
                  name="delivery_date"
                  value={formData.delivery_date}
                  onChange={handleInputChange}
                  min={formData.pickup_date || undefined}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <p className="text-xs text-gray-400 mt-1">Must be after pickup date</p>
              </div>
            </div>

            {/* Amount */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Total Amount ($)
              </label>
              <input
                type="number"
                name="total_amount"
                value={formData.total_amount}
                onChange={handleInputChange}
                step="0.01"
                min="0"
                className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            {/* Recipient Information */}
            <div className="space-y-4">
              <h4 className="text-lg font-semibold text-white">Recipient Information</h4>
              
              <div>
                <label className="block text-sm font-medium text-gray-300 mb-2">
                  Recipient Name
                </label>
                <input
                  type="text"
                  name="recipient_name"
                  value={formData.recipient_name}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Recipient Email
                  </label>
                  <input
                    type="email"
                    name="recipient_email"
                    value={formData.recipient_email}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Recipient Phone
                  </label>
                  <input
                    type="tel"
                    name="recipient_phone"
                    value={formData.recipient_phone}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Recipient Country
                  </label>
                  <input
                    type="text"
                    name="recipient_country"
                    value={formData.recipient_country}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Recipient City
                  </label>
                  <input
                    type="text"
                    name="recipient_city"
                    value={formData.recipient_city}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Recipient Address
                  </label>
                  <input
                    type="text"
                    name="recipient_address"
                    value={formData.recipient_address}
                    onChange={handleInputChange}
                    className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>
            </div>

            {/* Notes */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Notes
              </label>
              <textarea
                name="notes"
                value={formData.notes}
                onChange={handleInputChange}
                rows={4}
                className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Add any additional notes..."
              />
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-3 pt-4 border-t border-gray-700">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                disabled={loading}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Saving...
                  </>
                ) : (
                  <>
                    <FaSave className="mr-2" />
                    Save Changes
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default BookingEditModal;