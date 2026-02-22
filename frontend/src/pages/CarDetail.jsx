import { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { 
  FaArrowLeft,
  FaHeart,
  FaRegHeart,
  FaStar,
  FaMapMarkerAlt,
  FaShip,
  FaGasPump,
  FaTachometerAlt,
  FaCogs,
  FaCalendarAlt,
  FaPalette,
  FaRoad,
  FaUsers,
  FaDoorOpen,
  FaShieldAlt,
  FaTools,
  FaPhone,
  FaEnvelope,
  FaShare,
  FaWhatsapp,
  FaFacebook,
  FaTwitter,
  FaChevronLeft,
  FaChevronRight,
  FaExpand,
  FaCheck,
  FaTimes
} from 'react-icons/fa';

const CarDetail = () => {
  const { slug } = useParams();
  const navigate = useNavigate();
  const [car, setCar] = useState(null);
  const [similarCars, setSimilarCars] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isFavorite, setIsFavorite] = useState(false);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [showImageModal, setShowImageModal] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    if (slug) {
      fetchCarDetails();
      fetchSimilarCars();
    }
  }, [slug]);

  const fetchCarDetails = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`http://localhost:8000/api/cars/${slug}`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (data.success) {
        setCar(data.data);
      } else {
        throw new Error(data.message || 'Failed to fetch car details');
      }
    } catch (err) {
      console.error('Error fetching car details:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const fetchSimilarCars = async () => {
    try {
      const response = await fetch(`http://localhost:8000/api/cars/${slug}/similar`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setSimilarCars(data.data);
        }
      }
    } catch (err) {
      console.error('Error fetching similar cars:', err);
    }
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

  const nextImage = () => {
    if (car?.images?.length > 1) {
      setCurrentImageIndex((prev) => (prev + 1) % car.images.length);
    }
  };

  const prevImage = () => {
    if (car?.images?.length > 1) {
      setCurrentImageIndex((prev) => (prev - 1 + car.images.length) % car.images.length);
    }
  };

  const shareVehicle = (platform) => {
    const url = window.location.href;
    const title = `${car.brand?.name} ${car.model} (${car.year})`;
    
    switch (platform) {
      case 'whatsapp':
        window.open(`https://wa.me/?text=${encodeURIComponent(`Check out this ${title}: ${url}`)}`);
        break;
      case 'facebook':
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`);
        break;
      case 'twitter':
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`);
        break;
      default:
        navigator.clipboard.writeText(url);
        alert('Link copied to clipboard!');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom py-20">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading vehicle details...</p>
          </div>
        </div>
      </div>
    );
  }

  if (error || !car) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom py-20">
          <div className="text-center">
            <h1 className="text-2xl font-bold text-gray-900 mb-4">Vehicle Not Found</h1>
            <p className="text-gray-600 mb-6">{error || 'The requested vehicle could not be found.'}</p>
            <Link to="/cars" className="btn-primary">
              Browse All Vehicles
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      <div className="container-custom py-20">
        {/* Back Button */}
        <div className="mb-6">
          <button
            onClick={() => navigate(-1)}
            className="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 transition-colors duration-300"
          >
            <FaArrowLeft />
            <span>Back to Results</span>
          </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column - Images */}
          <div className="lg:col-span-2">
            {/* Main Image */}
            <div className="relative aspect-video bg-gray-100 rounded-2xl overflow-hidden mb-4">
              {car.images && car.images.length > 0 ? (
                <>
                  <img
                    src={car.images[currentImageIndex]?.image_url}
                    alt={`${car.brand?.name} ${car.model}`}
                    className="w-full h-full object-cover cursor-pointer"
                    onClick={() => setShowImageModal(true)}
                  />
                  
                  {/* Image Navigation */}
                  {car.images.length > 1 && (
                    <>
                      <button
                        onClick={prevImage}
                        className="absolute left-4 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-all duration-300"
                      >
                        <FaChevronLeft />
                      </button>
                      <button
                        onClick={nextImage}
                        className="absolute right-4 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-all duration-300"
                      >
                        <FaChevronRight />
                      </button>
                    </>
                  )}

                  {/* Expand Button */}
                  <button
                    onClick={() => setShowImageModal(true)}
                    className="absolute top-4 right-4 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-all duration-300"
                  >
                    <FaExpand />
                  </button>

                  {/* Badges */}
                  <div className="absolute top-4 left-4 flex gap-2">
                    <span className={`${getBadgeColor(car.condition, car.is_featured)} text-white text-sm font-bold px-3 py-1 rounded-full shadow-lg`}>
                      {getBadgeText(car.condition, car.is_featured)}
                    </span>
                    <span className="bg-white/90 backdrop-blur-sm text-gray-900 text-sm font-bold px-3 py-1 rounded-full shadow-lg">
                      {car.year}
                    </span>
                  </div>

                  {/* Image Counter */}
                  <div className="absolute bottom-4 right-4 bg-black/50 backdrop-blur-sm text-white text-sm px-3 py-1 rounded-full">
                    {currentImageIndex + 1} / {car.images.length}
                  </div>
                </>
              ) : (
                <div className="w-full h-full flex items-center justify-center text-gray-400">
                  <FaCar className="text-6xl" />
                </div>
              )}
            </div>

            {/* Thumbnail Images */}
            {car.images && car.images.length > 1 && (
              <div className="grid grid-cols-4 md:grid-cols-6 gap-2 mb-8">
                {car.images.map((image, index) => (
                  <button
                    key={index}
                    onClick={() => setCurrentImageIndex(index)}
                    className={`aspect-video rounded-lg overflow-hidden border-2 transition-all duration-300 ${
                      currentImageIndex === index ? 'border-blue-500' : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <img
                      src={image.image_url}
                      alt={`${car.brand?.name} ${car.model} - Image ${index + 1}`}
                      className="w-full h-full object-cover"
                    />
                  </button>
                ))}
              </div>
            )}

            {/* Tabs */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="border-b border-gray-200">
                <nav className="flex">
                  {[
                    { key: 'overview', label: 'Overview' },
                    { key: 'specifications', label: 'Specifications' },
                    { key: 'features', label: 'Features' },
                    { key: 'location', label: 'Location' }
                  ].map((tab) => (
                    <button
                      key={tab.key}
                      onClick={() => setActiveTab(tab.key)}
                      className={`px-6 py-4 font-medium transition-all duration-300 ${
                        activeTab === tab.key
                          ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
                          : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                      }`}
                    >
                      {tab.label}
                    </button>
                  ))}
                </nav>
              </div>

              <div className="p-6">
                {/* Overview Tab */}
                {activeTab === 'overview' && (
                  <div className="space-y-6">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-3">Description</h3>
                      <p className="text-gray-600 leading-relaxed">
                        {car.description || 'No description available for this vehicle.'}
                      </p>
                    </div>

                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-3">Key Specifications</h3>
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                          <FaGasPump className="text-blue-500" />
                          <div>
                            <p className="text-sm text-gray-500">Fuel Type</p>
                            <p className="font-medium text-gray-900">{car.fuel_type || 'N/A'}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                          <FaTachometerAlt className="text-green-500" />
                          <div>
                            <p className="text-sm text-gray-500">Mileage</p>
                            <p className="font-medium text-gray-900">
                              {car.mileage ? `${car.mileage.toLocaleString()} km` : 'N/A'}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                          <FaCogs className="text-purple-500" />
                          <div>
                            <p className="text-sm text-gray-500">Transmission</p>
                            <p className="font-medium text-gray-900">{car.transmission || 'N/A'}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                          <FaRoad className="text-orange-500" />
                          <div>
                            <p className="text-sm text-gray-500">Drive Type</p>
                            <p className="font-medium text-gray-900">{car.drive_type || 'N/A'}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Specifications Tab */}
                {activeTab === 'specifications' && (
                  <div className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Engine & Performance</h3>
                        <div className="space-y-3">
                          <div className="flex justify-between">
                            <span className="text-gray-600">Engine Type</span>
                            <span className="font-medium">{car.engine_type || 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Fuel Type</span>
                            <span className="font-medium">{car.fuel_type || 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Transmission</span>
                            <span className="font-medium">{car.transmission || 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Drive Type</span>
                            <span className="font-medium">{car.drive_type || 'N/A'}</span>
                          </div>
                        </div>
                      </div>

                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Dimensions & Capacity</h3>
                        <div className="space-y-3">
                          <div className="flex justify-between">
                            <span className="text-gray-600">Doors</span>
                            <span className="font-medium">{car.doors || 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Seats</span>
                            <span className="font-medium">{car.seats || 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Length</span>
                            <span className="font-medium">{car.length ? `${car.length} mm` : 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Width</span>
                            <span className="font-medium">{car.width ? `${car.width} mm` : 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Height</span>
                            <span className="font-medium">{car.height ? `${car.height} mm` : 'N/A'}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Weight</span>
                            <span className="font-medium">{car.weight ? `${car.weight} kg` : 'N/A'}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Features Tab */}
                {activeTab === 'features' && (
                  <div className="space-y-6">
                    {car.features && car.features.length > 0 && (
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Standard Features</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          {car.features.map((feature, index) => (
                            <div key={index} className="flex items-center gap-2">
                              <FaCheck className="text-green-500 text-sm" />
                              <span className="text-gray-700">{feature}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}

                    {car.safety_features && car.safety_features.length > 0 && (
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Safety Features</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          {car.safety_features.map((feature, index) => (
                            <div key={index} className="flex items-center gap-2">
                              <FaShieldAlt className="text-blue-500 text-sm" />
                              <span className="text-gray-700">{feature}</span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* Location Tab */}
                {activeTab === 'location' && (
                  <div className="space-y-6">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-4">Vehicle Location</h3>
                      <div className="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                        <FaMapMarkerAlt className="text-red-500 mt-1" />
                        <div>
                          <p className="font-medium text-gray-900">
                            {car.location_city}, {car.location_country}
                          </p>
                          <p className="text-sm text-gray-600 mt-1">
                            Vehicle is currently located in {car.location_city}, {car.location_country}
                          </p>
                        </div>
                      </div>
                    </div>

                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-4">Shipping Information</h3>
                      <div className="flex items-start gap-4 p-4 bg-blue-50 rounded-lg">
                        <FaShip className="text-blue-500 mt-1" />
                        <div>
                          <p className="font-medium text-gray-900">
                            Estimated Shipping Time: {car.estimated_shipping_days_min && car.estimated_shipping_days_max 
                              ? `${car.estimated_shipping_days_min}-${car.estimated_shipping_days_max} days`
                              : '21-30 days'
                            }
                          </p>
                          <p className="text-sm text-gray-600 mt-1">
                            Shipping time may vary based on destination and customs clearance
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Right Column - Vehicle Info & Actions */}
          <div className="space-y-6">
            {/* Vehicle Header */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <div className="flex items-start justify-between mb-4">
                <div>
                  <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                    {car.brand?.name} {car.model}
                  </h1>
                  <div className="flex items-center gap-4 text-sm text-gray-600">
                    <div className="flex items-center gap-1">
                      <FaStar className="text-yellow-400" />
                      <span>{car.rating || '4.5'}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <FaMapMarkerAlt />
                      <span>{car.location_country}</span>
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => setIsFavorite(!isFavorite)}
                  className="w-12 h-12 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-all duration-300"
                >
                  {isFavorite ? (
                    <FaHeart className="text-red-500" />
                  ) : (
                    <FaRegHeart className="text-gray-600" />
                  )}
                </button>
              </div>

              {/* Price */}
              <div className="text-3xl font-bold text-blue-600 mb-6">
                {formatPrice(car.price)}
              </div>

              {/* Quick Specs */}
              <div className="grid grid-cols-2 gap-4 mb-6">
                <div className="flex items-center gap-2">
                  <FaCalendarAlt className="text-gray-400" />
                  <span className="text-sm text-gray-600">{car.year}</span>
                </div>
                <div className="flex items-center gap-2">
                  <FaPalette className="text-gray-400" />
                  <span className="text-sm text-gray-600">{car.color}</span>
                </div>
                <div className="flex items-center gap-2">
                  <FaUsers className="text-gray-400" />
                  <span className="text-sm text-gray-600">{car.seats} seats</span>
                </div>
                <div className="flex items-center gap-2">
                  <FaDoorOpen className="text-gray-400" />
                  <span className="text-sm text-gray-600">{car.doors} doors</span>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="space-y-3">
                <Link
                  to={`/quote?vehicle=${car.id}`}
                  className="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold transition-all duration-300 text-center block"
                >
                  Get Quote & Shipping Info
                </Link>
                <div className="grid grid-cols-2 gap-3">
                  <button className="flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium transition-all duration-300">
                    <FaWhatsapp />
                    WhatsApp
                  </button>
                  <button className="flex items-center justify-center gap-2 bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-medium transition-all duration-300">
                    <FaPhone />
                    Call
                  </button>
                </div>
              </div>
            </div>

            {/* Share */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Share This Vehicle</h3>
              <div className="flex gap-3">
                <button
                  onClick={() => shareVehicle('whatsapp')}
                  className="flex-1 flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white py-2 px-3 rounded-lg transition-all duration-300"
                >
                  <FaWhatsapp />
                </button>
                <button
                  onClick={() => shareVehicle('facebook')}
                  className="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded-lg transition-all duration-300"
                >
                  <FaFacebook />
                </button>
                <button
                  onClick={() => shareVehicle('twitter')}
                  className="flex-1 flex items-center justify-center gap-2 bg-sky-500 hover:bg-sky-600 text-white py-2 px-3 rounded-lg transition-all duration-300"
                >
                  <FaTwitter />
                </button>
                <button
                  onClick={() => shareVehicle('copy')}
                  className="flex-1 flex items-center justify-center gap-2 bg-gray-500 hover:bg-gray-600 text-white py-2 px-3 rounded-lg transition-all duration-300"
                >
                  <FaShare />
                </button>
              </div>
            </div>

            {/* Shipping Info */}
            <div className="bg-gradient-to-r from-blue-50 to-purple-50 rounded-2xl p-6 border border-blue-100">
              <div className="flex items-center gap-3 mb-3">
                <FaShip className="text-blue-600 text-xl" />
                <h3 className="text-lg font-semibold text-gray-900">Shipping Information</h3>
              </div>
              <div className="space-y-2 text-sm text-gray-600">
                <p>• Estimated delivery: {car.estimated_shipping_days_min && car.estimated_shipping_days_max 
                  ? `${car.estimated_shipping_days_min}-${car.estimated_shipping_days_max} days`
                  : '21-30 days'
                }</p>
                <p>• Worldwide shipping available</p>
                <p>• Full insurance coverage included</p>
                <p>• Door-to-door delivery service</p>
              </div>
            </div>
          </div>
        </div>

        {/* Similar Cars */}
        {similarCars.length > 0 && (
          <div className="mt-16">
            <h2 className="text-2xl font-bold text-gray-900 mb-8">Similar Vehicles</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {similarCars.slice(0, 3).map((similarCar) => (
                <Link
                  key={similarCar.id}
                  to={`/cars/${similarCar.slug}`}
                  className="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden group"
                >
                  <div className="aspect-video overflow-hidden">
                    <img
                      src={similarCar.images?.[0]?.image_url || 'https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=800&auto=format&fit=crop'}
                      alt={`${similarCar.brand?.name} ${similarCar.model}`}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  </div>
                  <div className="p-4">
                    <h3 className="font-semibold text-gray-900 mb-1">
                      {similarCar.brand?.name} {similarCar.model} ({similarCar.year})
                    </h3>
                    <p className="text-xl font-bold text-blue-600">
                      {formatPrice(similarCar.price)}
                    </p>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Image Modal */}
      {showImageModal && car.images && (
        <div className="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4">
          <div className="relative max-w-4xl max-h-full">
            <button
              onClick={() => setShowImageModal(false)}
              className="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-all duration-300 z-10"
            >
              <FaTimes />
            </button>
            <img
              src={car.images[currentImageIndex]?.image_url}
              alt={`${car.brand?.name} ${car.model}`}
              className="max-w-full max-h-full object-contain"
            />
            {car.images.length > 1 && (
              <>
                <button
                  onClick={prevImage}
                  className="absolute left-4 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-all duration-300"
                >
                  <FaChevronLeft />
                </button>
                <button
                  onClick={nextImage}
                  className="absolute right-4 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-all duration-300"
                >
                  <FaChevronRight />
                </button>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default CarDetail;