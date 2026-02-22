import React from 'react';
import { Outlet } from 'react-router-dom';
import Header from '../components/Common/Header'; // Reusing header for now, might simplify later

const ManageBookingLayout = () => {
  return (
    <div className="flex flex-col min-h-screen bg-gray-50">
      <Header />
      <main className="flex-grow container mx-auto px-4 py-8">
        <Outlet />
      </main>
      <footer className="bg-white py-6 border-t mt-auto">
        <div className="container mx-auto px-4 text-center text-gray-500 text-sm">
          &copy; {new Date().getFullYear()} ShipWithGlowie Auto. All rights reserved.
        </div>
      </footer>
    </div>
  );
};

export default ManageBookingLayout;
