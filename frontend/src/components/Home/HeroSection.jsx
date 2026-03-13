import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaArrowRight, 
  FaShip, 
  FaShieldAlt, 
  FaClock, 
  FaStar,
  FaCheckCircle
} from 'react-icons/fa';

const HeroSection = () => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    setIsVisible(true);
  }, []);

  return (
    <section className="relative bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 text-white overflow-hidden h-[100vh] flex items-center pt-16">
      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute inset-0" style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
        }} />
      </div>

      {/* Background Image with Overlay */}
      <div 
        className="absolute inset-0 bg-cover bg-center bg-no-repeat"
        style={{
          backgroundImage: 'url(/images/rod.jpg)',
        }}
      >
        <div className="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-blue-900/80 to-slate-900/90"></div>
      </div>

      <div className={`container-custom relative z-10 w-full transition-all duration-1000 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'}`}>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          {/* Left Column - Content */}
          <div className="space-y-6 py-8 lg:py-0">

            {/* Main Heading */}
            <div className="space-y-4">
              <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight">
                Ship Your Dream Car
                <span className="block text-5xl md:text-5xl lg:text-7xl">to Uganda</span>
              </h1>
              <p className="text-lg md:text-xl text-blue-100 leading-relaxed max-w-lg">
                Seamless, transparent, and reliable vehicle shipping from 
                <span className="font-semibold text-white"> Japan, UK, and UAE</span> directly to Kampala.
              </p>
            </div>

            {/* CTA Buttons */}
            <div className="flex flex-col sm:flex-row gap-4 pt-2">
              <Link to="/quote" className="btn-primary text-base px-7 py-3 group">
                Get Instant Quote
                <FaArrowRight className="group-hover:translate-x-1 transition-transform" />
              </Link>
              <Link to="/track" className="btn-outline text-base px-7 py-3 group">
                <FaShip className="group-hover:scale-110 transition-transform" />
                Track Shipment
              </Link>
            </div>

            {/* Key Features */}
            <div className="grid grid-cols-3 gap-4 pt-2">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-blue-600/20 rounded-full flex items-center justify-center flex-shrink-0">
                  <FaShieldAlt className="text-blue-400 text-lg" />
                </div>
                <div>
                  <div className="font-semibold text-sm">100% Secure</div>
                  <div className="text-xs text-blue-200">Fully insured</div>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-green-600/20 rounded-full flex items-center justify-center flex-shrink-0">
                  <FaClock className="text-green-400 text-lg" />
                </div>
                <div>
                  <div className="font-semibold text-sm">Fast Delivery</div>
                  <div className="text-xs text-blue-200">21–30 days</div>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-yellow-600/20 rounded-full flex items-center justify-center flex-shrink-0">
                  <FaCheckCircle className="text-yellow-400 text-lg" />
                </div>
                <div>
                  <div className="font-semibold text-sm">All-Inclusive</div>
                  <div className="text-xs text-blue-200">No hidden fees</div>
                </div>
              </div>
            </div>

            {/* Trust Indicators */}
            <div className="flex flex-wrap items-center gap-6 pt-4 border-t border-white/10">
              <div className="text-center">
                <div className="text-2xl font-bold gradient-text-accent">15+</div>
                <div className="text-xs text-blue-200">Years Experience</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold gradient-text-accent">5,000+</div>
                <div className="text-xs text-blue-200">Vehicles Shipped</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold gradient-text-accent">98%</div>
                <div className="text-xs text-blue-200">On-Time Delivery</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold gradient-text-accent">24/7</div>
                <div className="text-xs text-blue-200">Customer Support</div>
              </div>
            </div>
          </div>

          {/* Right Column - Car Image */}
          <div className="relative hidden lg:flex items-center justify-center">
            {/* Glow behind image */}
            <div className="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-purple-600/20 rounded-3xl blur-3xl"></div>

            {/* Car image */}
            <img
              src="/images/car.png"
              alt="Premium Car Shipping"
              className="relative w-full h-auto max-h-[420px] object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-700"
            />

            {/* Floating badge */}
            <div className="absolute bottom-6 right-0 bg-white/10 backdrop-blur-md rounded-2xl px-4 py-3 border border-white/20 shadow-lg">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                  <FaShip className="text-white text-sm" />
                </div>
                <div>
                  <div className="font-semibold text-sm">In Transit</div>
                  <div className="text-xs text-blue-200">BMW X5 · Mombasa Port</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Scroll Indicator */}
      <div className="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <div className="w-6 h-10 border-2 border-white/30 rounded-full flex justify-center">
          <div className="w-1 h-3 bg-white/50 rounded-full mt-2 animate-pulse"></div>
        </div>
      </div>
    </section>
  );
};

export default HeroSection;

