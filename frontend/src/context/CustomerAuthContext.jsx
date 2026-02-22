import React, { createContext, useContext, useState, useEffect } from 'react';
import authService from '../services/authService';

const CustomerAuthContext = createContext(null);

export const CustomerAuthProvider = ({ children }) => {
  const [customer, setCustomer] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  // Initialize auth state on mount
  useEffect(() => {
    const initializeAuth = () => {
      const storedToken = authService.getCustomerToken();
      const storedCustomer = authService.getCurrentCustomer();
      
      if (storedToken && storedCustomer) {
        setToken(storedToken);
        setCustomer(storedCustomer);
        setIsAuthenticated(true);
      }
      
      setLoading(false);
    };

    initializeAuth();
  }, []);

  // Customer Login
  const login = async (email, password) => {
    try {
      setLoading(true);
      const result = await authService.customerLogin(email, password);
      
      if (result.success) {
        const { token: newToken, customer: customerData } = result.data;
        setToken(newToken);
        setCustomer(customerData);
        setIsAuthenticated(true);
        return { success: true };
      } else {
        return { success: false, error: result.error };
      }
    } catch (error) {
      console.error('Login error:', error);
      return { success: false, error: 'An unexpected error occurred' };
    } finally {
      setLoading(false);
    }
  };

  // Customer Logout
  const logout = () => {
    authService.customerLogout();
    setToken(null);
    setCustomer(null);
    setIsAuthenticated(false);
  };

  // Customer Registration
  const register = async (userData) => {
    try {
      setLoading(true);
      const result = await authService.customerRegister(userData);
      
      if (result.success) {
        const { token: newToken, customer: customerData } = result.data;
        setToken(newToken);
        setCustomer(customerData);
        setIsAuthenticated(true);
        return { success: true };
      } else {
        return { success: false, error: result.error };
      }
    } catch (error) {
      console.error('Registration error:', error);
      return { success: false, error: 'An unexpected error occurred' };
    } finally {
      setLoading(false);
    }
  };

  const value = {
    customer,
    token,
    loading,
    isAuthenticated,
    login,
    logout,
    register,
  };

  return (
    <CustomerAuthContext.Provider value={value}>
      {children}
    </CustomerAuthContext.Provider>
  );
};

export const useCustomerAuth = () => {
  const context = useContext(CustomerAuthContext);
  if (!context) {
    throw new Error('useCustomerAuth must be used within a CustomerAuthProvider');
  }
  return context;
};

export default CustomerAuthContext;