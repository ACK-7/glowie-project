import React, { useState, useEffect } from "react";
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
  FaChevronRight,
  FaUndo,
  FaBan,
  FaCheckDouble,
  FaPlus,
} from "react-icons/fa";
import StatCard from "../../components/Admin/StatCard";
import {
  getFinanceStats,
  getPayments,
  getPayment,
  createPayment,
  getBookings,
  updatePaymentStatus,
  processRefund,
  exportPayments,
  getFinancialSummary,
} from "../../services/adminService";
import { showAlert, showConfirm } from "../../utils/sweetAlert";
import DropdownMenu from '../../components/UI/DropdownMenu';


const FinanceDashboard = () => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [methodFilter, setMethodFilter] = useState("all");
  const [dateRange, setDateRange] = useState({
    start_date: "",
    end_date: "",
  });
  const [exporting, setExporting] = useState(false);

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalPayments, setTotalPayments] = useState(0);
  const [perPage, setPerPage] = useState(15);
  const [paginationInfo, setPaginationInfo] = useState(null);

  const [stats, setStats] = useState([
    {
      icon: FaDollarSign,
      title: "Total Revenue",
      value: "$0",
      change: "Loading...",
      changeType: "positive",
      iconBg: "bg-green-600",
    },
    {
      icon: FaClock,
      title: "Pending Payments",
      value: "0",
      change: "Loading...",
      changeType: "warning",
      iconBg: "bg-yellow-600",
    },
    {
      icon: FaChartLine,
      title: "Completed Payments",
      value: "0",
      change: "Loading...",
      changeType: "positive",
      iconBg: "bg-blue-600",
    },
    {
      icon: FaExclamationCircle,
      title: "Failed/Overdue",
      value: "0",
      change: "Loading...",
      changeType: "negative",
      iconBg: "bg-red-600",
    },
  ]);
  const [payments, setPayments] = useState([]);
  const [filteredPayments, setFilteredPayments] = useState([]);
  const [viewingPayment, setViewingPayment] = useState(null);
  const [viewLoading, setViewLoading] = useState(false);
  const [showRecordModal, setShowRecordModal] = useState(false);
  const [bookings, setBookings] = useState([]);
  const [recordForm, setRecordForm] = useState({
    booking_id: '',
    customer_id: '',
    amount: '',
    currency: 'USD',
    payment_method: 'bank_transfer',
    transaction_id: '',
    notes: ''
  });

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

  useEffect(() => {
    if (dateRange.start_date && dateRange.end_date) {
      fetchFinanceData(false);
    }
  }, [dateRange.start_date, dateRange.end_date]);

  const fetchFinanceData = async (showLoader = true) => {
    try {
      if (showLoader) setLoading(true);
      else setRefreshing(true);

      // Check if admin token exists
      const token = localStorage.getItem("admin_token");
      if (!token) {
        setError("Authentication required. Please log in as admin.");
        setStats([
          {
            icon: FaDollarSign,
            title: "Total Revenue",
            value: "$0",
            change: "Login Required",
            changeType: "neutral",
            iconBg: "bg-green-600",
          },
          {
            icon: FaClock,
            title: "Pending Payments",
            value: "0",
            change: "Login Required",
            changeType: "neutral",
            iconBg: "bg-yellow-600",
          },
          {
            icon: FaChartLine,
            title: "Completed Payments",
            value: "0",
            change: "Login Required",
            changeType: "neutral",
            iconBg: "bg-blue-600",
          },
          {
            icon: FaExclamationCircle,
            title: "Overdue",
            value: "0",
            change: "Login Required",
            changeType: "neutral",
            iconBg: "bg-red-600",
          },
        ]);
        return;
      }

      // Build query parameters
      const params = {
        page: currentPage,
        per_page: perPage,
        sort_by: 'created_at',
        sort_order: 'desc',
      };

      // Add filters to params
      if (statusFilter !== "all") {
        params.status = statusFilter;
      }
      if (methodFilter !== "all") {
        params.payment_method = methodFilter;
      }

      const [financeStatsResponse, paymentsResponse] = await Promise.all([
        getFinanceStats().catch(() => null),
        getPayments(params).catch(() => null),
      ]);

      // Update statistics - handle nested response: axios.data -> API wrapper.data -> stats
      const statsWrapper = financeStatsResponse?.data;
      const statsData = statsWrapper?.data || statsWrapper;
      if (statsData && (statsData.total_revenue !== undefined || statsData.total_payments !== undefined)) {
        setStats([
          {
            icon: FaDollarSign,
            title: "Total Revenue",
            value: formatCurrency(statsData.total_revenue),
            change: `${statsData.total_payments || 0} total payments`,
            changeType: "positive",
            iconBg: "bg-green-600",
          },
          {
            icon: FaClock,
            title: "Pending Payments",
            value: statsData.pending_payments?.toString() || "0",
            change: `${statsData.success_rate ? statsData.success_rate.toFixed(0) : 0}% success rate`,
            changeType: "warning",
            iconBg: "bg-yellow-600",
          },
          {
            icon: FaChartLine,
            title: "Completed Payments",
            value: statsData.completed_payments?.toString() || "0",
            change: `${statsData.refunded_payments || 0} refunded`,
            changeType: "positive",
            iconBg: "bg-blue-600",
          },
          {
            icon: FaExclamationCircle,
            title: "Failed/Overdue",
            value: ((statsData.failed_payments || 0) + (statsData.overdue_payments || 0)).toString(),
            change: `${statsData.cancelled_payments || 0} cancelled`,
            changeType: "negative",
            iconBg: "bg-red-600",
          },
        ]);
      }

      // Handle paginated payments response
      // API returns: { success, message, data: [...payments], meta: { pagination: {...} } }
      let paymentsData = [];
      let paginationData = null;

      if (paymentsResponse) {
        // paymentsResponse = { success, message, data: [...], meta: { pagination: {...} } }
        if (Array.isArray(paymentsResponse.data)) {
          paymentsData = paymentsResponse.data;
        } else if (paymentsResponse.data?.data && Array.isArray(paymentsResponse.data.data)) {
          paymentsData = paymentsResponse.data.data;
        }

        // Extract pagination from meta
        const pagination = paymentsResponse.meta?.pagination || paymentsResponse.data?.meta?.pagination;
        if (pagination) {
          paginationData = {
            current_page: pagination.current_page,
            last_page: pagination.last_page,
            per_page: pagination.per_page,
            total: pagination.total,
            from: pagination.from,
            to: pagination.to,
          };
        }
      }

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
      setError(
        "Failed to load finance data. Please ensure you are logged in as Admin.",
      );
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
      filtered = filtered.filter(
        (payment) =>
          payment.payment_reference
            ?.toLowerCase()
            .includes(searchTerm.toLowerCase()) ||
          (payment.booking?.customer?.full_name || payment.customer?.full_name || `${payment.booking?.customer?.first_name || ''} ${payment.booking?.customer?.last_name || ''}`.trim() || payment.customer_name || '')
            .toLowerCase()
            .includes(searchTerm.toLowerCase()) ||
          payment.booking?.reference_number
            ?.toLowerCase()
            .includes(searchTerm.toLowerCase()),
      );
    }

    // Apply status filter
    if (statusFilter !== "all") {
      filtered = filtered.filter((payment) => payment.status === statusFilter);
    }

    // Apply method filter
    if (methodFilter !== "all") {
      filtered = filtered.filter(
        (payment) => payment.payment_method === methodFilter,
      );
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
      showAlert.loading(
        "Exporting...",
        "Preparing your payment data export...",
      );

      const exportParams = {
        status: statusFilter !== "all" ? statusFilter : undefined,
        payment_method: methodFilter !== "all" ? methodFilter : undefined,
        search: searchTerm || undefined,
        ...dateRange,
      };

      await exportPayments(exportParams);
      showAlert.close();
      showAlert.success("Success!", "Payment data exported successfully!");
    } catch (error) {
      showAlert.close();
      const errorMessage =
        error.response?.data?.message ||
        error.message ||
        "Failed to export payment data";
      showAlert.error("Export Failed", errorMessage);
    } finally {
      setExporting(false);
    }
  };

  const handleRefresh = () => {
    fetchFinanceData(false);
    showAlert("Success", "Finance data refreshed successfully", "success");
  };

  const handleViewPayment = async (payment) => {
    try {
      setViewLoading(true);
      setViewingPayment(payment);
      const response = await getPayment(payment.id);
      if (response?.data) {
        setViewingPayment(response.data);
      }
    } catch {
      showAlert('Error', 'Failed to load payment details', 'error');
    } finally {
      setViewLoading(false);
    }
  };

  const handleMarkCompleted = async (payment) => {
    const confirmed = await showConfirm(
      'Mark as Completed',
      `Mark payment ${payment.payment_reference || payment.id} as completed?`,
      'question',
      'Yes, Complete'
    );
    if (confirmed) {
      try {
        showAlert.loading('Processing...', 'Updating payment status...');
        await updatePaymentStatus(payment.id, 'completed');
        showAlert.close();
        showAlert.success('Success!', 'Payment marked as completed');
        fetchFinanceData(false);
      } catch {
        showAlert.close();
        showAlert.error('Error', 'Failed to update payment status');
      }
    }
  };

  const handleProcessRefund = async (payment) => {
    const confirmed = await showConfirm(
      'Process Refund',
      `Process refund for payment ${payment.payment_reference || payment.id} (${formatCurrency(payment.amount)})?`,
      'warning',
      'Yes, Refund'
    );
    if (confirmed) {
      try {
        showAlert.loading('Processing...', 'Processing refund...');
        await processRefund(payment.id, payment.amount, 'Admin initiated refund');
        showAlert.close();
        showAlert.success('Success!', 'Refund processed successfully');
        fetchFinanceData(false);
      } catch {
        showAlert.close();
        showAlert.error('Error', 'Failed to process refund');
      }
    }
  };

  const handleCancelPayment = async (payment) => {
    const confirmed = await showConfirm(
      'Cancel Payment',
      `Cancel payment ${payment.payment_reference || payment.id}? This action cannot be undone.`,
      'warning',
      'Yes, Cancel'
    );
    if (confirmed) {
      try {
        showAlert.loading('Processing...', 'Cancelling payment...');
        await updatePaymentStatus(payment.id, 'cancelled', 'Cancelled by admin');
        showAlert.close();
        showAlert.success('Success!', 'Payment cancelled successfully');
        fetchFinanceData(false);
      } catch {
        showAlert.close();
        showAlert.error('Error', 'Failed to cancel payment');
      }
    }
  };

  const handleOpenRecordModal = async () => {
    try {
      const response = await getBookings({ per_page: 100, with: 'customer' });
      const bookingsData = response?.data?.data || response?.data || [];
      setBookings(bookingsData);
    } catch {
      setBookings([]);
    }
    setRecordForm({
      booking_id: '',
      customer_id: '',
      amount: '',
      currency: 'USD',
      payment_method: 'bank_transfer',
      transaction_id: '',
      notes: ''
    });
    setShowRecordModal(true);
  };

  const handleBookingSelect = (bookingId) => {
    const booking = bookings.find(b => String(b.id) === String(bookingId));
    setRecordForm(prev => ({
      ...prev,
      booking_id: bookingId,
      customer_id: booking?.customer_id || booking?.customer?.id || '',
      amount: booking?.total_amount || booking?.amount || ''
    }));
  };

  const handleRecordPayment = async (e) => {
    e.preventDefault();
    if (!recordForm.booking_id || !recordForm.amount) {
      showAlert('Warning', 'Booking and amount are required', 'warning');
      return;
    }
    try {
      showAlert.loading('Processing...', 'Recording payment...');
      await createPayment({
        booking_id: Number(recordForm.booking_id),
        customer_id: Number(recordForm.customer_id),
        amount: Number(recordForm.amount),
        currency: recordForm.currency,
        payment_method: recordForm.payment_method,
        transaction_id: recordForm.transaction_id || undefined,
        notes: recordForm.notes || undefined
      });
      showAlert.close();
      showAlert.success('Success!', 'Payment recorded successfully');
      setShowRecordModal(false);
      fetchFinanceData(false);
    } catch (err) {
      showAlert.close();
      const msg = err.response?.data?.message || err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(', ')
        : 'Failed to record payment';
      showAlert.error('Error', msg);
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
      completed: {
        bg: "bg-green-900/30",
        text: "text-green-400",
        label: "Completed",
        icon: FaCheckCircle,
      },
      failed: {
        bg: "bg-red-900/30",
        text: "text-red-400",
        label: "Failed",
        icon: FaTimes,
      },
      refunded: {
        bg: "bg-blue-900/30",
        text: "text-blue-400",
        label: "Refunded",
        icon: FaChartLine,
      },
      cancelled: {
        bg: "bg-gray-900/30",
        text: "text-gray-400",
        label: "Cancelled",
        icon: FaTimes,
      },
    };
    const c = config[status] || config.pending;
    const IconComponent = c.icon;
    return (
      <span
        className={`${c.bg} ${c.text} px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1`}
      >
        <IconComponent className="text-xs" />
        {c.label}
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  };

  const formatCurrency = (amount) => {
    if (!amount) return "$0";
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
          <h1 className="text-3xl font-bold text-white mb-2">
            Finance Dashboard
          </h1>
          <p className="text-gray-400">
            Manage revenue, payments, and financial analytics
            {paginationInfo
              ? ` (${paginationInfo.from}-${paginationInfo.to} of ${paginationInfo.total} payments)`
              : totalPayments > 0
                ? ` (${totalPayments} payments loaded)`
                : error?.includes("Authentication")
                  ? " - Please log in to view data"
                  : ""}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={handleOpenRecordModal}
            className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
          >
            <FaPlus />
            Record Payment
          </button>
          <button
            onClick={handleRefresh}
            disabled={refreshing}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            <FaSyncAlt className={refreshing ? "animate-spin" : ""} />
            Refresh Data
          </button>
        </div>
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
                onChange={(e) =>
                  setDateRange((prev) => ({
                    ...prev,
                    start_date: e.target.value,
                  }))
                }
                className="px-3 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                placeholder="Start Date"
              />
              <input
                type="date"
                value={dateRange.end_date}
                onChange={(e) =>
                  setDateRange((prev) => ({
                    ...prev,
                    end_date: e.target.value,
                  }))
                }
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
          {error.includes("Authentication") && (
            <button
              onClick={() => (window.location.href = "/admin/login")}
              className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Go to Admin Login
            </button>
          )}
        </div>
      ) : (
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
            <h2 className="text-xl font-bold text-white">
              Payment Transactions
            </h2>
            <button
              onClick={handleExport}
              disabled={exporting}
              className="text-blue-500 hover:text-blue-400 text-sm font-semibold flex items-center gap-2 disabled:opacity-50"
            >
              <FaDownload className={exporting ? "animate-spin" : ""} />
              {exporting ? "Exporting..." : "Export"}
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
                    <td
                      colSpan="8"
                      className="px-6 py-12 text-center text-gray-400"
                    >
                      No payments found
                    </td>
                  </tr>
                ) : (
                  filteredPayments.map((payment) => (
                    <tr
                      key={payment.id}
                      className="hover:bg-gray-800/30 transition-colors"
                    >
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
                            {payment.booking?.customer?.full_name ||
                              payment.customer?.full_name ||
                              (payment.booking?.customer?.first_name ? `${payment.booking.customer.first_name} ${payment.booking.customer.last_name || ''}`.trim() : null) ||
                              (payment.customer?.first_name ? `${payment.customer.first_name} ${payment.customer.last_name || ''}`.trim() : null) ||
                              payment.customer_name ||
                              "Unknown Customer"}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {payment.booking?.customer?.email ||
                              payment.customer?.email ||
                              "No email"}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {payment.booking?.reference_number ||
                              payment.booking?.booking_reference ||
                              `BK-${payment.booking_id}` ||
                              "N/A"}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {payment.booking?.vehicle
                              ? `${payment.booking.vehicle.make} ${payment.booking.vehicle.model}`
                              : "Vehicle info N/A"}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <FaCreditCard className="text-gray-400" />
                          <span className="text-white capitalize">
                            {payment.payment_method?.replace("_", " ") || "N/A"}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        {getStatusBadge(payment.status)}
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white text-sm">
                            {formatDate(
                              payment.payment_date || payment.created_at,
                            )}
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
                            {payment.currency || "USD"}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <DropdownMenu
                          items={[
                            {
                              label: 'View Details',
                              icon: <FaEye />,
                              onClick: () => handleViewPayment(payment)
                            },
                            payment.status === 'pending' && {
                              label: 'Mark Completed',
                              icon: <FaCheckDouble />,
                              onClick: () => handleMarkCompleted(payment)
                            },
                            payment.status === 'completed' && {
                              label: 'Process Refund',
                              icon: <FaUndo />,
                              onClick: () => handleProcessRefund(payment),
                              danger: true
                            },
                            payment.status === 'pending' && {
                              label: 'Cancel Payment',
                              icon: <FaBan />,
                              onClick: () => handleCancelPayment(payment),
                              danger: true
                            }
                          ].filter(Boolean)}
                        />
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
                      onChange={(e) =>
                        handlePerPageChange(Number(e.target.value))
                      }
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
                      Showing {paginationInfo.from} to {paginationInfo.to} of{" "}
                      {paginationInfo.total} results
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
                              ? "bg-blue-600 text-white"
                              : "bg-gray-800 text-gray-300 hover:bg-gray-700"
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

      {/* View Payment Modal */}
      {viewingPayment && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
          <div className="bg-[#1a1f28] border border-gray-700 rounded-xl w-full max-w-lg max-h-[80vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-700 flex items-center justify-between">
              <h3 className="text-xl font-bold text-white">Payment Details</h3>
              <button onClick={() => setViewingPayment(null)} className="text-gray-400 hover:text-white">
                <FaTimes />
              </button>
            </div>
            {viewLoading ? (
              <div className="p-6 text-center"><FaSpinner className="animate-spin text-blue-500 text-2xl mx-auto" /></div>
            ) : (
              <div className="p-6 space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p className="text-gray-400 text-sm">Payment Reference</p>
                    <p className="text-white font-semibold">{viewingPayment.payment_reference || `PAY-${viewingPayment.id}`}</p>
                  </div>
                  <div>
                    <p className="text-gray-400 text-sm">Status</p>
                    <div className="mt-1">{getStatusBadge(viewingPayment.status)}</div>
                  </div>
                  <div>
                    <p className="text-gray-400 text-sm">Amount</p>
                    <p className="text-white font-semibold text-lg">{formatCurrency(viewingPayment.amount)}</p>
                  </div>
                  <div>
                    <p className="text-gray-400 text-sm">Currency</p>
                    <p className="text-white">{viewingPayment.currency || 'USD'}</p>
                  </div>
                  <div>
                    <p className="text-gray-400 text-sm">Payment Method</p>
                    <p className="text-white capitalize">{viewingPayment.payment_method?.replace('_', ' ') || 'N/A'}</p>
                  </div>
                  <div>
                    <p className="text-gray-400 text-sm">Payment Date</p>
                    <p className="text-white">{formatDate(viewingPayment.payment_date || viewingPayment.created_at)}</p>
                  </div>
                </div>
                <hr className="border-gray-700" />
                <div>
                  <p className="text-gray-400 text-sm mb-1">Customer</p>
                  <p className="text-white font-medium">{viewingPayment.booking?.customer?.full_name || viewingPayment.customer?.full_name || (viewingPayment.booking?.customer?.first_name ? `${viewingPayment.booking.customer.first_name} ${viewingPayment.booking.customer.last_name || ''}`.trim() : null) || viewingPayment.customer_name || 'Unknown'}</p>
                  <p className="text-gray-400 text-sm">{viewingPayment.booking?.customer?.email || viewingPayment.customer?.email || ''}</p>
                </div>
                <div>
                  <p className="text-gray-400 text-sm mb-1">Booking Reference</p>
                  <p className="text-white">{viewingPayment.booking?.reference_number || viewingPayment.booking?.booking_reference || `BK-${viewingPayment.booking_id}` || 'N/A'}</p>
                </div>
                {viewingPayment.transaction_id && (
                  <div>
                    <p className="text-gray-400 text-sm mb-1">Transaction ID</p>
                    <p className="text-white font-mono text-sm">{viewingPayment.transaction_id}</p>
                  </div>
                )}
                {viewingPayment.notes && (
                  <div>
                    <p className="text-gray-400 text-sm mb-1">Notes</p>
                    <p className="text-white">{viewingPayment.notes}</p>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Record Payment Modal */}
      {showRecordModal && (
        <div className="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
          <div className="bg-[#1a1f28] border border-gray-700 rounded-xl w-full max-w-lg max-h-[80vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-700 flex items-center justify-between">
              <h3 className="text-xl font-bold text-white">Record Payment</h3>
              <button onClick={() => setShowRecordModal(false)} className="text-gray-400 hover:text-white">
                <FaTimes />
              </button>
            </div>
            <form onSubmit={handleRecordPayment} className="p-6 space-y-4">
              <div>
                <label className="block text-gray-400 text-sm mb-1">Booking *</label>
                <select
                  value={recordForm.booking_id}
                  onChange={(e) => handleBookingSelect(e.target.value)}
                  required
                  className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                >
                  <option value="">Select a booking...</option>
                  {bookings.map(b => (
                    <option key={b.id} value={b.id}>
                      {b.reference_number || b.booking_reference || `BK-${b.id}`} — {b.customer?.full_name || (b.customer?.first_name ? `${b.customer.first_name} ${b.customer.last_name || ''}`.trim() : 'Unknown')}
                    </option>
                  ))}
                </select>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-gray-400 text-sm mb-1">Amount *</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    value={recordForm.amount}
                    onChange={(e) => setRecordForm(prev => ({ ...prev, amount: e.target.value }))}
                    required
                    className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="block text-gray-400 text-sm mb-1">Currency</label>
                  <select
                    value={recordForm.currency}
                    onChange={(e) => setRecordForm(prev => ({ ...prev, currency: e.target.value }))}
                    className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  >
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="GBP">GBP</option>
                    <option value="ZAR">ZAR</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-gray-400 text-sm mb-1">Payment Method</label>
                <select
                  value={recordForm.payment_method}
                  onChange={(e) => setRecordForm(prev => ({ ...prev, payment_method: e.target.value }))}
                  className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                >
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="mobile_money">Mobile Money</option>
                  <option value="credit_card">Credit Card</option>
                  <option value="cash">Cash</option>
                </select>
              </div>
              <div>
                <label className="block text-gray-400 text-sm mb-1">Transaction ID</label>
                <input
                  type="text"
                  value={recordForm.transaction_id}
                  onChange={(e) => setRecordForm(prev => ({ ...prev, transaction_id: e.target.value }))}
                  className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  placeholder="External transaction reference"
                />
              </div>
              <div>
                <label className="block text-gray-400 text-sm mb-1">Notes</label>
                <textarea
                  value={recordForm.notes}
                  onChange={(e) => setRecordForm(prev => ({ ...prev, notes: e.target.value }))}
                  rows={3}
                  className="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500 resize-none"
                  placeholder="Payment notes..."
                />
              </div>
              <div className="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setShowRecordModal(false)}
                  className="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
                >
                  <FaDollarSign />
                  Record Payment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default FinanceDashboard;
