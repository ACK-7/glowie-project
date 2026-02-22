import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  FaCalculator, 
  FaPaperPlane, 
  FaFileAlt, 
  FaSpinner,
  FaSearch,
  FaEye,
  FaEdit,
  FaTrash,
  FaCheck,
  FaTimes,
  FaExclamationCircle,
  FaSyncAlt,
  FaPlus,
  FaFilter,
  FaChartLine,
  FaClock,
  FaCheckCircle,
  FaChevronLeft,
  FaChevronRight
} from 'react-icons/fa';
import { 
  getQuotes, 
  getQuote,
  createQuote, 
  updateQuote,
  approveQuote,
  rejectQuote,
  convertQuoteToBooking,
  getQuotesRequiringApproval,
  getExpiringQuotes,
  getQuoteStatistics,
  searchQuotes
} from '../../services/adminService';
import { showAlert, showConfirm } from '../../utils/sweetAlert';
import QuoteCreateModal from '../../components/Admin/QuoteCreateModal';

const QuoteManagement = () => {
  const navigate = useNavigate();
  const [quotes, setQuotes] = useState([]);
  const [filteredQuotes, setFilteredQuotes] = useState([]);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [showRequiringApproval, setShowRequiringApproval] = useState(false);
  const [showExpiring, setShowExpiring] = useState(false);

  // Modal states
  const [showCreateModal, setShowCreateModal] = useState(false);

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalQuotes, setTotalQuotes] = useState(0);
  const [perPage, setPerPage] = useState(15);
  const [paginationInfo, setPaginationInfo] = useState(null);

  // Quick quote calculator state
  const [formData, setFormData] = useState({
    vehicleType: '',
    origin: '',
    destination: 'Kampala',
    estimatedCost: 0
  });

  useEffect(() => {
    fetchData();
    
    // Cleanup function to clear search timeout
    return () => {
      if (window.searchTimeout) {
        clearTimeout(window.searchTimeout);
      }
    };
  }, [currentPage, perPage]); // Add pagination dependencies

  useEffect(() => {
    // Reset to first page when filters change (but not search term)
    if (currentPage !== 1 && (statusFilter !== 'all' || showRequiringApproval || showExpiring)) {
      setCurrentPage(1);
    } else if (statusFilter !== 'all' || showRequiringApproval || showExpiring) {
      filterQuotes();
    } else if (!searchTerm) {
      // No filters active and no search, use paginated data
      setFilteredQuotes(Array.isArray(quotes) ? quotes : []);
    }
  }, [quotes, statusFilter, showRequiringApproval, showExpiring]);

  const fetchData = async () => {
    try {
      setLoading(true);
      console.log('Fetching quotes data...', { currentPage, perPage });
      
      // Build query parameters
      const params = {
        page: currentPage,
        per_page: perPage,
        with: 'customer,vehicle,route',
        sort_by: 'created_at',
        sort_direction: 'desc'
      };

      // Add filters to params
      if (statusFilter !== 'all') {
        params.status = statusFilter;
      }

      const [quotesResponse, statsResponse] = await Promise.all([
        getQuotes(params),
        getQuoteStatistics().catch(() => null)
      ]);
      
      console.log('Raw quotes response:', quotesResponse);
      
      // Handle paginated response structure
      let quotesData = [];
      let paginationData = null;
      
      if (quotesResponse?.data) {
        if (quotesResponse.success && Array.isArray(quotesResponse.data)) {
          // ApiResponseService structure
          quotesData = quotesResponse.data;
          if (quotesResponse.meta?.pagination) {
            paginationData = {
              current_page: quotesResponse.meta.pagination.current_page,
              last_page: quotesResponse.meta.pagination.last_page,
              per_page: quotesResponse.meta.pagination.per_page,
              total: quotesResponse.meta.pagination.total,
              from: quotesResponse.meta.pagination.from,
              to: quotesResponse.meta.pagination.to
            };
          }
        } else if (quotesResponse.data.data && Array.isArray(quotesResponse.data.data)) {
          // Laravel pagination structure (fallback)
          quotesData = quotesResponse.data.data;
          paginationData = {
            current_page: quotesResponse.data.current_page,
            last_page: quotesResponse.data.last_page,
            per_page: quotesResponse.data.per_page,
            total: quotesResponse.data.total,
            from: quotesResponse.data.from,
            to: quotesResponse.data.to
          };
        } else if (Array.isArray(quotesResponse.data)) {
          quotesData = quotesResponse.data;
        }
      } else if (Array.isArray(quotesResponse)) {
        quotesData = quotesResponse;
      }
      
      console.log('Processed quotes data:', quotesData);
      console.log('Pagination info:', paginationData);
      
      // Ensure quotesData is always an array
      const safeQuotesData = Array.isArray(quotesData) ? quotesData : [];
      
      setQuotes(safeQuotesData);
      setFilteredQuotes(safeQuotesData); // For paginated data, filtered quotes are the same as quotes
      
      // Update pagination state
      if (paginationData) {
        setCurrentPage(paginationData.current_page);
        setTotalPages(paginationData.last_page);
        setTotalQuotes(paginationData.total);
        setPerPage(paginationData.per_page);
        setPaginationInfo(paginationData);
      } else {
        // Fallback for non-paginated response
        setTotalQuotes(quotesData.length);
        setTotalPages(1);
        setPaginationInfo(null);
      }
      
      setStatistics(statsResponse?.data || null);
      setError(null);
    } catch (err) {
      console.error('Failed to fetch quotes data:', err);
      setError('Failed to load quotes. Please ensure you are logged in as Admin.');
      setQuotes([]);
      setFilteredQuotes([]);
      setTotalQuotes(0);
      setTotalPages(1);
      setPaginationInfo(null);
    } finally {
      setLoading(false);
    }
  };

  const filterQuotes = async () => {
    // For paginated data, we don't filter locally - we refetch with filters
    if (showRequiringApproval || showExpiring || searchTerm) {
      try {
        setLoading(true);
        let response;
        
        if (showRequiringApproval) {
          response = await getQuotesRequiringApproval();
        } else if (showExpiring) {
          response = await getExpiringQuotes();
        } else if (searchTerm) {
          response = await searchQuotes(searchTerm);
        }
        
        // Ensure we always get an array
        let filteredData = [];
        if (response?.data) {
          if (Array.isArray(response.data)) {
            filteredData = response.data;
          } else if (response.data.data && Array.isArray(response.data.data)) {
            filteredData = response.data.data;
          }
        } else if (Array.isArray(response)) {
          filteredData = response;
        }
        
        console.log('Filtered data:', filteredData);
        setFilteredQuotes(filteredData);
        
        // Reset pagination for filtered results
        setTotalQuotes(filteredData.length);
        setTotalPages(1);
        setCurrentPage(1);
        setPaginationInfo(null);
        
      } catch (err) {
        console.error('Failed to fetch filtered quotes:', err);
        // Set empty array on error to prevent map errors
        setFilteredQuotes([]);
        setError('Failed to filter quotes. Please try again.');
      } finally {
        setLoading(false);
      }
    } else {
      // No filters active, use paginated data
      setFilteredQuotes(Array.isArray(quotes) ? quotes : []);
    }
  };

  const handleSearch = async (term) => {
    setSearchTerm(term);
    
    // Clear any existing timeout
    if (window.searchTimeout) {
      clearTimeout(window.searchTimeout);
    }
    
    if (term.length >= 2) {
      // Debounce search by 500ms
      window.searchTimeout = setTimeout(async () => {
        try {
          setLoading(true);
          const response = await searchQuotes(term);
          
          // Handle search response
          let searchResults = [];
          if (response?.data) {
            if (Array.isArray(response.data)) {
              searchResults = response.data;
            } else if (response.data.data && Array.isArray(response.data.data)) {
              searchResults = response.data.data;
            }
          } else if (Array.isArray(response)) {
            searchResults = response;
          }
          
          setFilteredQuotes(searchResults);
          setTotalQuotes(searchResults.length);
          setTotalPages(1);
          setCurrentPage(1);
          setPaginationInfo(null);
          
        } catch (err) {
          console.error('Search failed:', err);
          setError('Search failed. Please try again.');
          setFilteredQuotes([]);
        } finally {
          setLoading(false);
        }
      }, 500);
    } else if (term.length === 0) {
      // Clear search immediately, reset to paginated data
      setCurrentPage(1);
      fetchData();
    }
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
      setCurrentPage(page);
    }
  };

  const handlePerPageChange = (newPerPage) => {
    setPerPage(newPerPage);
    setCurrentPage(1); // Reset to first page
  };

  const handleApprove = async (quote) => {
    try {
      console.log('Approving quote:', quote);
      await approveQuote(quote.id, 'Quote approved by admin');
      await showAlert('Success', 'Quote approved successfully', 'success');
      fetchData();
    } catch (error) {
      console.error('Approve failed:', error);
      await showAlert('Error', 'Failed to approve quote', 'error');
    }
  };

  const handleReject = async (quote) => {
    const confirmed = await showConfirm(
      'Reject Quote',
      `Are you sure you want to reject quote ${quote.quote_reference || `Q-${quote.id}`}?`,
      'warning'
    );

    if (confirmed) {
      try {
        await rejectQuote(quote.id, 'Quote rejected by admin');
        await showAlert('Success', 'Quote rejected successfully', 'success');
        fetchData();
      } catch (error) {
        console.error('Reject failed:', error);
        await showAlert('Error', 'Failed to reject quote', 'error');
      }
    }
  };

  const handleConvertToBooking = async (quote) => {
    const confirmed = await showConfirm(
      'Convert to Booking',
      `Convert quote ${quote.quote_reference || `Q-${quote.id}`} to a booking?`,
      'question'
    );

    if (confirmed) {
      try {
        await convertQuoteToBooking(quote.id);
        await showAlert('Success', 'Quote converted to booking successfully', 'success');
        fetchData();
      } catch (error) {
        console.error('Convert failed:', error);
        await showAlert('Error', 'Failed to convert quote to booking', 'error');
      }
    }
  };

  const calculateQuote = async () => {
    if (!formData.origin || !formData.vehicleType) {
      await showAlert('Error', 'Please fill in vehicle type and origin', 'error');
      return;
    }
    
    try {
      // This would typically call an API endpoint for quote calculation
      const baseCosts = { 
        Japan: 2000, 
        UK: 3000, 
        UAE: 1500,
        'United States': 3500,
        Germany: 2800,
        Canada: 3200
      };
      const cost = baseCosts[formData.origin] || 2000;
      setFormData({ ...formData, estimatedCost: cost });
    } catch (err) {
      console.error('Failed to calculate quote:', err);
      await showAlert('Error', 'Failed to calculate quote. Please try again.', 'error');
    }
  };

  const handleCreateQuote = () => {
    setShowCreateModal(true);
  };

  const handleCreateSave = () => {
    setShowCreateModal(false);
    fetchData(); // Refresh the quotes list
  };

  const handleCreateClose = () => {
    setShowCreateModal(false);
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
      <span className={`${c.bg} ${c.text} px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1`}>
        <IconComponent className="text-xs" />
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

  const formatCurrency = (amount) => {
    if (!amount || amount === 0) return '0';
    return Number(amount).toLocaleString();
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <FaSpinner className="animate-spin text-blue-500 text-4xl" />
      </div>
    );
  }

  return (
    <div className="max-w-[calc(100vw-2rem)] mx-auto">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-2">Quote Management</h1>
          <p className="text-gray-400">
            Manage shipping quotes and calculations
            {paginationInfo ? (
              ` (${paginationInfo.from}-${paginationInfo.to} of ${paginationInfo.total} quotes)`
            ) : totalQuotes > 0 ? (
              ` (${totalQuotes} quotes loaded)`
            ) : ''}
          </p>
        </div>
        <button
          onClick={handleCreateQuote}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <FaPlus />
          New Quote
        </button>
      </div>

      {/* Statistics Cards */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Total Quotes</p>
                <p className="text-2xl font-bold text-white">{statistics.total_quotes || 0}</p>
              </div>
              <FaFileAlt className="text-blue-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Pending Approval</p>
                <p className="text-2xl font-bold text-white">{statistics.pending_quotes || 0}</p>
              </div>
              <FaClock className="text-yellow-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Approved</p>
                <p className="text-2xl font-bold text-white">{statistics.approved_quotes || 0}</p>
              </div>
              <FaCheckCircle className="text-green-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Converted</p>
                <p className="text-2xl font-bold text-white">{statistics.converted_quotes || 0}</p>
              </div>
              <FaChartLine className="text-purple-500 text-2xl" />
            </div>
          </div>
        </div>
      )}

      {/* Search and Filters */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6 mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search by quote number, customer, vehicle, or location..."
                value={searchTerm}
                onChange={(e) => {
                  const value = e.target.value;
                  setSearchTerm(value);
                  handleSearch(value);
                }}
                className="w-full pl-10 pr-10 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
              {searchTerm && (
                <button
                  onClick={() => {
                    setSearchTerm('');
                    handleSearch('');
                  }}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white"
                >
                  <FaTimes />
                </button>
              )}
            </div>
          </div>

          {/* Filters */}
          <div className="flex flex-wrap gap-4">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="expired">Expired</option>
              <option value="converted">Converted</option>
            </select>

            <button
              onClick={() => setShowRequiringApproval(!showRequiringApproval)}
              className={`px-4 py-3 rounded-lg font-medium transition-colors ${
                showRequiringApproval
                  ? 'bg-yellow-600 text-white'
                  : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
              }`}
            >
              <FaExclamationCircle className="inline mr-2" />
              Needs Approval
            </button>

            <button
              onClick={() => {
                // Clear filters and refresh
                setSearchTerm('');
                setStatusFilter('all');
                setShowRequiringApproval(false);
                setShowExpiring(false);
                setCurrentPage(1);
                fetchData();
              }}
              className="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <FaSyncAlt className="inline mr-2" />
              Refresh
            </button>
          </div>
        </div>
      </div>

      {/* Horizontal Quote Calculator */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6 mb-8">
        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-2">
          <FaCalculator className="text-blue-500" />
          Quick Quote Calculator
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 items-end">
          <div>
            <label className="block text-gray-400 text-sm mb-2">Vehicle Type</label>
            <input
              type="text"
              value={formData.vehicleType}
              onChange={(e) => setFormData({ ...formData, vehicleType: e.target.value })}
              className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
              placeholder="e.g., 2023 Toyota RAV4"
            />
          </div>

          <div>
            <label className="block text-gray-400 text-sm mb-2">Origin Country</label>
            <select
              value={formData.origin}
              onChange={(e) => setFormData({ ...formData, origin: e.target.value })}
              className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
            >
              <option value="">Select origin</option>
              <option value="Japan">Japan</option>
              <option value="UK">United Kingdom</option>
              <option value="UAE">UAE</option>
              <option value="United States">United States</option>
              <option value="Germany">Germany</option>
              <option value="Canada">Canada</option>
            </select>
          </div>

          <div>
            <label className="block text-gray-400 text-sm mb-2">Destination</label>
            <input
              type="text"
              value={formData.destination}
              onChange={(e) => setFormData({ ...formData, destination: e.target.value })}
              className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
              placeholder="Kampala, Uganda"
            />
          </div>

          <div>
            <button
              onClick={calculateQuote}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Calculate Quote
            </button>
          </div>

          {formData.estimatedCost > 0 && (
            <div className="bg-green-900/20 border border-green-700/50 rounded-lg p-4">
              <p className="text-gray-400 text-xs mb-1">Estimated Cost</p>
              <p className="text-green-400 text-xl font-bold">{formatCurrency(formData.estimatedCost)}</p>
            </div>
          )}
        </div>
      </div>

      {/* Quotes Table */}
      {error ? (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <p className="text-red-400">{error}</p>
        </div>
      ) : (
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-800/50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Quote Info
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Customer
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Vehicle & Route
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Amount
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Date
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-800">
                {!Array.isArray(filteredQuotes) || filteredQuotes.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="px-6 py-12 text-center text-gray-400">
                      {loading ? 'Loading quotes...' : 'No quotes found'}
                    </td>
                  </tr>
                ) : (
                  filteredQuotes.map((quote) => (
                    <tr key={quote.id} className="hover:bg-gray-800/30 transition-colors">
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-blue-400 font-semibold">
                            {quote.quote_reference || `Q-${quote.id}`}
                          </p>
                          <p className="text-gray-400 text-sm">
                            ID: {quote.id}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {quote.customer_name || 
                             (quote.customer?.first_name && quote.customer?.last_name 
                               ? `${quote.customer.first_name} ${quote.customer.last_name}` 
                               : quote.customer?.name || 'Unknown Customer')}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {quote.customer_email || quote.customer?.email || 'No email'}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {quote.vehicle_full_description || 
                             quote.vehicle_description ||
                             (quote.vehicle_details ? 
                               `${quote.vehicle_details.year || ''} ${quote.vehicle_details.make || ''} ${quote.vehicle_details.model || ''}`.trim() :
                               'Vehicle details not available')}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {quote.route_description || 
                             (quote.route ? `${quote.route.origin_country} → ${quote.route.destination_country}` :
                              `${quote.origin_country || 'N/A'} → ${quote.destination_country || 'Uganda'}`)}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <p className="text-white font-semibold">
                          ${formatCurrency(quote.total_amount || quote.estimated_cost || quote.amount || 0)}
                        </p>
                        {quote.currency && quote.currency !== 'USD' && (
                          <p className="text-gray-400 text-xs">
                            {quote.currency}
                          </p>
                        )}
                      </td>
                      <td className="px-6 py-4">
                        {getStatusBadge(quote.status)}
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white text-sm">
                            {formatDate(quote.created_at)}
                          </p>
                          {quote.valid_until && (
                            <p className="text-gray-400 text-xs">
                              Expires: {formatDate(quote.valid_until)}
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          {quote.status === 'pending' && (
                            <>
                              <button
                                onClick={() => handleApprove(quote)}
                                className="p-2 text-green-400 hover:text-green-300 hover:bg-green-900/20 rounded-lg transition-colors"
                                title="Approve Quote"
                              >
                                <FaCheck />
                              </button>
                              <button
                                onClick={() => handleReject(quote)}
                                className="p-2 text-red-400 hover:text-red-300 hover:bg-red-900/20 rounded-lg transition-colors"
                                title="Reject Quote"
                              >
                                <FaTimes />
                              </button>
                            </>
                          )}
                          {quote.status === 'approved' && (
                            <button
                              onClick={() => handleConvertToBooking(quote)}
                              className="p-2 text-blue-400 hover:text-blue-300 hover:bg-blue-900/20 rounded-lg transition-colors"
                              title="Convert to Booking"
                            >
                              <FaPaperPlane />
                            </button>
                          )}
                          <button
                            onClick={() => navigate(`/admin/quotes/${quote.id}`)}
                            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                            title="View Details"
                          >
                            <FaEye />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          
          {/* Pagination Controls */}
          {totalPages > 1 && (
            <div className="px-6 py-4 border-t border-gray-800">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="flex items-center gap-2">
                    <span className="text-gray-400 text-sm">Show:</span>
                    <select
                      value={perPage}
                      onChange={(e) => handlePerPageChange(Number(e.target.value))}
                      className="px-3 py-1 bg-gray-800 border border-gray-700 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                    >
                      <option value={10}>10</option>
                      <option value={15}>15</option>
                      <option value={25}>25</option>
                      <option value={50}>50</option>
                    </select>
                    <span className="text-gray-400 text-sm">per page</span>
                  </div>
                  
                  {paginationInfo && (
                    <div className="text-gray-400 text-sm">
                      Showing {paginationInfo.from} to {paginationInfo.to} of {paginationInfo.total} results
                    </div>
                  )}
                </div>

                <div className="flex items-center gap-2">
                  {/* Previous Button */}
                  <button
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                    className="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <FaChevronLeft />
                  </button>

                  {/* Page Numbers */}
                  <div className="flex items-center gap-1">
                    {[...Array(totalPages)].map((_, i) => {
                      const page = i + 1;
                      if (
                        page === 1 ||
                        page === totalPages ||
                        (page >= currentPage - 2 && page <= currentPage + 2)
                      ) {
                        return (
                          <button
                            key={page}
                            onClick={() => handlePageChange(page)}
                            className={`px-3 py-2 rounded-lg transition-colors ${
                              currentPage === page
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                            }`}
                          >
                            {page}
                          </button>
                        );
                      } else if (
                        page === currentPage - 3 ||
                        page === currentPage + 3
                      ) {
                        return <span key={page} className="text-gray-500 px-2">...</span>;
                      }
                      return null;
                    })}
                  </div>

                  {/* Next Button */}
                  <button
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage === totalPages}
                    className="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <FaChevronRight />
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {/* Create Quote Modal */}
      {showCreateModal && (
        <QuoteCreateModal
          onClose={handleCreateClose}
          onSave={handleCreateSave}
        />
      )}
    </div>
  );
};

export default QuoteManagement;
