import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaArrowRight, 
  FaCar, 
  FaGasPump, 
  FaTachometerAlt, 
  FaCogs,
  FaHeart,
  FaRegHeart,
  FaStar,
  FaMapMarkerAlt,
  FaShip,
  FaCalendarAlt
} from 'react-icons/fa';

const ExploreVehicles = () => {
  const [activeTab, setActiveTab] = useState('all');
  const [favorites, setFavorites] = useState(new Set());
  const [vehicles, setVehicles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchVehicles();
    // Load favorites from localStorage
    const savedFavorites = JSON.parse(localStorage.getItem('user_favorites') || '[]');
    setFavorites(new Set(savedFavorites));
  }, [activeTab]);

  const fetchVehicles = async () => {
    try {
      setLoading(true);
      setError(null);
      
      let url = '/api/cars/featured?limit=3';
      if (activeTab === 'new') {
        url = '/api/cars?condition=new&per_page=3';
      } else if (activeTab === 'used') {
        url = '/api/cars?condition=used&per_page=3';
      }
      
      const fullUrl = `http://localhost:8000${url}`;
      console.log('Fetching vehicles from:', fullUrl);
      
      const response = await fetch(fullUrl, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('Received data:', data);
      
      if (data.success) {
        setVehicles(data.data.data || data.data); // Handle both paginated and direct data
      } else {
        throw new Error(data.message || 'Failed to fetch vehicles');
      }
    } catch (err) {
      console.error('Error fetching vehicles:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const toggleFavorite = (id) => {
    const newFavorites = new Set(favorites);
    if (newFavorites.has(id)) {
      newFavorites.delete(id);
    } else {
      newFavorites.add(id);
    }
    setFavorites(newFavorites);
    
    // Save to localStorage
    const favoritesArray = Array.from(newFavorites);
    localStorage.setItem('user_favorites', JSON.stringify(favoritesArray));
  };

  const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price);
  };

  const getBadgeColor = (condition, isFeatured) => {
    if (isFeatured) return 'bg-blue-500';
    if (condition === 'new') return 'bg-green-500';
    if (condition === 'certified_pre_owned') return 'bg-purple-500';
    return 'bg-orange-500';
  };

  const getBadgeText = (condition, isFeatured) => {
    if (isFeatured) return 'Featured';
    if (condition === 'new') return 'New';
    if (condition === 'certified_pre_owned') return 'Certified';
    return 'Used';
  };

  const getShippingTimeRange = (car) => {
    if (car.estimated_shipping_days_min && car.estimated_shipping_days_max) {
      return `${car.estimated_shipping_days_min}-${car.estimated_shipping_days_max} days`;
    }
    return '21-30 days';
  };

  if (loading) {
    return (
      <section className="section-padding bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading vehicles...</p>
          </div>
        </div>
      </section>
    );
  }

  if (error) {
    return (
      <section className="section-padding bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom">
          <div className="text-center">
            <p className="text-red-600">Error loading vehicles: {error}</p>
            <button 
              onClick={fetchVehicles}
              className="mt-4 btn-primary"
            >
              Try Again
            </button>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="section-padding bg-gradient-to-b from-gray-50 to-white">
      <div className="container-custom">
        {/* Section Header */}
        <div className="text-center mb-16">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-4">
            <FaCar className="text-blue-600" />
            EXPLORE OUR INVENTORY
          </div>
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Featured <span className="gradient-text">Vehicles</span>
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Discover our handpicked selection of quality vehicles from trusted dealers worldwide
          </p>
        </div>

        {/* Tab Navigation */}
        <div className="flex justify-center mb-12">
          <div className="bg-white rounded-2xl p-2 shadow-lg border border-gray-200">
            <div className="flex gap-2">
              {[
                { key: 'all', label: 'Featured Cars' },
                { key: 'new', label: 'New Cars' },
                { key: 'used', label: 'Used Cars' }
              ].map((tab) => (
                <button
                  key={tab.key}
                  onClick={() => setActiveTab(tab.key)}
                  className={`px-6 py-3 rounded-xl font-semibold transition-all duration-300 ${
                    activeTab === tab.key
                      ? 'bg-blue-600 text-white shadow-lg'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                  }`}
                >
                  {tab.label}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Vehicle Grid - Limited to 3 vehicles */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
          {vehicles.slice(0, 3).map((vehicle) => (
            <div key={vehicle.id} className="group">
              <div className="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
                {/* Image Section */}
                <div className="relative aspect-video overflow-hidden">
                  <img 
                    src={vehicle.images?.[0]?.image_url || 'https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=800&auto=format&fit=crop'}
                    alt={`${vehicle.brand?.name} ${vehicle.model}`}
                    className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                  />
                  
                  {/* Badges */}
                  <div className="absolute top-4 left-4 flex gap-2">
                    <span className={`${getBadgeColor(vehicle.condition, vehicle.is_featured)} text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg`}>
                      {getBadgeText(vehicle.condition, vehicle.is_featured)}
                    </span>
                    <span className="bg-white/90 backdrop-blur-sm text-gray-900 text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                      {vehicle.year}
                    </span>
                  </div>

                  {/* Favorite Button */}
                  <button 
                    onClick={() => toggleFavorite(vehicle.id)}
                    className="absolute top-4 right-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all duration-300 hover:scale-110"
                  >
                    {favorites.has(vehicle.id) ? (
                      <FaHeart className="text-red-500" />
                    ) : (
                      <FaRegHeart className="text-gray-600" />
                    )}
                  </button>

                  {/* Location Badge */}
                  <div className="absolute bottom-4 left-4">
                    <div className="flex items-center gap-1 bg-black/50 backdrop-blur-sm text-white text-xs px-3 py-1 rounded-full">
                      <FaMapMarkerAlt />
                      <span>{vehicle.location_country}</span>
                    </div>
                  </div>
                </div>

                {/* Content Section */}
                <div className="p-6">
                  {/* Rating & Brand */}
                  <div className="flex items-center justify-between mb-3">
                    <div className="flex items-center gap-1">
                      <FaStar className="text-yellow-400 text-sm" />
                      <span className="text-sm font-medium text-gray-700">{vehicle.rating || '4.5'}</span>
                    </div>
                    <span className="text-sm text-gray-500">{vehicle.brand?.name}</span>
                  </div>

                  {/* Model & Year */}
                  <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                    {vehicle.brand?.name} {vehicle.model}
                  </h3>

                  {/* Price */}
                  <div className="text-2xl font-bold text-blue-600 mb-4">
                    {formatPrice(vehicle.price)}
                  </div>

                  {/* Specifications */}
                  <div className="grid grid-cols-3 gap-3 mb-4">
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <FaGasPump className="text-blue-500" />
                      <span>{vehicle.fuel_type || 'Petrol'}</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <FaTachometerAlt className="text-green-500" />
                      <span>{vehicle.mileage ? `${vehicle.mileage.toLocaleString()} km` : 'N/A'}</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <FaCogs className="text-purple-500" />
                      <span>{vehicle.transmission || 'Auto'}</span>
                    </div>
                  </div>

                  {/* Shipping Info */}
                  <div className="flex items-center justify-between mb-4 p-3 bg-blue-50 rounded-lg">
                    <div className="flex items-center gap-2">
                      <FaShip className="text-blue-600" />
                      <span className="text-sm font-medium text-blue-800">Shipping Time</span>
                    </div>
                    <span className="text-sm font-semibold text-blue-600">{getShippingTimeRange(vehicle)}</span>
                  </div>

                  {/* Action Buttons */}
                  <div className="flex gap-3">
                    <Link
                      to={`/cars/${vehicle.slug}`}
                      className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-4 rounded-lg font-semibold transition-all duration-300 text-center"
                    >
                      View Details
                    </Link>
                    <Link
                      to={`/quote?vehicle=${vehicle.id}`}
                      className="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-semibold transition-all duration-300 text-center flex items-center justify-center gap-2"
                    >
                      Get Quote
                      <FaArrowRight className="text-sm" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* View All Cars Button */}
        <div className="text-center mb-12">
          <Link
            to="/cars"
            className="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-900 font-semibold py-3 px-8 rounded-lg border-2 border-gray-200 hover:border-blue-300 transition-all duration-300 shadow-lg hover:shadow-xl"
          >
            View All Vehicles
            <FaArrowRight />
          </Link>
        </div>

        {/* CTA Section */}
        <div className="text-center">
          <div className="bg-gradient-to-r from-blue-600 to-purple-600 rounded-3xl p-8 md:p-12 text-white">
            <h3 className="text-2xl md:text-3xl font-bold mb-4">
              Looking for something specific?
            </h3>
            <p className="text-blue-100 mb-6 text-lg">
              Browse our complete inventory or let us help you find the perfect vehicle
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/cars"
                className="bg-white text-blue-600 hover:bg-gray-100 font-semibold py-3 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2"
              >
                <FaCar />
                Browse All Vehicles
              </Link>
              <Link
                to="/quote"
                className="border-2 border-white text-white hover:bg-white hover:text-blue-600 font-semibold py-3 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2"
              >
                <FaCalendarAlt />
                Request Custom Search
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default ExploreVehicles;
