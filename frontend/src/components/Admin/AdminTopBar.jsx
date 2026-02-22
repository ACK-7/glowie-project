import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaSearch, FaBell, FaUser, FaChevronDown, FaSignOutAlt, FaCog } from 'react-icons/fa';
import { useAuth } from '../../context/AuthContext';
import { getDashboardStats } from '../../services/adminService';

const AdminTopBar = () => {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);

  const [searchQuery, setSearchQuery] = useState('');
  const [unreadCount, setUnreadCount] = useState(0);

  const fetchUnreadCount = async () => {
    try {
      const response = await getDashboardStats();
      // Handle different response structures
      if (response) {
        if (response.success && response.data?.overview) {
          setUnreadCount(response.data.overview.unread_messages || 0);
        } else if (response.data?.overview) {
          setUnreadCount(response.data.overview.unread_messages || 0);
        } else if (response.overview) {
          setUnreadCount(response.overview.unread_messages || 0);
        }
      }
    } catch (error) {
      console.error('Failed to fetch unread notification count:', error);
      // Set to 0 on error to prevent UI issues
      setUnreadCount(0);
    }
  };

  useEffect(() => {
    fetchUnreadCount();
    const interval = setInterval(fetchUnreadCount, 60000); // Poll every minute
    return () => clearInterval(interval);
  }, []);

  const notifications = [
    { id: 1, message: 'Real-time notifications enabled', time: 'Just now', unread: unreadCount > 0 }
  ];

  const handleLogout = async () => {
    await logout();
    navigate('/admin/login');
  };

  const handleSettings = () => {
    setShowUserMenu(false);
    navigate('/admin/settings');
  };

  const handleSearch = (e) => {
    if (e.key === 'Enter' && searchQuery.trim()) {
      navigate(`/admin/bookings?search=${encodeURIComponent(searchQuery.trim())}`);
      setSearchQuery('');
    }
  };

  return (
    <div className="h-16 bg-[#1a1f28] border-b border-gray-800 fixed top-0 right-0 left-64 z-30 flex items-center justify-between px-6">
      {/* Search */}
      <div className="flex-1 max-w-xl">
        <div className="relative">
          <FaSearch className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            onKeyDown={handleSearch}
            placeholder="Search bookings, customers, shipments..."
            className="w-full pl-12 pr-4 py-2.5 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 placeholder-gray-500 focus:outline-none focus:border-blue-500 focus:bg-gray-800 transition-all"
          />
        </div>
      </div>

      {/* Right Section */}
      <div className="flex items-center gap-4 ml-6">
        {/* Notifications */}
        <div className="relative">
          <button
            onClick={() => setShowNotifications(!showNotifications)}
            className="relative p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-all"
          >
            <FaBell className="text-xl" />
            {unreadCount > 0 && (
              <span className="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                {unreadCount}
              </span>
            )}
          </button>

          {showNotifications && (
            <div className="absolute right-0 mt-2 w-80 bg-[#0f1419] border border-gray-700 rounded-lg shadow-2xl overflow-hidden">
              <div className="p-4 border-b border-gray-800">
                <h3 className="text-white font-semibold">Notifications</h3>
              </div>
              <div className="max-h-96 overflow-y-auto">
                {notifications.map((notif) => (
                  <div
                    key={notif.id}
                    className={`p-4 border-b border-gray-800 hover:bg-gray-800/50 cursor-pointer transition ${
                      notif.unread ? 'bg-blue-900/10' : ''
                    }`}
                  >
                    <p className="text-gray-200 text-sm mb-1">{notif.message}</p>
                    <span className="text-gray-500 text-xs">{notif.time}</span>
                  </div>
                ))}
              </div>
              <div className="p-3 border-t border-gray-800">
                <button 
                  onClick={() => {
                    setShowNotifications(false);
                    navigate('/admin/messages');
                  }}
                  className="text-blue-500 hover:text-blue-400 text-sm font-medium w-full text-center"
                >
                  View All Notifications
                </button>
              </div>
            </div>
          )}
        </div>

        {/* User Menu */}
        <div className="relative">
          <button
            onClick={() => setShowUserMenu(!showUserMenu)}
            className="flex items-center gap-3 px-3 py-2 hover:bg-gray-800 rounded-lg transition-all group"
          >
            <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
              <FaUser className="text-white text-sm" />
            </div>
            <div className="text-left hidden lg:block">
              <p className="text-white text-sm font-medium">
                {user ? (user.full_name || `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || 'Admin') : 'Loading...'}
              </p>
              <p className="text-gray-400 text-xs capitalize">
                {user?.role_label || user?.role || 'Admin'}
              </p>
            </div>
            <FaChevronDown className="text-gray-400 text-sm" />
          </button>

          {showUserMenu && (
            <div className="absolute right-0 mt-2 w-48 bg-[#0f1419] border border-gray-700 rounded-lg shadow-2xl overflow-hidden">
              <button 
                onClick={handleSettings}
                className="w-full px-4 py-3 text-left text-gray-200 hover:bg-gray-800 transition flex items-center gap-3"
              >
                <FaCog className="text-gray-400" />
                <span className="text-sm">Settings</span>
              </button>
              <div className="border-t border-gray-800"></div>
              <button 
                onClick={handleLogout}
                className="w-full px-4 py-3 text-left text-red-400 hover:bg-gray-800 transition flex items-center gap-3"
              >
                <FaSignOutAlt className="text-red-400" />
                <span className="text-sm">Logout</span>
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminTopBar;
