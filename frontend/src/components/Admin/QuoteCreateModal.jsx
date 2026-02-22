import React, { useState } from 'react';
import { 
  FaTimes, 
  FaPlus,
  FaCar,
  FaMapMarkerAlt,
  FaUser,
  FaDollarSign,
  FaCalendarAlt,
  FaInfoCircle
} from 'react-icons/fa';
import { createQuote } from '../../services/adminService';
import { showAlert } from '../../utils/sweetAlert';

const QuoteCreateModal = ({ onClose, onSave }) => {
  const [formData, setFormData] = useState({
    // Customer Information
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    
    // Vehicle Information
    vehicle_make: '',
    vehicle_model: '',
    vehicle_year: '',
    vehicle_type: 'sedan',
    vehicle_condition: 'used',
    
    // Route Information
    origin_country: '',
    origin_city: '',
    destination_country: 'Uganda',
    destination_city: 'Kampala',
    
    // Quote Details
    shipping_cost: '',
    insurance_cost: '',
    customs_cost: '',
    handling_cost: '',
    total_amount: '',
    currency: 'USD',
    valid_until: '',
    
    // Additional Information
    notes: '',
    special_requirements: ''
  });
  
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('customer');

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Auto-calculate total when individual costs change
    if (['shipping_cost', 'insurance_cost', 'customs_cost', 'handling_cost'].includes(name)) {
      calculateTotal({ ...formData, [name]: value });
    }
  };

  const calculateTotal = (data = formData) => {
    const shipping = parseFloat(data.shipping_cost) || 0;
    const insurance = parseFloat(data.insurance_cost) || 0;
    const customs = parseFloat(data.customs_cost) || 0;
    const handling = parseFloat(data.handling_cost) || 0;
    
    const total = shipping + insurance + customs + handling;
    setFormData(prev => ({ ...prev, total_amount: total.toString() }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      // Prepare data for API
      const quoteData = {
        // Customer info
        customer_name: formData.customer_name,
        customer_email: formData.customer_email,
        customer_phone: formData.customer_phone,
        
        // Vehicle info
        vehicle_details: {
          make: formData.vehicle_make,
          model: formData.vehicle_model,
          year: formData.vehicle_year,
          type: formData.vehicle_type,
          condition: formData.vehicle_condition
        },
        
        // Route info
        origin_country: formData.origin_country,
        origin_city: formData.origin_city,
        destination_country: formData.destination_country,
        destination_city: formData.destination_city,
        
        // Costs
        shipping_cost: parseFloat(formData.shipping_cost) || 0,
        insurance_cost: parseFloat(formData.insurance_cost) || 0,
        customs_cost: parseFloat(formData.customs_cost) || 0,
        handling_cost: parseFloat(formData.handling_cost) || 0,
        total_amount: parseFloat(formData.total_amount) || 0,
        currency: formData.currency,
        
        // Validity
        valid_until: formData.valid_until,
        
        // Additional info
        notes: formData.notes,
        special_requirements: formData.special_requirements,
        
        // Default status
        status: 'pending'
      };

      await createQuote(quoteData);
      await showAlert('Success', 'Quote created successfully', 'success');
      onSave();
    } catch (error) {
      console.error('Create failed:', error);
      const errorMessage = error.response?.data?.message || 'Failed to create quote';
      await showAlert('Error', errorMessage, 'error');
    } finally {
      setLoading(false);
    }
  };

  const tabs = [
    { id: 'customer', label: 'Customer Info', icon: FaUser },
    { id: 'vehicle', label: 'Vehicle Details', icon: FaCar },
    { id: 'route', label: 'Route & Location', icon: FaMapMarkerAlt },
    { id: 'pricing', label: 'Pricing & Terms', icon: FaDollarSign }
  ];

  // Set default expiry date (30 days from now)
  React.useEffect(() => {
    if (!formData.valid_until) {
      const defaultExpiry = new Date();
      defaultExpiry.setDate(defaultExpiry.getDate() + 30);
      setFormData(prev => ({
        ...prev,
        valid_until: defaultExpiry.toISOString().split('T')[0]
      }));
    }
  }, []);

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-800">
          <div className="flex items-center gap-4">
            <FaPlus className="text-blue-500 text-2xl" />
            <div>
              <h2 className="text-2xl font-bold text-white">Create New Quote</h2>
              <p className="text-gray-400">Generate a shipping quote for a customer</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
          >
            <FaTimes className="text-xl" />
          </button>
        </div>

        {/* Tabs */}
        <div className="flex border-b border-gray-800 overflow-x-auto">
          {tabs.map((tab) => {
            const IconComponent = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap ${
                  activeTab === tab.id
                    ? 'text-blue-400 border-b-2 border-blue-400 bg-blue-900/10'
                    : 'text-gray-400 hover:text-white hover:bg-gray-800/50'
                }`}
              >
                <IconComponent />
                {tab.label}
              </button>
            );
          })}
        </div>

        <form onSubmit={handleSubmit}>
          <div className="p-6 space-y-6">
            {/* Customer Info Tab */}
            {activeTab === 'customer' && (
              <div className="space-y-4">
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Customer Name *
                    </label>
                    <input
                      type="text"
                      name="customer_name"
                      value={formData.customer_name}
                      onChange={handleInputChange}
                      required
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Enter customer full name"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Email Address *
                    </label>
                    <input
                      type="email"
                      name="customer_email"
                      value={formData.customer_email}
                      onChange={handleInputChange}
                      required
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="customer@example.com"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">
                    Phone Number
                  </label>
                  <input
                    type="tel"
                    name="customer_phone"
                    value={formData.customer_phone}
                    onChange={handleInputChange}
                    className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                    placeholder="+256 700 000 000"
                  />
                </div>
              </div>
            )}

            {/* Vehicle Details Tab */}
            {activeTab === 'vehicle' && (
              <div className="space-y-4">
                <div className="grid md:grid-cols-3 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Make *
                    </label>
                    <input
                      type="text"
                      name="vehicle_make"
                      value={formData.vehicle_make}
                      onChange={handleInputChange}
                      required
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Toyota, Honda, BMW"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Model *
                    </label>
                    <input
                      type="text"
                      name="vehicle_model"
                      value={formData.vehicle_model}
                      onChange={handleInputChange}
                      required
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Camry, Civic, X5"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Year *
                    </label>
                    <input
                      type="number"
                      name="vehicle_year"
                      value={formData.vehicle_year}
                      onChange={handleInputChange}
                      required
                      min="1990"
                      max="2030"
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="2020"
                    />
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Vehicle Type
                    </label>
                    <select
                      name="vehicle_type"
                      value={formData.vehicle_type}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                    >
                      <option value="sedan">Sedan</option>
                      <option value="suv">SUV</option>
                      <option value="hatchback">Hatchback</option>
                      <option value="coupe">Coupe</option>
                      <option value="truck">Truck</option>
                      <option value="van">Van</option>
                      <option value="motorcycle">Motorcycle</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Condition
                    </label>
                    <select
                      name="vehicle_condition"
                      value={formData.vehicle_condition}
                      onChange={handleInputChange}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                    >
                      <option value="new">New</option>
                      <option value="used">Used</option>
                      <option value="damaged">Damaged</option>
                    </select>
                  </div>
                </div>
              </div>
            )}

            {/* Route & Location Tab */}
            {activeTab === 'route' && (
              <div className="space-y-4">
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-white">Origin</h3>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Country *
                      </label>
                      <select
                        name="origin_country"
                        value={formData.origin_country}
                        onChange={handleInputChange}
                        required
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                      >
                        <option value="">Select origin country</option>
                        <option value="Japan">Japan</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="UAE">UAE</option>
                        <option value="United States">United States</option>
                        <option value="Germany">Germany</option>
                        <option value="Canada">Canada</option>
                        <option value="South Korea">South Korea</option>
                        <option value="Thailand">Thailand</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        City *
                      </label>
                      <input
                        type="text"
                        name="origin_city"
                        value={formData.origin_city}
                        onChange={handleInputChange}
                        required
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="Tokyo, London, Dubai"
                      />
                    </div>
                  </div>

                  <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-white">Destination</h3>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Country
                      </label>
                      <input
                        type="text"
                        name="destination_country"
                        value={formData.destination_country}
                        onChange={handleInputChange}
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="Uganda"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        City
                      </label>
                      <input
                        type="text"
                        name="destination_city"
                        value={formData.destination_city}
                        onChange={handleInputChange}
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="Kampala"
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Pricing & Terms Tab */}
            {activeTab === 'pricing' && (
              <div className="space-y-6">
                <div className="grid md:grid-cols-2 gap-6">
                  <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-white">Cost Breakdown</h3>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Shipping Cost *
                      </label>
                      <input
                        type="number"
                        name="shipping_cost"
                        value={formData.shipping_cost}
                        onChange={handleInputChange}
                        required
                        min="0"
                        step="0.01"
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="2000.00"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Insurance Cost
                      </label>
                      <input
                        type="number"
                        name="insurance_cost"
                        value={formData.insurance_cost}
                        onChange={handleInputChange}
                        min="0"
                        step="0.01"
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="300.00"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Customs/Duties
                      </label>
                      <input
                        type="number"
                        name="customs_cost"
                        value={formData.customs_cost}
                        onChange={handleInputChange}
                        min="0"
                        step="0.01"
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="800.00"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Handling Fees
                      </label>
                      <input
                        type="number"
                        name="handling_cost"
                        value={formData.handling_cost}
                        onChange={handleInputChange}
                        min="0"
                        step="0.01"
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="200.00"
                      />
                    </div>
                  </div>

                  <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-white">Quote Terms</h3>
                    <div className="bg-green-900/20 border border-green-700/50 rounded-lg p-4">
                      <p className="text-gray-400 text-sm mb-1">Total Amount</p>
                      <p className="text-green-400 text-2xl font-bold">
                        ${formData.total_amount ? parseFloat(formData.total_amount).toLocaleString() : '0'}
                      </p>
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Currency
                      </label>
                      <select
                        name="currency"
                        value={formData.currency}
                        onChange={handleInputChange}
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                      >
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="GBP">GBP</option>
                        <option value="JPY">JPY</option>
                      </select>
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-2">
                        Valid Until *
                      </label>
                      <input
                        type="date"
                        name="valid_until"
                        value={formData.valid_until}
                        onChange={handleInputChange}
                        required
                        className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
                      />
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Special Requirements
                    </label>
                    <textarea
                      name="special_requirements"
                      value={formData.special_requirements}
                      onChange={handleInputChange}
                      rows={3}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Any special handling requirements, delivery instructions, etc."
                    />
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-2">
                      Internal Notes
                    </label>
                    <textarea
                      name="notes"
                      value={formData.notes}
                      onChange={handleInputChange}
                      rows={3}
                      className="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                      placeholder="Internal notes for admin reference"
                    />
                  </div>
                </div>

                <div className="bg-blue-900/20 border border-blue-700/50 rounded-lg p-4">
                  <div className="flex items-start gap-3">
                    <FaInfoCircle className="text-blue-400 mt-1" />
                    <div>
                      <h4 className="text-blue-400 font-medium mb-1">Quote Information</h4>
                      <p className="text-gray-300 text-sm">
                        This quote will be created with "Pending" status and will require approval before being sent to the customer. 
                        The customer will receive an email notification once the quote is approved.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="flex justify-end gap-4 p-6 border-t border-gray-800">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {loading ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                  Creating...
                </>
              ) : (
                <>
                  <FaPlus />
                  Create Quote
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default QuoteCreateModal;