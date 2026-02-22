import React, { useState, useEffect } from 'react';
import { 
  FaShip, 
  FaFileContract, 
  FaFileUpload, 
  FaFileDownload, 
  FaMoneyBillWave,
  FaBell,
  FaMapMarkerAlt,
  FaCheckCircle,
  FaClock,
  FaQuoteLeft,
  FaEye,
  FaCheck,
  FaTimes,
  FaUser,
  FaEdit,
  FaCreditCard,
  FaHistory,
  FaSearch,
  FaSignOutAlt
} from 'react-icons/fa';
import { showAlert } from '../utils/sweetAlert';
import { useCustomerAuth } from '../context/CustomerAuthContext';
import { useNavigate } from 'react-router-dom';
import authService from '../services/authService';
import * as customerService from '../services/customerService';
import DocumentUpload from '../components/Customer/DocumentUpload';
import TrackingMap from '../components/Customer/TrackingMap';
import TrackingTimeline from '../components/Tracking/TrackingTimeline';
import ManageBooking from './ManageBooking';

const CustomerPortal = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [quotes, setQuotes] = useState([]);
  const [bookings, setBookings] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [payments, setPayments] = useState([]);
  const [shipments, setShipments] = useState([]);
  const [dashboardStats, setDashboardStats] = useState({});
  const [customerProfile, setCustomerProfile] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState(null);
  const [activeTrackingView, setActiveTrackingView] = useState('map');
  const [isEditingProfile, setIsEditingProfile] = useState(false);
  const [editFormData, setEditFormData] = useState({});
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [passwordFormData, setPasswordFormData] = useState({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  });
  const { customer, isAuthenticated, logout } = useCustomerAuth();
  const navigate = useNavigate();

  // Load customer data on component mount
  useEffect(() => {
    if (isAuthenticated) {
      loadAllCustomerData();
    }
  }, [isAuthenticated]);

  const loadAllCustomerData = async () => {
    setIsLoading(true);
    try {
      // Load all customer data using the comprehensive service
      const [
        profileData,
        quotesData,
        bookingsData,
        documentsData,
        paymentsData,
        shipmentsData,
        statsData
      ] = await Promise.all([
        customerService.getCustomerProfile().catch((err) => {
          console.error('Profile fetch error:', err);
          return { data: null };
        }),
        customerService.getCustomerQuotes().catch((err) => {
          console.error('Quotes fetch error:', err);
          return { data: [] };
        }),
        customerService.getCustomerBookings().catch((err) => {
          console.error('Bookings fetch error:', err);
          return { data: [] };
        }),
        customerService.getCustomerDocuments().catch((err) => {
          console.error('Documents fetch error:', err);
          return { data: [] };
        }),
        customerService.getCustomerPayments().catch((err) => {
          console.error('Payments fetch error:', err);
          return { data: [] };
        }),
        customerService.getCustomerShipments().catch((err) => {
          console.error('Shipments fetch error:', err);
          return { data: [] };
        }),
        customerService.getCustomerDashboardStats().catch((err) => {
          console.error('Stats fetch error:', err);
          return { data: {} };
        })
      ]);

      // Set all state with safe data
      const profile = profileData?.data || null;
      setCustomerProfile(profile);
      
      // Extract bookings - handle nested data structure
      const extractedBookings = bookingsData?.data?.data || bookingsData?.data || [];
      
      // Extract quotes - API returns {success: true, data: {data: [...quotes...], meta: {...}}}
      const extractedQuotes = quotesData?.data?.data || quotesData?.data || [];
      
      setQuotes(Array.isArray(extractedQuotes) ? extractedQuotes : []);
      setBookings(Array.isArray(extractedBookings) ? extractedBookings : []);
      setDocuments(Array.isArray(documentsData.data) ? documentsData.data : Array.isArray(documentsData) ? documentsData : []);
      setPayments(Array.isArray(paymentsData.data) ? paymentsData.data : Array.isArray(paymentsData) ? paymentsData : []);
      setShipments(Array.isArray(shipmentsData.data) ? shipmentsData.data : Array.isArray(shipmentsData) ? shipmentsData : []);
      setDashboardStats(statsData.data || statsData || {});

    } catch (error) {
      console.error('❌ Error loading customer data:', error);
      showAlert.error('Error', 'Failed to load your data. Please try again.');
      
      // Ensure all state is properly reset on error
      setCustomerProfile(null);
      setQuotes([]);
      setBookings([]);
      setDocuments([]);
      setPayments([]);
      setShipments([]);
      setDashboardStats({});
    } finally {
      setIsLoading(false);
    }
  };

  const confirmQuote = async (quoteId) => {
    setIsLoading(true);
    try {
      await customerService.confirmQuoteToBooking(quoteId);
      showAlert.success('Quote Confirmed!', 'Your quote has been converted to a booking successfully.');
      loadAllCustomerData(); // Reload all data
    } catch (error) {
      console.error('Error confirming quote:', error);
      showAlert.error('Confirmation Failed', error.response?.data?.message || 'Failed to confirm quote');
    } finally {
      setIsLoading(false);
    }
  };

  const handleDocumentUploadSuccess = () => {
    // Reload documents after successful upload
    customerService.getCustomerDocuments()
      .then(data => {
        const safeDocuments = Array.isArray(data.data) ? data.data : Array.isArray(data) ? data : [];
        setDocuments(safeDocuments);
      })
      .catch(error => console.error('Error reloading documents:', error));
  };

  const downloadDocument = async (documentId) => {
    try {
      await customerService.downloadCustomerDocument(documentId);
      showAlert.success('Success', 'Document downloaded successfully.');
    } catch (error) {
      console.error('Error downloading document:', error);
      showAlert.error('Download Failed', 'Failed to download document. Please try again.');
    }
  };

  const handleEditProfile = () => {
    setEditFormData({
      first_name: customerProfile?.first_name || '',
      last_name: customerProfile?.last_name || '',
      phone: customerProfile?.phone || '',
      address: customerProfile?.address || '',
      city: customerProfile?.city || '',
      postal_code: customerProfile?.postal_code || '',
    });
    setIsEditingProfile(true);
  };

  const handleCancelEdit = () => {
    setIsEditingProfile(false);
    setEditFormData({});
  };

  const handleSaveProfile = async () => {
    setIsLoading(true);
    try {
      console.log('💾 Saving profile...', editFormData);
      const response = await customerService.updateCustomerProfile(editFormData);
      console.log('✅ Profile save response:', response);
      
      showAlert.success('Success', 'Profile updated successfully!');
      setIsEditingProfile(false);
      
      // Reload profile data
      await loadAllCustomerData();
      console.log('🔄 Profile data reloaded');
    } catch (error) {
      console.error('❌ Error updating profile:', error);
      console.error('Error details:', error.response?.data);
      showAlert.error('Update Failed', error.response?.data?.message || 'Failed to update profile');
    } finally {
      setIsLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setEditFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleChangePassword = () => {
    setPasswordFormData({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });
    setIsChangingPassword(true);
  };

  const handleCancelPasswordChange = () => {
    setIsChangingPassword(false);
    setPasswordFormData({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });
  };

  const handlePasswordInputChange = (e) => {
    const { name, value } = e.target;
    setPasswordFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSavePassword = async () => {
    // Validation
    if (!passwordFormData.current_password) {
      showAlert.error('Validation Error', 'Current password is required');
      return;
    }
    if (!passwordFormData.new_password) {
      showAlert.error('Validation Error', 'New password is required');
      return;
    }
    if (passwordFormData.new_password.length < 8) {
      showAlert.error('Validation Error', 'New password must be at least 8 characters');
      return;
    }
    if (passwordFormData.new_password !== passwordFormData.new_password_confirmation) {
      showAlert.error('Validation Error', 'New passwords do not match');
      return;
    }

    setIsLoading(true);
    try {
      console.log('🔐 Changing password...');
      await customerService.changeCustomerPassword(passwordFormData);
      console.log('✅ Password changed successfully');
      
      showAlert.success('Success', 'Password changed successfully!');
      setIsChangingPassword(false);
      setPasswordFormData({
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      });
    } catch (error) {
      console.error('❌ Error changing password:', error);
      console.error('Error details:', error.response?.data);
      showAlert.error('Change Failed', error.response?.data?.message || 'Failed to change password');
    } finally {
      setIsLoading(false);
    }
  };

  // Handle logout
  const handleLogout = async () => {
    const confirmed = await showAlert.confirm(
      'Logout',
      'Are you sure you want to logout?',
      'question',
      'Yes, Logout',
      'Cancel'
    );
    
    if (confirmed) {
      logout();
      showAlert.success('Logged Out', 'You have been successfully logged out.');
      navigate('/');
    }
  };

  // Get customer display name
  const getCustomerName = () => {
    if (customerProfile) {
      return `${customerProfile.first_name || ''} ${customerProfile.last_name || ''}`.trim();
    }
    if (customer) {
      return `${customer.first_name || ''} ${customer.last_name || ''}`.trim();
    }
    return 'Customer';
  };

  // Use dashboard stats from service
  const getDisplayStats = () => {
    return {
      activeShipments: dashboardStats.activeShipments || 0,
      totalBalance: dashboardStats.totalBalance || '0.00',
      contractStatus: dashboardStats.contractStatus || 'None',
      totalQuotes: dashboardStats.totalQuotes || 0,
      approvedQuotes: dashboardStats.approvedQuotes || 0,
      pendingDocuments: dashboardStats.pendingDocuments || 0
    };
  };

  const displayStats = getDisplayStats();

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white">
        <div className="container-custom py-6">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl font-bold mb-1">Welcome back, {getCustomerName()}</h1>
              <p className="text-blue-200">Customer Portal</p>
            </div>
            <div className="flex items-center gap-3">
              <button className="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <FaBell />
                <span className="hidden md:inline">Notifications</span>
              </button>
              <button 
                onClick={handleLogout}
                className="bg-red-600/80 hover:bg-red-600 px-4 py-2 rounded-lg flex items-center gap-2 transition"
              >
                <FaSignOutAlt />
                <span className="hidden md:inline">Logout</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Tabs Navigation */}
      <div className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="container-custom">
          <nav className="flex gap-8">
            <button
              onClick={() => setActiveTab('profile')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'profile'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              Profile
            </button>
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'dashboard'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              Dashboard
            </button>
            <button
              onClick={() => setActiveTab('quotes')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'quotes'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              My Quotes
            </button>
            <button
              onClick={() => setActiveTab('manage-booking')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'manage-booking'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              <FaSearch className="inline mr-2" />
              Manage Booking
            </button>
            <button
              onClick={() => setActiveTab('tracking')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'tracking'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              Tracking
            </button>
            <button
              onClick={() => setActiveTab('contracts')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'contracts'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              Contracts
            </button>
            <button
              onClick={() => setActiveTab('documents')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'documents'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              Documents
            </button>
            <button
              onClick={() => setActiveTab('payments')}
              className={`py-4 px-2 border-b-2 font-medium transition ${
                activeTab === 'payments'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900'
              }`}
            >
              Payments
            </button>
          </nav>
        </div>
      </div>

      {/* Content */}
      <div className="container-custom py-8">
        {/* Profile Tab */}
        {activeTab === 'profile' && (
          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="flex items-center gap-3 mb-6">
                <FaUser className="text-blue-600 text-2xl" />
                <h2 className="text-2xl font-bold text-navy-900">My Profile</h2>
              </div>
              
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="mt-4 text-gray-600">Loading profile...</p>
                </div>
              ) : customerProfile ? (
                <div className="space-y-6">
                  {!isEditingProfile ? (
                    <>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.first_name || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.last_name || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.email || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.phone || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.country || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.city || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                          <p className="text-lg font-semibold text-gray-900">{customerProfile?.address || 'N/A'}</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                          <p className="text-lg font-semibold text-gray-900">
                            {customerProfile?.created_at ? customerService.formatDate(customerProfile.created_at) : 'N/A'}
                          </p>
                        </div>
                      </div>
                      
                      <div className="flex gap-4 pt-6 border-t">
                        <button 
                          onClick={handleEditProfile}
                          className="btn-primary px-6 py-2 flex items-center gap-2"
                        >
                          <FaEdit />
                          Edit Profile
                        </button>
                        <button 
                          onClick={handleChangePassword}
                          className="btn-outline px-6 py-2 flex items-center gap-2"
                        >
                          <FaCreditCard />
                          Change Password
                        </button>
                      </div>
                    </>
                  ) : (
                    <>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                          <input
                            type="text"
                            name="first_name"
                            value={editFormData.first_name}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                          <input
                            type="text"
                            name="last_name"
                            value={editFormData.last_name}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                          <input
                            type="email"
                            value={customerProfile.email}
                            disabled
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                          />
                          <p className="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                          <input
                            type="tel"
                            name="phone"
                            value={editFormData.phone}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                          <input
                            type="text"
                            name="country"
                            value={editFormData.country}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                          <input
                            type="text"
                            name="city"
                            value={editFormData.city}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                        <div className="md:col-span-2">
                          <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                          <input
                            type="text"
                            name="address"
                            value={editFormData.address}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                          <input
                            type="text"
                            name="postal_code"
                            value={editFormData.postal_code}
                            onChange={handleInputChange}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          />
                        </div>
                      </div>
                      
                      <div className="flex gap-4 pt-6 border-t">
                        <button 
                          onClick={handleSaveProfile}
                          disabled={isLoading}
                          className="btn-primary px-6 py-2 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <FaCheck />
                          {isLoading ? 'Saving...' : 'Save Changes'}
                        </button>
                        <button 
                          onClick={handleCancelEdit}
                          disabled={isLoading}
                          className="btn-outline px-6 py-2 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <FaTimes />
                          Cancel
                        </button>
                      </div>
                    </>
                  )}
                </div>
              ) : (
                <div className="text-center py-8">
                  <FaUser className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">Profile information not available.</p>
                  <button 
                    onClick={loadAllCustomerData}
                    className="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg"
                  >
                    Retry Loading Profile
                  </button>
                </div>
              )}
            </div>

            {/* Account Statistics */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <h3 className="text-xl font-bold text-navy-900 mb-6">Account Summary</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="text-center">
                  <div className="text-3xl font-bold text-blue-600">{displayStats.totalQuotes}</div>
                  <div className="text-gray-600">Total Quotes</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-green-600">{bookings.length}</div>
                  <div className="text-gray-600">Active Bookings</div>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-orange-600">{documents.length}</div>
                  <div className="text-gray-600">Documents</div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Change Password Modal */}
        {isChangingPassword && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
              <div className="flex items-center gap-3 mb-6">
                <FaCreditCard className="text-blue-600 text-2xl" />
                <h2 className="text-2xl font-bold text-navy-900">Change Password</h2>
              </div>
              
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                  <input
                    type="password"
                    name="current_password"
                    value={passwordFormData.current_password}
                    onChange={handlePasswordInputChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter current password"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                  <input
                    type="password"
                    name="new_password"
                    value={passwordFormData.new_password}
                    onChange={handlePasswordInputChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter new password (min 8 characters)"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                  <input
                    type="password"
                    name="new_password_confirmation"
                    value={passwordFormData.new_password_confirmation}
                    onChange={handlePasswordInputChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Confirm new password"
                  />
                </div>
              </div>
              
              <div className="flex gap-4 mt-6">
                <button 
                  onClick={handleSavePassword}
                  disabled={isLoading}
                  className="flex-1 btn-primary px-6 py-2 flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  <FaCheck />
                  {isLoading ? 'Changing...' : 'Change Password'}
                </button>
                <button 
                  onClick={handleCancelPasswordChange}
                  disabled={isLoading}
                  className="flex-1 btn-outline px-6 py-2 flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  <FaTimes />
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Dashboard Tab */}
        {activeTab === 'dashboard' && (
          <div className="space-y-6">
            {/* Quick Status */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white rounded-xl shadow-md p-6">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <FaShip className="text-blue-600 text-xl" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Active Shipments</p>
                    <p className="text-2xl font-bold text-navy-900">{displayStats.activeShipments}</p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-xl shadow-md p-6">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <FaFileContract className="text-green-600 text-xl" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Contract Status</p>
                    <p className="text-2xl font-bold text-green-600">{displayStats.contractStatus}</p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-xl shadow-md p-6">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <FaMoneyBillWave className="text-orange-600 text-xl" />
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Balance Due</p>
                    <p className="text-2xl font-bold text-orange-600">${displayStats.totalBalance}</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Current Bookings */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <h2 className="text-2xl font-bold text-navy-900 mb-6">Current Bookings</h2>
              
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="mt-4 text-gray-600">Loading your bookings...</p>
                </div>
              ) : !Array.isArray(bookings) || bookings.length === 0 ? (
                <div className="text-center py-8">
                  <FaShip className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">No active bookings found.</p>
                  <p className="text-sm text-gray-500 mt-2">Your confirmed quotes will appear here as bookings.</p>
                </div>
              ) : (
                <div className="space-y-6">
                  {Array.isArray(bookings) && bookings.slice(0, 3).map((booking) => (
                    <div key={booking.id} className="border rounded-lg p-6">
                      <div className="flex justify-between items-start mb-4">
                        <div>
                          <h3 className="text-xl font-bold text-gray-900">
                            Booking: {booking.booking_reference}
                          </h3>
                          <p className="text-gray-600">Recipient: {booking.recipient_name}</p>
                          <p className="text-sm text-gray-500">
                            {booking.recipient_city}, {booking.recipient_country}
                          </p>
                        </div>
                        <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${
                          booking.status === 'confirmed' 
                            ? 'bg-green-100 text-green-800' 
                            : booking.status === 'in_transit'
                            ? 'bg-blue-100 text-blue-800'
                            : booking.status === 'processing'
                            ? 'bg-yellow-100 text-yellow-800'
                            : booking.status === 'delivered'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-800'
                        }`}>
                          {booking.status?.toUpperCase() || 'PENDING'}
                        </span>
                      </div>
                      
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="flex items-center gap-3">
                          <FaMapMarkerAlt className="text-blue-600" />
                          <div>
                            <p className="text-sm text-gray-600">Status</p>
                            <p className="font-semibold capitalize">{booking.status || 'Processing'}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <FaClock className="text-blue-600" />
                          <div>
                            <p className="text-sm text-gray-600">Pickup Date</p>
                            <p className="font-semibold">{new Date(booking.pickup_date).toLocaleDateString()}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <FaMoneyBillWave className="text-green-600" />
                          <div>
                            <p className="text-sm text-gray-600">Amount</p>
                            <p className="font-semibold">${Number(booking.total_amount).toLocaleString()}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Quotes Tab */}
        {activeTab === 'quotes' && (
          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="flex items-center gap-3 mb-6">
                <FaQuoteLeft className="text-blue-600 text-2xl" />
                <h2 className="text-2xl font-bold text-navy-900">My Quotes</h2>
              </div>
              
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="mt-4 text-gray-600">Loading your quotes...</p>
                </div>
              ) : !Array.isArray(quotes) || quotes.length === 0 ? (
                <div className="text-center py-8">
                  <FaQuoteLeft className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">No quotes found. Request a quote to get started!</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {Array.isArray(quotes) && quotes.map((quote) => (
                    <div key={quote.id} className="border rounded-lg p-6 hover:shadow-md transition">
                      <div className="flex justify-between items-start mb-4">
                        <div>
                          <h3 className="text-lg font-bold text-gray-900">
                            {quote.vehicle_details?.make || 'Vehicle'} {quote.vehicle_details?.model || ''} {quote.vehicle_details?.year ? `(${quote.vehicle_details.year})` : ''}
                          </h3>
                          <p className="text-gray-600">Quote: {quote.quote_reference}</p>
                          <p className="text-sm text-gray-500">
                            {quote.route?.origin_city || 'Origin'}, {quote.route?.origin_country || ''} → {quote.route?.destination_city || 'Destination'}
                          </p>
                        </div>
                        <div className="text-right">
                          <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${
                            quote.status === 'approved' 
                              ? 'bg-green-100 text-green-800' 
                              : quote.status === 'pending'
                              ? 'bg-yellow-100 text-yellow-800'
                              : quote.status === 'converted'
                              ? 'bg-blue-100 text-blue-800'
                              : quote.status === 'expired'
                              ? 'bg-gray-100 text-gray-800'
                              : 'bg-red-100 text-red-800'
                          }`}>
                            {quote.status?.toUpperCase() || 'PENDING'}
                          </span>
                          <p className="text-2xl font-bold text-blue-600 mt-2">
                            ${Number(quote.total_amount || 0).toLocaleString()}
                          </p>
                        </div>
                      </div>
                      
                      <div className="flex justify-between items-center pt-4 border-t">
                        <div className="text-sm text-gray-500">
                          <p>Valid until: {new Date(quote.valid_until).toLocaleDateString()}</p>
                          <p>Created: {new Date(quote.created_at).toLocaleDateString()}</p>
                        </div>
                        
                        {quote.status === 'approved' && (
                          <div className="flex gap-2">
                            <button 
                              className="btn-outline px-4 py-2 text-sm flex items-center gap-2"
                              onClick={() => {/* View details */}}
                            >
                              <FaEye />
                              View Details
                            </button>
                            <button 
                              className="btn-primary px-4 py-2 text-sm flex items-center gap-2"
                              onClick={() => confirmQuote(quote.id)}
                              disabled={isLoading}
                            >
                              <FaCheck />
                              Confirm Booking
                            </button>
                          </div>
                        )}
                        
                        {quote.status === 'pending' && (
                          <div className="text-sm text-yellow-600">
                            <p>⏳ Awaiting approval...</p>
                          </div>
                        )}
                        
                        {quote.status === 'converted' && (
                          <div className="text-sm text-blue-600">
                            <p>✅ Converted to booking</p>
                          </div>
                        )}
                        
                        {quote.status === 'expired' && (
                          <div className="text-sm text-gray-600">
                            <p>⏰ Quote expired</p>
                          </div>
                        )}
                        
                        {quote.status === 'rejected' && (
                          <div className="text-sm text-red-600">
                            <p>❌ Quote was rejected</p>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Manage Booking Tab */}
        {activeTab === 'manage-booking' && (
          <div className="space-y-6">
            <ManageBooking />
          </div>
        )}

        {/* Tracking Tab */}
        {activeTab === 'tracking' && (
          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-md p-8">
              <h2 className="text-2xl font-bold text-navy-900 mb-6">Shipment Tracking</h2>
              
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="mt-4 text-gray-600">Loading tracking information...</p>
                </div>
              ) : !Array.isArray(bookings) || bookings.length === 0 ? (
                <div className="text-center py-8">
                  <FaShip className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">No shipments to track.</p>
                  <p className="text-sm text-gray-500 mt-2">Your confirmed bookings will appear here for tracking.</p>
                </div>
              ) : (
                <div className="space-y-6">
                  {/* Booking Selection */}
                  <div className="border-b pb-4">
                    <h3 className="text-lg font-semibold text-gray-900 mb-3">Select Shipment to Track</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      {bookings.map((booking) => (
                        <div
                          key={booking.id}
                          onClick={() => setSelectedBooking(booking)}
                          className={`p-4 border rounded-lg cursor-pointer transition-all hover:shadow-md ${
                            selectedBooking?.id === booking.id
                              ? 'border-blue-500 bg-blue-50 shadow-md'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="flex items-center justify-between mb-2">
                            <h4 className="font-semibold text-gray-900 text-sm">
                              {booking.booking_reference}
                            </h4>
                            <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                              booking.status === 'confirmed' 
                                ? 'bg-green-100 text-green-800' 
                                : booking.status === 'in_transit'
                                ? 'bg-blue-100 text-blue-800'
                                : booking.status === 'processing'
                                ? 'bg-yellow-100 text-yellow-800'
                                : 'bg-gray-100 text-gray-800'
                            }`}>
                              {booking.status?.toUpperCase() || 'PENDING'}
                            </span>
                          </div>
                          <p className="text-sm text-gray-600 mb-1">
                            {booking.vehicle_make} {booking.vehicle_model} ({booking.vehicle_year})
                          </p>
                          <p className="text-xs text-gray-500">
                            {booking.origin_city} → {booking.destination_city}
                          </p>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Tracking Content */}
                  {selectedBooking ? (
                    <div className="space-y-6">
                      {/* Shipment Overview */}
                      <div className="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-6">
                        <div className="flex items-center justify-between">
                          <div>
                            <h3 className="text-xl font-bold mb-2">
                              {selectedBooking.vehicle_make} {selectedBooking.vehicle_model} ({selectedBooking.vehicle_year})
                            </h3>
                            <p className="text-blue-100 mb-1">
                              Booking: {selectedBooking.booking_reference}
                            </p>
                            <p className="text-blue-100">
                              {selectedBooking.origin_city}, {selectedBooking.origin_country} → {selectedBooking.destination_city}
                            </p>
                          </div>
                          <div className="text-right">
                            <div className="text-2xl font-bold">
                              {selectedBooking.status?.toUpperCase() || 'PROCESSING'}
                            </div>
                            <div className="text-blue-100 text-sm">Current Status</div>
                          </div>
                        </div>
                      </div>

                      {/* Tracking Tabs */}
                      <div className="bg-white border rounded-lg">
                        <div className="border-b">
                          <nav className="flex">
                            <button
                              onClick={() => setActiveTrackingView('map')}
                              className={`px-6 py-3 font-medium text-sm border-b-2 transition ${
                                activeTrackingView === 'map'
                                  ? 'border-blue-500 text-blue-600'
                                  : 'border-transparent text-gray-500 hover:text-gray-700'
                              }`}
                            >
                              <FaMapMarkerAlt className="inline mr-2" />
                              Map View
                            </button>
                            <button
                              onClick={() => setActiveTrackingView('timeline')}
                              className={`px-6 py-3 font-medium text-sm border-b-2 transition ${
                                activeTrackingView === 'timeline'
                                  ? 'border-blue-500 text-blue-600'
                                  : 'border-transparent text-gray-500 hover:text-gray-700'
                              }`}
                            >
                              <FaClock className="inline mr-2" />
                              Timeline
                            </button>
                          </nav>
                        </div>

                        <div className="p-6">
                          {activeTrackingView === 'map' ? (
                            <TrackingMap 
                              booking={selectedBooking} 
                              shipment={shipments.find(s => s.id === selectedBooking.id)?.shipment}
                            />
                          ) : (
                            <TrackingTimeline 
                              shipmentId={selectedBooking.id}
                              isPublic={false}
                            />
                          )}
                        </div>
                      </div>

                      {/* Quick Actions */}
                      <div className="bg-white rounded-lg border p-6">
                        <h4 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h4>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                          <button className="flex items-center justify-center gap-2 p-3 border border-blue-200 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                            <FaFileDownload />
                            Download Documents
                          </button>
                          <button className="flex items-center justify-center gap-2 p-3 border border-green-200 text-green-600 rounded-lg hover:bg-green-50 transition">
                            <FaBell />
                            Set Notifications
                          </button>
                          <button className="flex items-center justify-center gap-2 p-3 border border-orange-200 text-orange-600 rounded-lg hover:bg-orange-50 transition">
                            <FaHistory />
                            View History
                          </button>
                        </div>
                      </div>
                    </div>
                  ) : (
                    <div className="text-center py-12">
                      <FaMapMarkerAlt className="text-gray-300 text-4xl mx-auto mb-4" />
                      <p className="text-gray-600">Select a shipment above to view tracking details</p>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        )}

        {activeTab === 'contracts' && (
          <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-md p-8">
              <h2 className="text-2xl font-bold text-navy-900 mb-6">Service Agreements</h2>
              
              {!Array.isArray(bookings) || bookings.length === 0 ? (
                <div className="text-center py-8">
                  <FaFileContract className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">No contracts found.</p>
                  <p className="text-sm text-gray-500 mt-2">Contracts will appear here when you confirm bookings.</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {Array.isArray(bookings) && bookings.map((booking) => (
                    <div key={booking.id} className="border-l-4 border-green-500 bg-green-50 p-6 mb-6">
                      <div className="flex items-start gap-4">
                        <FaCheckCircle className="text-green-600 text-2xl flex-shrink-0 mt-1" />
                        <div className="flex-1">
                          <p className="font-bold text-green-900 mb-1">Booking Confirmed</p>
                          <p className="text-green-800 text-sm">Booking: {booking.booking_reference}</p>
                          <p className="text-green-800 text-sm">
                            {booking.vehicle_make} {booking.vehicle_model} ({booking.vehicle_year})
                          </p>
                          <p className="text-green-800 text-sm">
                            Created: {new Date(booking.created_at).toLocaleDateString()}
                          </p>
                        </div>
                        <div className="text-right">
                          <p className="text-2xl font-bold text-green-600">
                            ${Number(booking.total_amount || 0).toLocaleString()}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Quote Details for latest booking */}
            {Array.isArray(bookings) && bookings.length > 0 && (
              <div className="bg-white rounded-xl shadow-md p-8">
                <h2 className="text-2xl font-bold text-navy-900 mb-6">Latest Booking Details</h2>
                <div className="space-y-4">
                  {bookings[0] && (
                    <>
                      <div className="flex justify-between py-3 border-b">
                        <span className="text-gray-700">Vehicle</span>
                        <span className="font-semibold">
                          {bookings[0].vehicle_make} {bookings[0].vehicle_model} ({bookings[0].vehicle_year})
                        </span>
                      </div>
                      <div className="flex justify-between py-3 border-b">
                        <span className="text-gray-700">Route</span>
                        <span className="font-semibold">
                          {bookings[0].origin_city}, {bookings[0].origin_country} → {bookings[0].destination_city}
                        </span>
                      </div>
                      <div className="flex justify-between py-3 border-b">
                        <span className="text-gray-700">Status</span>
                        <span className="font-semibold">{bookings[0].status || 'Processing'}</span>
                      </div>
                      <div className="flex justify-between py-4 bg-blue-50 -mx-8 px-8">
                        <span className="text-lg font-bold">Total Amount</span>
                        <span className="text-2xl font-bold text-blue-600">
                          ${Number(bookings[0].total_amount || 0).toLocaleString()}
                        </span>
                      </div>
                    </>
                  )}
                </div>
              </div>
            )}
          </div>
        )}

        {activeTab === 'documents' && (
          <div className="space-y-6">
            {/* Document Upload Section */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="flex items-center gap-3 mb-6">
                <FaFileUpload className="text-blue-600 text-2xl" />
                <h2 className="text-2xl font-bold text-navy-900">Upload Documents</h2>
              </div>
              
              <p className="text-gray-600 mb-6">
                Upload your personal documents for customs clearance and verification
              </p>

              <DocumentUpload 
                onUploadSuccess={handleDocumentUploadSuccess}
                bookingId={selectedBooking?.id}
              />
            </div>

            {/* My Documents Section */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="flex items-center gap-3 mb-6">
                <FaFileDownload className="text-green-600 text-2xl" />
                <h2 className="text-2xl font-bold text-navy-900">My Documents</h2>
              </div>
              
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="mt-4 text-gray-600">Loading documents...</p>
                </div>
              ) : !Array.isArray(documents) || documents.length === 0 ? (
                <div className="text-center py-8">
                  <FaFileDownload className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">No documents uploaded yet.</p>
                  <p className="text-sm text-gray-500 mt-2">Upload documents using the section above.</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {documents.map((document) => (
                    <div key={document.id} className="border rounded-lg p-4 hover:shadow-md transition">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                          <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <FaFileDownload className="text-blue-600" />
                          </div>
                          <div>
                            <h4 className="font-semibold text-gray-900">{document.original_name || document.file_name}</h4>
                            <p className="text-sm text-gray-600">
                              Type: {document.document_type} • 
                              Uploaded: {customerService.formatDate(document.created_at)}
                            </p>
                            {document.description && (
                              <p className="text-sm text-gray-500 mt-1">{document.description}</p>
                            )}
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                            customerService.getStatusColor(document.status)
                          }`}>
                            {document.status?.toUpperCase() || 'PENDING'}
                          </span>
                          <button
                            onClick={() => downloadDocument(document.id)}
                            className="btn-outline px-4 py-2 text-sm flex items-center gap-2"
                          >
                            <FaFileDownload />
                            Download
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {activeTab === 'payments' && (
          <div className="space-y-6">
            {/* Payment Overview */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <h2 className="text-2xl font-bold text-navy-900 mb-6">Payment Information</h2>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-blue-50 rounded-lg p-4">
                  <div className="text-2xl font-bold text-blue-600">${displayStats.totalBalance}</div>
                  <div className="text-blue-800 text-sm">Outstanding Balance</div>
                </div>
                <div className="bg-green-50 rounded-lg p-4">
                  <div className="text-2xl font-bold text-green-600">{payments.filter(p => p.status === 'paid').length}</div>
                  <div className="text-green-800 text-sm">Completed Payments</div>
                </div>
                <div className="bg-orange-50 rounded-lg p-4">
                  <div className="text-2xl font-bold text-orange-600">{payments.filter(p => p.status === 'pending').length}</div>
                  <div className="text-orange-800 text-sm">Pending Payments</div>
                </div>
              </div>

              {/* Payment History */}
              {isLoading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p className="mt-4 text-gray-600">Loading payment information...</p>
                </div>
              ) : !Array.isArray(payments) || payments.length === 0 ? (
                <div className="text-center py-8">
                  <FaMoneyBillWave className="text-gray-300 text-6xl mx-auto mb-4" />
                  <p className="text-gray-600">No payment information available.</p>
                  <p className="text-sm text-gray-500 mt-2">Payment details will appear here when you have active bookings.</p>
                </div>
              ) : (
                <div className="space-y-4">
                  <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <FaHistory />
                    Payment History
                  </h3>
                  {payments.map((payment) => (
                    <div key={payment.id} className={`border-l-4 p-6 rounded ${
                      payment.status === 'paid' 
                        ? 'border-green-500 bg-green-50' 
                        : payment.status === 'partial'
                        ? 'border-yellow-500 bg-yellow-50'
                        : 'border-orange-500 bg-orange-50'
                    }`}>
                      <div className="flex justify-between items-center">
                        <div>
                          <p className="font-bold text-lg mb-1">
                            Payment #{payment.payment_reference || payment.id}
                          </p>
                          <p className="text-sm text-gray-600">
                            Method: {payment.payment_method || 'N/A'}
                          </p>
                          <p className="text-sm text-gray-600">
                            Date: {customerService.formatDate(payment.created_at)}
                          </p>
                          {payment.booking && (
                            <p className="text-sm text-gray-600">
                              Booking: {payment.booking.booking_reference}
                            </p>
                          )}
                        </div>
                        <div className="text-right">
                          <p className="text-2xl font-bold">${customerService.formatCurrency(payment.amount)}</p>
                          <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium mt-2 ${
                            customerService.getStatusColor(payment.status)
                          }`}>
                            {payment.status?.toUpperCase() || 'PENDING'}
                          </span>
                          {payment.status !== 'paid' && (
                            <button className="btn-primary px-6 py-2 mt-2 block">
                              Pay Now
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {/* Booking Payments */}
              {Array.isArray(bookings) && bookings.length > 0 && (
                <div className="mt-8">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Booking Payments</h3>
                  <div className="space-y-4">
                    {bookings.map((booking) => (
                      <div key={booking.id} className={`border-l-4 p-6 rounded ${
                        booking.payment_status === 'paid' 
                          ? 'border-green-500 bg-green-50' 
                          : booking.payment_status === 'partial'
                          ? 'border-yellow-500 bg-yellow-50'
                          : 'border-orange-500 bg-orange-50'
                      }`}>
                        <div className="flex justify-between items-center">
                          <div>
                            <p className="font-bold text-lg mb-1">
                              {booking.vehicle_make} {booking.vehicle_model} ({booking.vehicle_year})
                            </p>
                            <p className="text-sm text-gray-600">Booking: {booking.booking_reference}</p>
                            <p className="text-sm text-gray-600">
                              Created: {customerService.formatDate(booking.created_at)}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="text-2xl font-bold">${customerService.formatCurrency(booking.total_amount)}</p>
                            <span className={`inline-block px-3 py-1 rounded-full text-sm font-medium mt-2 ${
                              customerService.getStatusColor(booking.payment_status)
                            }`}>
                              {booking.payment_status?.toUpperCase() || 'PENDING'}
                            </span>
                            {booking.payment_status !== 'paid' && (
                              <button className="btn-primary px-6 py-2 mt-2 block">
                                Pay Now
                              </button>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div className="mt-6 p-4 bg-blue-50 rounded-lg">
                <p className="text-sm text-gray-700">
                  <strong>Note:</strong> All payments are processed securely. You can pay via bank transfer, mobile money, or credit card.
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CustomerPortal;