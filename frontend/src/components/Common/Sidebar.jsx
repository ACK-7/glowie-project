import React from 'react';
import { Link } from 'react-router-dom';

const Sidebar = () => {
  return (
    <div className="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col">
      <div className="p-6 border-b border-gray-800">
        <h2 className="text-xl font-bold">SWG Admin</h2>
      </div>
      
      <nav className="flex-1 p-4 space-y-2">
        <Link to="/admin" className="block px-4 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">
          Dashboard
        </Link>
        <Link to="/admin/bookings" className="block px-4 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">
          Bookings
        </Link>
        <Link to="/admin/shipments" className="block px-4 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">
          Shipments
        </Link>
        <Link to="/admin/documents" className="block px-4 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">
          Documents
        </Link>
        <Link to="/admin/users" className="block px-4 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">
          Users
        </Link>
        <Link to="/admin/settings" className="block px-4 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">
          Settings
        </Link>
      </nav>
      
      <div className="p-4 border-t border-gray-800">
        <button className="w-full text-left px-4 py-2 text-red-400 hover:text-red-300">
          Logout
        </button>
      </div>
    </div>
  );
};

export default Sidebar;
