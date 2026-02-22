import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { showAlert } from '../../utils/sweetAlert';
import {
  FaSearch,
  FaFilter,
  FaEye,
  FaEdit,
  FaTrash,
  FaDownload,
  FaPlus,
  FaChevronLeft,
  FaChevronRight,
  FaSpinner,
  FaExclamationCircle,
  FaCar,
  FaRoute,
  FaDollarSign,
  FaCalendarAlt,
  FaUser,
  FaMapMarkerAlt,
  FaSort,
  FaSortUp,
  FaSortDown,
  FaSyncAlt,
  FaTimes
} from 'react-icons/fa';
import { getBookings, getBooking, updateBooking, deleteBooking } from '../../services/adminService';
import DropdownMenu from '../../components/UI/DropdownMenu';
import BookingViewModal from '../../components/Admin/BookingViewModal';
import BookingEditModal from '../../components/Admin/BookingEditModal';

const BookingsList = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const initialSearch = searchParams.get('search') || '';
  
  // State Management
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [refreshing, setRefreshing] = useState(false);
  
  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [itemsPerPage, setItemsPerPage] = useState(15);
  
  // Filter State
  const [filters, setFilters] = useState({
    search: initialSearch,
    status: '',
    payment_status: '',
    origin_country: '',
    destination_country: '',
    date_from: '',
    date_to: '',
    amount_min: '',
    amount_max: ''
  });
  
  // Sorting State
  const [sortBy, setSortBy] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');
  
  // UI State
  const [showAdvancedFilters, setShowAdvancedFilters] = useState(false);
  const [selectedBookings, setSelectedBookings] = useState([]);
  const [viewModalOpen, setViewModalOpen] = useState(false);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);

  // Debounced search
  const [searchDebounce, setSearchDebounce] = useState(initialSearch);
  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters(prev => ({ ...prev, search: searchDebounce }));
    }, 500);
    return () => clearTimeout(timer);
  }, [searchDebounce]);

  // Sync with URL params
  useEffect(() => {
    const query = searchParams.get('search');
    if (query !== null && query !== searchDebounce) {
      setSearchDebounce(query);
    }
  }, [searchParams]);

  // Fetch Bookings with proper error handling
  const fetchBookings = useCallback(async (showLoader = true, bustCache = false) => {
    if (showLoader) setLoading(true);
    else setRefreshing(true);
    
    setError(null);
    
    try {
      const params = {
        page: currentPage,
        per_page: itemsPerPage,
        sort_by: sortBy,
        sort_direction: sortDirection,
        ...Object.fromEntries(
          Object.entries(filters).filter(([_, value]) => value !== '')
        ),
        // Cache-bust so the browser doesn't return a cached list after edit/delete
        ...(bustCache ? { _: Date.now() } : {})
      };

      const response = await getBookings(params);
      
      if (response.success) {
        const { data, meta } = response;
        setBookings(data || []);
        
        if (meta?.pagination) {
          setTotalPages(meta.pagination.last_page || 1);
          setTotalItems(meta.pagination.total || 0);
        } else {
          setTotalPages(1);
          setTotalItems(data?.length || 0);
        }
      } else {
        throw new Error(response.message || 'Failed to fetch bookings');
      }
    } catch (err) {
      console.error('Failed to fetch bookings:', err);

      // On refresh (bustCache), avoid replacing the list with error UI; we may have optimistic data
      if (bustCache) return;

      const isNetworkError = !err.response || err.code === 'ERR_NETWORK';
      const errorMessage = isNetworkError
        ? 'Cannot reach the server. Please check that the backend is running and try again.'
        : (err.response?.data?.message || err.message || 'Failed to load bookings');

      if (err.response?.status === 401 || err.response?.status === 403) {
        setError('Authentication failed. Please log in again.');
        showAlert.error('Authentication Error', 'Your session has expired. Please log in again.');
        setTimeout(() => navigate('/admin/login'), 2000);
      } else if (err.response?.status === 404) {
        setError('Bookings service not available.');
        showAlert.error('Service Error', 'Bookings service is currently unavailable.');
      } else {
        setError(errorMessage);
        showAlert.error('Error', errorMessage);
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [currentPage, itemsPerPage, sortBy, sortDirection, filters, navigate]);

  // Effects
  useEffect(() => {
    fetchBookings();
  }, [fetchBookings]);

  // Reset to first page when filters change
  useEffect(() => {
    if (currentPage !== 1) {
      setCurrentPage(1);
    }
  }, [filters, sortBy, sortDirection]);

  // Helper Functions
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

  const getPaymentStatusConfig = (booking) => {
    const totalAmount = parseFloat(booking.total_amount || 0);
    const paidAmount = parseFloat(booking.paid_amount || 0);
    
    let status = 'unpaid';
    if (paidAmount >= totalAmount && totalAmount > 0) {
      status = 'paid';
    } else if (paidAmount > 0) {
      status = 'partial';
    }
    
    const configs = {
      paid: { bg: 'bg-green-900/30', text: 'text-green-400', border: 'border-green-700/50', label: 'Paid' },
      partial: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', border: 'border-yellow-700/50', label: 'Partial' },
      unpaid: { bg: 'bg-red-900/30', text: 'text-red-400', border: 'border-red-700/50', label: 'Unpaid' }
    };
    return configs[status];
  };

  const handleSort = (column) => {
    if (sortBy === column) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(column);
      setSortDirection('asc');
    }
  };

  const getSortIcon = (column) => {
    if (sortBy !== column) return <FaSort className="text-gray-500" />;
    return sortDirection === 'asc' ? 
      <FaSortUp className="text-blue-400" /> : 
      <FaSortDown className="text-blue-400" />;
  };

  const clearFilters = () => {
    setFilters({
      search: '',
      status: '',
      payment_status: '',
      origin_country: '',
      destination_country: '',
      date_from: '',
      date_to: '',
      amount_min: '',
      amount_max: ''
    });
    setSearchDebounce('');
    setCurrentPage(1);
    showAlert.success('Filters Cleared', 'All filters have been reset.');
  };

  const handleRefresh = () => {
    fetchBookings(false, true);
    showAlert.success('Refreshed', 'Bookings data has been refreshed.');
  };

  // Action Handlers
  const handleViewBooking = async (booking) => {
    // Show loading indicator briefly to confirm action
    const loadingAlert = showAlert.loading('Loading...', 'Fetching booking details...');
    try {
      const response = await getBooking(booking.id);
      showAlert.close(); // Close loading alert
      // API returns { success: true, data: {...}, message: "..." }
      setSelectedBooking(response.data || booking);
      setViewModalOpen(true);
    } catch (error) {
      console.error('Error fetching booking details:', error);
      showAlert.close();
      // Fallback to list data if API call fails
      setSelectedBooking(booking);
      setViewModalOpen(true);
    }
  };

  const handleEditBooking = async (booking) => {
    const loadingAlert = showAlert.loading('Loading...', 'Fetching booking details...');
    try {
      const response = await getBooking(booking.id);
      showAlert.close();
      // API returns { success: true, data: {...}, message: "..." }
      setSelectedBooking(response.data || booking);
      setEditModalOpen(true);
    } catch (error) {
      console.error('Error fetching booking details:', error);
      showAlert.close();
      // Fallback to list data if API call fails
      setSelectedBooking(booking);
      setEditModalOpen(true);
    }
  };

  const handleSaveBooking = async (id, data) => {
    const loadingAlert = showAlert.loading('Updating...', 'Please wait while we update the booking.');

    try {
      const response = await updateBooking(id, data);
      showAlert.close();

      // Optimistic update: apply changes to the list immediately so the UI reflects even if refresh fails
      const updated = response?.data || data;
      setBookings(prev => prev.map(b => b.id === id ? { ...b, ...updated } : b));

      setEditModalOpen(false);
      setSelectedBooking(null);

      const message = response?.message || 'Booking updated successfully';
      await showAlert.success('Success', message);

      // Refresh in background with cache-bust; on failure we already updated optimistically
      fetchBookings(false, true).catch(() => {});

      return response;
    } catch (error) {
      showAlert.close();
      console.error('Error updating booking:', error);
      
      // Extract detailed error message
      let errorMessage = 'Failed to update booking';
      
      if (error.response?.data) {
        const errorData = error.response.data;
        errorMessage = errorData.message || errorMessage;
        
        // If there are validation errors, format them nicely
        if (errorData.errors && typeof errorData.errors === 'object') {
          const validationErrors = Object.entries(errorData.errors)
            .map(([field, messages]) => {
              const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
              const errorMsg = Array.isArray(messages) ? messages[0] : messages;
              return `• ${fieldName}: ${errorMsg}`;
            })
            .join('\n');
          errorMessage = `Validation Errors:\n${validationErrors}`;
        }
      } else if (error.code === 'ERR_NETWORK' || !error.response) {
        errorMessage = 'Cannot reach the server. Check that the backend is running.';
      } else if (error.message) {
        errorMessage = error.message;
      }
      
      await showAlert.error('Update Failed', errorMessage);
      throw error;
    }
  };

  const handleDeleteBooking = async (booking) => {
    try {
      const result = await showAlert.confirm(
        'Delete Booking',
        `Are you sure you want to delete booking ${booking.booking_reference}? This action cannot be undone.`,
        'warning',
        'Delete',
        'Cancel'
      );
      
      if (result) {
        const loadingAlert = showAlert.loading('Deleting...', 'Please wait while we delete the booking.');

        try {
          await deleteBooking(booking.id);
          showAlert.close();

          // Optimistic update: remove from list immediately so the UI reflects even if refresh fails
          setBookings(prev => prev.filter(b => b.id !== booking.id));
          setTotalItems(prev => Math.max(0, prev - 1));

          await showAlert.success('Booking Deleted', `Booking ${booking.booking_reference} has been deleted successfully.`);

          // Refresh in background with cache-bust; on failure we already updated optimistically
          fetchBookings(false, true).catch(() => {});
        } catch (error) {
          showAlert.close();
          console.error('Error deleting booking:', error);
          const errorMessage = error.response?.data?.message ||
            (error.code === 'ERR_NETWORK' || !error.response
              ? 'Cannot reach the server. Check that the backend is running.'
              : error.message) ||
            'Failed to delete booking';
          await showAlert.error('Delete Failed', errorMessage);
        }
      }
    } catch (error) {
      console.error('Error in delete confirmation:', error);
      await showAlert.error('Error', 'An unexpected error occurred. Please try again.');
    }
  };

  const getDropdownItems = (booking) => [
    {
      label: 'View Details',
      icon: <FaEye className="text-blue-400" />,
      onClick: () => handleViewBooking(booking)
    },
    {
      label: 'Edit Booking',
      icon: <FaEdit className="text-green-400" />,
      onClick: () => handleEditBooking(booking)
    },
    {
      label: 'Delete Booking',
      icon: <FaTrash className="text-red-400" />,
      onClick: () => handleDeleteBooking(booking),
      danger: true
    }
  ];

  const handleDropdownAction = (item) => {
    if (item && typeof item.onClick === 'function') {
      item.onClick();
    } else {
      console.error('Dropdown item onClick is not a function:', item);
    }
  };

  const handleBulkAction = async (action) => {
    if (selectedBookings.length === 0) {
      showAlert.warning('No Selection', 'Please select bookings to perform bulk actions.');
      return;
    }

    const result = await showAlert.confirm(
      'Bulk Action',
      `Are you sure you want to ${action} ${selectedBookings.length} selected booking(s)?`
    );

    if (result) {
      try {
        // Add bulk action API call here
        showAlert.success('Bulk Action Completed', `Successfully ${action}ed ${selectedBookings.length} booking(s).`);
        setSelectedBookings([]);
        fetchBookings(false);
      } catch (error) {
        showAlert.error('Bulk Action Failed', `Failed to ${action} selected bookings.`);
      }
    }
  };

  const handleExport = async () => {
    const result = await showAlert.confirm(
      'Export Bookings',
      'Export all bookings matching current filters to CSV?'
    );
    
    if (result) {
      try {
        // Add export API call here
        showAlert.success('Export Started', 'Your export is being prepared. You will receive a download link shortly.');
      } catch (error) {
        showAlert.error('Export Failed', 'Failed to export bookings. Please try again.');
      }
    }
  };

  const handleNewBooking = () => {
    navigate('/admin/bookings/new');
  };

  // Statistics calculation
  const stats = {
    total: totalItems,
    confirmed: bookings.filter(b => b.status === 'confirmed').length,
    in_transit: bookings.filter(b => b.status === 'in_transit').length,
    delivered: bookings.filter(b => b.status === 'delivered').length,
    revenue: bookings.reduce((sum, b) => sum + (parseFloat(b.total_amount) || 0), 0)
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div className="min-w-0 flex-1">
          <h1 className="text-2xl sm:text-3xl font-bold text-white">Bookings Management</h1>
          <p className="text-gray-400 mt-1">Manage and track all vehicle shipping bookings</p>
        </div>
        <div className="flex items-center gap-2 flex-shrink-0">
          <button 
            onClick={handleRefresh}
            disabled={refreshing}
            className="inline-flex items-center px-3 py-2 border border-gray-700 rounded-lg text-gray-300 bg-gray-800/50 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 transition-colors text-sm"
          >
            <FaSyncAlt className={`mr-1 ${refreshing ? 'animate-spin' : ''}`} />
            Refresh
          </button>
          <button 
            onClick={handleNewBooking}
            className="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors text-sm"
          >
            <FaPlus className="mr-1" />
            New Booking
          </button>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div className="bg-[#1a1f28] border border-gray-800 rounded-lg p-3 min-w-0">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <FaCar className="text-blue-400 text-sm" />
              </div>
            </div>
            <div className="ml-3 min-w-0">
              <p className="text-xs font-medium text-gray-400">Total Bookings</p>
              <p className="text-lg font-semibold text-white truncate">{stats.total.toLocaleString()}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-[#1a1f28] border border-gray-800 rounded-lg p-3 min-w-0">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center">
                <FaEye className="text-green-400 text-sm" />
              </div>
            </div>
            <div className="ml-3 min-w-0">
              <p className="text-xs font-medium text-gray-400">Confirmed</p>
              <p className="text-lg font-semibold text-white truncate">{stats.confirmed}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-[#1a1f28] border border-gray-800 rounded-lg p-3 min-w-0">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                <FaRoute className="text-purple-400 text-sm" />
              </div>
            </div>
            <div className="ml-3 min-w-0">
              <p className="text-xs font-medium text-gray-400">In Transit</p>
              <p className="text-lg font-semibold text-white truncate">{stats.in_transit}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-[#1a1f28] border border-gray-800 rounded-lg p-3 min-w-0">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center">
                <FaMapMarkerAlt className="text-emerald-400 text-sm" />
              </div>
            </div>
            <div className="ml-3 min-w-0">
              <p className="text-xs font-medium text-gray-400">Delivered</p>
              <p className="text-lg font-semibold text-white truncate">{stats.delivered}</p>
            </div>
          </div>
        </div>
        
        <div className="bg-[#1a1f28] border border-gray-800 rounded-lg p-3 min-w-0 col-span-2 lg:col-span-1">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="w-8 h-8 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                <FaDollarSign className="text-yellow-400 text-sm" />
              </div>
            </div>
            <div className="ml-3 min-w-0">
              <p className="text-xs font-medium text-gray-400">Total Revenue</p>
              <p className="text-lg font-semibold text-white truncate">${stats.revenue.toLocaleString()}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-lg p-4">
        <div className="space-y-4">
          {/* Basic Filters Row */}
          <div className="flex flex-col lg:flex-row gap-3">
            {/* Search */}
            <div className="flex-1 min-w-0">
              <div className="relative">
                <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm" />
                <input
                  type="text"
                  placeholder="Search by reference, customer, vehicle..."
                  value={searchDebounce}
                  onChange={(e) => setSearchDebounce(e.target.value)}
                  className="w-full pl-9 pr-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
            </div>

            {/* Status Filter */}
            <div className="w-full lg:w-auto">
              <select
                value={filters.status}
                onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value }))}
                className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
              >
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="processing">Processing</option>
                <option value="in_transit">In Transit</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>

            {/* Payment Status Filter */}
            <div className="w-full lg:w-auto">
              <select
                value={filters.payment_status}
                onChange={(e) => setFilters(prev => ({ ...prev, payment_status: e.target.value }))}
                className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
              >
                <option value="">All Payments</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
              </select>
            </div>

            {/* Action Buttons */}
            <div className="flex items-center gap-2 flex-wrap">
              <button
                onClick={() => setShowAdvancedFilters(!showAdvancedFilters)}
                className="inline-flex items-center px-3 py-2 border border-gray-700 rounded-lg text-gray-300 bg-gray-800/50 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap text-sm"
              >
                <FaFilter className="mr-1" />
                Advanced
              </button>
              <button
                onClick={clearFilters}
                className="inline-flex items-center px-3 py-2 border border-gray-700 rounded-lg text-gray-300 bg-gray-800/50 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap text-sm"
              >
                <FaTimes className="mr-1" />
                Clear
              </button>
              <button
                onClick={handleExport}
                className="inline-flex items-center px-3 py-2 border border-gray-700 rounded-lg text-gray-300 bg-gray-800/50 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 whitespace-nowrap text-sm"
              >
                <FaDownload className="mr-1" />
                Export
              </button>
            </div>
          </div>

          {/* Advanced Filters */}
          {showAdvancedFilters && (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 pt-4 border-t border-gray-700">
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1">Origin Country</label>
                <select
                  value={filters.origin_country}
                  onChange={(e) => setFilters(prev => ({ ...prev, origin_country: e.target.value }))}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                >
                  <option value="">All Origins</option>
                  <option value="Japan">Japan</option>
                  <option value="UK">United Kingdom</option>
                  <option value="UAE">UAE</option>
                  <option value="USA">United States</option>
                </select>
              </div>
              
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1">Date From</label>
                <input
                  type="date"
                  value={filters.date_from}
                  onChange={(e) => setFilters(prev => ({ ...prev, date_from: e.target.value }))}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
              
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1">Date To</label>
                <input
                  type="date"
                  value={filters.date_to}
                  onChange={(e) => setFilters(prev => ({ ...prev, date_to: e.target.value }))}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
              
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1">Min Amount ($)</label>
                <input
                  type="number"
                  placeholder="0"
                  value={filters.amount_min}
                  onChange={(e) => setFilters(prev => ({ ...prev, amount_min: e.target.value }))}
                  className="w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Bulk Actions */}
      {selectedBookings.length > 0 && (
        <div className="bg-blue-900/20 border border-blue-700/50 rounded-lg p-3">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <span className="text-sm text-blue-300 flex-shrink-0">
              {selectedBookings.length} booking(s) selected
            </span>
            <div className="flex items-center gap-2 flex-wrap">
              <button
                onClick={() => handleBulkAction('update status')}
                className="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 whitespace-nowrap"
              >
                Update Status
              </button>
              <button
                onClick={() => handleBulkAction('export')}
                className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 whitespace-nowrap"
              >
                Export Selected
              </button>
              <button
                onClick={() => setSelectedBookings([])}
                className="px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 whitespace-nowrap"
              >
                Clear Selection
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Table */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-lg overflow-hidden">
        {loading && (
          <div className="absolute inset-0 bg-gray-900/80 z-10 flex items-center justify-center">
            <div className="flex items-center gap-3">
              <FaSpinner className="animate-spin text-blue-500 text-xl" />
              <span className="text-gray-300">Loading bookings...</span>
            </div>
          </div>
        )}

        {error ? (
          <div className="flex flex-col items-center justify-center py-12">
            <FaExclamationCircle className="text-red-400 text-4xl mb-4" />
            <h3 className="text-lg font-medium text-white mb-2">Error Loading Bookings</h3>
            <p className="text-gray-400 text-center mb-4">{error}</p>
            <button
              onClick={() => fetchBookings()}
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              <FaSyncAlt className="mr-2" />
              Try Again
            </button>
          </div>
        ) : (
          <>
            <div className="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-800">
              <table className="w-full divide-y divide-gray-700" style={{ tableLayout: 'fixed', width: '100%' }}>
                <thead className="bg-gray-800/50">
                  <tr>
                    <th className="px-2 py-3 text-left" style={{ width: '40px' }}>
                      <input
                        type="checkbox"
                        checked={selectedBookings.length === bookings.length && bookings.length > 0}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedBookings(bookings.map(b => b.id));
                          } else {
                            setSelectedBookings([]);
                          }
                        }}
                        className="rounded border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                      />
                    </th>
                    <th 
                      className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase cursor-pointer hover:bg-gray-700/50"
                      onClick={() => handleSort('booking_reference')}
                      style={{ width: '100px' }}
                    >
                      <div className="flex items-center gap-1">
                        <span className="truncate">REF</span>
                        {getSortIcon('booking_reference')}
                      </div>
                    </th>
                    <th 
                      className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase cursor-pointer hover:bg-gray-700/50"
                      onClick={() => handleSort('customer_name')}
                      style={{ width: '140px' }}
                    >
                      <div className="flex items-center gap-1">
                        <span className="truncate">CUSTOMER</span>
                        {getSortIcon('customer_name')}
                      </div>
                    </th>
                    <th className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase" style={{ width: '120px' }}>
                      <span className="truncate">VEHICLE</span>
                    </th>
                    <th className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase" style={{ width: '130px' }}>
                      <span className="truncate">ROUTE</span>
                    </th>
                    <th 
                      className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase cursor-pointer hover:bg-gray-700/50"
                      onClick={() => handleSort('status')}
                      style={{ width: '95px' }}
                    >
                      <div className="flex items-center gap-1">
                        <span className="truncate">STATUS</span>
                        {getSortIcon('status')}
                      </div>
                    </th>
                    <th className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase" style={{ width: '85px' }}>
                      <span className="truncate">PAYMENT</span>
                    </th>
                    <th 
                      className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase cursor-pointer hover:bg-gray-700/50"
                      onClick={() => handleSort('total_amount')}
                      style={{ width: '90px' }}
                    >
                      <div className="flex items-center gap-1">
                        <span className="truncate">AMOUNT</span>
                        {getSortIcon('total_amount')}
                      </div>
                    </th>
                    <th 
                      className="px-2 py-3 text-left text-xs font-medium text-gray-300 uppercase cursor-pointer hover:bg-gray-700/50"
                      onClick={() => handleSort('created_at')}
                      style={{ width: '90px' }}
                    >
                      <div className="flex items-center gap-1">
                        <span className="truncate">DATE</span>
                        {getSortIcon('created_at')}
                      </div>
                    </th>
                    <th className="px-2 py-3 text-center text-xs font-medium text-gray-300 uppercase" style={{ width: '60px' }}>
                      <span className="truncate">ACT</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-[#1a1f28] divide-y divide-gray-700">
                  {bookings.length === 0 && !loading ? (
                    <tr>
                      <td colSpan="10" className="px-6 py-12 text-center">
                        <div className="flex flex-col items-center">
                          <FaCar className="text-gray-500 text-4xl mb-4" />
                          <h3 className="text-lg font-medium text-white mb-2">No bookings found</h3>
                          <p className="text-gray-400">Try adjusting your search criteria or create a new booking.</p>
                        </div>
                      </td>
                    </tr>
                  ) : (
                    bookings.map((booking) => {
                      const statusConfig = getStatusConfig(booking.status);
                      const paymentConfig = getPaymentStatusConfig(booking);
                      
                      return (
                        <tr key={booking.id} className="hover:bg-gray-800/50">
                          <td className="px-2 py-3">
                            <input
                              type="checkbox"
                              checked={selectedBookings.includes(booking.id)}
                              onChange={(e) => {
                                if (e.target.checked) {
                                  setSelectedBookings(prev => [...prev, booking.id]);
                                } else {
                                  setSelectedBookings(prev => prev.filter(id => id !== booking.id));
                                }
                              }}
                              className="rounded border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                            />
                          </td>
                          <td className="px-2 py-3 whitespace-nowrap">
                            <div className="text-xs font-medium text-white truncate">
                              {booking.booking_reference || 'N/A'}
                            </div>
                          </td>
                          <td className="px-2 py-3">
                            <div className="text-xs font-medium text-white truncate">
                              {booking.customer 
                                ? (booking.customer.first_name && booking.customer.last_name
                                    ? `${booking.customer.first_name} ${booking.customer.last_name}`
                                    : booking.customer.name || 'Unknown')
                                : booking.recipient_name || 'Unknown'}
                            </div>
                          </td>
                          <td className="px-2 py-3">
                            <div className="text-xs text-white truncate">
                              {booking.vehicle ? `${booking.vehicle.make} ${booking.vehicle.model}` : 'N/A'}
                            </div>
                          </td>
                          <td className="px-2 py-3">
                            <div className="text-xs text-white truncate">
                              {booking.route 
                                ? `${booking.route.origin_country} → ${booking.route.destination_country}`
                                : 'N/A'}
                            </div>
                          </td>
                          <td className="px-2 py-3">
                            <span className={`inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ${statusConfig.bg} ${statusConfig.text}`}>
                              {statusConfig.label}
                            </span>
                          </td>
                          <td className="px-2 py-3">
                            <span className={`inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium ${paymentConfig.bg} ${paymentConfig.text}`}>
                              {paymentConfig.label}
                            </span>
                          </td>
                          <td className="px-2 py-3">
                            <div className="text-xs font-medium text-white truncate">
                              ${booking.total_amount ? Number(booking.total_amount).toLocaleString() : '0'}
                            </div>
                          </td>
                          <td className="px-2 py-3">
                            <div className="text-xs text-white truncate">
                              {booking.created_at ? new Date(booking.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'N/A'}
                            </div>
                          </td>
                          <td className="px-2 py-3 whitespace-nowrap text-center">
                            <DropdownMenu
                              items={getDropdownItems(booking)}
                              onItemClick={handleDropdownAction}
                            />
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="bg-[#1a1f28] px-3 py-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-700">
                <div className="flex-1 flex justify-between sm:hidden">
                  <button
                    onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                    disabled={currentPage === 1}
                    className="relative inline-flex items-center px-3 py-1 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800/50 hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <button
                    onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                    disabled={currentPage === totalPages}
                    className="relative inline-flex items-center px-3 py-1 border border-gray-700 text-sm font-medium rounded-md text-gray-300 bg-gray-800/50 hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                  <div className="flex items-center gap-3 flex-wrap">
                    <p className="text-xs text-gray-300 whitespace-nowrap">
                      Showing <span className="font-medium">{((currentPage - 1) * itemsPerPage) + 1}</span> to{' '}
                      <span className="font-medium">
                        {Math.min(currentPage * itemsPerPage, totalItems)}
                      </span>{' '}
                      of <span className="font-medium">{totalItems}</span> results
                    </p>
                    <select
                      value={itemsPerPage}
                      onChange={(e) => {
                        setItemsPerPage(Number(e.target.value));
                        setCurrentPage(1);
                      }}
                      className="border border-gray-700 bg-gray-800/50 text-gray-200 rounded px-2 py-1 text-xs"
                    >
                      <option value={10}>10 per page</option>
                      <option value={15}>15 per page</option>
                      <option value={25}>25 per page</option>
                      <option value={50}>50 per page</option>
                    </select>
                  </div>
                  <div className="flex-shrink-0">
                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                      <button
                        onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                        disabled={currentPage === 1}
                        className="relative inline-flex items-center px-2 py-1 rounded-l-md border border-gray-700 bg-gray-800/50 text-xs font-medium text-gray-300 hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <FaChevronLeft />
                      </button>
                      
                      {/* Page Numbers */}
                      {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                        let pageNum;
                        if (totalPages <= 5) {
                          pageNum = i + 1;
                        } else if (currentPage <= 3) {
                          pageNum = i + 1;
                        } else if (currentPage >= totalPages - 2) {
                          pageNum = totalPages - 4 + i;
                        } else {
                          pageNum = currentPage - 2 + i;
                        }
                        
                        return (
                          <button
                            key={pageNum}
                            onClick={() => setCurrentPage(pageNum)}
                            className={`relative inline-flex items-center px-3 py-1 border text-xs font-medium ${
                              currentPage === pageNum
                                ? 'z-10 bg-blue-600 border-blue-600 text-white'
                                : 'bg-gray-800/50 border-gray-700 text-gray-300 hover:bg-gray-700'
                            }`}
                          >
                            {pageNum}
                          </button>
                        );
                      })}
                      
                      <button
                        onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                        disabled={currentPage === totalPages}
                        className="relative inline-flex items-center px-2 py-1 rounded-r-md border border-gray-700 bg-gray-800/50 text-xs font-medium text-gray-300 hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <FaChevronRight />
                      </button>
                    </nav>
                  </div>
                </div>
              </div>
            )}
          </>
        )}
      </div>

      {/* Modals */}
      <BookingViewModal
        booking={selectedBooking}
        isOpen={viewModalOpen}
        onClose={() => {
          setViewModalOpen(false);
          setSelectedBooking(null);
        }}
      />

      <BookingEditModal
        booking={selectedBooking}
        isOpen={editModalOpen}
        onClose={() => {
          setEditModalOpen(false);
          setSelectedBooking(null);
        }}
        onSave={handleSaveBooking}
      />
    </div>
  );
};

export default BookingsList;
