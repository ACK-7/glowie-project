import React, { useState, useEffect } from 'react';
import { NavLink } from 'react-router-dom';
import {
  FaTachometerAlt,
  FaBox,
  FaShip,
  FaUsers,
  FaFileInvoiceDollar,
  FaDollarSign,
  FaFileAlt,
  FaChartBar,
  FaComments,
  FaCog,
  FaPlus,
  FaUserCog,
  FaCar
} from 'react-icons/fa';
import { getDashboardStats } from '../../services/adminService';

const AdminSidebar = () => {
  const [unreadMessages, setUnreadMessages] = useState(0);

  const fetchUnreadCount = async () => {
    try {
      const response = await getDashboardStats();
      if (response && response.success && response.data.overview) {
        setUnreadMessages(response.data.overview.unread_messages || 0);
      }
    } catch (error) {
      console.error('Failed to fetch unread message count:', error);
    }
  };

  useEffect(() => {
    fetchUnreadCount();
    const interval = setInterval(fetchUnreadCount, 60000); // Poll every minute
    return () => clearInterval(interval);
  }, []);

  const navItems = [
    { path: '/admin', label: 'Dashboard', icon: FaTachometerAlt, exact: true },
    { path: '/admin/bookings', label: 'Bookings', icon: FaBox },
    { path: '/admin/shipments', label: 'Tracking', icon: FaShip },
    { path: '/admin/customers', label: 'Customers', icon: FaUsers },
    { path: '/admin/quotes', label: 'Quotes', icon: FaFileInvoiceDollar },
    { path: '/admin/finance', label: 'Finance', icon: FaDollarSign },
    { path: '/admin/documents', label: 'Documents', icon: FaFileAlt },
    { path: '/admin/inventory', label: 'Car Inventory', icon: FaCar },
    { path: '/admin/reports', label: 'Reports', icon: FaChartBar },
    { path: '/admin/messages', label: 'Messages', icon: FaComments, badge: unreadMessages > 0 ? unreadMessages : null },
    { path: '/admin/users', label: 'User Management', icon: FaUserCog },
    { path: '/admin/settings', label: 'Settings', icon: FaCog }
  ];

  return (
    <aside className="w-64 bg-[#0a0e13] min-h-screen fixed left-0 top-0 flex flex-col border-r border-gray-800">
      {/* Logo */}
      <div className="p-6 border-b border-gray-800">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
            <span className="text-white font-bold text-xl">G</span>
          </div>
          <div>
            <h1 className="text-white font-bold text-lg">ShipWithGlowie</h1>
            <p className="text-gray-400 text-xs">Admin Panel</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 py-6 px-3 overflow-y-auto">
        <div className="space-y-1">
          {navItems.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              end={item.exact}
              className={({ isActive }) =>
                `flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 group relative ${
                  isActive
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/50'
                    : 'text-gray-400 hover:bg-gray-800/50 hover:text-white'
                }`
              }
            >
              <item.icon className="text-lg flex-shrink-0" />
              <span className="font-medium text-sm">{item.label}</span>
              
              {item.badge && (
                <span className="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[24px] text-center">
                  {item.badge}
                </span>
              )}
            </NavLink>
          ))}
        </div>
      </nav>

      {/* Create Button */}
      <div className="p-4 border-t border-gray-800">
        <button className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-all shadow-lg shadow-blue-600/30 hover:shadow-blue-600/50">
          <FaPlus />
          <span>New Booking</span>
        </button>
      </div>
    </aside>
  );
};

export default AdminSidebar;
