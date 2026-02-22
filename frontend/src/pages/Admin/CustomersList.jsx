import { useState, useEffect, useCallback } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { showAlert, showConfirm } from '../../utils/sweetAlert';
import { safeRender } from '../../utils/safeRender';
import ErrorBoundary from '../../components/Common/ErrorBoundary';
import {
  FaSearch,
  FaEye,
  FaEdit,
  FaTrash,
  FaPlus,
  FaChevronLeft,
  FaChevronRight,
  FaExclamationCircle,
  FaUser,
  FaUserCheck,
  FaUserTimes,
  FaEnvelope,
  FaSort,
  FaSortUp,
  FaSortDown,
  FaSyncAlt,
  FaTimes,
  FaKey
} from 'react-icons/fa';
import { 
  getCustomers, 
  getCustomer,
  updateCustomerStatus,
  verifyCustomer,
  deleteCustomer,
  resetCustomerPassword
} from '../../services/adminService';
import DropdownMenu from '../../components/UI/DropdownMenu';
import CustomerViewModal from '../../components/Admin/CustomerViewModal';
import CustomerEditModal from '../../components/Admin/CustomerEditModal';
import CustomerCreateModal from '../../components/Admin/CustomerCreateModal';

const CustomersList = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const initialSearch = searchParams.get('search') || '';
  
  // State Management
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [refreshing, setRefreshing] = useState(false);
  
  // Modal States
  const [showViewModal, setShowViewModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  
  // Pagination State
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [itemsPerPage, setItemsPerPage] = useState(15);
  
  // Filter State
  const [filters, setFilters] = useState({
    search: initialSearch,
    status: ''
  });
  
  // Sorting State
  const [sortBy, setSortBy] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');

  // Reset to page 1 when filters change
  useEffect(() => {
    setCurrentPage(1);
  }, [filters.status, filters.search]);

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

  // Fetch Customers with proper error handling
  const fetchCustomers = useCallback(async (showLoader = true) => {
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
        )
      };

      const customersResponse = await getCustomers(params);
      
      if (customersResponse.success !== false) {
        const { data, meta } = customersResponse;
        
        // Debug log to verify data
        console.log('Fetched customers:', {
          count: data?.length,
          statusFilter: params.status,
          customers: data?.map(c => ({ id: c.id, name: `${c.first_name} ${c.last_name}`, status: c.status }))
        });
        
        setCustomers(data || []);
        
        if (meta?.pagination) {
          setTotalPages(meta.pagination.last_page || 1);
          setTotalItems(meta.pagination.total || 0);
        } else {
          setTotalPages(1);
          setTotalItems(data?.length || 0);
        }
      } else {
        throw new Error(customersResponse.message || 'Failed to fetch customers');
      }

    } catch (err) {
      console.error('Failed to fetch customers:', err);
      
      if (err.response?.status === 401 || err.response?.status === 403) {
        setError('Authentication failed. Please log in again.');
        showAlert.error('Authentication Error', 'Your session has expired. Please log in again.');
        setTimeout(() => navigate('/admin/login'), 2000);
      } else {
        const errorMessage = err.response?.data?.message || err.message || 'Failed to load customers';
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
    fetchCustomers();
  }, [fetchCustomers]);
  // Handle sorting
  const handleSort = (column) => {
    if (sortBy === column) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(column);
      setSortDirection('asc');
    }
  };

  // Get sort icon
  const getSortIcon = (column) => {
    if (sortBy !== column) return <FaSort className="text-gray-500" />;
    return sortDirection === 'asc' ? <FaSortUp className="text-blue-400" /> : <FaSortDown className="text-blue-400" />;
  };

  // Handle actions with proper Sweet Alerts
  const handleView = async (customer) => {
    try {
      showAlert.loading('Loading...', 'Fetching customer details...');
      const response = await getCustomer(customer.id);
      showAlert.close();
      setSelectedCustomer(response.data || customer);
      setShowViewModal(true);
    } catch (error) {
      showAlert.close();
      console.error('Failed to fetch customer details:', error);
      showAlert.error('Error', 'Failed to load customer details. Showing basic information.');
      setSelectedCustomer(customer);
      setShowViewModal(true);
    }
  };

  const handleEdit = async (customer) => {
    try {
      showAlert.loading('Loading...', 'Fetching customer details...');
      const response = await getCustomer(customer.id);
      showAlert.close();
      setSelectedCustomer(response.data || customer);
      setShowEditModal(true);
    } catch (error) {
      showAlert.close();
      console.error('Failed to fetch customer details:', error);
      showAlert.error('Error', 'Failed to load customer details. Showing basic information.');
      setSelectedCustomer(customer);
      setShowEditModal(true);
    }
  };

  const handleCreate = () => {
    setShowCreateModal(true);
  };

  const handleDelete = async (customer) => {
    const confirmed = await showConfirm(
      'Delete Customer',
      `Are you sure you want to delete customer "${customer.first_name || customer.name || 'Unknown'}"? This action cannot be undone.`,
      'warning',
      'Yes, Delete',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      showAlert.loading('Deleting...', 'Please wait while we delete the customer.');
      await deleteCustomer(customer.id, 'Deleted by admin');
      showAlert.close();
      fetchCustomers(false);
      showAlert.success('Success!', 'Customer deleted successfully!');
    } catch (error) {
      showAlert.close();
      console.error('Delete failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to delete customer';
      showAlert.error('Error', `Delete failed: ${errorMessage}`);
    }
  };

  const handleStatusUpdate = async (customer, newStatus) => {
    try {
      console.log('Updating customer status:', { customerId: customer.id, oldStatus: customer.status, newStatus });
      showAlert.loading('Updating...', `Setting customer status to ${newStatus}...`);
      const response = await updateCustomerStatus(customer.id, newStatus);
      console.log('Status update response:', response);
      showAlert.close();
      
      // Refresh the customer list
      console.log('Refreshing customer list...');
      await fetchCustomers(false);
      console.log('Customer list refreshed');
      
      // Show success message
      await showAlert.success('Success!', `Customer status updated to ${newStatus}!`);
    } catch (error) {
      showAlert.close();
      console.error('Status update failed:', error);
      console.error('Error details:', {
        response: error.response?.data,
        status: error.response?.status,
        message: error.message
      });
      const errorMessage = error.response?.data?.message || error.message || 'Failed to update status';
      await showAlert.error('Error', `Status update failed: ${errorMessage}`);
    }
  };

  const handleVerify = async (customer) => {
    const confirmed = await showConfirm(
      'Verify Customer',
      `Are you sure you want to verify customer "${customer.first_name || customer.name || 'Unknown'}"?`,
      'info',
      'Yes, Verify',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      showAlert.loading('Verifying...', 'Please wait while we verify the customer.');
      await verifyCustomer(customer.id);
      showAlert.close();
      fetchCustomers(false);
      showAlert.success('Success!', 'Customer verified successfully!');
    } catch (error) {
      showAlert.close();
      console.error('Verification failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to verify customer';
      showAlert.error('Error', `Verification failed: ${errorMessage}`);
    }
  };

  const handleResetPassword = async (customer) => {
    const confirmed = await showConfirm(
      'Reset Password',
      `Are you sure you want to reset the password for "${customer.first_name || customer.name || 'Unknown'}"? A new temporary password will be generated and sent to their email.`,
      'warning',
      'Yes, Reset Password',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      showAlert.loading('Resetting Password...', 'Please wait while we reset the password.');
      const response = await resetCustomerPassword(customer.id);
      showAlert.close();
      
      // Show the temporary password to admin
      if (response.data?.temporary_password) {
        await showAlert.success(
          'Password Reset Successfully!',
          `Temporary Password: ${response.data.temporary_password}\n\nThis password has been sent to the customer's email. They will be required to change it on first login.`
        );
      } else {
        showAlert.success('Success!', 'Password reset successfully! A temporary password has been sent to the customer\'s email.');
      }
      
      fetchCustomers(false);
    } catch (error) {
      showAlert.close();
      console.error('Password reset failed:', error);
      const errorMessage = error.response?.data?.message || error.message || 'Failed to reset password';
      showAlert.error('Error', `Password reset failed: ${errorMessage}`);
    }
  };

  // Modal close handlers
  const closeViewModal = () => {
    setShowViewModal(false);
    setSelectedCustomer(null);
  };

  const closeEditModal = () => {
    setShowEditModal(false);
    setSelectedCustomer(null);
  };

  const closeCreateModal = () => {
    setShowCreateModal(false);
  };

  // Handle modal save actions
  const handleEditSave = () => {
    closeEditModal();
    fetchCustomers(false);
  };

  const handleCreateSave = () => {
    closeCreateModal();
    fetchCustomers(false);
  };

  // Get dropdown items for each customer
  const getDropdownItems = (customer) => {
    console.log('Getting dropdown items for customer:', { id: customer.id, status: customer.status });
    
    const items = [
      {
        label: 'View Details',
        icon: <FaEye className="w-4 h-4" />,
        onClick: () => handleView(customer)
      },
      {
        label: 'Edit Customer',
        icon: <FaEdit className="w-4 h-4" />,
        onClick: () => handleEdit(customer)
      }
    ];

    // Add status change options
    if (customer.status !== 'active') {
      console.log('Adding "Set Active" option');
      items.push({
        label: 'Set Active',
        icon: <FaUserCheck className="w-4 h-4" />,
        onClick: () => handleStatusUpdate(customer, 'active')
      });
    }
    
    if (customer.status !== 'inactive') {
      console.log('Adding "Set Inactive" option');
      items.push({
        label: 'Set Inactive',
        icon: <FaUser className="w-4 h-4" />,
        onClick: () => handleStatusUpdate(customer, 'inactive')
      });
    }
    
    if (customer.status !== 'suspended') {
      console.log('Adding "Suspend" option');
      items.push({
        label: 'Suspend',
        icon: <FaUserTimes className="w-4 h-4" />,
        onClick: () => handleStatusUpdate(customer, 'suspended')
      });
    }

    // Add verify option if not verified
    if (!customer.email_verified_at) {
      items.push({
        label: 'Verify Customer',
        icon: <FaUserCheck className="w-4 h-4 text-green-400" />,
        onClick: () => handleVerify(customer)
      });
    }

    // Add reset password option
    items.push({
      label: 'Reset Password',
      icon: <FaKey className="w-4 h-4 text-yellow-400" />,
      onClick: () => handleResetPassword(customer)
    });

    // Add delete option
    items.push({
      label: 'Delete',
      icon: <FaTrash className="w-4 h-4" />,
      onClick: () => handleDelete(customer),
      danger: true
    });

    console.log('Total dropdown items:', items.length);
    return items;
  };

  const handleDropdownAction = (action) => {
    // Actions are handled by individual onClick functions
    console.log('Dropdown action:', action);
  };
  // Utility functions
  const getStatusBadge = (status) => {
    const config = {
      active: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Active', icon: FaUserCheck },
      inactive: { bg: 'bg-gray-900/30', text: 'text-gray-400', label: 'Inactive', icon: FaUser },
      suspended: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Suspended', icon: FaUserTimes }
    };
    const c = config[status] || config.active;
    const IconComponent = c.icon;
    return (
      <span className={`${c.bg} ${c.text} px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1`}>
        <IconComponent className="text-xs" />
        {c.label}
      </span>
    );
  };

  const getVerificationBadge = (isVerified) => {
    return isVerified ? (
      <span className="bg-green-900/30 text-green-400 px-2 py-1 rounded-full text-xs font-semibold">
        Verified
      </span>
    ) : (
      <span className="bg-yellow-900/30 text-yellow-400 px-2 py-1 rounded-full text-xs font-semibold">
        Unverified
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (loading) {
    return (
      <div className="p-6 bg-[#0a0e13] min-h-screen text-white">
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          <p className="ml-4 text-gray-400">Loading customers...</p>
        </div>
      </div>
    );
  }
  return (
    <ErrorBoundary>
      <div className="p-6 bg-[#0a0e13] min-h-screen text-white">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white">Customers</h1>
          <p className="text-gray-400 text-sm">Manage customer accounts</p>
        </div>
        <button
          onClick={handleCreate}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <FaPlus />
          Add Customer
        </button>
      </div>
      {/* Search and Filters */}
      <div className="bg-gray-800 border border-gray-700 rounded-xl p-4 mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search customers..."
                value={searchDebounce}
                onChange={(e) => setSearchDebounce(e.target.value)}
                className="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
              {searchDebounce && (
                <button
                  onClick={() => setSearchDebounce('')}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white"
                >
                  <FaTimes />
                </button>
              )}
            </div>
          </div>

          {/* Basic Filters */}
          <div className="flex gap-3">
            <select
              value={filters.status}
              onChange={(e) => setFilters(prev => ({ ...prev, status: e.target.value }))}
              className="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>

            <button
              onClick={() => fetchCustomers(false)}
              disabled={refreshing}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50"
            >
              <FaSyncAlt className={refreshing ? 'animate-spin' : ''} />
              Refresh
            </button>
          </div>
        </div>
      </div>
      {/* Customers Table */}
      {error ? (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <FaExclamationCircle className="text-red-400 text-4xl mx-auto mb-4" />
          <p className="text-red-400 text-lg mb-2">Error Loading Customers</p>
          <p className="text-gray-400">{error}</p>
          <button
            onClick={() => fetchCustomers()}
            className="mt-4 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      ) : (
        <div className="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-800/50">
                <tr>
                  <th 
                    className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white transition-colors"
                    onClick={() => handleSort('first_name')}
                  >
                    <div className="flex items-center gap-2">
                      Customer
                      {getSortIcon('first_name')}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white transition-colors"
                    onClick={() => handleSort('status')}
                  >
                    <div className="flex items-center gap-2">
                      Status
                      {getSortIcon('status')}
                    </div>
                  </th>
                  <th 
                    className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white transition-colors"
                    onClick={() => handleSort('created_at')}
                  >
                    <div className="flex items-center gap-2">
                      Joined
                      {getSortIcon('created_at')}
                    </div>
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-800">
                {customers.length === 0 ? (
                  <tr>
                    <td colSpan="4" className="px-6 py-12 text-center text-gray-400">
                      <FaUser className="text-4xl mx-auto mb-4 opacity-50" />
                      <p className="text-lg mb-2">No customers found</p>
                      <p className="text-sm">Try adjusting your search or filters</p>
                    </td>
                  </tr>
                ) : (
                  customers.map((customer) => (
                    <tr key={`${customer.id}-${customer.status}`} className="hover:bg-gray-800/30 transition-colors">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                            <span className="text-white font-bold text-sm">
                              {(customer.first_name || customer.name || 'C')[0].toUpperCase()}
                            </span>
                          </div>
                          <div>
                            <p className="text-white font-medium">
                              {customer.first_name && customer.last_name 
                                ? `${safeRender(customer.first_name)} ${safeRender(customer.last_name)}`
                                : safeRender(customer.name, 'Unknown Customer')}
                            </p>
                            <div className="flex items-center gap-2 text-sm">
                              <FaEnvelope className="text-gray-500" />
                              <span className="text-gray-400">{safeRender(customer.email)}</span>
                              {getVerificationBadge(!!customer.email_verified_at)}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        {getStatusBadge(customer.status || 'active')}
                      </td>
                      <td className="px-6 py-4">
                        <span className="text-gray-300 text-sm">
                          {formatDate(customer.created_at)}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <DropdownMenu
                          items={getDropdownItems(customer)}
                          onItemClick={handleDropdownAction}
                        />
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}
      {/* Pagination */}
      {!error && totalPages > 1 && (
        <div className="bg-gray-800 border border-gray-700 rounded-xl p-4 mt-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <span className="text-gray-400 text-sm">
                Showing {((currentPage - 1) * itemsPerPage) + 1} to {Math.min(currentPage * itemsPerPage, totalItems)} of {totalItems.toLocaleString()} customers
              </span>
              <select
                value={itemsPerPage}
                onChange={(e) => setItemsPerPage(Number(e.target.value))}
                className="px-3 py-1 bg-gray-800 border border-gray-700 rounded text-white text-sm focus:outline-none focus:border-blue-500"
              >
                <option value={15}>15 per page</option>
                <option value={25}>25 per page</option>
                <option value={50}>50 per page</option>
                <option value={100}>100 per page</option>
              </select>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                disabled={currentPage === 1}
                className="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <FaChevronLeft />
              </button>
              
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
                        onClick={() => setCurrentPage(page)}
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

              <button
                onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                disabled={currentPage === totalPages}
                className="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <FaChevronRight />
              </button>
            </div>
          </div>
        </div>
      )}
      {/* Modals */}
      {showViewModal && selectedCustomer && (
        <CustomerViewModal
          customer={selectedCustomer}
          onClose={closeViewModal}
        />
      )}

      {showEditModal && selectedCustomer && (
        <CustomerEditModal
          customer={selectedCustomer}
          onClose={closeEditModal}
          onSave={handleEditSave}
        />
      )}

      {showCreateModal && (
        <CustomerCreateModal
          onClose={closeCreateModal}
          onSave={handleCreateSave}
        />
      )}
    </div>
    </ErrorBoundary>
  );
};

export default CustomersList;