import React, { useState, useEffect } from 'react';
import { 
  FaDollarSign, 
  FaChartLine, 
  FaExclamationCircle, 
  FaSpinner,
  FaSearch,
  FaSyncAlt,
  FaEye,
  FaDownload,
  FaCreditCard,
  FaCheckCircle,
  FaClock,
  FaTimes,
  FaFileExport,
  FaCalendarAlt,
  FaFilter,
  FaChevronLeft,
  FaChevronRight
} from 'react-icons/fa';
import StatCard from '../../components/Admin/StatCard';
import { 
  getFinanceStats, 
  getPayments, 
  getPayment,
  updatePaymentStatus,
  processRefund,
  exportPayments,
  getFinancialSummary
} from '../../services/adminService';
import { showAlert, showConfirm } from '../../utils/sweetAlert';

// Import for debugging
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const getAuthHeaders = () => {
  const token = localStorage.getItem('admin_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
};

const FinanceDashboard = () => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [methodFilter, setMethodFilter] = useState('all');
  const [dateRange, setDateRange] = useState({
    start_date: '',
    end_date: ''
  });
  const [exporting, setExporting] = useState(false);
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalPayments, setTotalPayments] = useState(0);
  const [perPage, setPerPage] = useState(15);
  const [paginationInfo, setPaginationInfo] = useState(null);

  const [stats, setStats] = useState([
    { icon: FaDollarSign, title: 'Total Revenue', value: '$0', change: 'Loading...', changeType: 'positive', iconBg: 'bg-green-600' },
    { icon: FaClock, title: 'Pending Payments', value: '0', change: 'Loading...', changeType: 'warning', iconBg: 'bg-yellow-600' },
    { icon: FaChartLine, title: 'Completed Payments', value: '0', change: 'Loading...', changeType: 'positive', iconBg: 'bg-blue-600' },
    { icon: FaExclamationCircle, title: 'Failed/Overdue', value: '0', change: 'Loading...', changeType: 'negative', iconBg: 'bg-red-600' }
  ]);
  const [payments, setPayments] = useState([]);
  const [filteredPayments, setFilteredPayments] = useState([]);

  useEffect(() => {
    fetchFinanceData();
  }, [currentPage, perPage]);

  useEffect(() => {
    // Reset to first page when filters change
    if (currentPage !== 1) {
      setCurrentPage(1);
    } else {
      filterPayments();
    }
  }, [payments, searchTerm, statusFilter, methodFilter]);

  const fetchFinanceData = async (showLoader = true) => {
    try {
      if (showLoader) setLoading(true);
      else setRefreshing(true);
      
      console.log('Fetching finance data...', { currentPage, perPage });
      
      // Check if admin token exists
      const token = localStorage.getItem('admin_token');
      console.log('Admin token exists:', !!token);
      if (!token) {
        console.error('No admin token found in localStorage');
        setError('Authentication required. Please log in as admin.');
        setStats([
          { icon: FaDollarSign, title: 'Total Revenue', value: '$0', change: 'Login Required', changeType: 'neutral', iconBg: 'bg-green-600' },
          { icon: FaClock, title: 'Pending Payments', value: '0', change: 'Login Required', changeType: 'neutral', iconBg: 'bg-yellow-600' },
          { icon: FaChartLine, title: 'Completed Payments', value: '0', change: 'Login Required', changeType: 'neutral', iconBg: 'bg-blue-600' },
          { icon: FaExclamationCircle, title: 'Overdue', value: '0', change: 'Login Required', changeType: 'neutral', iconBg: 'bg-red-600' }
        ]);
        return;
      }
      
      // Build query parameters
      const params = {
        page: currentPage,
        per_page: perPage,
        with: 'booking.customer,customer,booking.vehicle'
      };

      // Add filters to params
      if (statusFilter !== 'all') {
        params.status = statusFilter;
      }
      if (methodFilter !== 'all') {
        params.payment_method = methodFilter;
      }

      // Test API connectivity
      console.log('Testing API connectivity...');
      console.log('API_BASE_URL:', API_BASE_URL);
      console.log('Auth headers:', getAuthHeaders());
      
      // Test statistics endpoint directly
      try {
        console.log('Testing statistics endpoint directly...');
        const directResponse = await fetch(`${API_BASE_URL}/admin/crud/payments/statistics`, {
          headers: getAuthHeaders()
        });
        console.log('Direct statistics response status:', directResponse.status);
        const directData = await directResponse.json();
        console.log('Direct statistics response data:', directData);
      } catch (directError) {
        console.error('Direct statistics test failed:', directError);
      }
      
      const [financeStatsResponse, paymentsResponse] = await Promise.all([
        getFinanceStats().catch((error) => {
          console.error('Finance stats API error:', error);
          return null;
        }),
        getPayments(params).catch((error) => {
          console.error('Payments API error:', error);
          return null;
        })
      ]);
      
      console.log('Raw finance stats response:', financeStatsResponse);
      console.log('Raw payments response:', paymentsResponse);
      
      // Debug: Check response structure
      if (financeStatsResponse) {
        console.log('Finance response keys:', Object.keys(financeStatsResponse));
        console.log('Finance response data:', financeStatsResponse.data);
      } else {
        console.log('No finance stats response received');
      }
      
      // Update statistics
      if (financeStatsResponse?.data) {
        const statsData = financeStatsResponse.data;
        setStats([
          {
            icon: FaDollarSign,
            title: 'Total Revenue',
            value: `$${statsData.total_revenue ? (statsData.total_revenue / 1000).toFixed(1) + 'k' : '0'}`,
            change: statsData.revenue_change || 'No change',
            changeType: statsData.revenue_change?.includes('+') ? 'positive' : 'neutral',
            iconBg: 'bg-green-600'
          },
          {
            icon: FaClock,
            title: 'Pending Payments',
            value: statsData.pending_payments?.toString() || '0',
            change: statsData.pending_change || 'No change',
            changeType: 'warning',
            iconBg: 'bg-yellow-600'
          },
          {
            icon: FaChartLine,
            title: 'Completed Payments',
            value: statsData.completed_payments?.toString() || '0',
            change: statsData.completed_change || 'No change',
            changeType: 'positive',
            iconBg: 'bg-blue-600'
          },
          {
            icon: FaExclamationCircle,
            title: 'Overdue',
            value: statsData.overdue_payments?.toString() || '0',
            change: statsData.overdue_change || 'No change',
            changeType: 'negative',
            iconBg: 'bg-red-600'
          }
        ]);
      }

      // Handle paginated payments response
      let paymentsData = [];
      let paginationData = null;
      
      if (paymentsResponse?.data) {
        if (paymentsResponse.data.data && Array.isArray(paymentsResponse.data.data)) {
          // Laravel pagination structure
          paymentsData = paymentsResponse.data.data;
          paginationData = {
            current_page: paymentsResponse.data.current_page,
            last_page: paymentsResponse.data.last_page,
            per_page: paymentsResponse.data.per_page,
            total: paymentsResponse.data.total,
            from: paymentsResponse.data.from,
            to: paymentsResponse.data.to
          };
        } else if (Array.isArray(paymentsResponse.data)) {
          paymentsData = paymentsResponse.data;
        }
      } else if (Array.isArray(paymentsResponse)) {
        paymentsData = paymentsResponse;
      }
      
      console.log('Processed payments data:', paymentsData);
      console.log('Pagination info:', paginationData);
      
      setPayments(paymentsData);
      setFilteredPayments(paymentsData);
      
      // Update pagination state
      if (paginationData) {
        setCurrentPage(paginationData.current_page);
        setTotalPages(paginationData.last_page);
        setTotalPayments(paginationData.total);
        setPerPage(paginationData.per_page);
        setPaginationInfo(paginationData);
      } else {
        // Fallback for non-paginated response
        setTotalPayments(paymentsData.length);
        setTotalPages(1);
        setPaginationInfo(null);
      }
      
      setError(null);
    } catch (err) {
      console.error('Failed to fetch finance data:', err);
      setError('Failed to load finance data. Please ensure you are logged in as Admin.');
      setPayments([]);
      setFilteredPayments([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const filterPayments = () => {
    let filtered = payments;

    // Apply search filter
    if (searchTerm) {
      filtered = filtered.filter(payment =>
        payment.payment_reference?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        payment.booking?.customer?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        payment.customer_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        payment.booking?.reference_number?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Apply status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(payment => payment.status === statusFilter);
    }

    // Apply method filter
    if (methodFilter !== 'all') {
      filtered = filtered.filter(payment => payment.payment_method === methodFilter);
    }

    setFilteredPayments(filtered);
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

  const handleExport = async () => {
    try {
      setExporting(true);
      showAlert.loading('Exporting...', 'Preparing your payment data export...');
      
      const exportParams = {
        status: statusFilter !== 'all' ? statusFilter : undefined,
        payment_method: methodFilter !== 'all' ? methodFilter : undefined,
        search: searchTerm || undefined,
        ...dateRange
      };
      
      await exportPayments(exportParams);
      showAlert.close();
      showAlert.success('Success!', 'Payment data exported successfully!');
    } catch (error) {
      showAlert.close();
      console.error('Export failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to export payment data';
      showAlert.error('Export Failed', errorMessage);
    } finally {
      setExporting(false);
    }
  };

  const handleRefresh = () => {
    fetchFinanceData(false);
    showAlert('Success', 'Finance data refreshed successfully', 'success');
  };

  const getStatusBadge = (status) => {
    const config = {
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Pending', icon: FaClock },
      completed: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Completed', icon: FaCheckCircle },
      failed: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Failed', icon: FaTimes },
      refunded: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Refunded', icon: FaChartLine },
      cancelled: { bg: 'bg-gray-900/30', text: 'text-gray-400', label: 'Cancelled', icon: FaTimes }
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
    if (!amount) return '$0';
    return `$${Number(amount).toLocaleString()}`;
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
          <h1 className="text-3xl font-bold text-white mb-2">Finance Dashboard</h1>
          <p className="text-gray-400">
            Manage revenue, payments, and financial analytics
            {paginationInfo ? (
              ` (${paginationInfo.from}-${paginationInfo.to} of ${paginationInfo.total} payments)`
            ) : totalPayments > 0 ? (
              ` (${totalPayments} payments loaded)`
            ) : error?.includes('Authentication') ? (
              ' - Please log in to view data'
            ) : ''}
          </p>
        </div>
        <button
          onClick={handleRefresh}
          disabled={refreshing}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50"
        >
          <FaSyncAlt className={refreshing ? 'animate-spin' : ''} />
          Refresh Data
        </button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {stats.map((stat, index) => (
          <StatCard key={index} {...stat} />
        ))}
      </div>

      {/* Search and Filters */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6 mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search by payment reference, customer, or booking..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
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
              <option value="completed">Completed</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
              <option value="cancelled">Cancelled</option>
            </select>

            <select
              value={methodFilter}
              onChange={(e) => setMethodFilter(e.target.value)}
              className="px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="all">All Methods</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="mobile_money">Mobile Money</option>
              <option value="credit_card">Credit Card</option>
              <option value="cash">Cash</option>
            </select>

            <div className="flex gap-2">
              <input
                type="date"
                value={dateRange.start_date}
                onChange={(e) => setDateRange(prev => ({ ...prev, start_date: e.target.value }))}
                className="px-3 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                placeholder="Start Date"
              />
              <input
                type="date"
                value={dateRange.end_date}
                onChange={(e) => setDateRange(prev => ({ ...prev, end_date: e.target.value }))}
                className="px-3 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                placeholder="End Date"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Error State */}
      {error ? (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <p className="text-red-400 mb-4">{error}</p>
          {error.includes('Authentication') && (
            <button
              onClick={() => window.location.href = '/admin/login'}
              className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Go to Admin Login
            </button>
          )}
        </div>
      ) : (
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
            <h2 className="text-xl font-bold text-white">Payment Transactions</h2>
            <button 
              onClick={handleExport}
              disabled={exporting}
              className="text-blue-500 hover:text-blue-400 text-sm font-semibold flex items-center gap-2 disabled:opacity-50"
            >
              <FaDownload className={exporting ? 'animate-spin' : ''} />
              {exporting ? 'Exporting...' : 'Export'}
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-800/50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Payment Info
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Customer
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Booking
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Method
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Date
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Amount
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-800">
                {filteredPayments.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="px-6 py-12 text-center text-gray-400">
                      No payments found
                    </td>
                  </tr>
                ) : (
                  filteredPayments.map((payment) => (
                    <tr key={payment.id} className="hover:bg-gray-800/30 transition-colors">
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-blue-400 font-semibold">
                            {payment.payment_reference || `PAY-${payment.id}`}
                          </p>
                          <p className="text-gray-400 text-sm">
                            ID: {payment.id}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {payment.booking?.customer?.name || 
                             payment.customer?.name ||
                             payment.customer_name || 
                             'Unknown Customer'}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {payment.booking?.customer?.email || 
                             payment.customer?.email ||
                             'No email'}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {payment.booking?.reference_number || 
                             payment.booking?.booking_reference ||
                             `BK-${payment.booking_id}` || 
                             'N/A'}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {payment.booking?.vehicle ? 
                              `${payment.booking.vehicle.make} ${payment.booking.vehicle.model}` : 
                              'Vehicle info N/A'}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <FaCreditCard className="text-gray-400" />
                          <span className="text-white capitalize">
                            {payment.payment_method?.replace('_', ' ') || 'N/A'}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        {getStatusBadge(payment.status)}
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white text-sm">
                            {formatDate(payment.payment_date || payment.created_at)}
                          </p>
                          {payment.transaction_id && (
                            <p className="text-gray-400 text-xs">
                              TXN: {payment.transaction_id}
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-semibold">
                            {formatCurrency(payment.amount)}
                          </p>
                          <p className="text-gray-400 text-xs">
                            {payment.currency || 'USD'}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => showAlert('Info', 'View payment details coming soon', 'info')}
                            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                            title="View Details"
                          >
                            <FaEye />
                          </button>
                          {payment.status === 'completed' && (
                            <button
                              onClick={() => showAlert('Info', 'Refund functionality coming soon', 'info')}
                              className="p-2 text-yellow-400 hover:text-yellow-300 hover:bg-yellow-900/20 rounded-lg transition-colors"
                              title="Process Refund"
                            >
                              <FaChartLine />
                            </button>
                          )}
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
                    className="px-3 py-2 text-sm bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Previous
                  </button>

                  {/* Page Numbers */}
                  <div className="flex items-center gap-1">
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

                      if (pageNum < 1 || pageNum > totalPages) return null;

                      return (
                        <button
                          key={pageNum}
                          onClick={() => handlePageChange(pageNum)}
                          className={`px-3 py-2 text-sm rounded-lg transition-colors ${
                            pageNum === currentPage
                              ? 'bg-blue-600 text-white'
                              : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                          }`}
                        >
                          {pageNum}
                        </button>
                      );
                    })}
                  </div>

                  {/* Next Button */}
                  <button
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage === totalPages}
                    className="px-3 py-2 text-sm bg-gray-800 text-gray-300 rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default FinanceDashboard;
