import api from './api';
import { safeGetItem, safeGetJSON, safeSetItem, safeSetJSON, safeRemoveItem } from '../utils/localStorage';

class AuthService {
  // Customer Authentication
  async customerLogin(email, password) {
    try {
      const response = await api.post('/auth/customer/login', {
        email,
        password,
      });

      const { token, customer } = response.data;
      
      // Store customer token and data
      safeSetItem('customer_token', token);
      safeSetJSON('customer_data', customer);
      
      return {
        success: true,
        data: { token, customer }
      };
    } catch (error) {
      console.error('Customer login error:', error);
      return {
        success: false,
        error: error.response?.data?.message || error.response?.data?.errors?.email?.[0] || 'Login failed'
      };
    }
  }

  async customerRegister(userData) {
    try {
      const response = await api.post('/auth/customer/register', userData);
      
      const { token, customer } = response.data;
      
      // Store customer token and data
      safeSetItem('customer_token', token);
      safeSetJSON('customer_data', customer);
      
      return {
        success: true,
        data: { token, customer }
      };
    } catch (error) {
      console.error('Customer registration error:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Registration failed'
      };
    }
  }

  // Admin Authentication
  async adminLogin(email, password) {
    try {
      const response = await api.post('/auth/admin/login', {
        email,
        password,
      });

      const { token, user } = response.data;
      
      // Store admin token and data
      safeSetItem('admin_token', token);
      safeSetJSON('admin_data', user);
      
      return {
        success: true,
        data: { token, user }
      };
    } catch (error) {
      console.error('Admin login error:', error);
      return {
        success: false,
        error: error.response?.data?.message || 'Login failed'
      };
    }
  }

  // Logout functions
  customerLogout() {
    safeRemoveItem('customer_token');
    safeRemoveItem('customer_data');
  }

  adminLogout() {
    safeRemoveItem('admin_token');
    safeRemoveItem('admin_data');
  }

  // Get current user data
  getCurrentCustomer() {
    return safeGetJSON('customer_data');
  }

  getCurrentAdmin() {
    return safeGetJSON('admin_data');
  }

  // Check authentication status
  isCustomerAuthenticated() {
    return !!safeGetItem('customer_token');
  }

  isAdminAuthenticated() {
    return !!safeGetItem('admin_token');
  }

  // Get tokens
  getCustomerToken() {
    return safeGetItem('customer_token');
  }

  getAdminToken() {
    return safeGetItem('admin_token');
  }
}

export default new AuthService();