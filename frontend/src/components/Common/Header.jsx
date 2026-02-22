import { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { FaPhone, FaEnvelope, FaFacebookF, FaInstagram, FaTwitter, FaPinterestP, FaChevronDown } from 'react-icons/fa';

const Header = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isServicesOpen, setIsServicesOpen] = useState(false);
  const [isAboutOpen, setIsAboutOpen] = useState(false);
  const location = useLocation();
  
  const isHomePage = location.pathname === '/';

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <header 
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${
        isHomePage 
          ? (isScrolled 
              ? 'bg-white/95 backdrop-blur-md shadow-lg' 
              : 'bg-transparent')
          : (isScrolled 
              ? 'bg-white shadow-lg' 
              : 'bg-white/95 backdrop-blur-md')
      }`}
    >
      {/* Top Bar - Contact Info - Always visible */}
      <div className="bg-navy-900 text-white py-4">
        <div className="container-custom flex justify-between items-center text-sm">
          <div className="flex items-center gap-6">
            <a href="tel:+256123456789" className="flex items-center gap-2 hover:text-gold-400 transition ms-5">
              <FaPhone className="text-sm" />
              <span className="hidden md:inline">Hot Line: +256 756 689882</span>
            </a>
            <a href="mailto:info@shipwithglowie.com" className="flex items-center gap-2 hover:text-gold-400 transition">
              <FaEnvelope className="text-sm" />
              <span className="hidden md:inline">info@shipwithglowie.com</span>
            </a>
          </div>
          <div className="flex items-center gap-4 mx-5">
            <span className="text-xs hidden md:inline">Follow us:</span>
            <div className="flex gap-3">
              <a href="#" className="hover:text-gold-400 transition"><FaFacebookF className="text-sm" /></a>
              <a href="#" className="hover:text-gold-400 transition"><FaInstagram className="text-sm" /></a>
              <a href="#" className="hover:text-gold-400 transition"><FaTwitter className="text-sm" /></a>
              <a href="#" className="hover:text-gold-400 transition"><FaPinterestP className="text-sm" /></a>
            </div>
          </div>
        </div>
      </div>

      {/* Main Navigation */}
      <div className={`container-custom flex justify-between items-center py-4 transition-all duration-300 ${
        isHomePage 
          ? (isScrolled 
              ? 'bg-white/95 backdrop-blur-md' 
              : 'bg-transparent')
          : 'bg-white'
      }`}>
        {/* Logo */}
        <Link to="/" className="flex items-center gap-2">
          <div className="text-2xl md:text-3xl font-heading font-bold ps-5">
            <span className={`${isHomePage && !isScrolled ? 'text-white' : 'gradient-text'}`}>ShipWith</span>
            <span className={`${isHomePage && !isScrolled ? 'text-yellow-400' : 'text-navy-900'}`}>Glowie</span>
          </div>
        </Link>
        
        {/* Center Navigation */}
        <nav className="hidden lg:flex items-center space-x-8">
          <Link 
            to="/" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            Home
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link>
          
          {/* <Link 
            to="/brands" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            Brands
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link> */}
          
          {/* <Link 
            to="/cars" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            Cars
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link> */}
          
          {/* <Link 
            to="/favorites" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            Favorites
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link> */}
          
          {/* Services Dropdown */}
          <div 
            className="relative"
            onMouseEnter={() => setIsServicesOpen(true)}
            onMouseLeave={() => setIsServicesOpen(false)}
          >
            <button
              className={`font-medium transition-all duration-300 relative group flex items-center gap-1 py-2 ${
                isHomePage && !isScrolled 
                  ? 'text-white hover:text-yellow-400' 
                  : 'text-gray-700 hover:text-blue-600'
              }`}
            >
              Services
              <FaChevronDown className={`text-xs transition-transform duration-300 ${isServicesOpen ? 'rotate-180' : ''}`} />
              <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
                isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
              }`}></span>
            </button>
            
            {/* Dropdown Menu */}
            {isServicesOpen && (
              <div className="absolute top-full left-0 pt-2 z-50">
                <div className="w-64 bg-white rounded-lg shadow-xl py-2 border border-gray-100">
                  <Link 
                    to="/services/how-it-works" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    How It Works
                  </Link>
                  <Link 
                    to="/services/request-quote" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    Request a Quote
                  </Link>
                  <Link 
                    to="/services/inland-transport" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    Inland Transport
                  </Link>
                  <Link 
                    to="/services/customs-clearance" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    Customs & Clearance
                  </Link>
                </div>
              </div>
            )}
          </div>
          
          {/* About Us Dropdown */}
          <div 
            className="relative"
            onMouseEnter={() => setIsAboutOpen(true)}
            onMouseLeave={() => setIsAboutOpen(false)}
          >
            <button
              className={`font-medium transition-all duration-300 relative group flex items-center gap-1 py-2 ${
                isHomePage && !isScrolled 
                  ? 'text-white hover:text-yellow-400' 
                  : 'text-gray-700 hover:text-blue-600'
              }`}
            >
              About Us
              <FaChevronDown className={`text-xs transition-transform duration-300 ${isAboutOpen ? 'rotate-180' : ''}`} />
              <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
                isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
              }`}></span>
            </button>
            
            {/* Dropdown Menu */}
            {isAboutOpen && (
              <div className="absolute top-full left-0 pt-2 z-50">
                <div className="w-64 bg-white rounded-lg shadow-xl py-2 border border-gray-100">
                  <Link 
                    to="/about" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    About ShipWithGlowie
                  </Link>
                  <Link 
                    to="/about/our-story" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    Our Story & Team
                  </Link>
                  <Link 
                    to="/about/certifications" 
                    className="block px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"
                  >
                    Certifications
                  </Link>
                </div>
              </div>
            )}
          </div>
          
          <Link 
            to="/faq" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            FAQ
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link>
          
          <Link 
            to="/news" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            News
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link>
          <Link 
            to="/contact" 
            className={`font-medium transition-all duration-300 relative group ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            Contact Us
            <span className={`absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 ${
              isHomePage && !isScrolled ? 'bg-yellow-400' : 'bg-gradient-primary'
            }`}></span>
          </Link>
        </nav>
        
        {/* Right Side - Action Buttons */}
        <div className="flex items-center gap-3">
          <Link 
            to="/login" 
            className={`hidden lg:flex items-center gap-2 px-5 py-2.5 font-medium transition-all duration-300 ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
          >
            <span>👤</span>
            <span>Login</span>
          </Link>
          
          <Link 
            to="/track" 
            className={`hidden md:flex items-center gap-2 px-3 py-2 rounded-md font-medium transition-all duration-300 shadow-md hover:shadow-lg mx-5 ${
              isHomePage && !isScrolled 
                ? 'bg-yellow-500 hover:bg-yellow-600 text-gray-900' 
                : 'bg-navy-900 hover:bg-blue-900 text-white'
            }`}
          >
            <span>📦</span>
            <span>Track Shipment</span>
          </Link>
          
          {/* Mobile Menu Button */}
          <button 
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className={`lg:hidden p-2 transition ${
              isHomePage && !isScrolled 
                ? 'text-white hover:text-yellow-400' 
                : 'text-gray-700 hover:text-blue-600'
            }`}
            aria-label="Toggle menu"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              {isMobileMenuOpen ? (
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              ) : (
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              )}
            </svg>
          </button>
        </div>
      </div>

      {/* Mobile Menu */}
      {isMobileMenuOpen && (
        <div className="lg:hidden bg-white/95 backdrop-blur-md border-t border-gray-200 animate-slide-up">
          <nav className="container-custom py-4 flex flex-col space-y-3">
            <Link 
              to="/" 
              className="text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
              onClick={() => setIsMobileMenuOpen(false)}
            >
              Home
            </Link>
            <Link 
              to="/brands" 
              className="text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
              onClick={() => setIsMobileMenuOpen(false)}
            >
              Brands
            </Link>
            <Link 
              to="/cars" 
              className="text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
              onClick={() => setIsMobileMenuOpen(false)}
            >
              Cars
            </Link>
            <Link 
              to="/favorites" 
              className="text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
              onClick={() => setIsMobileMenuOpen(false)}
            >
              Favorites
            </Link>
            <Link 
              to="/news" 
              className="text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
              onClick={() => setIsMobileMenuOpen(false)}
            >
              News
            </Link>
            <Link 
              to="/contact" 
              className="text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
              onClick={() => setIsMobileMenuOpen(false)}
            >
              Contact Us
            </Link>
            <div className="border-t border-gray-200 pt-3 mt-3 space-y-3">
              <Link 
                to="/login" 
                className="flex items-center gap-2 text-gray-700 hover:text-blue-600 font-medium py-2 px-4 rounded-lg hover:bg-blue-50 transition"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                <span>👤</span>
                <span>Register / Login</span>
              </Link>
              <Link 
                to="/track" 
                className="flex items-center justify-center gap-2 bg-navy-900 text-white px-4 py-3 rounded-md font-medium transition text-center"
                onClick={() => setIsMobileMenuOpen(false)}
              >
                <span>📦</span>
                <span>Track Shipment</span>
              </Link>
            </div>
          </nav>
        </div>
      )}
    </header>
  );
};

export default Header;
