import React, { useState } from 'react';
import axios from 'axios';
import { showAlert } from '../utils/sweetAlert';
import { safeRender } from '../utils/safeRender';
import { FaSearch, FaCar, FaShip, FaFileInvoiceDollar, FaUser, FaCheckCircle, FaExclamationCircle, FaFileUpload } from 'react-icons/fa';

// API Base URL
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

const ManageBooking = () => {
  const [step, setStep] = useState('lookup'); // lookup, review, success
  const [reference, setReference] = useState('');
  const [email, setEmail] = useState('');
  const [quoteData, setQuoteData] = useState(null);
  const [bookingData, setBookingData] = useState(null); // Result after booking
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  // File Uploads
  const [idDocument, setIdDocument] = useState(null);
  const [logbookDocument, setLogbookDocument] = useState(null);

  const handleLookup = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    console.log('Lookup request:', { reference, email, API_BASE_URL });

    try {
      const response = await axios.post(`${API_BASE_URL}/quotes/lookup`, {
        reference: reference.trim(),
        email: email.trim()
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });
      
      console.log('Lookup response:', response.data);
      
      // The API returns the quote data directly, not wrapped in a data property
      if (response.data && (response.data.id || response.data.quote_reference)) {
        setQuoteData(response.data);
        setStep('review');
        showAlert.success('Quote Found!', 'Your quote has been found successfully.');
      } else {
        throw new Error('Invalid quote data received');
      }
    } catch (err) {
      console.error('Lookup error:', err);
      console.error('Error response:', err.response?.data);
      console.error('Error status:', err.response?.status);
      
      let errorMessage = 'Quote not found or details do not match.';
      
      if (err.response?.status === 404) {
        errorMessage = 'Quote not found. Please check your reference number and email address.';
      } else if (err.response?.status === 400) {
        errorMessage = err.response.data?.message || 'Invalid request. Please check your input.';
      } else if (err.response?.status === 422) {
        const errors = err.response.data?.errors;
        if (errors) {
          errorMessage = Object.values(errors).flat().join(', ');
        } else {
          errorMessage = err.response.data?.message || 'Validation error. Please check your input.';
        }
      } else if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
      } else if (err.code === 'NETWORK_ERROR' || err.message.includes('Network Error')) {
        errorMessage = 'Network error. Please check if the backend server is running.';
      }
      
      setError(errorMessage);
      showAlert.error('Lookup Failed', errorMessage);
    } finally {
      setIsLoading(false);
    }
  };

  const handleConfirmBooking = async () => {
      setIsLoading(true);
      setError(null);

      // Create FormData for file upload
      const formData = new FormData();
      formData.append('quote_reference', quoteData.quote_reference || quoteData.reference);
      formData.append('email', email); // Confirming email
      if (idDocument) formData.append('id_document', idDocument);
      if (logbookDocument) formData.append('logbook_document', logbookDocument);
      
      try {
          const response = await axios.post(`${API_BASE_URL}/bookings/confirm`, formData, {
              headers: {
                  'Content-Type': 'multipart/form-data'
              }
          });
          
          setBookingData(response.data);
          setStep('success'); // Move to success step
          showAlert.success('Booking Confirmed!', 'Your booking has been confirmed successfully.');
          
      } catch (err) {
          console.error(err);
          setError(err.response?.data?.message || 'Failed to confirm booking. Please try again.');
          showAlert.error('Booking Failed', err.response?.data?.message || 'Failed to confirm booking. Please try again.');
      } finally {
          setIsLoading(false);
      }
  };

  const handleFileChange = (e, setter) => {
      if (e.target.files && e.target.files[0]) {
          setter(e.target.files[0]);
      }
  };

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto">
        
        {/* Header */}
        <div className="text-center mb-10">
          <h1 className="text-3xl font-extrabold text-navy-900">
            {step === 'lookup' ? 'Manage Your Booking' : (step === 'success' ? 'Booking Confirmed!' : 'Review Your Quote')}
          </h1>
          <p className="mt-2 text-lg text-gray-600">
            {step === 'lookup' 
                ? 'Enter your details to track status or complete your booking' 
                : (step === 'success' ? 'Thank you for choosing ShipWithGlowie' : 'Please verify details and upload documents')}
          </p>
        </div>

        {/* LOOKUP STEP */}
        {step === 'lookup' && (
            <div className="bg-white rounded-xl shadow-lg p-8">
                <form onSubmit={handleLookup} className="space-y-6">
                    {error && (
                        <div className="bg-red-50 border-l-4 border-red-500 p-4">
                            <div className="flex">
                                <FaExclamationCircle className="text-red-500 mt-1 mr-3" />
                                <p className="text-red-700">{error}</p>
                            </div>
                        </div>
                    )}

                    <div>
                    <label htmlFor="reference" className="block text-sm font-medium text-gray-700 mb-1">
                        Booking Reference
                    </label>
                    <div className="relative rounded-md shadow-sm">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <FaSearch className="text-gray-400" />
                        </div>
                        <input
                            type="text"
                            id="reference"
                            value={reference}
                            onChange={(e) => setReference(e.target.value.toUpperCase())}
                            placeholder="QT-XXXXX"
                            className="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 py-3 sm:text-sm border-gray-300 rounded-md"
                            required
                        />
                    </div>
                    </div>
                    
                    <div>
                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <div className="relative rounded-md shadow-sm">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <FaUser className="text-gray-400" />
                        </div>
                        <input
                            type="email"
                            id="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="The email used for the quote"
                            className="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 py-3 sm:text-sm border-gray-300 rounded-md"
                            required
                        />
                    </div>
                    </div>
                    
                    <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        {isLoading ? 'Searching...' : 'Find My Quote'}
                    </button>
                </form>
            </div>
        )}

        {/* REVIEW & UPLOAD STEP */}
        {step === 'review' && quoteData && (
            <div className="space-y-6">
                
                {error && (
                    <div className="bg-red-50 border-l-4 border-red-500 p-4">
                        <p className="text-red-700">{error}</p>
                    </div>
                )}
            
                {/* Status Card */}
                <div className="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
                    <div className="flex justify-between items-center">
                        <div>
                            <p className="text-sm text-gray-500 uppercase tracking-wide">Status</p>
                            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mt-1">
                                {safeRender(quoteData.status, 'pending').toUpperCase()}
                            </span>
                        </div>
                        <div className="text-right">
                             <p className="text-sm text-gray-500 uppercase tracking-wide">Quote Reference</p>
                             <p className="text-xl font-bold text-gray-900">{safeRender(quoteData.quote_reference || quoteData.reference)}</p>
                        </div>
                    </div>
                </div>

                {/* Details Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Vehicle */}
                    <div className="bg-white rounded-xl shadow p-6">
                        <div className="flex items-center mb-4 text-navy-900">
                            <FaCar className="mr-2 text-xl text-blue-600" />
                            <h3 className="text-lg font-bold">Vehicle Details</h3>
                        </div>
                        <dl className="space-y-2 text-sm text-gray-600">
                            <div className="flex justify-between">
                                <dt>Make / Model:</dt>
                                <dd className="font-semibold text-gray-900">
                                  {safeRender(quoteData.vehicle_details?.make)} {safeRender(quoteData.vehicle_details?.model)}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Year:</dt>
                                <dd className="font-semibold text-gray-900">{safeRender(quoteData.vehicle_details?.year)}</dd>
                            </div>
                        </dl>
                    </div>

                    {/* Shipping */}
                    <div className="bg-white rounded-xl shadow p-6">
                         <div className="flex items-center mb-4 text-navy-900">
                            <FaShip className="mr-2 text-xl text-blue-600" />
                            <h3 className="text-lg font-bold">Shipping Route</h3>
                        </div>
                        <dl className="space-y-2 text-sm text-gray-600">
                             <div className="flex justify-between">
                                <dt>Origin:</dt>
                                <dd className="font-semibold text-gray-900">
                                  {safeRender(quoteData.route?.origin_city)}, {safeRender(quoteData.route?.origin_country)}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Destination:</dt>
                                <dd className="font-semibold text-gray-900">
                                  {safeRender(quoteData.route?.destination_city)}, {safeRender(quoteData.route?.destination_country)}
                                </dd>
                            </div>
                             <div className="flex justify-between">
                                <dt>Total Estimate:</dt>
                                <dd className="font-bold text-green-600 text-lg">
                                  ${Number(quoteData.total_amount || 0).toLocaleString()}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Document Upload Section */}
                <div className="bg-white rounded-xl shadow-lg p-6">
                    <div className="flex items-center mb-4 text-navy-900">
                        <FaFileUpload className="mr-2 text-xl text-blue-600" />
                        <h3 className="text-lg font-bold">Required Documents</h3>
                    </div>
                    <p className="text-sm text-gray-500 mb-6">Please upload clear copies of the following documents to proceed with your booking.</p>

                    <div className="space-y-4">
                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors">
                            <label className="cursor-pointer block">
                                <div className="flex flex-col items-center">
                                    <span className="mb-2 text-sm font-medium text-gray-900">ID / Passport Copy (Required)</span>
                                    {idDocument ? (
                                        <span className="text-green-600 text-sm font-semibold flex items-center"><FaCheckCircle className="mr-1"/> {idDocument.name}</span>
                                    ) : (
                                        <span className="text-gray-500 text-xs text-center">Click to upload PDF or Image (Max 5MB)</span>
                                    )}
                                </div>
                                <input type="file" className="hidden" accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => handleFileChange(e, setIdDocument)} required />
                            </label>
                        </div>

                         <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors">
                            <label className="cursor-pointer block">
                                <div className="flex flex-col items-center">
                                    <span className="mb-2 text-sm font-medium text-gray-900">Vehicle Logbook / Title (If available)</span>
                                     {logbookDocument ? (
                                        <span className="text-green-600 text-sm font-semibold flex items-center"><FaCheckCircle className="mr-1"/> {logbookDocument.name}</span>
                                    ) : (
                                        <span className="text-gray-500 text-xs text-center">Click to upload PDF or Image (Max 5MB)</span>
                                    )}
                                </div>
                                <input type="file" className="hidden" accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => handleFileChange(e, setLogbookDocument)} />
                            </label>
                        </div>
                    </div>

                    {/* Action */}
                    <div className="mt-8 pt-6 border-t border-gray-100 flex justify-end gap-3">
                        <button 
                            onClick={() => setStep('lookup')}
                            className="btn-outline px-6"
                            disabled={isLoading}
                        >
                            Cancel
                        </button>
                        <button 
                            onClick={handleConfirmBooking}
                            className="btn-primary px-8 shadow-lg shadow-blue-200"
                            disabled={isLoading}
                        >
                            {isLoading ? 'Processing...' : 'Confirm Booking'}
                        </button>
                    </div>
                </div>
            </div>
        )}

        {/* SUCCESS STEP */}
        {step === 'success' && bookingData && (
             <div className="bg-white rounded-xl shadow-lg p-8 text-center animate-fade-in">
                <div className="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mb-6">
                    <FaCheckCircle className="text-green-600 text-5xl" />
                </div>
                <h2 className="text-3xl font-bold text-navy-900 mb-2">Booking Confirmed!</h2>
                <p className="text-gray-600 text-lg mb-6">Your shipment has been successfully booked.</p>
                
                <div className="bg-blue-50 rounded-lg p-6 mb-8 inline-block w-full max-w-md">
                    <p className="text-sm text-gray-500 uppercase tracking-wide mb-1">Booking Reference</p>
                    <p className="text-3xl font-mono font-bold text-blue-700 select-all">{safeRender(bookingData.booking_reference)}</p>
                    <p className="text-xs text-gray-400 mt-2">Save this number to track your shipment.</p>
                </div>

                <div className="space-y-4">
                    <p className="text-gray-600">
                        We have sent a confirmation email to <strong>{email}</strong>. 
                        Our team will verify your documents and contact you within 24 hours regarding the next steps (Payment & Vehicle Drop-off).
                    </p>
                    
                    <button 
                        onClick={() => window.location.href = '/'}
                        className="btn-primary px-8 mt-4"
                    >
                        Return to Home
                    </button>
                </div>
            </div>
        )}

      </div>
    </div>
  );
};

export default ManageBooking;
