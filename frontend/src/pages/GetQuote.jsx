import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { FaCar, FaShip, FaCalculator, FaCheckCircle } from 'react-icons/fa';
import axios from 'axios';

// API Base URL
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

const GetQuote = () => {
  const [searchParams] = useSearchParams();
  const vehicleSlug = searchParams.get('vehicle');
  
  const [currentStep, setCurrentStep] = useState(1);
  const [formData, setFormData] = useState({
    vehicleType: '',
    year: '',
    make: '',
    model: '',
    engineSize: '',
    originCountry: '',
    originPort: '',
    shippingMethod: '',
    fullName: '',
    email: '',
    phone: '',
    deliveryLocation: '',
    additionalInfo: ''
  });

  const [estimatedQuote, setEstimatedQuote] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const mapCategoryToVehicleType = (categoryName) => {
    if (!categoryName) return '';
    const categoryLower = categoryName.toLowerCase();
    const mapping = {
      'sedan': 'sedan',
      'suv': 'suv',
      'truck': 'truck',
      'van': 'van',
      'luxury': 'luxury',
      'motorcycle': 'motorcycle'
    };
    return mapping[categoryLower] || '';
  };

  useEffect(() => {
    if (vehicleSlug) {
      setIsLoading(true);
      axios.get(`${API_BASE_URL}/cars/${vehicleSlug}`)
        .then(response => {
          const car = response.data.data || response.data;
          setFormData(prev => ({
            ...prev,
            year: car.year?.toString() || '',
            make: car.brand?.name || car.make || '',
            model: car.model || '',
            engineSize: car.engine_size?.toString() || car.engineSize?.toString() || '',
            vehicleType: mapCategoryToVehicleType(car.category?.name) || ''
          }));
          setIsLoading(false);
        })
        .catch(err => {
          console.error('Failed to load vehicle details:', err);
          setIsLoading(false);
        });
    }
  }, [vehicleSlug]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
        const response = await axios.post(`${API_BASE_URL}/quotes`, formData);
        
        const { reference, total_estimated, breakdown, ai_reasoning, confidence_score, ai_powered } = response.data;
        
        setEstimatedQuote({
            shipping: breakdown?.shipping || 0,
            customs: breakdown?.customs_duty || 800,
            vat: breakdown?.vat || 0,
            levies: breakdown?.levies || 350,
            total: total_estimated,
            reference: reference,
            ai_reasoning: ai_reasoning,
            confidence_score: confidence_score,
            ai_powered: ai_powered
        });

        setCurrentStep(4);
    } catch (err) {
        console.error("Quote Error:", err);
        setError(err.response?.data?.message || 'Failed to generate quote. Please try again.');
    } finally {
        setIsLoading(false);
    }
  };

  const nextStep = () => {
    if (currentStep < 3) setCurrentStep(currentStep + 1);
  };

  const prevStep = () => {
    if (currentStep > 1) setCurrentStep(currentStep - 1);
  };

  const isStepValid = () => {
    if (currentStep === 1) {
      return formData.vehicleType && formData.year && formData.make;
    }
    if (currentStep === 2) {
      return formData.originCountry && formData.shippingMethod;
    }
    if (currentStep === 3) {
      return formData.fullName && formData.email && formData.phone;
    }
    return true;
  };

  const steps = [
    { number: 1, title: 'Vehicle Info', icon: <FaCar /> },
    { number: 2, title: 'Shipping Details', icon: <FaShip /> },
    { number: 3, title: 'Your Details', icon: <FaCalculator /> },
    { number: 4, title: 'Get Quote', icon: <FaCheckCircle /> }
  ];

  return (
    <div className="bg-gray-50 min-h-screen">
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Get Your Free Quote</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Fill in your details and get an instant, AI-powered transparent quote for shipping your car to Uganda.
          </p>
        </div>
      </section>

      <section className="section-padding pt-12">
        <div className="container-custom max-w-4xl mx-auto">
          <div className="flex justify-between mb-12">
            {steps.map((step, index) => (
              <div key={index} className="flex flex-col items-center flex-1">
                <div className={`w-16 h-16 rounded-full flex items-center justify-center text-2xl mb-3 transition-all duration-300 ${
                  currentStep >= step.number
                    ? 'bg-gradient-primary text-white shadow-glow'
                    : 'bg-gray-200 text-gray-400'
                }`}>
                  {step.icon}
                </div>
                <p className={`text-sm font-medium ${
                  currentStep >= step.number ? 'text-blue-600' : 'text-gray-400'
                }`}>
                  {step.title}
                </p>
              </div>
            ))}
          </div>

          <div className="bg-white rounded-2xl shadow-xl p-8 md:p-12">
            {error && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    {error}
                </div>
            )}
          
            <form onSubmit={handleSubmit}>
              {currentStep === 1 && (
                <div className="space-y-6">
                  <h2 className="text-2xl font-bold text-navy-900 mb-6">Tell Us About Your Vehicle</h2>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Vehicle Type *</label>
                      <select name="vehicleType" value={formData.vehicleType} onChange={handleInputChange}
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select type</option>
                        <option value="sedan">Sedan</option>
                        <option value="suv">SUV / 4x4</option>
                        <option value="truck">Pickup Truck</option>
                        <option value="van">Van / Minibus</option>
                        <option value="luxury">Luxury Car</option>
                        <option value="motorcycle">Motorcycle</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Year *</label>
                      <input type="number" name="year" value={formData.year} onChange={handleInputChange}
                        placeholder="e.g., 2020" min="1990" max="2025"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Make *</label>
                      <input type="text" name="make" value={formData.make} onChange={handleInputChange}
                        placeholder="e.g., Toyota"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Model</label>
                      <input type="text" name="model" value={formData.model} onChange={handleInputChange}
                        placeholder="e.g., Land Cruiser"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-700 mb-2">Engine Size (cc)</label>
                      <input type="number" name="engineSize" value={formData.engineSize} onChange={handleInputChange}
                        placeholder="e.g., 2500"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                  </div>
                </div>
              )}

              {currentStep === 2 && (
                <div className="space-y-6">
                  <h2 className="text-2xl font-bold text-navy-900 mb-6">Shipping Information</h2>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Origin Country *</label>
                      <select name="originCountry" value={formData.originCountry} onChange={handleInputChange}
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select country</option>
                        <option value="japan">🇯🇵 Japan</option>
                        <option value="uk">🇬🇧 United Kingdom</option>
                        <option value="uae">🇦🇪 UAE (Dubai)</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Origin Port</label>
                      <input type="text" name="originPort" value={formData.originPort} onChange={handleInputChange}
                        placeholder="e.g., Tokyo, Southampton, Dubai"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-700 mb-3">Shipping Method *</label>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label className={`border-2 rounded-lg p-6 cursor-pointer transition ${
                          formData.shippingMethod === 'roro' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300'
                        }`}>
                          <input type="radio" name="shippingMethod" value="roro" checked={formData.shippingMethod === 'roro'}
                            onChange={handleInputChange} className="sr-only" />
                          <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                              <FaShip className="text-blue-600 text-xl" />
                            </div>
                            <div className="flex-1">
                              <h4 className="font-bold text-gray-900 mb-1">RoRo Shipping</h4>
                              <p className="text-sm text-gray-600">Most economical. Vehicle driven onto ship deck.</p>
                            </div>
                          </div>
                        </label>
                        <label className={`border-2 rounded-lg p-6 cursor-pointer transition ${
                          formData.shippingMethod === 'container' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-blue-300'
                        }`}>
                          <input type="radio" name="shippingMethod" value="container" checked={formData.shippingMethod === 'container'}
                            onChange={handleInputChange} className="sr-only" />
                          <div className="flex items-start gap-3">
                            <div className="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                              <FaShip className="text-blue-600 text-xl" />
                            </div>
                            <div className="flex-1">
                              <h4 className="font-bold text-gray-900 mb-1">Container Shipping</h4>
                              <p className="text-sm text-gray-600">Maximum protection. Private enclosed container.</p>
                            </div>
                          </div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {currentStep === 3 && (
                <div className="space-y-6">
                  <h2 className="text-2xl font-bold text-navy-900 mb-6">Your Contact Information</h2>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                      <input type="text" name="fullName" value={formData.fullName} onChange={handleInputChange}
                        placeholder="John Doe"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                      <input type="email" name="email" value={formData.email} onChange={handleInputChange}
                        placeholder="john@example.com"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                      <input type="tel" name="phone" value={formData.phone} onChange={handleInputChange}
                        placeholder="+256 700 000 000"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-700 mb-2">Delivery Location in Uganda</label>
                      <input type="text" name="deliveryLocation" value={formData.deliveryLocation} onChange={handleInputChange}
                        placeholder="e.g., Kampala, Entebbe, Jinja"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-700 mb-2">Additional Information</label>
                      <textarea name="additionalInfo" value={formData.additionalInfo} onChange={handleInputChange}
                        rows="4" placeholder="Any special requirements or questions..."
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                  </div>
                </div>
              )}

              {currentStep === 4 && estimatedQuote && (
                <div className="space-y-6">
                  <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                      <FaCheckCircle className="text-green-600 text-4xl" />
                    </div>
                    <h2 className="text-3xl font-bold text-navy-900 mb-2">Your Estimated Quote</h2>
                    <p className="text-gray-600 text-lg">Reference: <span className="font-bold text-blue-600">{estimatedQuote.reference}</span></p>
                    {estimatedQuote.ai_powered && (
                      <div className="inline-flex items-center gap-2 mt-2 px-4 py-2 bg-purple-50 border border-purple-200 rounded-full">
                        <span className="text-2xl">🤖</span>
                        <span className="text-sm font-medium text-purple-700">AI-Powered Quote</span>
                      </div>
                    )}
                  </div>

                  {estimatedQuote.ai_reasoning && (
                    <div className="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-6 mb-6">
                      <div className="flex items-start gap-3">
                        <div className="flex-shrink-0 text-3xl">💡</div>
                        <div className="flex-1">
                          <h3 className="font-bold text-gray-900 mb-2">AI Pricing Analysis</h3>
                          <p className="text-gray-700 text-sm leading-relaxed mb-3">{estimatedQuote.ai_reasoning}</p>
                          {estimatedQuote.confidence_score && (
                            <div className="flex items-center gap-2">
                              <span className="text-xs font-medium text-gray-600">Confidence:</span>
                              <div className="flex-1 max-w-xs bg-gray-200 rounded-full h-2">
                                <div 
                                  className="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-500"
                                  style={{ width: `${(estimatedQuote.confidence_score * 100).toFixed(0)}%` }}
                                ></div>
                              </div>
                              <span className="text-xs font-bold text-purple-700">
                                {(estimatedQuote.confidence_score * 100).toFixed(0)}%
                              </span>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  )}

                  <div className="bg-gray-50 rounded-xl p-6 space-y-4">
                    <div className="flex justify-between items-center pb-3 border-b border-gray-200">
                      <span className="text-gray-700">Ocean Freight ({formData.shippingMethod.toUpperCase()})</span>
                      <span className="font-semibold">${estimatedQuote.shipping.toLocaleString()}</span>
                    </div>
                    <div className="flex justify-between items-center pb-3 border-b border-gray-200">
                      <span className="text-gray-700">Customs Duty (Estimated)</span>
                      <span className="font-semibold">${estimatedQuote.customs.toLocaleString()}</span>
                    </div>
                    <div className="flex justify-between items-center pb-3 border-b border-gray-200">
                      <span className="text-gray-700">VAT (18%)</span>
                      <span className="font-semibold">${estimatedQuote.vat.toLocaleString()}</span>
                    </div>
                    <div className="flex justify-between items-center pb-3 border-b border-gray-200">
                      <span className="text-gray-700">Levies & Fees</span>
                      <span className="font-semibold">${estimatedQuote.levies.toLocaleString()}</span>
                    </div>
                    <div className="flex justify-between items-center pt-3">
                      <span className="text-xl font-bold text-navy-900">Total Estimated Cost</span>
                      <span className="text-3xl font-bold text-blue-600">${parseFloat(estimatedQuote.total).toLocaleString()}</span>
                    </div>
                  </div>

                  {/* Portal Access Notification */}
                  <div className="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-6 mt-6">
                    <div className="flex items-start gap-4">
                      <div className="flex-shrink-0 text-4xl">🔐</div>
                      <div className="flex-1">
                        <h3 className="text-lg font-bold text-green-900 mb-2">Customer Portal Access Created!</h3>
                        <p className="text-sm text-green-800 mb-3">
                          We've sent login credentials to <strong>{formData.email}</strong>. Check your email to access your customer portal where you can:
                        </p>
                        <ul className="space-y-2 text-sm text-green-800">
                          <li className="flex items-center gap-2">
                            <span className="text-green-600">✓</span>
                            <span>View your quote status in real-time</span>
                          </li>
                          <li className="flex items-center gap-2">
                            <span className="text-green-600">✓</span>
                            <span>Accept your quote when approved</span>
                          </li>
                          <li className="flex items-center gap-2">
                            <span className="text-green-600">✓</span>
                            <span>Track your shipment progress</span>
                          </li>
                          <li className="flex items-center gap-2">
                            <span className="text-green-600">✓</span>
                            <span>Upload required documents</span>
                          </li>
                          <li className="flex items-center gap-2">
                            <span className="text-green-600">✓</span>
                            <span>Manage your bookings</span>
                          </li>
                        </ul>
                        <div className="mt-4 pt-4 border-t border-green-200">
                          <p className="text-xs text-green-700">
                            <strong>📧 Check your email</strong> for your temporary password and portal access link.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                    <p className="text-sm text-gray-700">
                      <strong>Note:</strong> This is a preliminary estimate saved with reference <strong>{estimatedQuote.reference}</strong>. A member of our team will review and approve your quote. You'll be notified via email when it's ready to accept.
                    </p>
                  </div>

                  <div className="text-center mt-8">
                    <button type="button"
                      onClick={() => {
                          setCurrentStep(1);
                          setEstimatedQuote(null);
                          setFormData(prev => ({ ...prev, year: '', make: '', model: '' }));
                      }}
                      className="btn-primary px-12">
                      Request New Quote
                    </button>
                  </div>
                </div>
              )}

              {currentStep < 4 && (
                <div className="flex gap-4 mt-8 justify-between">
                  {currentStep > 1 && (
                    <button type="button" onClick={prevStep} className="btn-outline px-8">
                      Previous
                    </button>
                  )}
                  <div className="flex-1"></div>
                  {currentStep < 3 && (
                    <button type="button" onClick={nextStep} disabled={!isStepValid()}
                      className="btn-primary px-8 disabled:opacity-50 disabled:cursor-not-allowed">
                      Next Step
                    </button>
                  )}
                  {currentStep === 3 && (
                    <button type="submit" disabled={!isStepValid() || isLoading}
                      className="btn-primary px-8 disabled:opacity-50 disabled:cursor-not-allowed">
                      {isLoading ? 'Generating AI Quote...' : 'Calculate Quote'}
                    </button>
                  )}
                </div>
              )}
            </form>
          </div>
        </div>
      </section>

      <section className="section-padding bg-white">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
              <div className="text-4xl mb-3">🔒</div>
              <h3 className="font-bold text-navy-900 mb-2">100% Secure</h3>
              <p className="text-gray-600 text-sm">Your information is protected and never shared</p>
            </div>
            <div>
              <div className="text-4xl mb-3">🤖</div>
              <h3 className="font-bold text-navy-900 mb-2">AI-Powered</h3>
              <p className="text-gray-600 text-sm">Intelligent pricing with market analysis</p>
            </div>
            <div>
              <div className="text-4xl mb-3">🎯</div>
              <h3 className="font-bold text-navy-900 mb-2">No Obligation</h3>
              <p className="text-gray-600 text-sm">Free quote with no commitment required</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default GetQuote;
