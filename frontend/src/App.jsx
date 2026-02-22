import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';

// Context
import { AuthProvider } from './context/AuthContext';
import { CustomerAuthProvider } from './context/CustomerAuthContext';

// Components
import ProtectedRoute from './components/Auth/ProtectedRoute';

// Layouts
import PublicLayout from './layouts/PublicLayout';
import ManageBookingLayout from './layouts/ManageBookingLayout';
import AdminLayout from './layouts/AdminLayout';

// Pages
import Home from './pages/Home';
import ManageBooking from './pages/ManageBooking';
import AdminLogin from './pages/AdminLogin';
import AdminPanel from './pages/AdminPanel';
import BookingsList from './pages/Admin/BookingsList';
import BookingCreate from './pages/Admin/BookingCreate';
import ShipmentManagement from './pages/Admin/ShipmentManagement';
import CustomersList from './pages/Admin/CustomersList';
import QuoteManagement from './pages/Admin/QuoteManagement';
import QuoteDetails from './pages/Admin/QuoteDetails';
import FinanceDashboard from './pages/Admin/FinanceDashboard';
import DocumentManager from './pages/Admin/DocumentManager';
import ReportsHub from './pages/Admin/ReportsHub';
import MessageCenter from './pages/Admin/MessageCenter';
import AdminSettings from './pages/Admin/AdminSettings';
import UserManagement from './pages/Admin/UserManagement';
import CarInventoryManagement from './pages/Admin/CarInventoryManagement';
import TrackShipment from './pages/TrackShipment';
import Brands from './pages/Brands';
import Cars from './pages/Cars';
import CarDetail from './pages/CarDetail';
import Favorites from './pages/Favorites';
import NotFound from './pages/NotFound';

import GetQuote from './pages/GetQuote';
import Services from './pages/Services';
import HowItWorks from './pages/HowItWorks';
import InlandTransport from './pages/InlandTransport';
import CustomsClearance from './pages/CustomsClearance';
import ShipmentStatus from './pages/ShipmentStatus';
import AboutUs from './pages/AboutUs';
import OurStory from './pages/OurStory';
import Certifications from './pages/Certifications';
import FAQ from './pages/FAQ';
import News from './pages/News';
import Contact from './pages/Contact';
import Login from './pages/Login';
import Register from './pages/Register';
import CustomerPortal from './pages/CustomerPortal';
import DevCredentials from './pages/DevCredentials';

function App() {
  return (
    <AuthProvider>
      <CustomerAuthProvider>
        <Router>
        <Routes>
          {/* Public Website Routes */}
          <Route element={<PublicLayout />}>
            <Route path="/" element={<Home />} />
            <Route path="/quote" element={<GetQuote />} />
            <Route path="/services" element={<Services />} />
            <Route path="/services/how-it-works" element={<HowItWorks />} />
            <Route path="/services/request-quote" element={<GetQuote />} />
            <Route path="/services/inland-transport" element={<InlandTransport />} />
            <Route path="/services/customs-clearance" element={<CustomsClearance />} />
            <Route path="/tracking" element={<ShipmentStatus />} />
            <Route path="/track" element={<TrackShipment />} />
            <Route path="/track/:trackingNumber" element={<TrackShipment />} />
            <Route path="/brands" element={<Brands />} />
            <Route path="/cars" element={<Cars />} />
            <Route path="/cars/:slug" element={<CarDetail />} />
            <Route path="/favorites" element={<Favorites />} />
            <Route path="/about" element={<AboutUs />} />
            <Route path="/about/our-story" element={<OurStory />} />
            <Route path="/about/certifications" element={<Certifications />} />
            <Route path="/faq" element={<FAQ />} />
            <Route path="/news" element={<News />} />
            <Route path="/contact" element={<Contact />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/portal/dashboard" element={<CustomerPortal />} />
            <Route path="/dev/credentials" element={<DevCredentials />} />
          </Route>

          {/* Admin Login (Public) */}
          <Route path="/admin/login" element={<AdminLogin />} />

          {/* Admin Dashboard Routes (Protected) */}
          <Route 
            path="/admin" 
            element={
              <ProtectedRoute>
                <AdminLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<AdminPanel />} />
            <Route path="bookings/new" element={<BookingCreate />} />
            <Route path="bookings" element={<BookingsList />} />
            <Route path="shipments" element={<ShipmentManagement />} />
            <Route path="customers" element={<CustomersList />} />
            <Route path="quotes/:id" element={<QuoteDetails />} />
            <Route path="quotes" element={<QuoteManagement />} />
            <Route path="finance" element={<FinanceDashboard />} />
            <Route path="documents" element={<DocumentManager />} />
            <Route path="reports" element={<ReportsHub />} />
            <Route path="messages" element={<MessageCenter />} />
            <Route path="users" element={<UserManagement />} />
            <Route path="inventory" element={<CarInventoryManagement />} />
            <Route path="settings" element={<AdminSettings />} />
          </Route>

          {/* 404 Not Found */}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </Router>
      </CustomerAuthProvider>
    </AuthProvider>
  );
}

export default App;
