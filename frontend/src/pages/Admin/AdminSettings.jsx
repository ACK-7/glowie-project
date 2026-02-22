import React, { useState, useEffect } from 'react';
import { FaCog, FaUser, FaDollarSign, FaEnvelope, FaSpinner, FaCheck } from 'react-icons/fa';
import { getSettings, updateSettings, getUsers } from '../../services/adminService';

const AdminSettings = () => {
  const [activeTab, setActiveTab] = useState('company');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [settings, setSettings] = useState({
    company_name: '',
    contact_email: '',
    phone_number: '',
    pricing: {}
  });
  const [adminUsers, setAdminUsers] = useState([]);
  const [notificationSettings, setNotificationSettings] = useState({
    new_booking_alerts: true,
    payment_notifications: true,
    shipment_updates: true,
    customer_messages: true
  });
  const [saveSuccess, setSaveSuccess] = useState(false);

  useEffect(() => {
    fetchSettings();
    fetchAdminUsers();
  }, []);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      const response = await getSettings();
      if (response) {
        setSettings({
          company_name: response.company_name || 'ShipWithGlowie Auto',
          contact_email: response.contact_email || 'support@shipwithglowie.com',
          phone_number: response.phone_number || '+256 700 000 000',
          pricing: response.pricing || {}
        });
        if (response.notification_settings) {
          setNotificationSettings(response.notification_settings);
        }
      }
    } catch (error) {
      console.error('Failed to fetch settings:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAdminUsers = async () => {
    try {
      const response = await getUsers();
      const data = response.data || response || [];
      setAdminUsers(data);
    } catch (error) {
      console.error('Failed to fetch admin users:', error);
    }
  };

  const handleSaveSettings = async () => {
    try {
      setSaving(true);
      await updateSettings({
        ...settings,
        notification_settings: notificationSettings
      });
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
    } catch (error) {
      console.error('Failed to save settings:', error);
      alert('Failed to save settings. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  const tabs = [
    { id: 'company', label: 'Company Info', icon: FaCog },
    { id: 'pricing', label: 'Pricing', icon: FaDollarSign },
    { id: 'users', label: 'User Management', icon: FaUser },
    { id: 'notifications', label: 'Notifications', icon: FaEnvelope }
  ];

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-white mb-2">Settings</h1>
        <p className="text-gray-400">Configure system settings and preferences</p>
      </div>

      <div className="grid lg:grid-cols-4 gap-6">
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-4">
          <nav className="space-y-1">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`w-full text-left px-4 py-3 rounded-lg transition flex items-center gap-3 ${
                  activeTab === tab.id
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                }`}
              >
                <tab.icon />
                <span className="font-medium">{tab.label}</span>
              </button>
            ))}
          </nav>
        </div>

        <div className="lg:col-span-3 bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <FaSpinner className="animate-spin text-blue-500 text-4xl" />
            </div>
          ) : (
            <>
              {activeTab === 'company' && (
                <div>
                  <h2 className="text-xl font-bold text-white mb-6">Company Information</h2>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-gray-400 text-sm mb-2">Company Name</label>
                      <input
                        type="text"
                        value={settings.company_name}
                        onChange={(e) => setSettings({ ...settings, company_name: e.target.value })}
                        className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-gray-400 text-sm mb-2">Contact Email</label>
                      <input
                        type="email"
                        value={settings.contact_email}
                        onChange={(e) => setSettings({ ...settings, contact_email: e.target.value })}
                        className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-gray-400 text-sm mb-2">Phone Number</label>
                      <input
                        type="tel"
                        value={settings.phone_number}
                        onChange={(e) => setSettings({ ...settings, phone_number: e.target.value })}
                        className="w-full px-4 py-3 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                      />
                    </div>
                    <button 
                      onClick={handleSaveSettings}
                      disabled={saving}
                      className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-lg transition flex items-center gap-2"
                    >
                      {saving ? (
                        <>
                          <FaSpinner className="animate-spin" />
                          Saving...
                        </>
                      ) : saveSuccess ? (
                        <>
                          <FaCheck />
                          Saved!
                        </>
                      ) : (
                        'Save Changes'
                      )}
                    </button>
                  </div>
                </div>
              )}

              {activeTab === 'pricing' && (
                <div>
                  <h2 className="text-xl font-bold text-white mb-6">Pricing Configuration</h2>
                  <div className="space-y-4">
                    {['Japan', 'UK', 'UAE'].map((origin) => {
                      const originKey = origin.toLowerCase();
                      const pricing = settings.pricing[originKey] || { base_price: '', processing_fee: '' };
                      
                      return (
                        <div key={origin} className="bg-gray-800/30 border border-gray-700 rounded-lg p-4">
                          <p className="text-white font-semibold mb-3">{origin} → Kampala</p>
                          <div className="grid md:grid-cols-2 gap-4">
                            <div>
                              <label className="block text-gray-400 text-sm mb-2">Base Price</label>
                              <input
                                type="number"
                                value={pricing.base_price || ''}
                                onChange={(e) => {
                                  const newPricing = { ...settings.pricing };
                                  newPricing[originKey] = { ...pricing, base_price: e.target.value };
                                  setSettings({ ...settings, pricing: newPricing });
                                }}
                                placeholder="2000"
                                className="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                              />
                            </div>
                            <div>
                              <label className="block text-gray-400 text-sm mb-2">Processing Fee</label>
                              <input
                                type="number"
                                value={pricing.processing_fee || ''}
                                onChange={(e) => {
                                  const newPricing = { ...settings.pricing };
                                  newPricing[originKey] = { ...pricing, processing_fee: e.target.value };
                                  setSettings({ ...settings, pricing: newPricing });
                                }}
                                placeholder="150"
                                className="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:outline-none focus:border-blue-500"
                              />
                            </div>
                          </div>
                        </div>
                      );
                    })}
                    <button 
                      onClick={handleSaveSettings}
                      disabled={saving}
                      className="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-lg transition flex items-center gap-2"
                    >
                      {saving ? (
                        <>
                          <FaSpinner className="animate-spin" />
                          Updating...
                        </>
                      ) : saveSuccess ? (
                        <>
                          <FaCheck />
                          Updated!
                        </>
                      ) : (
                        'Update Pricing'
                      )}
                    </button>
                  </div>
                </div>
              )}

              {activeTab === 'users' && (
                <div>
                  <h2 className="text-xl font-bold text-white mb-6">Admin Users</h2>
                  {adminUsers.length === 0 ? (
                    <p className="text-gray-400">No admin users found</p>
                  ) : (
                    <div className="space-y-3">
                      {adminUsers.map((user) => (
                        <div key={user.id} className="bg-gray-800/30 border border-gray-700 rounded-lg p-4 flex items-center justify-between">
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                              <FaUser className="text-white" />
                            </div>
                            <div>
                              <p className="text-white font-medium">{user.name || 'Unknown User'}</p>
                              <p className="text-gray-400 text-sm">{user.email}</p>
                            </div>
                          </div>
                          <div className="flex items-center gap-3">
                            <span className={`px-3 py-1 rounded-full text-xs font-semibold ${
                              user.role === 'admin' ? 'bg-purple-900/30 text-purple-400' :
                              user.role === 'manager' ? 'bg-blue-900/30 text-blue-400' :
                              'bg-green-900/30 text-green-400'
                            }`}>
                              {user.role}
                            </span>
                            <button className="text-blue-400 hover:text-blue-300 text-sm font-semibold">Edit</button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}

              {activeTab === 'notifications' && (
                <div>
                  <h2 className="text-xl font-bold text-white mb-6">Notification Settings</h2>
                  <div className="space-y-4">
                    {[
                      { key: 'new_booking_alerts', label: 'New Booking Alerts' },
                      { key: 'payment_notifications', label: 'Payment Notifications' },
                      { key: 'shipment_updates', label: 'Shipment Updates' },
                      { key: 'customer_messages', label: 'Customer Messages' }
                    ].map((notif) => (
                      <div key={notif.key} className="flex items-center justify-between py-3 border-b border-gray-800">
                        <span className="text-gray-300">{notif.label}</span>
                        <input 
                          type="checkbox" 
                          checked={notificationSettings[notif.key] || false}
                          onChange={(e) => {
                            setNotificationSettings({
                              ...notificationSettings,
                              [notif.key]: e.target.checked
                            });
                          }}
                          className="w-5 h-5" 
                        />
                      </div>
                    ))}
                  </div>
                  <button 
                    onClick={handleSaveSettings}
                    disabled={saving}
                    className="mt-6 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-lg transition flex items-center gap-2"
                  >
                    {saving ? (
                      <>
                        <FaSpinner className="animate-spin" />
                        Saving...
                      </>
                    ) : saveSuccess ? (
                      <>
                        <FaCheck />
                        Saved!
                      </>
                    ) : (
                      'Save Notification Settings'
                    )}
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminSettings;
