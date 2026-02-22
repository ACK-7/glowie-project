import React, { useState, useEffect } from 'react';
import { 
  FaUpload, 
  FaFileAlt, 
  FaDownload, 
  FaEye, 
  FaSpinner,
  FaCheckCircle,
  FaClock,
  FaTimes,
  FaExclamationCircle,
  FaSearch,
  FaSyncAlt,
  FaCheck,
  FaBan,
  FaFilter,
  FaSort,
  FaSortUp,
  FaSortDown,
  FaExclamationTriangle
} from 'react-icons/fa';
import { 
  getBookings, 
  getDocuments, 
  approveDocument, 
  rejectDocument, 
  downloadDocument,
  bulkApproveDocuments,
  bulkRejectDocuments
} from '../../services/adminService';
import { showAlert, showConfirm } from '../../utils/sweetAlert';
import DropdownMenu from '../../components/UI/DropdownMenu';
import DocumentViewModal from '../../components/Admin/DocumentViewModal';

const DocumentManager = () => {
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [bookings, setBookings] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [documentsLoading, setDocumentsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  
  // New states for enhanced functionality
  const [selectedDocuments, setSelectedDocuments] = useState([]);
  const [statusFilter, setStatusFilter] = useState('all');
  const [typeFilter, setTypeFilter] = useState('all');
  const [sortBy, setSortBy] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');
  const [showViewModal, setShowViewModal] = useState(false);
  const [selectedDocument, setSelectedDocument] = useState(null);
  const [bulkActionLoading, setBulkActionLoading] = useState(false);

  useEffect(() => {
    fetchBookings();
  }, []);

  useEffect(() => {
    if (selectedBooking) {
      fetchDocuments(selectedBooking);
    }
  }, [selectedBooking]);

  const fetchBookings = async () => {
    try {
      setLoading(true);
      setError(null);
      
      console.log('Fetching bookings...');
      const response = await getBookings({ per_page: 50, with: 'customer' });
      
      console.log('Bookings response:', response);
      
      // Handle different response structures
      let bookingsData = [];
      if (response?.success && response?.data) {
        if (response.data.data && Array.isArray(response.data.data)) {
          // Laravel pagination structure
          bookingsData = response.data.data;
        } else if (Array.isArray(response.data)) {
          bookingsData = response.data;
        }
      } else if (response?.data) {
        if (response.data.data && Array.isArray(response.data.data)) {
          // Laravel pagination structure
          bookingsData = response.data.data;
        } else if (Array.isArray(response.data)) {
          bookingsData = response.data;
        }
      } else if (Array.isArray(response)) {
        bookingsData = response;
      }
      
      console.log('Processed bookings data:', bookingsData);
      
      if (!Array.isArray(bookingsData)) {
        console.error('Bookings data is not an array:', bookingsData);
        setError('Invalid bookings data received from server');
        setBookings([]);
        return;
      }
      
      setBookings(bookingsData);
      
      // Auto-select first booking if available
      if (bookingsData.length > 0 && !selectedBooking) {
        const firstBooking = bookingsData[0];
        setSelectedBooking(firstBooking.id);
      }
      
    } catch (err) {
      console.error('Failed to fetch bookings:', err);
      const errorMessage = err.response?.data?.message || err.message || 'Unknown error occurred';
      setError(`Failed to load bookings: ${errorMessage}. Please ensure you are logged in as Admin.`);
      setBookings([]);
    } finally {
      setLoading(false);
    }
  };

  const fetchDocuments = async (bookingId) => {
    try {
      setDocumentsLoading(true);
      
      const response = await getDocuments({ booking_id: bookingId });
      
      // Handle the specific byBooking endpoint response structure
      let documentsData = [];
      if (response?.success && response?.data) {
        if (response.data.documents && Array.isArray(response.data.documents)) {
          // byBooking endpoint returns { documents: [], missing_documents: [], ... }
          documentsData = response.data.documents;
        } else if (Array.isArray(response.data)) {
          documentsData = response.data;
        }
      } else if (response?.data) {
        if (response.data.data && Array.isArray(response.data.data)) {
          // Laravel pagination structure
          documentsData = response.data.data;
        } else if (Array.isArray(response.data)) {
          documentsData = response.data;
        }
      } else if (Array.isArray(response)) {
        documentsData = response;
      }
      
      if (!Array.isArray(documentsData)) {
        console.error('Documents data is not an array:', documentsData);
        documentsData = [];
      }
      
      setDocuments(documentsData);
      
    } catch (err) {
      console.error('Failed to fetch documents:', err);
      const errorMessage = err.response?.data?.message || err.message || 'Unknown error occurred';
      setDocuments([]);
      showAlert('Error', `Failed to load documents: ${errorMessage}`, 'error');
    } finally {
      setDocumentsLoading(false);
    }
  };

  const handleRefresh = () => {
    if (selectedBooking) {
      fetchDocuments(selectedBooking);
    } else {
      fetchBookings();
    }
  };

  // New handler functions
  const handleViewDocument = (document) => {
    setSelectedDocument(document);
    setShowViewModal(true);
  };

  const handleCloseViewModal = () => {
    setShowViewModal(false);
    setSelectedDocument(null);
  };

  const handleDocumentUpdate = () => {
    // Refresh documents after update
    if (selectedBooking) {
      fetchDocuments(selectedBooking);
    }
  };

  const handleQuickApprove = async (document) => {
    if (document.status !== 'pending') {
      showAlert.error('Error', 'Only pending documents can be approved');
      return;
    }

    const confirmed = await showConfirm(
      'Approve Document',
      `Are you sure you want to approve this ${getDocumentTypeLabel(document.document_type)}?`,
      'question',
      'Yes, Approve',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      showAlert.loading('Approving...', 'Processing document approval...');
      
      const result = await approveDocument(document.id, 'Quick approval');
      
      showAlert.close();
      showAlert.success('Success!', 'Document approved successfully!');
      
      // Add a small delay to ensure backend processing is complete
      setTimeout(() => {
        handleDocumentUpdate();
      }, 500);
      
    } catch (error) {
      showAlert.close();
      console.error('Approval failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to approve document';
      showAlert.error('Approval Failed', errorMessage);
    }
  };

  const handleQuickReject = async (document) => {
    if (document.status !== 'pending') {
      showAlert.error('Error', 'Only pending documents can be rejected');
      return;
    }

    const { value: reason } = await showAlert.input(
      'Reject Document',
      'Please provide a reason for rejection:',
      'text',
      'Enter rejection reason...'
    );

    if (!reason || !reason.trim()) {
      showAlert.error('Error', 'Rejection reason is required');
      return;
    }

    try {
      showAlert.loading('Rejecting...', 'Processing document rejection...');
      await rejectDocument(document.id, reason);
      showAlert.close();
      showAlert.success('Success!', 'Document rejected successfully!');
      handleDocumentUpdate();
    } catch (error) {
      showAlert.close();
      console.error('Rejection failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to reject document';
      showAlert.error('Rejection Failed', errorMessage);
    }
  };

  const handleDownloadDocument = async (document) => {
    try {
      showAlert.loading('Downloading...', 'Preparing document download...');
      await downloadDocument(document.id);
      showAlert.close();
      showAlert.success('Success!', 'Document downloaded successfully!');
    } catch (error) {
      showAlert.close();
      console.error('Download failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to download document';
      showAlert.error('Download Failed', errorMessage);
    }
  };

  const handleSelectDocument = (documentId) => {
    setSelectedDocuments(prev => 
      prev.includes(documentId) 
        ? prev.filter(id => id !== documentId)
        : [...prev, documentId]
    );
  };

  const handleSelectAllDocuments = () => {
    const filteredDocs = getFilteredDocuments();
    if (selectedDocuments.length === filteredDocs.length) {
      setSelectedDocuments([]);
    } else {
      setSelectedDocuments(filteredDocs.map(doc => doc.id));
    }
  };

  const handleBulkApprove = async () => {
    if (selectedDocuments.length === 0) {
      showAlert.error('Error', 'Please select documents to approve');
      return;
    }

    const confirmed = await showConfirm(
      'Bulk Approve Documents',
      `Are you sure you want to approve ${selectedDocuments.length} selected documents?`,
      'question',
      'Yes, Approve All',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      setBulkActionLoading(true);
      showAlert.loading('Approving...', `Processing ${selectedDocuments.length} documents...`);
      
      await bulkApproveDocuments(selectedDocuments, 'Bulk approval');
      
      showAlert.close();
      showAlert.success('Success!', `${selectedDocuments.length} documents approved successfully!`);
      
      setSelectedDocuments([]);
      handleDocumentUpdate();
    } catch (error) {
      showAlert.close();
      console.error('Bulk approval failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to approve documents';
      showAlert.error('Bulk Approval Failed', errorMessage);
    } finally {
      setBulkActionLoading(false);
    }
  };

  const handleBulkReject = async () => {
    if (selectedDocuments.length === 0) {
      showAlert.error('Error', 'Please select documents to reject');
      return;
    }

    const { value: reason } = await showAlert.input(
      'Bulk Reject Documents',
      `Please provide a reason for rejecting ${selectedDocuments.length} documents:`,
      'text',
      'Enter rejection reason...'
    );

    if (!reason || !reason.trim()) {
      showAlert.error('Error', 'Rejection reason is required');
      return;
    }

    try {
      setBulkActionLoading(true);
      showAlert.loading('Rejecting...', `Processing ${selectedDocuments.length} documents...`);
      
      await bulkRejectDocuments(selectedDocuments, reason);
      
      showAlert.close();
      showAlert.success('Success!', `${selectedDocuments.length} documents rejected successfully!`);
      
      setSelectedDocuments([]);
      handleDocumentUpdate();
    } catch (error) {
      showAlert.close();
      console.error('Bulk rejection failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to reject documents';
      showAlert.error('Bulk Rejection Failed', errorMessage);
    } finally {
      setBulkActionLoading(false);
    }
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
    return sortDirection === 'asc' ? <FaSortUp className="text-blue-400" /> : <FaSortDown className="text-blue-400" />;
  };

  const getFilteredDocuments = () => {
    let filtered = [...documents];

    // Apply status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(doc => doc.status === statusFilter);
    }

    // Apply type filter
    if (typeFilter !== 'all') {
      filtered = filtered.filter(doc => doc.document_type === typeFilter);
    }

    // Apply search filter
    if (searchTerm) {
      const searchLower = searchTerm.toLowerCase();
      filtered = filtered.filter(doc =>
        doc.file_name?.toLowerCase().includes(searchLower) ||
        doc.document_type?.toLowerCase().includes(searchLower) ||
        getDocumentTypeLabel(doc.document_type).toLowerCase().includes(searchLower)
      );
    }

    // Apply sorting
    filtered.sort((a, b) => {
      let aValue = a[sortBy];
      let bValue = b[sortBy];

      if (sortBy === 'created_at' || sortBy === 'verified_at') {
        aValue = new Date(aValue || 0);
        bValue = new Date(bValue || 0);
      }

      if (aValue < bValue) return sortDirection === 'asc' ? -1 : 1;
      if (aValue > bValue) return sortDirection === 'asc' ? 1 : -1;
      return 0;
    });

    return filtered;
  };

  const getStatusBadge = (status) => {
    const config = {
      verified: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Verified', icon: FaCheckCircle },
      approved: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Approved', icon: FaCheckCircle },
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Pending', icon: FaClock },
      rejected: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Rejected', icon: FaTimes },
      requires_revision: { bg: 'bg-orange-900/30', text: 'text-orange-400', label: 'Needs Revision', icon: FaExclamationTriangle }
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

  const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const mb = bytes / (1024 * 1024);
    return `${mb.toFixed(2)} MB`;
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getDocumentTypeLabel = (type) => {
    const labels = {
      passport: 'Passport',
      license: 'Driving License',
      invoice: 'Purchase Invoice',
      insurance: 'Insurance Certificate',
      customs: 'Customs Declaration',
      other: 'Other Document'
    };
    return labels[type] || 'Unknown Document';
  };

  // Get dropdown items for each document
  const getDropdownItems = (doc) => {
    const items = [
      {
        label: 'View Details',
        icon: <FaEye className="w-4 h-4" />,
        onClick: () => handleViewDocument(doc)
      },
      {
        label: 'Download',
        icon: <FaDownload className="w-4 h-4" />,
        onClick: () => handleDownloadDocument(doc)
      }
    ];

    // Add approval/rejection options for pending documents
    if (doc.status === 'pending') {
      items.push({
        label: 'Approve',
        icon: <FaCheck className="w-4 h-4 text-green-400" />,
        onClick: () => handleQuickApprove(doc)
      });
      
      items.push({
        label: 'Reject',
        icon: <FaBan className="w-4 h-4 text-red-400" />,
        onClick: () => handleQuickReject(doc),
        danger: true
      });
    }

    return items;
  };

  const handleDropdownAction = (action) => {
    // Actions are handled by individual onClick functions
    console.log('Dropdown action:', action);
  };

  // Filter bookings based on search term
  const filteredBookings = bookings.filter(booking => {
    if (!searchTerm) return true;
    const searchLower = searchTerm.toLowerCase();
    const bookingRef = booking.booking_reference || booking.reference_number || `BK-${booking.id}`;
    const customerName = booking.customer?.first_name && booking.customer?.last_name 
      ? `${booking.customer.first_name} ${booking.customer.last_name}`
      : booking.customer?.name || '';
    
    return (
      bookingRef.toLowerCase().includes(searchLower) ||
      customerName.toLowerCase().includes(searchLower) ||
      booking.id?.toString().includes(searchLower)
    );
  });

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
          <h1 className="text-3xl font-bold text-white mb-2">Document Manager</h1>
          <p className="text-gray-400">Manage shipping documents and compliance verification</p>
        </div>
        <button
          onClick={handleRefresh}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <FaSyncAlt />
          Refresh
        </button>
      </div>

      {error ? (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <p className="text-red-400 mb-4">{error}</p>
          <button
            onClick={fetchBookings}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      ) : (
        <div className="grid lg:grid-cols-4 gap-6">
          {/* Bookings Sidebar */}
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-white font-bold">Bookings</h3>
              <span className="text-gray-400 text-sm">{bookings.length} total</span>
            </div>
            
            {/* Search */}
            <div className="relative mb-4">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search bookings..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
            </div>

            {filteredBookings.length === 0 ? (
              <p className="text-gray-400 text-sm text-center py-4">
                {searchTerm ? 'No bookings match your search' : 'No bookings found'}
              </p>
            ) : (
              <div className="space-y-2 max-h-96 overflow-y-auto">
                {filteredBookings.map((booking) => (
                  <button
                    key={booking.id}
                    onClick={() => setSelectedBooking(booking.id)}
                    className={`w-full text-left px-4 py-3 rounded-lg transition ${
                      selectedBooking === booking.id
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-800/30 text-gray-400 hover:bg-gray-800'
                    }`}
                  >
                    <div className="font-medium">
                      {booking.booking_reference || booking.reference_number || `BK-${booking.id}`}
                    </div>
                    <div className="text-xs opacity-75">
                      {booking.customer?.first_name && booking.customer?.last_name 
                        ? `${booking.customer.first_name} ${booking.customer.last_name}`
                        : booking.customer?.name || 'Unknown Customer'
                      }
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Main Content */}
          <div className="lg:col-span-3 space-y-6">
            {/* Documents Section */}
            <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-xl font-bold text-white">
                  Documents for {selectedBooking ? 
                    (() => {
                      const booking = bookings.find(b => b.id === selectedBooking);
                      return booking?.booking_reference || booking?.reference_number || `BK-${selectedBooking}`;
                    })() : 
                    'Select Booking'
                  }
                </h2>
                
                {selectedBooking && (
                  <div className="flex items-center gap-3">
                    {/* Bulk Actions */}
                    {selectedDocuments.length > 0 && (
                      <div className="flex items-center gap-2">
                        <span className="text-sm text-gray-400">
                          {selectedDocuments.length} selected
                        </span>
                        <button
                          onClick={handleBulkApprove}
                          disabled={bulkActionLoading}
                          className="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors disabled:opacity-50"
                        >
                          <FaCheck className="inline mr-1" />
                          Approve All
                        </button>
                        <button
                          onClick={handleBulkReject}
                          disabled={bulkActionLoading}
                          className="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition-colors disabled:opacity-50"
                        >
                          <FaBan className="inline mr-1" />
                          Reject All
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>

              {/* Filters and Search */}
              {selectedBooking && (
                <div className="mb-6 space-y-4">
                  <div className="flex flex-col lg:flex-row gap-4">
                    {/* Search */}
                    <div className="flex-1">
                      <div className="relative">
                        <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                        <input
                          type="text"
                          placeholder="Search documents..."
                          value={searchTerm}
                          onChange={(e) => setSearchTerm(e.target.value)}
                          className="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        />
                      </div>
                    </div>

                    {/* Filters */}
                    <div className="flex gap-3">
                      <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                      >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                        <option value="requires_revision">Needs Revision</option>
                      </select>

                      <select
                        value={typeFilter}
                        onChange={(e) => setTypeFilter(e.target.value)}
                        className="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                      >
                        <option value="all">All Types</option>
                        <option value="passport">Passport</option>
                        <option value="license">License</option>
                        <option value="invoice">Invoice</option>
                        <option value="insurance">Insurance</option>
                        <option value="customs">Customs</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                  </div>
                </div>
              )}

              {!selectedBooking ? (
                <div className="text-center py-12 text-gray-400">
                  <FaFileAlt className="text-4xl mx-auto mb-4 opacity-50" />
                  <p>Select a booking to view its documents</p>
                </div>
              ) : documentsLoading ? (
                <div className="flex items-center justify-center py-12">
                  <FaSpinner className="animate-spin text-blue-500 text-2xl" />
                </div>
              ) : !Array.isArray(documents) || documents.length === 0 ? (
                <div className="text-center py-12 text-gray-400">
                  <FaFileAlt className="text-4xl mx-auto mb-4 opacity-50" />
                  <p>No documents found for this booking</p>
                  <p className="text-sm mt-2">Customer needs to upload required documents</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {/* Table Header */}
                  <div className="bg-gray-800/30 border border-gray-700 rounded-lg p-3">
                    <div className="flex items-center gap-4">
                      <input
                        type="checkbox"
                        checked={selectedDocuments.length === getFilteredDocuments().length && getFilteredDocuments().length > 0}
                        onChange={handleSelectAllDocuments}
                        className="w-4 h-4 text-blue-600 bg-gray-800 border-gray-600 rounded focus:ring-blue-500"
                      />
                      <div className="flex-1 grid grid-cols-12 gap-4 text-xs font-medium text-gray-400 uppercase tracking-wider">
                        <div className="col-span-4 cursor-pointer hover:text-white transition-colors" onClick={() => handleSort('document_type')}>
                          <div className="flex items-center gap-1">
                            Document Type
                            {getSortIcon('document_type')}
                          </div>
                        </div>
                        <div className="col-span-2 cursor-pointer hover:text-white transition-colors" onClick={() => handleSort('status')}>
                          <div className="flex items-center gap-1">
                            Status
                            {getSortIcon('status')}
                          </div>
                        </div>
                        <div className="col-span-2 cursor-pointer hover:text-white transition-colors" onClick={() => handleSort('file_size')}>
                          <div className="flex items-center gap-1">
                            Size
                            {getSortIcon('file_size')}
                          </div>
                        </div>
                        <div className="col-span-2 cursor-pointer hover:text-white transition-colors" onClick={() => handleSort('created_at')}>
                          <div className="flex items-center gap-1">
                            Upload Date
                            {getSortIcon('created_at')}
                          </div>
                        </div>
                        <div className="col-span-2">Actions</div>
                      </div>
                    </div>
                  </div>

                  {/* Document List */}
                  {getFilteredDocuments().map((doc) => (
                    <div key={doc.id} className="bg-gray-800/30 border border-gray-700 rounded-lg p-4 hover:border-gray-600 transition">
                      <div className="flex items-center gap-4">
                        <input
                          type="checkbox"
                          checked={selectedDocuments.includes(doc.id)}
                          onChange={() => handleSelectDocument(doc.id)}
                          className="w-4 h-4 text-blue-600 bg-gray-800 border-gray-600 rounded focus:ring-blue-500"
                        />
                        
                        <div className="flex-1 grid grid-cols-12 gap-4 items-center">
                          <div className="col-span-4 flex items-center gap-3">
                            <div className="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                              <FaFileAlt className="text-blue-400" />
                            </div>
                            <div>
                              <p className="text-white font-medium">
                                {getDocumentTypeLabel(doc.document_type)}
                              </p>
                              <p className="text-gray-400 text-sm">
                                {doc.file_name || 'Document'}
                              </p>
                            </div>
                          </div>
                          
                          <div className="col-span-2">
                            {getStatusBadge(doc.status || 'pending')}
                          </div>
                          
                          <div className="col-span-2">
                            <p className="text-gray-300 text-sm">
                              {formatFileSize(doc.file_size)}
                            </p>
                            <p className="text-gray-500 text-xs">
                              {doc.mime_type || 'Unknown'}
                            </p>
                          </div>
                          
                          <div className="col-span-2">
                            <p className="text-gray-300 text-sm">
                              {formatDate(doc.created_at)}
                            </p>
                          </div>
                          
                          <div className="col-span-2">
                            <DropdownMenu
                              items={getDropdownItems(doc)}
                              onItemClick={handleDropdownAction}
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Required Documents Checklist */}
            <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
              <h3 className="text-white font-bold mb-4">Required Documents Checklist</h3>
              <div className="grid md:grid-cols-2 gap-3">
                {[
                  { type: 'passport', label: 'Passport' },
                  { type: 'license', label: 'Driving License' }, 
                  { type: 'invoice', label: 'Purchase Invoice' },
                  { type: 'insurance', label: 'Insurance Certificate' },
                  { type: 'customs', label: 'Customs Declaration' },
                  { type: 'other', label: 'Other Documents' }
                ].map((item, i) => {
                  const isCompleted = documents.some(doc => 
                    doc.document_type === item.type && 
                    (doc.status === 'approved' || doc.status === 'verified')
                  );
                  const isPending = documents.some(doc => 
                    doc.document_type === item.type && doc.status === 'pending'
                  );
                  const isRejected = documents.some(doc => 
                    doc.document_type === item.type && doc.status === 'rejected'
                  );
                  
                  return (
                    <div key={i} className="flex items-center gap-3">
                      <div className={`w-5 h-5 rounded flex items-center justify-center ${
                        isCompleted ? 'bg-green-600' : 
                        isPending ? 'bg-yellow-600' :
                        isRejected ? 'bg-red-600' : 'bg-gray-700'
                      }`}>
                        {isCompleted && <FaCheckCircle className="text-white text-xs" />}
                        {isPending && <FaClock className="text-white text-xs" />}
                        {isRejected && <FaTimes className="text-white text-xs" />}
                      </div>
                      <span className={`${
                        isCompleted ? 'text-green-400' : 
                        isPending ? 'text-yellow-400' :
                        isRejected ? 'text-red-400' : 'text-gray-400'
                      }`}>
                        {item.label}
                        {isPending && <span className="text-xs ml-2">(Pending Review)</span>}
                        {isRejected && <span className="text-xs ml-2">(Rejected)</span>}
                      </span>
                    </div>
                  );
                })}
              </div>
              
              {selectedBooking && (
                <div className="mt-4 pt-4 border-t border-gray-700">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-400">
                      Progress: {documents.filter(doc => doc.status === 'approved' || doc.status === 'verified').length} of 6 completed
                    </span>
                    <span className="text-gray-400">
                      {documents.filter(doc => doc.status === 'pending').length} pending review
                    </span>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Document View Modal */}
      {showViewModal && selectedDocument && (
        <DocumentViewModal
          document={selectedDocument}
          onClose={handleCloseViewModal}
          onUpdate={handleDocumentUpdate}
        />
      )}
    </div>
  );
};

export default DocumentManager;
