import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaHeart, 
  FaTrash, 
  FaEye, 
  FaShare,
  FaCompressArrowsAlt,
  FaArrowRight,
  FaCar
} from 'react-icons/fa';

const FavoritesManager = () => {
  const [favorites, setFavorites] = useState([]);
  const [selectedForComparison, setSelectedForComparison] = useState([]);

  useEffect(() => {
    loadFavorites();
  }, []);

  const loadFavorites = () => {
    const savedFavorites = JSON.parse(localStorage.getItem('favorites') || '[]');
    // In a real app, you'd fetch full car details from API using the IDs
    setFavorites(savedFavorites);
  };

  const removeFavorite = (carId) => {
    const updatedFavorites = favorites.filter(car => car.id !== carId);
    setFavorites(updatedFavorites);
    localStorage.setItem('favorites', JSON.stringify(updatedFavorites));
  };

  const toggleComparison = (car) => {
    if (selectedForComparison.find(c => c.id === car.id)) {
      setSelectedForComparison(selectedForComparison.filter(c => c.id !== car.id));
    } else if (selectedForComparison.length < 3) {
      setSelectedForComparison([...selectedForComparison, car]);
    } else {
      alert('You can compare up to 3 vehicles at once');
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

  const shareVehicle = (car) => {
    const url = `${window.location.origin}/cars/${car.slug}`;
    const title = `${car.brand?.name} ${car.model} (${car.year})`;
    
    if (navigator.share) {
      navigator.share({
        title: title,
        text: `Check out this ${title}`,
        url: url,
      });
    } else {
      navigator.clipboard.writeText(url);
      alert('Link copied to clipboard!');
    }
  };

  if (favorites.length === 0) {
    return (
      <div className="text-center py-12">
        <FaHeart className="text-6xl text-gray-300 mx-auto mb-4" />
        <h3 className="text-xl font-semibold text-gray-900 mb-2">No Favorites Yet</h3>
        <p className="text-gray-600 mb-6">
          Start browsing vehicles and add them to your favorites to see them here
        </p>
        <Link to="/cars" className="btn-primary">
          Browse Vehicles
        </Link>
      </div>
    );
  }

  return (
    <div>
      {/* Comparison Bar */}
      {selectedForComparison.length > 0 && (
        <div className="bg-blue-600 text-white p-4 rounded-lg mb-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <FaCompressArrowsAlt />
              <span className="font-medium">
                {selectedForComparison.length} vehicle{selectedForComparison.length > 1 ? 's' : ''} selected for comparison
              </span>
            </div>
            <div className="flex items-center gap-3">
              {selectedForComparison.length >= 2 && (
                <Link
                  to={`/compare?ids=${selectedForComparison.map(c => c.id).join(',')}`}
                  className="bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors"
                >
                  Compare Now
                </Link>
              )}
              <button
                onClick={() => setSelectedForComparison([])}
                className="text-white hover:text-gray-200 transition-colors"
              >
                Clear
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Favorites Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {favorites.map((car) => (
          <div key={car.id} className="bg-white rounded-xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
            {/* Image */}
            <div className="relative aspect-video overflow-hidden">
              <img
                src={car.images?.[0]?.image_url || 'https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=800&auto=format&fit=crop'}
                alt={`${car.brand?.name} ${car.model}`}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              
              {/* Favorite Button */}
              <button
                onClick={() => removeFavorite(car.id)}
                className="absolute top-3 right-3 w-10 h-10 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-all duration-300 shadow-lg"
              >
                <FaHeart />
              </button>

              {/* Comparison Checkbox */}
              <div className="absolute top-3 left-3">
                <label className="flex items-center gap-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={selectedForComparison.find(c => c.id === car.id) !== undefined}
                    onChange={() => toggleComparison(car)}
                    className="rounded"
                  />
                  <span className="text-sm font-medium text-gray-700">Compare</span>
                </label>
              </div>

              {/* Year Badge */}
              <div className="absolute bottom-3 left-3">
                <span className="bg-black/50 backdrop-blur-sm text-white text-sm font-bold px-3 py-1 rounded-full">
                  {car.year}
                </span>
              </div>
            </div>

            {/* Content */}
            <div className="p-6">
              {/* Header */}
              <div className="flex items-start justify-between mb-3">
                <div>
                  <h3 className="text-xl font-bold text-gray-900 mb-1">
                    {car.brand?.name} {car.model}
                  </h3>
                  <p className="text-gray-600">{car.location_country}</p>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-bold text-blue-600">
                    {formatPrice(car.price)}
                  </div>
                </div>
              </div>

              {/* Quick Specs */}
              <div className="grid grid-cols-2 gap-3 mb-4 text-sm text-gray-600">
                <div>Fuel: {car.fuel_type || 'N/A'}</div>
                <div>Transmission: {car.transmission || 'N/A'}</div>
                <div>Mileage: {car.mileage ? `${car.mileage.toLocaleString()} km` : 'N/A'}</div>
                <div>Condition: {car.condition || 'N/A'}</div>
              </div>

              {/* Action Buttons */}
              <div className="flex gap-2">
                <Link
                  to={`/cars/${car.slug}`}
                  className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-medium transition-all duration-300 text-center flex items-center justify-center gap-2"
                >
                  <FaEye />
                  View
                </Link>
                <button
                  onClick={() => shareVehicle(car)}
                  className="bg-blue-100 hover:bg-blue-200 text-blue-600 py-2 px-4 rounded-lg font-medium transition-all duration-300 flex items-center justify-center"
                >
                  <FaShare />
                </button>
                <Link
                  to={`/quote?vehicle=${car.id}`}
                  className="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition-all duration-300 flex items-center justify-center"
                >
                  <FaArrowRight />
                </Link>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Bottom Actions */}
      <div className="mt-8 text-center">
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            to="/cars"
            className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2"
          >
            <FaCar />
            Browse More Vehicles
          </Link>
          {selectedForComparison.length >= 2 && (
            <Link
              to={`/compare?ids=${selectedForComparison.map(c => c.id).join(',')}`}
              className="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2"
            >
              <FaCompressArrowsAlt />
              Compare Selected ({selectedForComparison.length})
            </Link>
          )}
        </div>
      </div>
    </div>
  );
};

export default FavoritesManager;