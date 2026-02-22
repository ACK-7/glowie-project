import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaArrowRight, 
  FaCar,
  FaShip,
  FaTruck,
  FaMotorcycle,
  FaRoad,
  FaIndustry
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

const BrowseByBrand = () => {
  const [brands, setBrands] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const brandsUrl = `http://localhost:8000/api/cars/brands`;
      const categoriesUrl = `http://localhost:8000/api/cars/categories`;
      
      console.log('Fetching brands from:', brandsUrl);
      console.log('Fetching categories from:', categoriesUrl);
      
      const [brandsResponse, categoriesResponse] = await Promise.all([
        fetch(brandsUrl, {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        }),
        fetch(categoriesUrl, {
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        })
      ]);
      
      console.log('Brands response status:', brandsResponse.status);
      console.log('Categories response status:', categoriesResponse.status);
      
      if (!brandsResponse.ok || !categoriesResponse.ok) {
        throw new Error(`HTTP error! Brands: ${brandsResponse.status}, Categories: ${categoriesResponse.status}`);
      }
      
      const brandsData = await brandsResponse.json();
      const categoriesData = await categoriesResponse.json();
      
      console.log('Brands data:', brandsData);
      console.log('Categories data:', categoriesData);
      
      if (brandsData.success && categoriesData.success) {
        setBrands(brandsData.data);
        setCategories(categoriesData.data);
      } else {
        throw new Error('Failed to fetch data');
      }
    } catch (err) {
      console.error('Error fetching data:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // Icon mapping for brands
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

  // Color mapping for brands - Minimalistic approach
  const getBrandColors = (brandName) => {
    // Single consistent color scheme for all brands
    return { 
      color: 'text-gray-700', 
      bgColor: 'bg-white hover:bg-gray-50', 
      borderColor: 'border-gray-200 hover:border-blue-300' 
    };
  };

  // Icon mapping for categories
  const getCategoryIcon = (categoryName) => {
    const iconMap = {
      'Sedan': FaCar,
      'SUV': FaTruck,
      'Hatchback': FaCar,
      'Coupe': FaCar,
      'Convertible': FaCar,
      'Wagon': FaTruck,
      'Pickup': FaTruck,
      'Van': FaTruck,
      'Motorcycle': FaMotorcycle,
      'Truck': FaTruck,
    };
    return iconMap[categoryName] || FaCar;
  };

  // Color mapping for categories - Minimalistic approach
  const getCategoryColors = (categoryName) => {
    // Single consistent color scheme for all categories
    return { 
      color: 'text-gray-700', 
      bgColor: 'bg-white hover:bg-gray-50' 
    };
  };

  if (loading) {
    return (
      <section className="section-padding bg-gradient-to-b from-gray-50 to-white">
        <div className="container-custom">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading brands and categories...</p>
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
            <p className="text-red-600">Error loading data: {error}</p>
            <button 
              onClick={fetchData}
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
            <FaShip className="text-blue-600" />
            FIND YOUR PERFECT VEHICLE
          </div>
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Browse by <span className="gradient-text">Popular Brands</span>
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Choose from thousands of quality vehicles from trusted manufacturers worldwide
          </p>
        </div>

        {/* Vehicle Categories */}
        {categories.length > 0 && (
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
            {categories.slice(0, 4).map((category) => {
              const CategoryIcon = getCategoryIcon(category.name);
              const colors = getCategoryColors(category.name);
              
              return (
                <Link
                  key={category.id}
                  to={`/cars?category=${category.slug}`}
                  className="group"
                >
                  <div className={`${colors.bgColor} rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:scale-105 border-2 border-gray-200 hover:border-blue-300`}>
                    <div className={`w-16 h-16 ${colors.color} mx-auto mb-4 flex items-center justify-center text-3xl`}>
                      <CategoryIcon />
                    </div>
                    <h3 className="font-bold text-gray-900 mb-1">{category.name}</h3>
                    <p className="text-sm text-gray-600">{category.active_cars_count || 0} available</p>
                  </div>
                </Link>
              );
            })}
          </div>
        )}

        {/* Brand Grid - Limited to 6 brands */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
          {brands.slice(0, 6).map((brand) => {
            const BrandIcon = getBrandIcon(brand.name);
            const colors = getBrandColors(brand.name);
            
            return (
              <Link
                key={brand.id}
                to={`/cars?brand=${brand.slug}`}
                className="group"
              >
                <div className={`${colors.bgColor} ${colors.borderColor} rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-lg hover:scale-105 border-2`}>
                  <div className={`w-16 h-16 ${colors.color} mx-auto mb-4 flex items-center justify-center text-4xl`}>
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

        {/* View All Brands Button */}
        {brands.length > 6 && (
          <div className="text-center mb-12">
            <Link
              to="/brands"
              className="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-900 font-semibold py-3 px-8 rounded-lg border-2 border-gray-200 hover:border-blue-300 transition-all duration-300 shadow-lg hover:shadow-xl"
            >
              View All {brands.length} Brands
              <FaArrowRight />
            </Link>
          </div>
        )}

        {/* CTA Section */}
        <div className="text-center">
          <div className="bg-gradient-to-r from-blue-600 to-purple-600 rounded-3xl p-8 md:p-12 text-white">
            <h3 className="text-2xl md:text-3xl font-bold mb-4">
              Can't find your preferred brand?
            </h3>
            <p className="text-blue-100 mb-6 text-lg">
              We work with dealers worldwide to source any vehicle you need
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/cars"
                className="bg-white text-blue-600 hover:bg-gray-100 font-semibold py-3 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2"
              >
                Browse All Vehicles
                <FaArrowRight />
              </Link>
              <Link
                to="/contact"
                className="border-2 border-white text-white hover:bg-white hover:text-blue-600 font-semibold py-3 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2"
              >
                Request Custom Search
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default BrowseByBrand;
