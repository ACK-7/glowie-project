import React from 'react';
import { Outlet } from 'react-router-dom';
import AdminSidebar from '../components/Admin/AdminSidebar';
import AdminTopBar from '../components/Admin/AdminTopBar';

const AdminLayout = () => {
  return (
    <div className="flex h-screen bg-[#0f1419] overflow-hidden">
      <AdminSidebar />
      
      <div className="flex-1 flex flex-col ml-64">
        <AdminTopBar />
        
        <main className="flex-1 overflow-x-auto overflow-y-auto mt-16 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default AdminLayout;
