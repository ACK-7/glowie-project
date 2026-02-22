import React, { useState, useEffect } from 'react';
import api from '../services/api';

const DevCredentials = () => {
  const [customers, setCustomers] = useState([]);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('customers');

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [customersRes, notificationsRes] = await Promise.all([
        api.get('/dev/customers'),
        api.get('/dev/notifications')
      ]);
      
      setCustomers(customersRes.data.customers || []);
      setNotifications(notificationsRes.data.notifications || []);
    } catch (error) {
      console.error('Error fetching dev data:', error);
    } finally {
      setLoading(false);
    }
  };

  const resetPassword = async (email) => {
    const newPassword = prompt('Enter new password for ' + email + ':');
    if (!newPassword) return;

    try {
      const response = await api.post('/dev/reset-password', {
        email,
        password: newPassword
      });
      
      alert(`Password reset successfully for ${email}\nNew password: ${newPassword}`);
      fetchData();
    } catch (error) {
      alert('Error resetting password: ' + (error.response?.data?.message || error.message));
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-lg">Loading development data...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-6xl mx-auto px-4">
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
          <strong>⚠️ Development Only:</strong> This page is only available in development mode and shows sensitive information.
        </div>

        <h1 className="text-3xl font-bold text-gray-900 mb-6">Development Credentials & Notifications</h1>

        {/* Tabs */}
        <div className="border-b border-gray-200 mb-6">
          <nav className="-mb-px flex space-x-8">
            <button
              onClick={() => setActiveTab('customers')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'customers'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Customer Credentials ({customers.length})
            </button>
            <button
              onClick={() => setActiveTab('notifications')}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'notifications'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Recent Notifications ({notifications.length})
            </button>
          </nav>
        </div>

        {/* Customers Tab */}
        {activeTab === 'customers' && (
          <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                Customer Login Credentials
              </h3>
              <p className="text-sm text-gray-600 mb-4">
                Check the Laravel logs for temporary passwords when quotes are approved.
              </p>
              
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Customer
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Updated
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {customers.map((customer) => (
                      <tr key={customer.id}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                          {customer.name}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {customer.email}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                            customer.has_temporary_password
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-green-100 text-green-800'
                          }`}>
                            {customer.has_temporary_password ? 'Temporary Password' : 'Permanent Password'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {customer.updated_at}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <button
                            onClick={() => resetPassword(customer.email)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            Reset Password
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* Notifications Tab */}
        {activeTab === 'notifications' && (
          <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="px-4 py-5 sm:p-6">
              <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                Recent Notifications
              </h3>
              
              <div className="space-y-4">
                {notifications.map((notification) => (
                  <div key={notification.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2">
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                            notification.type === 'quote_approved'
                              ? 'bg-green-100 text-green-800'
                              : notification.type === 'quote_created'
                              ? 'bg-blue-100 text-blue-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}>
                            {notification.type}
                          </span>
                          <span className="text-sm text-gray-500">{notification.created_at}</span>
                        </div>
                        
                        <h4 className="text-lg font-medium text-gray-900 mt-2">
                          {notification.title}
                        </h4>
                        
                        {notification.customer && (
                          <p className="text-sm text-gray-600 mt-1">
                            To: {notification.customer.name} ({notification.customer.email})
                          </p>
                        )}
                        
                        <div className="mt-2 text-sm text-gray-700 whitespace-pre-wrap">
                          {notification.message}
                        </div>
                        
                        {notification.data && Object.keys(notification.data).length > 0 && (
                          <details className="mt-2">
                            <summary className="text-sm text-gray-500 cursor-pointer">
                              Additional Data
                            </summary>
                            <pre className="mt-1 text-xs bg-gray-100 p-2 rounded overflow-auto">
                              {JSON.stringify(notification.data, null, 2)}
                            </pre>
                          </details>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        <div className="mt-6 text-center">
          <button
            onClick={fetchData}
            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
          >
            Refresh Data
          </button>
        </div>
      </div>
    </div>
  );
};

export default DevCredentials;