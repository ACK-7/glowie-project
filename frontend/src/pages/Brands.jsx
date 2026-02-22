import { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { 
  FaSearch,
  FaCar,
  FaChevronLeft,
  FaChevronRight,
  FaFilter,
  FaTh,
  FaList
} from 'react-icons/fa';
import { 
  SiToyota,
  SiHonda,
  SiBmw,
  SiMercedes,
  SiAudi,
  SiNissan,
  SiMitsubishi,
  SiSubaru,
  SiMazda,
  SiVolkswagen,
  SiFord,
  SiChevrolet
} from 'react-icons/si';

const Brands = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const [brands, setBrands] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState(searchParams.get('search') || '');
  const [currentPage, setCurrentPage] = useState(parseInt(searchParams.get('page')) || 1);
  const [totalPages, setTotalPages] = useState(1);
  const [viewMode, setViewMode] = useState('grid');
  const [sortBy, setSortBy] = useState('name');

  useEffect(() => {
    fetchBrands();
  }, [currentPage, searchTerm, sortBy]);

  const fetchBrands = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const params = new URLSearchParams({
        page: currentPage,
        per_page: 12,
        sort: sortBy,
        ...(searchTerm && { search: searchTerm })
      });
      
      const url = `http://localhost:8000/api/cars/brands?${params}`;
      console.log('Fetching brands from:', url);
      
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
      console.log('Brands data:', data);
      
      if (data.success) {
        setBrands(data.data.data || data.data);
        setTotalPages(data.data.last_page || 1);
      } else {
        throw new Error(data.message || 'Failed to fetch brands');
      }
    } catch (err) {
      console.error('Error fetching brands:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    e.preventDefault();
    setCurrentPage(1);
    setSearchParams({ search: searchTerm, page: 1 });
    fetchBrands();
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
    setSearchParams({ 
      ...(searchTerm && { search: searchTerm }), 
      page: page 
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const getBrandIcon = (brandName) => {
    const iconMap = {
      'Toyota': SiToyota,
      'Honda': SiHonda,
      'BMW': SiBmw,
      'Mercedes-Benz': SiMercedes,
      'Audi': SiAudi,
      'Nissan': SiNissan,
      'Mitsubishi': SiMitsubishi,
      'Subaru': SiSubaru,
      'Mazda': SiMazda,
      'Volkswagen': SiVolkswagen,
      'Ford': SiFord,
      'Chevrolet': SiChevrolet,
    };
    return iconMap[brandName] || FaCar;
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

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom py-20">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading brands...</p>
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
            <p className="text-red-600 mb-4">Error loading brands: {error}</p>
            <button 
              onClick={fetchBrands}
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
            VEHICLE BRANDS
          </div>
          <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Browse All <span className="gradient-text">Brands</span>
          </h1>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Discover vehicles from trusted manufacturers worldwide
          </p>
        </div>

        {/* Search and Filters */}
        <div className="bg-white rounded-2xl shadow-lg p-6 mb-8">
          <div className="flex flex-col lg:flex-row gap-4 items-center justify-between">
            {/* Search */}
            <form onSubmit={handleSearch} className="flex-1 max-w-md">
              <div className="relative">
                <FaSearch className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search brands..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </form>

            {/* Controls */}
            <div className="flex items-center gap-4">
              {/* Sort */}
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="name">Sort by Name</option>
                <option value="cars_count">Sort by Car Count</option>
                <option value="created_at">Sort by Date Added</option>
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
        </div>

        {/* Results Count */}
        <div className="mb-6">
          <p className="text-gray-600">
            Showing {brands.length} brands {searchTerm && `for "${searchTerm}"`}
          </p>
        </div>

        {/* Brands Grid/List */}
        {viewMode === 'grid' ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
            {brands.map((brand) => {
              const BrandIcon = getBrandIcon(brand.name);
              
              return (
                <Link
                  key={brand.id}
                  to={`/cars?brand=${brand.slug}`}
                  className="group"
                >
                  <div className="bg-white hover:bg-gray-50 border-2 border-gray-200 hover:border-blue-300 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:scale-105">
                    <div className="w-16 h-16 text-gray-700 mx-auto mb-4 flex items-center justify-center text-4xl">
                      <BrandIcon />
                    </div>
                    <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors mb-1">
                      {brand.name}
                    </h3>
                    <p className="text-xs text-gray-500">{brand.active_cars_count || 0} cars</p>
                  </div>
                </Link>
              );
            })}
          </div>
        ) : (
          <div className="space-y-4">
            {brands.map((brand) => {
              const BrandIcon = getBrandIcon(brand.name);
              
              return (
                <Link
                  key={brand.id}
                  to={`/cars?brand=${brand.slug}`}
                  className="group"
                >
                  <div className="bg-white hover:bg-gray-50 border border-gray-200 hover:border-blue-300 rounded-xl p-6 transition-all duration-300 hover:shadow-lg">
                    <div className="flex items-center gap-6">
                      <div className="w-16 h-16 text-gray-700 flex items-center justify-center text-4xl">
                        <BrandIcon />
                      </div>
                      <div className="flex-1">
                        <h3 className="text-xl font-semibold text-gray-900 group-hover:text-blue-600 transition-colors mb-1">
                          {brand.name}
                        </h3>
                        <p className="text-gray-600">{brand.active_cars_count || 0} vehicles available</p>
                        {brand.description && (
                          <p className="text-sm text-gray-500 mt-2">{brand.description}</p>
                        )}
                      </div>
                      <div className="text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <FaChevronRight />
                      </div>
                    </div>
                  </div>
                </Link>
              );
            })}
          </div>
        )}

        {/* Empty State */}
        {brands.length === 0 && !loading && (
          <div className="text-center py-12">
            <FaCar className="text-6xl text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-900 mb-2">No brands found</h3>
            <p className="text-gray-600 mb-6">
              {searchTerm ? `No brands match "${searchTerm}"` : 'No brands available at the moment'}
            </p>
            {searchTerm && (
              <button
                onClick={() => {
                  setSearchTerm('');
                  setSearchParams({});
                  setCurrentPage(1);
                }}
                className="btn-primary"
              >
                Clear Search
              </button>
            )}
          </div>
        )}

        {/* Pagination */}
        {totalPages > 1 && renderPagination()}
      </div>
    </div>
  );
};

export default Brands;