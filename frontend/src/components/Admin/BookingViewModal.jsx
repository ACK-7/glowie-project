import React from 'react';
import { FaTimes, FaCar, FaUser, FaRoute, FaDollarSign, FaCalendarAlt, FaMapMarkerAlt, FaPhone, FaEnvelope } from 'react-icons/fa';

const BookingViewModal = ({ booking, isOpen, onClose }) => {
  if (!isOpen || !booking) return null;

  const getStatusConfig = (status) => {
    const configs = {
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', border: 'border-yellow-700/50', label: 'Pending' },
      confirmed: { bg: 'bg-blue-900/30', text: 'text-blue-400', border: 'border-blue-700/50', label: 'Confirmed' },
      processing: { bg: 'bg-purple-900/30', text: 'text-purple-400', border: 'border-purple-700/50', label: 'Processing' },
      in_transit: { bg: 'bg-indigo-900/30', text: 'text-indigo-400', border: 'border-indigo-700/50', label: 'In Transit' },
      delivered: { bg: 'bg-green-900/30', text: 'text-green-400', border: 'border-green-700/50', label: 'Delivered' },
      cancelled: { bg: 'bg-red-900/30', text: 'text-red-400', border: 'border-red-700/50', label: 'Cancelled' },
      completed: { bg: 'bg-emerald-900/30', text: 'text-emerald-400', border: 'border-emerald-700/50', label: 'Completed' }
    };
    return configs[status] || configs.pending;
  };

  const statusConfig = getStatusConfig(booking.status);

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" onClick={onClose}></div>

        <div className="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-[#1a1f28] border border-gray-700 shadow-xl rounded-lg">
          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <div>
              <h3 className="text-2xl font-bold text-white">Booking Details</h3>
              <p className="text-gray-400">Reference: {booking.booking_reference}</p>
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-white transition-colors"
            >
              <FaTimes className="w-6 h-6" />
            </button>
          </div>

          {/* Status Badge */}
          <div className="mb-6">
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${statusConfig.bg} ${statusConfig.text} ${statusConfig.border}`}>
              {statusConfig.label}
            </span>
          </div>

          {/* Content Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Customer Information */}
            <div className="bg-gray-800/30 rounded-lg p-4">
              <h4 className="text-lg font-semibold text-white mb-4 flex items-center">
                <FaUser className="mr-2 text-blue-400" />
                Customer Information
              </h4>
              <div className="space-y-3">
                <div>
                  <label className="text-sm text-gray-400">Name</label>
                  <p className="text-white">
                    {booking.customer 
                      ? (booking.customer.first_name && booking.customer.last_name
                          ? `${booking.customer.first_name} ${booking.customer.last_name}`
                          : booking.customer.name || 'Unknown')
                      : booking.recipient_name || 'Unknown'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Email</label>
                  <p className="text-white flex items-center">
                    <FaEnvelope className="mr-2 text-gray-400" />
                    {booking.customer?.email || booking.recipient_email || 'N/A'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Phone</label>
                  <p className="text-white flex items-center">
                    <FaPhone className="mr-2 text-gray-400" />
                    {booking.customer?.phone || booking.recipient_phone || 'N/A'}
                  </p>
                </div>
              </div>
            </div>

            {/* Vehicle Information */}
            <div className="bg-gray-800/30 rounded-lg p-4">
              <h4 className="text-lg font-semibold text-white mb-4 flex items-center">
                <FaCar className="mr-2 text-green-400" />
                Vehicle Information
              </h4>
              <div className="space-y-3">
                <div>
                  <label className="text-sm text-gray-400">Make & Model</label>
                  <p className="text-white">
                    {booking.vehicle ? `${booking.vehicle.make} ${booking.vehicle.model}` : 'N/A'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Year</label>
                  <p className="text-white">{booking.vehicle?.year || 'N/A'}</p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">VIN</label>
                  <p className="text-white">{booking.vehicle?.vin || 'N/A'}</p>
                </div>
              </div>
            </div>

            {/* Route Information */}
            <div className="bg-gray-800/30 rounded-lg p-4">
              <h4 className="text-lg font-semibold text-white mb-4 flex items-center">
                <FaRoute className="mr-2 text-purple-400" />
                Route Information
              </h4>
              <div className="space-y-3">
                <div>
                  <label className="text-sm text-gray-400">Origin</label>
                  <p className="text-white flex items-center">
                    <FaMapMarkerAlt className="mr-2 text-gray-400" />
                    {booking.route?.origin_country || 'N/A'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Destination</label>
                  <p className="text-white flex items-center">
                    <FaMapMarkerAlt className="mr-2 text-gray-400" />
                    {booking.route?.destination_country || 'N/A'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Service Type</label>
                  <p className="text-white">{booking.service_type || 'Standard'}</p>
                </div>
              </div>
            </div>

            {/* Financial Information */}
            <div className="bg-gray-800/30 rounded-lg p-4">
              <h4 className="text-lg font-semibold text-white mb-4 flex items-center">
                <FaDollarSign className="mr-2 text-yellow-400" />
                Financial Information
              </h4>
              <div className="space-y-3">
                <div>
                  <label className="text-sm text-gray-400">Total Amount</label>
                  <p className="text-white text-xl font-semibold">
                    ${booking.total_amount ? Number(booking.total_amount).toLocaleString() : '0'} {booking.currency || 'USD'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Paid Amount</label>
                  <p className="text-white">
                    ${booking.paid_amount ? Number(booking.paid_amount).toLocaleString() : '0'} {booking.currency || 'USD'}
                  </p>
                </div>
                <div>
                  <label className="text-sm text-gray-400">Payment Status</label>
                  <p className="text-white">
                    {booking.paid_amount >= booking.total_amount ? 'Fully Paid' : 
                     booking.paid_amount > 0 ? 'Partially Paid' : 'Unpaid'}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Dates */}
          <div className="mt-6 bg-gray-800/30 rounded-lg p-4">
            <h4 className="text-lg font-semibold text-white mb-4 flex items-center">
              <FaCalendarAlt className="mr-2 text-indigo-400" />
              Important Dates
            </h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="text-sm text-gray-400">Created</label>
                <p className="text-white">
                  {booking.created_at ? new Date(booking.created_at).toLocaleDateString() : 'N/A'}
                </p>
              </div>
              <div>
                <label className="text-sm text-gray-400">Pickup Date</label>
                <p className="text-white">
                  {booking.pickup_date ? new Date(booking.pickup_date).toLocaleDateString() : 'N/A'}
                </p>
              </div>
              <div>
                <label className="text-sm text-gray-400">Delivery Date</label>
                <p className="text-white">
                  {booking.delivery_date ? new Date(booking.delivery_date).toLocaleDateString() : 'N/A'}
                </p>
              </div>
            </div>
          </div>

          {/* Notes */}
          {booking.notes && (
            <div className="mt-6 bg-gray-800/30 rounded-lg p-4">
              <h4 className="text-lg font-semibold text-white mb-2">Notes</h4>
              <p className="text-gray-300">{booking.notes}</p>
            </div>
          )}

          {/* Actions */}
          <div className="flex justify-end mt-6 pt-4 border-t border-gray-700">
            <button
              onClick={onClose}
              className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BookingViewModal;