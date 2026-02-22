import { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { 
  FaSearch,
  FaCar,
  FaChevronLeft,
  FaChevronRight,
  FaFilter,
  FaTh,
  FaList,
  FaGasPump,
  FaTachometerAlt,
  FaCogs,
  FaHeart,
  FaRegHeart,
  FaStar,
  FaMapMarkerAlt,
  FaShip,
  FaArrowRight,
  FaTimes
} from 'react-icons/fa';

const Cars = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [cars, setCars] = useState([]);
  const [brands, setBrands] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [favorites, setFavorites] = useState(new Set());
  
  // Filter states
  const [searchTerm, setSearchTerm] = useState(searchParams.get('search') || '');
  const [selectedBrand, setSelectedBrand] = useState(searchParams.get('brand') || '');
  const [selectedCategory, setSelectedCategory] = useState(searchParams.get('category') || '');
  const [selectedCondition, setSelectedCondition] = useState(searchParams.get('condition') || '');
  const [priceRange, setPriceRange] = useState({
    min: searchParams.get('price_min') || '',
    max: searchParams.get('price_max') || ''
  });
  const [yearRange, setYearRange] = useState({
    min: searchParams.get('year_min') || '',
    max: searchParams.get('year_max') || ''
  });
  
  // Pagination and view states
  const [currentPage, setCurrentPage] = useState(parseInt(searchParams.get('page')) || 1);
  const [totalPages, setTotalPages] = useState(1);
  const [viewMode, setViewMode] = useState('grid');
  const [sortBy, setSortBy] = useState(searchParams.get('sort') || 'created_at');
  const [showFilters, setShowFilters] = useState(false);

  useEffect(() => {
    fetchInitialData();
    // Load favorites from localStorage
    const savedFavorites = JSON.parse(localStorage.getItem('user_favorites') || '[]');
    setFavorites(new Set(savedFavorites));
  }, []);

  useEffect(() => {
    fetchCars();
  }, [currentPage, searchTerm, selectedBrand, selectedCategory, selectedCondition, priceRange, yearRange, sortBy]);

  const fetchInitialData = async () => {
    try {
      const [brandsResponse, categoriesResponse] = await Promise.all([
        fetch('http://localhost:8000/api/cars/brands', {
          headers: { 'Accept': 'application/json' }
        }),
        fetch('http://localhost:8000/api/cars/categories', {
          headers: { 'Accept': 'application/json' }
        })
      ]);

      const brandsData = await brandsResponse.json();
      const categoriesData = await categoriesResponse.json();

      if (brandsData.success) setBrands(brandsData.data);
      if (categoriesData.success) setCategories(categoriesData.data);
    } catch (err) {
      console.error('Error fetching initial data:', err);
    }
  };

  const fetchCars = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const params = new URLSearchParams({
        page: currentPage,
        per_page: 12,
        sort: sortBy,
        ...(searchTerm && { search: searchTerm }),
        ...(selectedBrand && { brand: selectedBrand }),
        ...(selectedCategory && { category: selectedCategory }),
        ...(selectedCondition && { condition: selectedCondition }),
        ...(priceRange.min && { price_min: priceRange.min }),
        ...(priceRange.max && { price_max: priceRange.max }),
        ...(yearRange.min && { year_min: yearRange.min }),
        ...(yearRange.max && { year_max: yearRange.max })
      });
      
      const url = `http://localhost:8000/api/cars?${params}`;
      console.log('Fetching cars from:', url);
      
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('Cars data:', data);
      
      if (data.success) {
        setCars(data.data.data || data.data);
        setTotalPages(data.data.last_page || 1);
      } else {
        throw new Error(data.message || 'Failed to fetch cars');
      }
    } catch (err) {
      console.error('Error fetching cars:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    updateSearchParams();
  };

  const updateSearchParams = () => {
    const params = new URLSearchParams();
    if (searchTerm) params.set('search', searchTerm);
    if (selectedBrand) params.set('brand', selectedBrand);
    if (selectedCategory) params.set('category', selectedCategory);
    if (selectedCondition) params.set('condition', selectedCondition);
    if (priceRange.min) params.set('price_min', priceRange.min);
    if (priceRange.max) params.set('price_max', priceRange.max);
    if (yearRange.min) params.set('year_min', yearRange.min);
    if (yearRange.max) params.set('year_max', yearRange.max);
    if (sortBy !== 'created_at') params.set('sort', sortBy);
    if (currentPage > 1) params.set('page', currentPage);
    
    setSearchParams(params);
  };

  const clearFilters = () => {
    setSearchTerm('');
    setSelectedBrand('');
    setSelectedCategory('');
    setSelectedCondition('');
    setPriceRange({ min: '', max: '' });
    setYearRange({ min: '', max: '' });
    setCurrentPage(1);
    setSearchParams({});
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

  const handlePageChange = (page) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const renderPagination = () => {
    const pages = [];
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      pages.push(
        <button
          key={i}
          onClick={() => handlePageChange(i)}
          className={`px-4 py-2 rounded-lg font-medium transition-all duration-300 ${
            currentPage === i
              ? 'bg-blue-600 text-white shadow-lg'
              : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'
          }`}
        >
          {i}
        </button>
      );
    }

    return (
      <div className="flex items-center justify-center gap-2 mt-12">
        <button
          onClick={() => handlePageChange(currentPage - 1)}
          disabled={currentPage === 1}
          className="p-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
        >
          <FaChevronLeft />
        </button>
        
        {startPage > 1 && (
          <>
            <button
              onClick={() => handlePageChange(1)}
              className="px-4 py-2 rounded-lg font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-all duration-300"
            >
              1
            </button>
            {startPage > 2 && <span className="text-gray-400">...</span>}
          </>
        )}
        
        {pages}
        
        {endPage < totalPages && (
          <>
            {endPage < totalPages - 1 && <span className="text-gray-400">...</span>}
            <button
              onClick={() => handlePageChange(totalPages)}
              className="px-4 py-2 rounded-lg font-medium bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 transition-all duration-300"
            >
              {totalPages}
            </button>
          </>
        )}
        
        <button
          onClick={() => handlePageChange(currentPage + 1)}
          disabled={currentPage === totalPages}
          className="p-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
        >
          <FaChevronRight />
        </button>
      </div>
    );
  };

  if (loading && cars.length === 0) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom py-20">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading vehicles...</p>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom py-20">
          <div className="text-center">
            <p className="text-red-600 mb-4">Error loading vehicles: {error}</p>
            <button 
              onClick={fetchCars}
              className="btn-primary"
            >
              Try Again
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      <div className="container-custom py-20">
        {/* Header */}
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-4">
            <FaCar className="text-blue-600" />
            VEHICLE INVENTORY
          </div>
          <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Browse All <span className="gradient-text">Vehicles</span>
          </h1>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Find your perfect vehicle from our extensive inventory
          </p>
        </div>

        {/* Search and Filters */}
        <div className="bg-white rounded-2xl shadow-lg p-6 mb-8">
          {/* Search Bar */}
          <form onSubmit={handleSearch} className="mb-6">
            <div className="relative">
              <FaSearch className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search by make, model, or keyword..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </form>

          {/* Filter Toggle */}
          <div className="flex items-center justify-between mb-4">
            <button
              onClick={() => setShowFilters(!showFilters)}
              className="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-300"
            >
              <FaFilter />
              Filters
            </button>
            
            <div className="flex items-center gap-4">
              {/* Sort */}
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="created_at">Newest First</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="year_desc">Year: Newest First</option>
                <option value="year_asc">Year: Oldest First</option>
                <option value="name">Name A-Z</option>
              </select>

              {/* View Mode */}
              <div className="flex bg-gray-100 rounded-lg p-1">
                <button
                  onClick={() => setViewMode('grid')}
                  className={`p-2 rounded-md transition-all duration-300 ${
                    viewMode === 'grid' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-600'
                  }`}
                >
                  <FaTh />
                </button>
                <button
                  onClick={() => setViewMode('list')}
                  className={`p-2 rounded-md transition-all duration-300 ${
                    viewMode === 'list' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-600'
                  }`}
                >
                  <FaList />
                </button>
              </div>
            </div>
          </div>

          {/* Filters Panel */}
          {showFilters && (
            <div className="border-t border-gray-200 pt-6">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                {/* Brand Filter */}
                <select
                  value={selectedBrand}
                  onChange={(e) => setSelectedBrand(e.target.value)}
                  className="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">All Brands</option>
                  {brands.map(brand => (
                    <option key={brand.id} value={brand.slug}>{brand.name}</option>
                  ))}
                </select>

                {/* Category Filter */}
                <select
                  value={selectedCategory}
                  onChange={(e) => setSelectedCategory(e.target.value)}
                  className="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">All Categories</option>
                  {categories.map(category => (
                    <option key={category.id} value={category.slug}>{category.name}</option>
                  ))}
                </select>

                {/* Condition Filter */}
                <select
                  value={selectedCondition}
                  onChange={(e) => setSelectedCondition(e.target.value)}
                  className="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">All Conditions</option>
                  <option value="new">New</option>
                  <option value="used">Used</option>
                  <option value="certified_pre_owned">Certified Pre-Owned</option>
                </select>

                {/* Clear Filters */}
                <button
                  onClick={clearFilters}
                  className="flex items-center justify-center gap-2 px-4 py-2 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg transition-colors duration-300"
                >
                  <FaTimes />
                  Clear All
                </button>
              </div>

              {/* Price and Year Range */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Price Range */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                  <div className="flex gap-2">
                    <input
                      type="number"
                      placeholder="Min Price"
                      value={priceRange.min}
                      onChange={(e) => setPriceRange(prev => ({ ...prev, min: e.target.value }))}
                      className="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <input
                      type="number"
                      placeholder="Max Price"
                      value={priceRange.max}
                      onChange={(e) => setPriceRange(prev => ({ ...prev, max: e.target.value }))}
                      className="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                </div>

                {/* Year Range */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Year Range</label>
                  <div className="flex gap-2">
                    <input
                      type="number"
                      placeholder="Min Year"
                      value={yearRange.min}
                      onChange={(e) => setYearRange(prev => ({ ...prev, min: e.target.value }))}
                      className="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <input
                      type="number"
                      placeholder="Max Year"
                      value={yearRange.max}
                      onChange={(e) => setYearRange(prev => ({ ...prev, max: e.target.value }))}
                      className="flex-1 px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Results Count */}
        <div className="mb-6">
          <p className="text-gray-600">
            Showing {cars.length} vehicles
            {searchTerm && ` for "${searchTerm}"`}
            {(selectedBrand || selectedCategory || selectedCondition) && ' with filters applied'}
          </p>
        </div>

        {/* Cars Grid/List */}
        {viewMode === 'grid' ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {cars.map((car) => (
              <div key={car.id} className="group">
                <div className="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 overflow-hidden border border-gray-100">
                  {/* Image Section */}
                  <div className="relative aspect-video overflow-hidden">
                    <img 
                      src={car.images?.[0]?.image_url || 'https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=800&auto=format&fit=crop'}
                      alt={`${car.brand?.name} ${car.model}`}
                      className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                    />
                    
                    {/* Badges */}
                    <div className="absolute top-4 left-4 flex gap-2">
                      <span className={`${getBadgeColor(car.condition, car.is_featured)} text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg`}>
                        {getBadgeText(car.condition, car.is_featured)}
                      </span>
                      <span className="bg-white/90 backdrop-blur-sm text-gray-900 text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                        {car.year}
                      </span>
                    </div>

                    {/* Favorite Button */}
                    <button 
                      onClick={() => toggleFavorite(car.id)}
                      className="absolute top-4 right-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all duration-300 hover:scale-110"
                    >
                      {favorites.has(car.id) ? (
                        <FaHeart className="text-red-500" />
                      ) : (
                        <FaRegHeart className="text-gray-600" />
                      )}
                    </button>

                    {/* Location Badge */}
                    <div className="absolute bottom-4 left-4">
                      <div className="flex items-center gap-1 bg-black/50 backdrop-blur-sm text-white text-xs px-3 py-1 rounded-full">
                        <FaMapMarkerAlt />
                        <span>{car.location_country}</span>
                      </div>
                    </div>
                  </div>

                  {/* Content Section */}
                  <div className="p-6">
                    {/* Rating & Brand */}
                    <div className="flex items-center justify-between mb-3">
                      <div className="flex items-center gap-1">
                        <FaStar className="text-yellow-400 text-sm" />
                        <span className="text-sm font-medium text-gray-700">{car.rating || '4.5'}</span>
                      </div>
                      <span className="text-sm text-gray-500">{car.brand?.name}</span>
                    </div>

                    {/* Model & Year */}
                    <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                      {car.brand?.name} {car.model}
                    </h3>

                    {/* Price */}
                    <div className="text-2xl font-bold text-blue-600 mb-4">
                      {formatPrice(car.price)}
                    </div>

                    {/* Specifications */}
                    <div className="grid grid-cols-3 gap-3 mb-4">
                      <div className="flex items-center gap-2 text-sm text-gray-600">
                        <FaGasPump className="text-blue-500" />
                        <span>{car.fuel_type || 'Petrol'}</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-gray-600">
                        <FaTachometerAlt className="text-green-500" />
                        <span>{car.mileage ? `${car.mileage.toLocaleString()} km` : 'N/A'}</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm text-gray-600">
                        <FaCogs className="text-purple-500" />
                        <span>{car.transmission || 'Auto'}</span>
                      </div>
                    </div>

                    {/* Shipping Info */}
                    <div className="flex items-center justify-between mb-4 p-3 bg-blue-50 rounded-lg">
                      <div className="flex items-center gap-2">
                        <FaShip className="text-blue-600" />
                        <span className="text-sm font-medium text-blue-800">Shipping Time</span>
                      </div>
                      <span className="text-sm font-semibold text-blue-600">
                        {car.estimated_shipping_days_min && car.estimated_shipping_days_max 
                          ? `${car.estimated_shipping_days_min}-${car.estimated_shipping_days_max} days`
                          : '21-30 days'
                        }
                      </span>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-3">
                      <Link
                        to={`/cars/${car.slug}`}
                        className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-4 rounded-lg font-semibold transition-all duration-300 text-center"
                      >
                        View Details
                      </Link>
                      <Link
                        to={`/quote?vehicle=${car.id}`}
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
        ) : (
          <div className="space-y-6">
            {cars.map((car) => (
              <div key={car.id} className="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                <div className="flex flex-col md:flex-row">
                  {/* Image */}
                  <div className="md:w-1/3 aspect-video md:aspect-square relative overflow-hidden">
                    <img 
                      src={car.images?.[0]?.image_url || 'https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=800&auto=format&fit=crop'}
                      alt={`${car.brand?.name} ${car.model}`}
                      className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                    />
                    
                    {/* Badges */}
                    <div className="absolute top-4 left-4 flex gap-2">
                      <span className={`${getBadgeColor(car.condition, car.is_featured)} text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg`}>
                        {getBadgeText(car.condition, car.is_featured)}
                      </span>
                    </div>

                    {/* Favorite Button */}
                    <button 
                      onClick={() => toggleFavorite(car.id)}
                      className="absolute top-4 right-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-white transition-all duration-300"
                    >
                      {favorites.has(car.id) ? (
                        <FaHeart className="text-red-500" />
                      ) : (
                        <FaRegHeart className="text-gray-600" />
                      )}
                    </button>
                  </div>

                  {/* Content */}
                  <div className="md:w-2/3 p-6">
                    <div className="flex justify-between items-start mb-4">
                      <div>
                        <h3 className="text-2xl font-bold text-gray-900 mb-2">
                          {car.brand?.name} {car.model} ({car.year})
                        </h3>
                        <div className="flex items-center gap-4 text-sm text-gray-600 mb-2">
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
                      <div className="text-right">
                        <div className="text-3xl font-bold text-blue-600 mb-1">
                          {formatPrice(car.price)}
                        </div>
                        <div className="text-sm text-gray-500">
                          Shipping: {car.estimated_shipping_days_min && car.estimated_shipping_days_max 
                            ? `${car.estimated_shipping_days_min}-${car.estimated_shipping_days_max} days`
                            : '21-30 days'
                          }
                        </div>
                      </div>
                    </div>

                    {/* Specifications */}
                    <div className="grid grid-cols-3 gap-4 mb-6">
                      <div className="flex items-center gap-2 text-gray-600">
                        <FaGasPump className="text-blue-500" />
                        <span>{car.fuel_type || 'Petrol'}</span>
                      </div>
                      <div className="flex items-center gap-2 text-gray-600">
                        <FaTachometerAlt className="text-green-500" />
                        <span>{car.mileage ? `${car.mileage.toLocaleString()} km` : 'N/A'}</span>
                      </div>
                      <div className="flex items-center gap-2 text-gray-600">
                        <FaCogs className="text-purple-500" />
                        <span>{car.transmission || 'Auto'}</span>
                      </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-3">
                      <Link
                        to={`/cars/${car.slug}`}
                        className="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-6 rounded-lg font-semibold transition-all duration-300 text-center"
                      >
                        View Details
                      </Link>
                      <Link
                        to={`/quote?vehicle=${car.id}`}
                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold transition-all duration-300 text-center flex items-center justify-center gap-2"
                      >
                        Get Quote
                        <FaArrowRight />
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Empty State */}
        {cars.length === 0 && !loading && (
          <div className="text-center py-12">
            <FaCar className="text-6xl text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">No vehicles found</h3>
            <p className="text-gray-600 mb-6">
              Try adjusting your search criteria or filters
            </p>
            <button
              onClick={clearFilters}
              className="btn-primary"
            >
              Clear All Filters
            </button>
          </div>
        )}

        {/* Pagination */}
        {totalPages > 1 && renderPagination()}
      </div>
    </div>
  );
};

export default Cars;