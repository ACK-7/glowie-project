import React from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Header from '../components/Common/Header';
import Footer from '../components/Common/Footer';

const PublicLayout = () => {
  const location = useLocation();
  const isHomePage = location.pathname === '/';

  return (
    <div className="flex flex-col min-h-screen">
      <Header />
      <main className={`flex-grow ${isHomePage ? '' : 'pt-32'}`}>
        <Outlet />
      </main>
      <Footer />
    </div>
  );
};

export default PublicLayout;
