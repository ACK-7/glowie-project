import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  FaArrowLeft,
  FaSpinner,
  FaCheck,
  FaTimes,
  FaClock,
  FaCheckCircle,
  FaExclamationCircle,
  FaUser,
  FaCar,
  FaRoute,
  FaDollarSign,
  FaCalendar,
  FaFileAlt,
  FaPaperPlane,
  FaEdit,
  FaHistory
} from 'react-icons/fa';
import { getQuote, approveQuote, rejectQuote, convertQuoteToBooking } from '../../services/adminService';
import { showAlert, showConfirm } from '../../utils/sweetAlert';

const QuoteDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [quote, setQuote] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchQuoteDetails();
  }, [id]);

  const fetchQuoteDetails = async () => {
    try {
      setLoading(true);
      const response = await getQuote(id);
      
      let quoteData = null;
      if (response?.data) {
        if (response.success) {
          quoteData = response.data;
        } else if (response.data.data) {
          quoteData = response.data.data;
        } else {
          quoteData = response.data;
        }
      }
      
      if (!quoteData) {
        throw new Error('Quote not found');
      }
      
      setQuote(quoteData);
      setError(null);
    } catch (err) {
      console.error('Failed to fetch quote details:', err);
      setError('Failed to load quote details. Please try again.');
      await showAlert('Error', 'Failed to load quote details', 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async () => {
    const confirmed = await showConfirm(
      'Approve Quote',
      'Are you sure you want to approve this quote? The customer will be notified with login credentials.',
      'question'
    );

    if (confirmed) {
      try {
        setActionLoading(true);
        await approveQuote(quote.id, 'Quote approved by admin');
        await showAlert('Success', 'Quote approved successfully. Customer has been notified.', 'success');
        fetchQuoteDetails();
      } catch (error) {
        console.error('Approve failed:', error);
        await showAlert('Error', 'Failed to approve quote', 'error');
      } finally {
        setActionLoading(false);
      }
    }
  };

  const handleReject = async () => {
    const result = await showAlert.input(
      'Reject Quote',
      'Please provide a reason for rejecting this quote:',
      'textarea',
      'Enter rejection reason...'
    );

    if (result.isConfirmed && result.value) {
      try {
        setActionLoading(true);
        await rejectQuote(quote.id, result.value);
        await showAlert.success('Success', 'Quote rejected successfully');
        fetchQuoteDetails();
      } catch (error) {
        console.error('Reject failed:', error);
        await showAlert.error('Error', 'Failed to reject quote');
      } finally {
        setActionLoading(false);
      }
    }
  };

  const handleConvertToBooking = async () => {
    const confirmed = await showConfirm(
      'Convert to Booking',
      'Convert this quote to a booking? This action cannot be undone.',
      'question'
    );

    if (confirmed) {
      try {
        setActionLoading(true);
        await convertQuoteToBooking(quote.id);
        await showAlert('Success', 'Quote converted to booking successfully', 'success');
        navigate('/admin/bookings');
      } catch (error) {
        console.error('Convert failed:', error);
        await showAlert('Error', 'Failed to convert quote to booking', 'error');
      } finally {
        setActionLoading(false);
      }
    }
  };

  const getStatusBadge = (status) => {
    const config = {
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Pending', icon: FaClock },
      approved: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Approved', icon: FaCheckCircle },
      rejected: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Rejected', icon: FaTimes },
      expired: { bg: 'bg-gray-900/30', text: 'text-gray-400', label: 'Expired', icon: FaExclamationCircle },
      converted: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Converted', icon: FaCheck }
    };
    const c = config[status] || config.pending;
    const IconComponent = c.icon;
    return (
      <span className={`${c.bg} ${c.text} px-4 py-2 rounded-full text-sm font-semibold flex items-center gap-2 w-fit`}>
        <IconComponent />
        {c.label}
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatCurrency = (amount) => {
    if (!amount || amount === 0) return '0.00';
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <FaSpinner className="animate-spin text-blue-500 text-4xl" />
      </div>
    );
  }

  if (error || !quote) {
    return (
      <div className="max-w-4xl mx-auto">
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <p className="text-red-400">{error || 'Quote not found'}</p>
          <Link
            to="/admin/quotes"
            className="mt-4 inline-flex items-center text-blue-400 hover:text-blue-300"
          >
            <FaArrowLeft className="mr-2" />
            Back to Quotes
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link
            to="/admin/quotes"
            className="text-gray-400 hover:text-white transition-colors"
          >
            <FaArrowLeft className="text-xl" />
          </Link>
          <div>
            <h1 className="text-3xl font-bold text-white">Quote Details</h1>
            <p className="text-gray-400 mt-1">
              {quote.quote_reference || `Q-${quote.id}`}
            </p>
          </div>
        </div>
        {getStatusBadge(quote.status)}
      </div>

      {/* Action Buttons */}
      {quote.status === 'pending' && (
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
          <div className="flex flex-wrap gap-4">
            <button
              onClick={handleApprove}
              disabled={actionLoading}
              className="flex-1 min-w-[200px] px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <FaCheck />
              {actionLoading ? 'Processing...' : 'Approve Quote'}
            </button>
            <button
              onClick={handleReject}
              disabled={actionLoading}
              className="flex-1 min-w-[200px] px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <FaTimes />
              Reject Quote
            </button>
          </div>
        </div>
      )}

      {quote.status === 'approved' && (
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
          <button
            onClick={handleConvertToBooking}
            disabled={actionLoading}
            className="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <FaPaperPlane />
            {actionLoading ? 'Converting...' : 'Convert to Booking'}
          </button>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Customer Information */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <FaUser className="text-blue-500" />
              Customer Information
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-gray-400 text-sm mb-1">Name</p>
                <p className="text-white font-medium">
                  {quote.customer?.first_name && quote.customer?.last_name
                    ? `${quote.customer.first_name} ${quote.customer.last_name}`
                    : quote.customer?.name || 'N/A'}
                </p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Email</p>
                <p className="text-white font-medium">{quote.customer?.email || 'N/A'}</p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Phone</p>
                <p className="text-white font-medium">{quote.customer?.phone || 'N/A'}</p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Customer ID</p>
                <p className="text-white font-medium">#{quote.customer_id}</p>
              </div>
            </div>
          </div>

          {/* Vehicle Information */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <FaCar className="text-blue-500" />
              Vehicle Information
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-gray-400 text-sm mb-1">Make</p>
                <p className="text-white font-medium">
                  {quote.vehicle_details?.make || 'N/A'}
                </p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Model</p>
                <p className="text-white font-medium">
                  {quote.vehicle_details?.model || 'N/A'}
                </p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Year</p>
                <p className="text-white font-medium">
                  {quote.vehicle_details?.year || 'N/A'}
                </p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Vehicle Type</p>
                <p className="text-white font-medium">
                  {quote.vehicle_details?.vehicle_type || 'N/A'}
                </p>
              </div>
              {quote.vehicle_details?.engine_size && (
                <div>
                  <p className="text-gray-400 text-sm mb-1">Engine Size</p>
                  <p className="text-white font-medium">
                    {quote.vehicle_details.engine_size}
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Route Information */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <FaRoute className="text-blue-500" />
              Shipping Route
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-gray-400 text-sm mb-1">Origin</p>
                <p className="text-white font-medium">
                  {quote.route?.origin_city && quote.route?.origin_country
                    ? `${quote.route.origin_city}, ${quote.route.origin_country}`
                    : quote.route?.origin_country || 'N/A'}
                </p>
              </div>
              <div>
                <p className="text-gray-400 text-sm mb-1">Destination</p>
                <p className="text-white font-medium">
                  {quote.route?.destination_city && quote.route?.destination_country
                    ? `${quote.route.destination_city}, ${quote.route.destination_country}`
                    : quote.route?.destination_country || 'N/A'}
                </p>
              </div>
              {quote.route?.origin_port && (
                <div>
                  <p className="text-gray-400 text-sm mb-1">Origin Port</p>
                  <p className="text-white font-medium">{quote.route.origin_port}</p>
                </div>
              )}
              {quote.route?.destination_port && (
                <div>
                  <p className="text-gray-400 text-sm mb-1">Destination Port</p>
                  <p className="text-white font-medium">{quote.route.destination_port}</p>
                </div>
              )}
            </div>
          </div>

          {/* Additional Fees Breakdown */}
          {quote.additional_fees && Array.isArray(quote.additional_fees) && quote.additional_fees.length > 0 && (
            <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
              <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <FaFileAlt className="text-blue-500" />
                Fee Breakdown
              </h2>
              <div className="space-y-3">
                <div className="flex justify-between items-center pb-3 border-b border-gray-700">
                  <span className="text-gray-400">Base Price</span>
                  <span className="text-white font-semibold">
                    ${formatCurrency(quote.base_price || 0)}
                  </span>
                </div>
                {quote.additional_fees.map((fee, index) => (
                  <div key={index} className="flex justify-between items-center">
                    <span className="text-gray-400">{fee.name || `Fee ${index + 1}`}</span>
                    <span className="text-white font-semibold">
                      ${formatCurrency(fee.amount || 0)}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Pricing Summary */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <FaDollarSign className="text-green-500" />
              Pricing
            </h2>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-gray-400">Base Price</span>
                <span className="text-white font-semibold">
                  ${formatCurrency(quote.base_price || 0)}
                </span>
              </div>
              {quote.total_fees > 0 && (
                <div className="flex justify-between items-center">
                  <span className="text-gray-400">Additional Fees</span>
                  <span className="text-white font-semibold">
                    ${formatCurrency(quote.total_fees || 0)}
                  </span>
                </div>
              )}
              <div className="pt-3 border-t border-gray-700">
                <div className="flex justify-between items-center">
                  <span className="text-white font-bold">Total Amount</span>
                  <span className="text-green-400 text-2xl font-bold">
                    ${formatCurrency(quote.total_amount || 0)}
                  </span>
                </div>
                {quote.currency && quote.currency !== 'USD' && (
                  <p className="text-gray-400 text-xs mt-1 text-right">
                    Currency: {quote.currency}
                  </p>
                )}
              </div>
            </div>
          </div>

          {/* Timeline */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <FaCalendar className="text-purple-500" />
              Timeline
            </h2>
            <div className="space-y-4">
              <div>
                <p className="text-gray-400 text-sm mb-1">Created</p>
                <p className="text-white font-medium">{formatDate(quote.created_at)}</p>
              </div>
              {quote.valid_until && (
                <div>
                  <p className="text-gray-400 text-sm mb-1">Valid Until</p>
                  <p className={`font-medium ${quote.is_expired ? 'text-red-400' : 'text-white'}`}>
                    {formatDate(quote.valid_until)}
                  </p>
                  {quote.days_until_expiry !== undefined && quote.days_until_expiry >= 0 && (
                    <p className="text-gray-400 text-xs mt-1">
                      {quote.days_until_expiry} days remaining
                    </p>
                  )}
                </div>
              )}
              {quote.updated_at && quote.updated_at !== quote.created_at && (
                <div>
                  <p className="text-gray-400 text-sm mb-1">Last Updated</p>
                  <p className="text-white font-medium">{formatDate(quote.updated_at)}</p>
                </div>
              )}
            </div>
          </div>

          {/* Notes */}
          {quote.notes && (
            <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
              <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <FaFileAlt className="text-orange-500" />
                Notes
              </h2>
              <p className="text-gray-300 whitespace-pre-wrap">{quote.notes}</p>
            </div>
          )}

          {/* System Information */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
              <FaHistory className="text-gray-500" />
              System Info
            </h2>
            <div className="space-y-3 text-sm">
              <div>
                <p className="text-gray-400 mb-1">Quote ID</p>
                <p className="text-white font-mono">#{quote.id}</p>
              </div>
              {quote.created_by && (
                <div>
                  <p className="text-gray-400 mb-1">Created By</p>
                  <p className="text-white">
                    {typeof quote.created_by === 'object' 
                      ? (quote.created_by.name || quote.created_by.full_name || `User #${quote.created_by.id}`)
                      : `User #${quote.created_by}`}
                  </p>
                </div>
              )}
              {quote.approved_by && (
                <div>
                  <p className="text-gray-400 mb-1">Approved By</p>
                  <p className="text-white">
                    {typeof quote.approved_by === 'object'
                      ? (quote.approved_by.name || quote.approved_by.full_name || `User #${quote.approved_by.id}`)
                      : `User #${quote.approved_by}`}
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default QuoteDetails;
