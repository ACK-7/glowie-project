import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

// Get auth token from localStorage
const getAuthHeaders = () => {
  const token = localStorage.getItem('admin_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
};

// Dashboard API
export const getDashboardStats = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/dashboard/statistics`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getKPIMetrics = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/dashboard/kpis`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getRecentActivity = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/dashboard/recent-activity`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

// Bookings API
export const getBookings = async (params = {}) => {
  try {
    const response = await axios.get(`${API_BASE_URL}/admin/crud/bookings`, {
      headers: getAuthHeaders(),
      params
    });
    
    // Handle API response structure
    if (response.data && response.data.success !== false) {
      return response.data;
    }
    
    throw new Error(response.data?.message || 'Failed to fetch bookings');
  } catch (error) {
    console.error('Get bookings error:', error);
    throw error;
  }
};

export const getBooking = async (id) => {
  try {
    const response = await axios.get(`${API_BASE_URL}/admin/crud/bookings/${id}`, {
      headers: getAuthHeaders()
    });
    
    // Handle API response structure: { success: true, data: {...}, message: "..." }
    if (response.data && response.data.success !== false) {
      return response.data;
    }
    
    throw new Error(response.data?.message || 'Failed to fetch booking');
  } catch (error) {
    console.error('Get booking error:', error);
    throw error;
  }
};

export const createBooking = async (data) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/bookings`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateBooking = async (id, data) => {
  try {
    console.log('updateBooking called:', { id, data });
    const response = await axios.put(`${API_BASE_URL}/admin/crud/bookings/${id}`, data, {
      headers: getAuthHeaders()
    });
    console.log('updateBooking response:', response.data);
    
    if (response.data && response.data.success !== false) {
      return response.data;
    }
    throw new Error(response.data?.message || 'Update failed');
  } catch (error) {
    console.error('Update booking error:', error);
    console.error('Error response data:', error.response?.data);
    console.error('Error response status:', error.response?.status);
    console.error('Error response headers:', error.response?.headers);
    
    // Re-throw with enhanced error information
    if (error.response?.data) {
      const errorData = error.response.data;
      const errorMessage = errorData.message || 'Validation failed';
      const validationErrors = errorData.errors || {};
      
      console.log('Extracted error details:', {
        message: errorMessage,
        validationErrors,
        statusCode: error.response.status
      });
      
      // Create a detailed error with validation info
      const detailedError = new Error(errorMessage);
      detailedError.response = error.response;
      detailedError.validationErrors = validationErrors;
      detailedError.statusCode = error.response.status;
      
      throw detailedError;
    }
    throw error;
  }
};

export const deleteBooking = async (id, reason = 'Deleted by admin') => {
  const response = await axios.delete(`${API_BASE_URL}/admin/crud/bookings/${id}`, {
    headers: getAuthHeaders(),
    data: {
      reason: reason,
      confirmation: true
    }
  });
  return response.data;
};

export const updateBookingStatus = async (id, status) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/bookings/${id}/status`, { status }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

// Customers API
export const getCustomers = async (params = {}) => {
  try {
    const response = await axios.get(`${API_BASE_URL}/admin/crud/customers`, {
      headers: getAuthHeaders(),
      params
    });
    
    // Handle API response structure
    if (response.data && response.data.success !== false) {
      return response.data;
    }
    
    throw new Error(response.data?.message || 'Failed to fetch customers');
  } catch (error) {
    console.error('Get customers error:', error);
    throw error;
  }
};

export const getCustomer = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/customers/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const createCustomer = async (data) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/customers`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateCustomer = async (id, data) => {
  try {
    const response = await axios.put(`${API_BASE_URL}/admin/crud/customers/${id}`, data, {
      headers: getAuthHeaders()
    });
    
    // Check if response indicates success
    if (response.data && response.data.success !== false) {
      return response.data;
    }
    
    throw new Error(response.data?.message || 'Update failed');
  } catch (error) {
    console.error('Update customer error:', error);
    throw error;
  }
};

export const deleteCustomer = async (id, reason = 'Deleted by admin') => {
  const response = await axios.delete(`${API_BASE_URL}/admin/crud/customers/${id}`, {
    headers: getAuthHeaders(),
    data: {
      reason: reason,
      confirmation: true
    }
  });
  return response.data;
};

export const updateCustomerStatus = async (id, status) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/customers/${id}/status`, { 
    status,
    reason: `Status changed to ${status} by admin`
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const verifyCustomer = async (id) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/customers/${id}/verify`, {}, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const resetCustomerPassword = async (id) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/customers/${id}/reset-password`, {}, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getCustomerBookings = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/customers/${id}/bookings`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getCustomerCommunications = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/customers/${id}/communications`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getRoutes = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/routes`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getVehicles = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/vehicles`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getCustomerStatistics = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/customers/statistics`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getCustomersRequiringAttention = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/customers/requires-attention`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const searchCustomers = async (query) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/customers/search`, {
    headers: getAuthHeaders(),
    params: { query }
  });
  return response.data;
};

export const exportCustomers = async (params = {}) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/customers/export`, params, {
    headers: getAuthHeaders(),
    responseType: 'blob'
  });
  return response.data;
};

// Quotes API
export const getQuotes = async (params = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes`, {
    headers: getAuthHeaders(),
    params
  });
  return response.data;
};

export const getQuote = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const createQuote = async (data) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/quotes`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateQuote = async (id, data) => {
  const response = await axios.put(`${API_BASE_URL}/admin/crud/quotes/${id}`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const approveQuote = async (id, notes = null) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/quotes/${id}/approve`, {
    notes
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const rejectQuote = async (id, reason = null) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/quotes/${id}/reject`, {
    reason
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const convertQuoteToBooking = async (id) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/quotes/${id}/convert`, {}, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const extendQuoteValidity = async (id, newExpiryDate) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/quotes/${id}/extend`, {
    expiry_date: newExpiryDate
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getQuotesRequiringApproval = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes/requires-approval`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getExpiringQuotes = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes/expiring-soon`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const processExpiredQuotes = async () => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/quotes/process-expired`, {}, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getQuoteStatistics = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes/statistics`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const searchQuotes = async (query) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes/search`, {
    headers: getAuthHeaders(),
    params: { query }
  });
  return response.data;
};

export const getQuoteAnalytics = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/quotes/analytics`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

// Shipments API
export const getShipments = async (params = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments`, {
    headers: getAuthHeaders(),
    params
  });
  return response.data;
};

export const getShipment = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const createShipment = async (data) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/shipments`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateShipment = async (id, data) => {
  const response = await axios.put(`${API_BASE_URL}/admin/crud/shipments/${id}`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const deleteShipment = async (id) => {
  const response = await axios.delete(`${API_BASE_URL}/admin/crud/shipments/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateShipmentStatus = async (id, status, location = null, notes = null) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/shipments/${id}/status`, {
    status,
    location,
    notes
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateEstimatedArrival = async (id, estimatedArrival, reason = null) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/shipments/${id}/estimated-arrival`, {
    estimated_arrival: estimatedArrival,
    reason
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const trackShipment = async (trackingNumber) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/track/${trackingNumber}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getShipmentsRequiringAttention = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/requires-attention`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getShipmentStatistics = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/statistics`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const processDelayedShipments = async () => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/shipments/process-delayed`, {}, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getDeliveryPerformance = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/delivery-performance`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const getCarrierPerformance = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/carrier-performance`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const searchShipments = async (query) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/search`, {
    headers: getAuthHeaders(),
    params: { query }
  });
  return response.data;
};

export const getRecentShipments = async (limit = 10) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/recent`, {
    headers: getAuthHeaders(),
    params: { limit }
  });
  return response.data;
};

export const getShipmentTrends = async (days = 30) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments/trends`, {
    headers: getAuthHeaders(),
    params: { days }
  });
  return response.data;
};

// Finance API
export const getFinanceStats = async () => {
  console.log('getFinanceStats called');
  console.log('Making request to:', `${API_BASE_URL}/admin/crud/payments/statistics`);
  console.log('With headers:', getAuthHeaders());
  
  try {
    const response = await axios.get(`${API_BASE_URL}/admin/crud/payments/statistics`, {
      headers: getAuthHeaders()
    });
    console.log('getFinanceStats response:', response);
    return response;
  } catch (error) {
    console.error('getFinanceStats error:', error);
    throw error;
  }
};

export const getPayments = async (params = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/payments`, {
    headers: getAuthHeaders(),
    params
  });
  return response.data;
};

export const getPayment = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/payments/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updatePaymentStatus = async (id, status, notes = null) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/payments/${id}/status`, {
    status,
    notes
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const processRefund = async (id, amount, reason) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/payments/${id}/refund`, {
    amount,
    reason
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const exportPayments = async (params = {}) => {
  const response = await axios.post(`${API_BASE_URL}/admin/crud/payments/export`, params, {
    headers: getAuthHeaders(),
    responseType: 'blob'
  });
  
  // Create download link
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `payments-export-${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
  
  return response.data;
};

export const getFinancialSummary = async (dateRange = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/analytics/financial-summary`, {
    headers: getAuthHeaders(),
    params: dateRange
  });
  return response.data;
};

// Documents API
export const getDocuments = async (params = {}) => {
  try {
    // If booking_id is provided, use the booking-specific endpoint
    if (params.booking_id) {
      const response = await axios.get(`${API_BASE_URL}/admin/crud/documents/booking/${params.booking_id}`, {
        headers: getAuthHeaders()
      });
      console.log('Documents API response:', response);
      return response.data;
    }
    const response = await axios.get(`${API_BASE_URL}/admin/crud/documents`, {
      headers: getAuthHeaders(),
      params
    });
    console.log('Documents API response:', response);
    return response.data;
  } catch (error) {
    console.error('Documents API error:', error);
    throw error;
  }
};

export const uploadDocument = async (formData) => {
  const response = await axios.post(`${API_BASE_URL}/admin/documents`, formData, {
    headers: {
      ...getAuthHeaders(),
      'Content-Type': 'multipart/form-data'
    }
  });
  return response.data;
};

// Document verification functions
export const getDocument = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/documents/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const approveDocument = async (id, notes = '') => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/documents/${id}/approve`, {
    notes
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const rejectDocument = async (id, reason) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/documents/${id}/reject`, {
    reason
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const downloadDocument = async (id) => {
  const response = await axios.get(`${API_BASE_URL}/admin/crud/documents/${id}/download`, {
    headers: getAuthHeaders(),
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

export const bulkApproveDocuments = async (documentIds, notes = '') => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/documents/bulk-approve`, {
    document_ids: documentIds,
    notes
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const bulkRejectDocuments = async (documentIds, reason) => {
  const response = await axios.patch(`${API_BASE_URL}/admin/crud/documents/bulk-reject`, {
    document_ids: documentIds,
    reason
  }, {
    headers: getAuthHeaders()
  });
  return response.data;
};

// Reports API
export const getReports = async (params = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/analytics/dashboard`, {
    headers: getAuthHeaders(),
    params
  });
  return response.data;
};

export const exportReport = async (type, params = {}) => {
  const response = await axios.post(`${API_BASE_URL}/admin/analytics/export`, {
    type,
    ...params
  }, {
    headers: getAuthHeaders(),
    responseType: 'blob'
  });
  return response.data;
};

// Specific report functions
// Specific report functions with fallback endpoints
export const getRevenueReport = async (params = {}) => {
  try {
    // Try the specific revenue endpoint first
    const response = await axios.get(`${API_BASE_URL}/admin/analytics/revenue`, {
      headers: getAuthHeaders(),
      params
    });
    return response.data;
  } catch (error) {
    console.warn('Revenue endpoint not available, using dashboard data');
    // Fallback to dashboard endpoint
    try {
      const response = await axios.get(`${API_BASE_URL}/admin/analytics/dashboard`, {
        headers: getAuthHeaders(),
        params
      });
      return response.data;
    } catch (fallbackError) {
      console.error('Both revenue endpoints failed:', fallbackError);
      throw fallbackError;
    }
  }
};

export const getOperationalMetrics = async (params = {}) => {
  try {
    const response = await axios.get(`${API_BASE_URL}/admin/analytics/operational`, {
      headers: getAuthHeaders(),
      params
    });
    return response.data;
  } catch (error) {
    console.warn('Operational endpoint not available, using dashboard data');
    try {
      const response = await axios.get(`${API_BASE_URL}/admin/analytics/dashboard`, {
        headers: getAuthHeaders(),
        params
      });
      return response.data;
    } catch (fallbackError) {
      console.error('Both operational endpoints failed:', fallbackError);
      throw fallbackError;
    }
  }
};

export const getCustomerAnalytics = async (params = {}) => {
  try {
    const response = await axios.get(`${API_BASE_URL}/admin/analytics/customers`, {
      headers: getAuthHeaders(),
      params
    });
    return response.data;
  } catch (error) {
    console.warn('Customer analytics endpoint not available, using dashboard data');
    try {
      const response = await axios.get(`${API_BASE_URL}/admin/analytics/dashboard`, {
        headers: getAuthHeaders(),
        params
      });
      return response.data;
    } catch (fallbackError) {
      console.error('Both customer endpoints failed:', fallbackError);
      throw fallbackError;
    }
  }
};

export const getShipmentReport = async (params = {}) => {
  try {
    // Try the shipments endpoint first
    const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments`, {
      headers: getAuthHeaders(),
      params: {
        ...params,
        with: 'customer,booking,route'
      }
    });
    return response.data;
  } catch (error) {
    console.warn('Shipments CRUD endpoint failed, trying analytics endpoint');
    try {
      // Try the analytics shipments endpoint
      const response = await axios.get(`${API_BASE_URL}/admin/analytics/shipments`, {
        headers: getAuthHeaders(),
        params
      });
      return response.data;
    } catch (analyticsError) {
      console.warn('Analytics shipments endpoint failed, using dashboard data');
      try {
        // Fallback to dashboard endpoint
        const response = await axios.get(`${API_BASE_URL}/admin/analytics/dashboard`, {
          headers: getAuthHeaders(),
          params
        });
        return response.data;
      } catch (fallbackError) {
        console.error('All shipment endpoints failed:', fallbackError);
        throw fallbackError;
      }
    }
  }
};

export const exportRevenueReport = async (params = {}) => {
  try {
    const response = await axios.post(`${API_BASE_URL}/admin/analytics/export/revenue`, params, {
      headers: getAuthHeaders(),
      responseType: 'blob'
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `revenue-report-${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    return response.data;
  } catch (error) {
    console.warn('Revenue export endpoint not available, using generic export');
    // Fallback to generic export
    return await exportReport('revenue', params);
  }
};

export const exportOperationalReport = async (params = {}) => {
  try {
    const response = await axios.post(`${API_BASE_URL}/admin/analytics/export/operational`, params, {
      headers: getAuthHeaders(),
      responseType: 'blob'
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `operational-report-${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    return response.data;
  } catch (error) {
    console.warn('Operational export endpoint not available, using generic export');
    return await exportReport('operational', params);
  }
};

export const exportCustomerReport = async (params = {}) => {
  try {
    const response = await axios.post(`${API_BASE_URL}/admin/analytics/export/customers`, params, {
      headers: getAuthHeaders(),
      responseType: 'blob'
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `customer-analytics-${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    return response.data;
  } catch (error) {
    console.warn('Customer export endpoint not available, using generic export');
    return await exportReport('customers', params);
  }
};

export const exportShipmentReport = async (params = {}) => {
  try {
    // Try the specific shipment export endpoint
    const response = await axios.post(`${API_BASE_URL}/admin/analytics/export/shipments`, params, {
      headers: getAuthHeaders(),
      responseType: 'blob'
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `shipment-report-${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    return response.data;
  } catch (error) {
    console.warn('Shipment export endpoint failed, trying alternative approaches');
    
    try {
      // Try exporting shipments data from CRUD endpoint
      const response = await axios.get(`${API_BASE_URL}/admin/crud/shipments`, {
        headers: {
          ...getAuthHeaders(),
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        },
        params: { ...params, export: 'xlsx' },
        responseType: 'blob'
      });
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `shipment-report-${new Date().toISOString().split('T')[0]}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
      return response.data;
    } catch (crudError) {
      console.warn('CRUD shipment export failed, using generic export');
      return await exportReport('shipments', params);
    }
  }
};

// Messages API
export const getMessages = async (params = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/messages`, {
    headers: getAuthHeaders(),
    params
  });
  return response.data;
};

export const sendMessage = async (data) => {
  // Use reply endpoint if message_id is provided, otherwise create new
  if (data.message_id) {
    const response = await axios.post(`${API_BASE_URL}/admin/messages/${data.message_id}/reply`, {
      reply: data.reply,
      customer_id: data.customer_id
    }, {
      headers: getAuthHeaders()
    });
    return response.data;
  } else {
    const response = await axios.post(`${API_BASE_URL}/admin/messages`, data, {
      headers: getAuthHeaders()
    });
    return response.data;
  }
};

// Users API
export const getUsers = async (params = {}) => {
  const response = await axios.get(`${API_BASE_URL}/admin/users`, {
    headers: getAuthHeaders(),
    params
  });
  return response.data;
};

export const createUser = async (data) => {
  const response = await axios.post(`${API_BASE_URL}/admin/users`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateUser = async (id, data) => {
  const response = await axios.put(`${API_BASE_URL}/admin/users/${id}`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const deleteUser = async (id) => {
  const response = await axios.delete(`${API_BASE_URL}/admin/users/${id}`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

// Settings API
export const getSettings = async () => {
  const response = await axios.get(`${API_BASE_URL}/admin/settings`, {
    headers: getAuthHeaders()
  });
  return response.data;
};

export const updateSettings = async (data) => {
  const response = await axios.put(`${API_BASE_URL}/admin/settings`, data, {
    headers: getAuthHeaders()
  });
  return response.data;
};