import React, { useState, useEffect } from 'react';
import { 
  FaTimes, 
  FaUser, 
  FaEnvelope,
  FaPhone,
  FaMapMarkerAlt,
  FaCalendarAlt,
  FaUserCheck,
  FaUserTimes,
  FaInfoCircle
} from 'react-icons/fa';
import { getCustomer } from '../../services/adminService';

const CustomerViewModal = ({ customer, onClose }) => {
  const [detailedCustomer, setDetailedCustomer] = useState(customer);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (customer?.id) {
      fetchDetailedCustomer();
    }
  }, [customer?.id]);

  const fetchDetailedCustomer = async () => {
    try {
      setLoading(true);
      const response = await getCustomer(customer.id);
      setDetailedCustomer(response.data || response);
    } catch (error) {
      console.error('Failed to fetch detailed customer:', error);
      // Use the basic customer data if detailed fetch fails
      setDetailedCustomer(customer);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const config = {
      active: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Active', icon: FaUserCheck },
      inactive: { bg: 'bg-gray-900/30', text: 'text-gray-400', label: 'Inactive', icon: FaUser },
      suspended: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Suspended', icon: FaUserTimes },
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Pending', icon: FaInfoCircle }
    };
    const c = config[status] || config.active;
    const IconComponent = c.icon;
    return (
      <span className={`${c.bg} ${c.text} px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-2`}>
        <IconComponent className="text-sm" />
        {c.label}
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-800">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
              <span className="text-white font-bold text-lg">
                {(detailedCustomer.first_name || detailedCustomer.name || 'C')[0].toUpperCase()}
              </span>
            </div>
            <div>
              <h2 className="text-2xl font-bold text-white">
                {detailedCustomer.first_name && detailedCustomer.last_name 
                  ? `${detailedCustomer.first_name} ${detailedCustomer.last_name}`
                  : detailedCustomer.name || 'Unknown Customer'}
              </h2>
              <p className="text-gray-400">Customer Details</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
          >
            <FaTimes className="text-xl" />
          </button>
        </div>

        {loading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
            <p className="text-gray-400 mt-4">Loading customer details...</p>
          </div>
        ) : (
          <div className="p-6 space-y-6">
            {/* Status and Basic Info */}
            <div className="grid md:grid-cols-2 gap-6">
              <div className="bg-gray-800/30 rounded-xl p-6">
                <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                  <FaInfoCircle className="text-blue-500" />
                  Account Status
                </h3>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Status:</span>
                    {getStatusBadge(detailedCustomer.status || 'active')}
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Email Verified:</span>
                    <span className={`px-3 py-1 rounded-full text-xs font-semibold ${
                      detailedCustomer.email_verified_at 
                        ? 'bg-green-900/30 text-green-400' 
                        : 'bg-yellow-900/30 text-yellow-400'
                    }`}>
                      {detailedCustomer.email_verified_at ? 'Verified' : 'Unverified'}
                    </span>
                  </div>
                  <div>
                    <span className="text-gray-400">Customer ID:</span>
                    <p className="text-white font-medium">{detailedCustomer.id}</p>
                  </div>
                </div>
              </div>

              <div className="bg-gray-800/30 rounded-xl p-6">
                <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                  <FaEnvelope className="text-purple-500" />
                  Contact Information
                </h3>
                <div className="space-y-3">
                  <div>
                    <span className="text-gray-400">Email:</span>
                    <p className="text-white font-medium">{detailedCustomer.email}</p>
                  </div>
                  <div>
                    <span className="text-gray-400">Phone:</span>
                    <p className="text-white font-medium">{detailedCustomer.phone || 'N/A'}</p>
                  </div>
                  <div>
                    <span className="text-gray-400">Address:</span>
                    <p className="text-white font-medium">
                      {detailedCustomer.address || 'N/A'}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Account Timeline */}
            <div className="bg-gray-800/30 rounded-xl p-6">
              <h3 className="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <FaCalendarAlt className="text-orange-500" />
                Account Timeline
              </h3>
              <div className="grid md:grid-cols-3 gap-4">
                <div>
                  <span className="text-gray-400">Joined:</span>
                  <p className="text-white font-medium">{formatDate(detailedCustomer.created_at)}</p>
                </div>
                <div>
                  <span className="text-gray-400">Last Login:</span>
                  <p className="text-white font-medium">{formatDate(detailedCustomer.last_login_at)}</p>
                </div>
                <div>
                  <span className="text-gray-400">Email Verified:</span>
                  <p className="text-white font-medium">{formatDate(detailedCustomer.email_verified_at)}</p>
                </div>
              </div>
            </div>

            {/* Additional Information */}
            {(detailedCustomer.notes || detailedCustomer.preferences) && (
              <div className="bg-gray-800/30 rounded-xl p-6">
                <h3 className="text-lg font-semibold text-white mb-4">Additional Information</h3>
                <div className="space-y-3">
                  {detailedCustomer.notes && (
                    <div>
                      <span className="text-gray-400">Notes:</span>
                      <p className="text-white">{detailedCustomer.notes}</p>
                    </div>
                  )}
                  {detailedCustomer.preferences && (
                    <div>
                      <span className="text-gray-400">Preferences:</span>
                      <p className="text-white">{detailedCustomer.preferences}</p>
                    </div>
                  )}
                </div>
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

export default CustomerViewModal;