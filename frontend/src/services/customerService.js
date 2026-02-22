import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Get customer auth token from localStorage
const getCustomerAuthHeaders = () => {
  const token = localStorage.getItem('customer_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
};

// Customer Profile API
export const getCustomerProfile = async () => {
  const response = await axios.get(`${API_BASE_URL}/customer/profile`, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

export const updateCustomerProfile = async (data) => {
  const response = await axios.put(`${API_BASE_URL}/customer/profile`, data, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

export const changeCustomerPassword = async (data) => {
  const response = await axios.post(`${API_BASE_URL}/customer/change-password`, data, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

// Customer Quotes API
export const getCustomerQuotes = async () => {
  const response = await axios.get(`${API_BASE_URL}/quotes`, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

export const getCustomerQuote = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/quotes/${id}`, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

// Customer Bookings API
export const getCustomerBookings = async () => {
  const response = await axios.get(`${API_BASE_URL}/bookings`, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

export const getCustomerBooking = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/bookings/${id}`, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

export const confirmQuoteToBooking = async (quoteId, additionalData = {}) => {
  const response = await axios.post(`${API_BASE_URL}/bookings`, {
    quote_id: quoteId,
    ...additionalData
  }, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

// Customer Documents API
export const getCustomerDocuments = async () => {
  try {
    // First try to get customer ID from profile
    const profile = await getCustomerProfile();
    const customerId = profile.data?.id || profile.id;
    
    if (customerId) {
      const response = await axios.get(`${API_BASE_URL}/admin/crud/documents/customer/${customerId}`, {
        headers: getCustomerAuthHeaders()
      });
      return response.data;
    }
    
    // Fallback to general documents endpoint
    const response = await axios.get(`${API_BASE_URL}/documents`, {
      headers: getCustomerAuthHeaders()
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching customer documents:', error);
    throw error;
  }
};

export const uploadCustomerDocument = async (formData) => {
  const response = await axios.post(`${API_BASE_URL}/documents`, formData, {
    headers: {
      ...getCustomerAuthHeaders(),
      'Content-Type': 'multipart/form-data'
    }
  });
  return response.data;
};

export const downloadCustomerDocument = async (documentId) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/documents/${documentId}/download`, {
    headers: getCustomerAuthHeaders(),
    responseType: 'blob'
  });
  
  // Create download link
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  
  // Try to get filename from response headers
  const contentDisposition = response.headers['content-disposition'];
  let filename = 'document';
  if (contentDisposition) {
    const filenameMatch = contentDisposition.match(/filename="(.+)"/);
    if (filenameMatch) {
      filename = filenameMatch[1];
    }
  }
  
  link.setAttribute('download', filename);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
  
  return response.data;
};

// Customer Payments API
export const getCustomerPayments = async () => {
  try {
    // First try to get customer ID from profile
    const profile = await getCustomerProfile();
    const customerId = profile.data?.id || profile.id;
    
    if (customerId) {
      const response = await axios.get(`${API_BASE_URL}/admin/crud/payments/customer/${customerId}`, {
        headers: getCustomerAuthHeaders()
      });
      return response.data;
    }
    
    return { data: [] };
  } catch (error) {
    console.error('Error fetching customer payments:', error);
    return { data: [] };
  }
};

// Customer Shipment Tracking API
export const getCustomerShipments = async () => {
  try {
    const bookings = await getCustomerBookings();
    const bookingsData = bookings.data || bookings || [];
    
    // Get shipment details for each booking
    const shipmentsPromises = bookingsData.map(async (booking) => {
      try {
        const response = await axios.get(`${API_BASE_URL}/tracking/${booking.id}`, {
          headers: getCustomerAuthHeaders()
        });
        return {
          ...booking,
          shipment: response.data.data || response.data
        };
      } catch (error) {
        return {
          ...booking,
          shipment: null
        };
      }
    });
    
    const shipments = await Promise.all(shipmentsPromises);
    return { data: shipments };
  } catch (error) {
    console.error('Error fetching customer shipments:', error);
    return { data: [] };
  }
};

export const trackShipment = async (trackingNumber) => {
  const response = await axios.get(`${API_BASE_URL}/tracking/${trackingNumber}`, {
    headers: getCustomerAuthHeaders()
  });
  return response.data;
};

// Customer Dashboard Stats
export const getCustomerDashboardStats = async () => {
  try {
    const [quotes, bookings, documents, payments] = await Promise.all([
      getCustomerQuotes().catch(() => ({ data: [] })),
      getCustomerBookings().catch(() => ({ data: [] })),
      getCustomerDocuments().catch(() => ({ data: [] })),
      getCustomerPayments().catch(() => ({ data: [] }))
    ]);
    
    const quotesData = quotes.data || quotes || [];
    const bookingsData = bookings.data || bookings || [];
    const documentsData = documents.data || documents || [];
    const paymentsData = payments.data || payments || [];
    
    // Calculate stats
    const activeShipments = bookingsData.filter(booking => 
      ['confirmed', 'in_transit', 'processing'].includes(booking.status)
    ).length;
    
    const pendingPayments = paymentsData.filter(payment => 
      payment.status === 'pending' || payment.status === 'partial'
    );
    
    const totalBalance = pendingPayments.reduce((sum, payment) => {
      return sum + (parseFloat(payment.amount || 0) - parseFloat(payment.paid_amount || 0));
    }, 0);
    
    const pendingDocuments = documentsData.filter(doc => 
      doc.status === 'pending' || doc.status === 'requires_revision'
    ).length;
    
    const approvedQuotes = quotesData.filter(quote => quote.status === 'approved').length;
    
    return {
      data: {
        totalQuotes: quotesData.length,
        approvedQuotes,
        pendingQuotes: quotesData.filter(q => q.status === 'pending').length,
        totalBookings: bookingsData.length,
        activeShipments,
        completedShipments: bookingsData.filter(b => b.status === 'delivered').length,
        totalDocuments: documentsData.length,
        pendingDocuments,
        approvedDocuments: documentsData.filter(d => d.status === 'approved').length,
        totalPayments: paymentsData.length,
        pendingPayments: pendingPayments.length,
        totalBalance: totalBalance.toFixed(2),
        contractStatus: bookingsData.length > 0 ? 'Active' : 'None'
      }
    };
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
    return {
      data: {
        totalQuotes: 0,
        approvedQuotes: 0,
        pendingQuotes: 0,
        totalBookings: 0,
        activeShipments: 0,
        completedShipments: 0,
        totalDocuments: 0,
        pendingDocuments: 0,
        approvedDocuments: 0,
        totalPayments: 0,
        pendingPayments: 0,
        totalBalance: '0.00',
        contractStatus: 'None'
      }
    };
  }
};

// Utility function to format currency
export const formatCurrency = (amount) => {
  if (!amount || amount === 0) return '0.00';
  return Number(amount).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Utility function to format date
export const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Utility function to get status badge color
export const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    confirmed: 'bg-blue-100 text-blue-800',
    in_transit: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    processing: 'bg-orange-100 text-orange-800',
    paid: 'bg-green-100 text-green-800',
    partial: 'bg-yellow-100 text-yellow-800'
  };
  return colors[status] || 'bg-gray-100 text-gray-800';
};