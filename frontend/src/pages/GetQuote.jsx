import { useState, useEffect, useCallback, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import { FaCar, FaShip, FaCalculator, FaCheckCircle, FaMagic, FaSpinner } from 'react-icons/fa';
import axios from 'axios';

// API Base URLs
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const AI_SERVICE_URL = import.meta.env.VITE_AI_SERVICE_URL || 'http://localhost:8001';

const GetQuote = () => {
  const [searchParams] = useSearchParams();
  const vehicleSlug = searchParams.get('vehicle');
  
  const [currentStep, setCurrentStep] = useState(1);
  const [formData, setFormData] = useState({
    vehicleType: '',
    year: '',
    make: '',
    model: '',
    color: '',
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
  const [prefilledCar, setPrefilledCar] = useState(null);
  const [engineTypeLabel, setEngineTypeLabel] = useState('');
  const [rawCategory, setRawCategory] = useState('');

  // AI feature states
  const [nlDescription, setNlDescription] = useState('');
  const [nlLoading, setNlLoading] = useState(false);
  const [nlError, setNlError] = useState('');
  const [livePreview, setLivePreview] = useState(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [validationWarnings, setValidationWarnings] = useState([]);
  const [suggestionLoading, setSuggestionLoading] = useState(false);
  const [suggestion, setSuggestion] = useState(null);
  const previewTimerRef = useRef(null);
  const validationTimerRef = useRef(null);

  const mapCategoryToVehicleType = (categoryName) => {
    if (!categoryName) return '';
    const c = categoryName.toLowerCase();
    if (c.includes('sedan')) return 'sedan';
    if (c.includes('suv') || c.includes('4x4')) return 'suv';
    if (c.includes('truck') || c.includes('pickup')) return 'truck';
    if (c.includes('van') || c.includes('minibus')) return 'van';
    if (c.includes('luxury')) return 'luxury';
    if (c.includes('motorcycle') || c.includes('moto')) return 'motorcycle';
    if (c.includes('hatchback')) return 'hatchback';
    if (c.includes('wagon') || c.includes('estate')) return 'wagon';
    if (c.includes('coupe') || c.includes('coupé')) return 'coupe';
    if (c.includes('convertible') || c.includes('cabriolet') || c.includes('roadster')) return 'convertible';
    return '';
  };

  const mapCityToPort = (city) => {
    if (!city) return city;
    const portMap = {
      // Japan
      'tokyo': 'Tokyo (Yokohama Port)',
      'yokohama': 'Yokohama Port',
      'osaka': 'Osaka Port',
      'nagoya': 'Nagoya Port',
      'kobe': 'Kobe Port',
      // UK
      'london': 'Southampton Port',
      'birmingham': 'Southampton Port',
      'southampton': 'Southampton Port',
      'bristol': 'Bristol Port',
      'glasgow': 'Glasgow Port',
      // UAE
      'dubai': 'Jebel Ali Port (Dubai)',
      'abu dhabi': 'Abu Dhabi Port',
      'sharjah': 'Sharjah Port',
    };
    return portMap[city.toLowerCase()] || city;
  };

  const mapOriginCountry = (locationCountry) => {
    if (!locationCountry) return '';
    const c = locationCountry.toLowerCase();
    if (c.includes('japan')) return 'japan';
    if (c.includes('uk') || c.includes('united kingdom') || c.includes('britain') || c.includes('england')) return 'uk';
    if (c.includes('uae') || c.includes('dubai') || c.includes('united arab')) return 'uae';
    return '';
  };

  // #2 — Natural language form filler
  const handleNlParse = async () => {
    if (!nlDescription.trim()) return;
    setNlLoading(true);
    setNlError('');
    try {
      const res = await axios.post(`${AI_SERVICE_URL}/agents/parse-description`, { description: nlDescription });
      if (res.data.success && res.data.data) {
        const d = res.data.data;
        setFormData(prev => ({
          ...prev,
          ...(d.year && { year: d.year }),
          ...(d.make && { make: d.make }),
          ...(d.model && { model: d.model }),
          ...(d.vehicleType && { vehicleType: d.vehicleType }),
          ...(d.engineSize && { engineSize: d.engineSize }),
          ...(d.originCountry && { originCountry: d.originCountry }),
        }));
        setNlDescription('');
      } else {
        setNlError('Could not understand that description. Try: "2019 Toyota RAV4 from Japan"');
      }
    } catch (err) {
      const detail = err?.response?.data?.detail || err?.message || '';
      if (err?.code === 'ERR_NETWORK' || err?.code === 'ECONNREFUSED') {
        setNlError('AI service unavailable. Please fill in the fields manually.');
      } else {
        setNlError(`Could not parse description${detail ? ': ' + detail : ''}. Try: "2019 Toyota RAV4 from Japan"`);
      }
    } finally {
      setNlLoading(false);
    }
  };

  // #5 — Auto-suggest vehicle type + engine from make+model (debounced)
  const triggerSuggestion = useCallback((make, model) => {
    if (!make || make.length < 2) return;
    clearTimeout(validationTimerRef.current);
    validationTimerRef.current = setTimeout(async () => {
      setSuggestionLoading(true);
      try {
        const res = await axios.post(`${AI_SERVICE_URL}/agents/suggest-vehicle`, { make, model });
        if (res.data.success && res.data.data) {
          setSuggestion(res.data.data);
        }
      } catch {
        // silent — suggestion is optional
      } finally {
        setSuggestionLoading(false);
      }
    }, 900);
  }, []);

  // #4 — Smart field validation (debounced, runs after Step 1 is filled)
  const triggerValidation = useCallback((data) => {
    if (!data.make || !data.year) return;
    clearTimeout(previewTimerRef.current);
    previewTimerRef.current = setTimeout(async () => {
      try {
        const res = await axios.post(`${AI_SERVICE_URL}/agents/validate-vehicle`, data);
        setValidationWarnings(res.data.warnings || []);
      } catch {
        // silent
      }
    }, 1200);
  }, []);

  // #1 — Live quote preview (debounced, runs on Step 2 when fields are ready)
  const triggerLivePreview = useCallback((data) => {
    if (!data.vehicleType || !data.year || !data.make || !data.originCountry || !data.shippingMethod) {
      setLivePreview(null);
      return;
    }
    setPreviewLoading(true);
    clearTimeout(previewTimerRef.current);
    previewTimerRef.current = setTimeout(async () => {
      try {
        const res = await axios.post(`${AI_SERVICE_URL}/agents/quote-preview`, {
          vehicle_type: data.vehicleType,
          year: parseInt(data.year) || 2020,
          make: data.make,
          model: data.model || '',
          engine_size: parseInt(data.engineSize) || null,
          origin_country: data.originCountry,
          origin_port: data.originPort || '',
          shipping_method: data.shippingMethod,
        });
        setLivePreview(res.data);
      } catch {
        setLivePreview(null);
      } finally {
        setPreviewLoading(false);
      }
    }, 1000);
  }, []);

  useEffect(() => {
    if (vehicleSlug) {
      setIsLoading(true);
      axios.get(`${API_BASE_URL}/cars/${vehicleSlug}`)
        .then(response => {
          const car = response.data.data || response.data;
          const originCountry = mapOriginCountry(car.location_country);
          const originPort = originCountry ? mapCityToPort(car.location_city || '') : '';
          const mappedType = mapCategoryToVehicleType(car.category?.name);
          const engineType = car.engine_type || '';
          const engineNum = car.engine_size?.toString() || car.engineSize?.toString() || engineType.match(/[\d.]+/)?.[0] || '';
          const carName = `${car.year || ''} ${car.brand?.name || car.make || ''} ${car.model || ''}`.trim();
          const additionalInfo = [
            engineType ? `Engine: ${engineType}` : '',
            car.transmission ? `Transmission: ${car.transmission}` : '',
            car.fuel_type ? `Fuel: ${car.fuel_type}` : '',
            car.condition ? `Condition: ${car.condition}` : '',
          ].filter(Boolean).join(', ');

          setEngineTypeLabel(engineType);
          setRawCategory(mappedType ? '' : (car.category?.name || ''));
          setPrefilledCar({
            name: carName,
            image: car.images?.[0]?.url || car.primary_image || null,
          });
          setFormData(prev => ({
            ...prev,
            year: car.year?.toString() || '',
            make: car.brand?.name || car.make || '',
            model: car.model || '',
            color: car.color || '',
            engineSize: engineNum,
            vehicleType: mappedType,
            originCountry,
            originPort,
            additionalInfo: additionalInfo || prev.additionalInfo,
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
    setFormData(prev => {
      const updated = { ...prev, [name]: value };
      // Trigger AI suggestion when make or model changes
      if (name === 'make' || name === 'model') {
        triggerSuggestion(updated.make, updated.model);
        triggerValidation(updated);
      }
      // Trigger validation when year or vehicleType changes
      if (name === 'year' || name === 'vehicleType' || name === 'engineSize' || name === 'color') {
        triggerValidation(updated);
      }
      // Trigger live preview on Step 2 fields
      if (['originCountry', 'shippingMethod', 'originPort'].includes(name)) {
        triggerLivePreview(updated);
      }
      return updated;
    });
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
    if (currentStep < 3) {
      const next = currentStep + 1;
      setCurrentStep(next);
      if (next === 2) triggerLivePreview(formData);
    }
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

          {/* Pre-filled Banner */}
          {prefilledCar && (
            <div className="mb-8 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-4">
              {prefilledCar.image && (
                <img src={prefilledCar.image} alt={prefilledCar.name} className="w-16 h-12 object-cover rounded-lg flex-shrink-0" />
              )}
              {!prefilledCar.image && (
                <div className="w-16 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <FaCar className="text-blue-500 text-xl" />
                </div>
              )}
              <div className="flex-1">
                <p className="text-blue-800 font-semibold text-sm">
                  ✅ Vehicle details pre-filled from: <span className="font-bold">{prefilledCar.name}</span>
                </p>
                <p className="text-blue-600 text-xs mt-0.5">Review the fields below and adjust anything that needs changing.</p>
              </div>
              <button
                type="button"
                onClick={() => setPrefilledCar(null)}
                className="text-blue-400 hover:text-blue-600 text-lg font-bold flex-shrink-0"
                aria-label="Dismiss"
              >
                ×
              </button>
            </div>
          )}

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
                  <h2 className="text-2xl font-bold text-navy-900 mb-6">Tell Us About Your Vehicle
                    {prefilledCar && <span className="ml-3 text-sm font-normal text-blue-500 bg-blue-50 px-2 py-1 rounded-full">✨ Pre-filled</span>}
                  </h2>

                  {/* #2 — Natural Language Form Filler */}
                  <div className="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-4">
                    <label className="block text-sm font-semibold text-purple-800 mb-2">
                      🤖 Describe your car in plain text <span className="font-normal text-purple-600">(AI will fill the form)</span>
                    </label>
                    <div className="flex gap-2">
                      <input
                        type="text"
                        value={nlDescription}
                        onChange={e => setNlDescription(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && handleNlParse()}
                        placeholder='e.g. "2019 Toyota Land Cruiser from Japan" or "BMW X5 diesel UK"'
                        className="flex-1 px-4 py-2.5 border border-purple-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400 focus:border-purple-400 bg-white"
                      />
                      <button
                        type="button"
                        onClick={handleNlParse}
                        disabled={nlLoading || !nlDescription.trim()}
                        className="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                      >
                        {nlLoading ? <FaSpinner className="animate-spin" /> : <FaMagic />}
                        {nlLoading ? 'Parsing...' : 'Auto-fill'}
                      </button>
                    </div>
                    {nlError && <p className="text-red-600 text-xs mt-2">{nlError}</p>}
                  </div>

                  {/* #5 — AI Suggestion Banner */}
                  {suggestion && !suggestionLoading && (
                    <div className="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 flex items-start gap-3">
                      <span className="text-xl">💡</span>
                      <div className="flex-1 text-sm">
                        <span className="font-semibold text-amber-800">AI Suggestion: </span>
                        <span className="text-amber-700">
                          {formData.make} {formData.model} is typically a <strong>{suggestion.vehicleType}</strong>
                          {suggestion.engineSize ? `, ~${suggestion.engineSize}cc engine` : ''}
                          {suggestion.originCountry ? `, commonly imported from ${suggestion.originCountry}` : ''}.
                        </span>
                        <button
                          type="button"
                          onClick={() => {
                            setFormData(prev => ({
                              ...prev,
                              ...(suggestion.vehicleType && !prev.vehicleType && { vehicleType: suggestion.vehicleType }),
                              ...(suggestion.engineSize && !prev.engineSize && { engineSize: suggestion.engineSize }),
                              ...(suggestion.originCountry && !prev.originCountry && { originCountry: suggestion.originCountry }),
                            }));
                            setSuggestion(null);
                          }}
                          className="ml-2 underline text-amber-800 font-medium hover:text-amber-900"
                        >Apply</button>
                        <button type="button" onClick={() => setSuggestion(null)} className="ml-2 text-amber-400 hover:text-amber-600">✕</button>
                      </div>
                    </div>
                  )}
                  {suggestionLoading && (
                    <div className="text-xs text-gray-400 flex items-center gap-2">
                      <FaSpinner className="animate-spin" /> AI is looking up {formData.make} {formData.model}...
                    </div>
                  )}

                  {/* #4 — Validation Warnings */}
                  {validationWarnings.length > 0 && (
                    <div className="bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
                      <p className="text-sm font-semibold text-orange-800 mb-1">⚠️ Please review:</p>
                      <ul className="space-y-1">
                        {validationWarnings.map((w, i) => (
                          <li key={i} className="text-sm text-orange-700">• {w}</li>
                        ))}
                      </ul>
                    </div>
                  )}

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Vehicle Type *</label>
                      <select name="vehicleType" value={formData.vehicleType} onChange={handleInputChange}
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Select type</option>
                        {rawCategory && (
                          <option value="" disabled>── {rawCategory} (not yet supported) ──</option>
                        )}
                        <option value="sedan">Sedan</option>
                        <option value="suv">SUV / 4x4</option>
                        <option value="truck">Pickup Truck</option>
                        <option value="van">Van / Minibus</option>
                        <option value="luxury">Luxury Car</option>
                        <option value="motorcycle">Motorcycle</option>
                        <option value="hatchback">Hatchback</option>
                        <option value="wagon">Wagon / Estate</option>
                        <option value="coupe">Coupe</option>
                        <option value="convertible">Convertible</option>
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
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Color</label>
                      <input type="text" name="color" value={formData.color} onChange={handleInputChange}
                        placeholder="e.g., White"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">Engine Size (cc)</label>
                      <input type="number" name="engineSize" value={formData.engineSize} onChange={handleInputChange}
                        placeholder="e.g., 2500"
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                      {engineTypeLabel && (
                        <p className="text-xs text-gray-500 mt-1">ℹ️ Detected engine: <span className="font-medium text-gray-700">{engineTypeLabel}</span></p>
                      )}
                    </div>
                  </div>
                </div>
              )}

              {currentStep === 2 && (
                <div className="space-y-6">
                  <h2 className="text-2xl font-bold text-navy-900 mb-6">Shipping Information
                    {prefilledCar && formData.originCountry && <span className="ml-3 text-sm font-normal text-blue-500 bg-blue-50 px-2 py-1 rounded-full">✨ Pre-filled</span>}
                  </h2>
                  
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

                  {/* #1 — Live Quote Preview Panel */}
                  {(previewLoading || livePreview) && (
                    <div className="mt-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5">
                      <div className="flex items-center gap-2 mb-3">
                        <span className="text-lg">🤖</span>
                        <h3 className="font-bold text-blue-900 text-sm">Live Cost Estimate</h3>
                        <span className="text-xs text-blue-500 bg-blue-100 px-2 py-0.5 rounded-full">Preview — not saved</span>
                        {previewLoading && <FaSpinner className="animate-spin text-blue-400 ml-auto" />}
                      </div>
                      {livePreview && !previewLoading && (
                        <>
                          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
                            <div className="bg-white rounded-lg p-3 shadow-sm">
                              <p className="text-xs text-gray-500 mb-1">Ocean Freight</p>
                              <p className="font-bold text-gray-900">${(livePreview.breakdown?.shipping || 0).toLocaleString()}</p>
                            </div>
                            <div className="bg-white rounded-lg p-3 shadow-sm">
                              <p className="text-xs text-gray-500 mb-1">Customs Duty</p>
                              <p className="font-bold text-gray-900">${(livePreview.breakdown?.customs_duty || 0).toLocaleString()}</p>
                            </div>
                            <div className="bg-white rounded-lg p-3 shadow-sm">
                              <p className="text-xs text-gray-500 mb-1">VAT + Levies</p>
                              <p className="font-bold text-gray-900">${((livePreview.breakdown?.vat || 0) + (livePreview.breakdown?.levies || 0)).toLocaleString()}</p>
                            </div>
                            <div className="bg-blue-600 rounded-lg p-3 shadow-sm">
                              <p className="text-xs text-blue-200 mb-1">Est. Total</p>
                              <p className="font-bold text-white text-lg">${(livePreview.total_cost || 0).toLocaleString()}</p>
                            </div>
                          </div>
                          {livePreview.estimated_delivery_days && (
                            <p className="text-xs text-blue-600 mt-2 text-center">
                              🚢 Estimated delivery: <strong>{livePreview.estimated_delivery_days} days</strong> to Uganda
                            </p>
                          )}
                          {livePreview.ai_reasoning && (
                            <p className="text-xs text-gray-500 mt-2 italic">💡 {livePreview.ai_reasoning}</p>
                          )}
                        </>
                      )}
                      {previewLoading && (
                        <div className="text-xs text-blue-500 text-center py-2">Calculating live estimate...</div>
                      )}
                    </div>
                  )}
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
