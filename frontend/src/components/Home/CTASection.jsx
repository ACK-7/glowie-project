import React from 'react';
import { Link } from 'react-router-dom';
import { 
  FaArrowRight, 
  FaShip, 
  FaClock, 
  FaShieldAlt, 
  FaPhone,
  FaEnvelope,
  FaWhatsapp
} from 'react-icons/fa';

const CTASection = () => {
  return (
    <section className="section-padding bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 text-white relative overflow-hidden">
      {/* Background Elements */}
      <div className="absolute inset-0 opacity-10">
        <div className="absolute top-10 right-10 w-96 h-96 bg-blue-400 rounded-full filter blur-3xl animate-pulse"></div>
        <div className="absolute bottom-10 left-10 w-96 h-96 bg-purple-400 rounded-full filter blur-3xl animate-pulse" style={{ animationDelay: '1s' }}></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-yellow-400 rounded-full filter blur-3xl animate-pulse" style={{ animationDelay: '2s' }}></div>
      </div>

      {/* Background Pattern */}
      <div className="absolute inset-0 opacity-5">
        <div className="absolute inset-0" style={{
          backgroundImage: `url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")`,
        }} />
      </div>
      
      <div className="container-custom relative z-10">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Left Column - Main CTA */}
          <div className="text-center lg:text-left">
            <div className="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md rounded-full text-sm font-semibold mb-6">
              <FaShip className="text-blue-400" />
              START YOUR JOURNEY TODAY
            </div>
            
            <h2 className="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
              Ready to Ship Your
              <span className="block">
                <span className="gradient-text-accent">Dream Car</span>?
              </span>
            </h2>
            
            <p className="text-xl text-blue-100 mb-8 leading-relaxed">
              Join thousands of satisfied customers who trusted ShipWithGlowie 
              with their vehicle imports. Get started with a free instant quote today.
            </p>
            
            <div className="flex flex-col sm:flex-row gap-4 mb-8">
              <Link 
                to="/quote" 
                className="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-bold py-4 px-8 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-flex items-center justify-center gap-2 text-lg"
              >
                Get Free Quote Now
                <FaArrowRight className="group-hover:translate-x-1 transition-transform" />
              </Link>
              <Link 
                to="/track" 
                className="border-2 border-white text-white hover:bg-white hover:text-blue-900 font-semibold py-4 px-8 rounded-lg transition-all duration-300 inline-flex items-center justify-center gap-2 text-lg"
              >
                <FaShip />
                Track Existing Shipment
              </Link>
            </div>

            {/* Trust Indicators */}
            <div className="flex flex-wrap items-center gap-6 justify-center lg:justify-start">
              <div className="flex items-center gap-2">
                <div className="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                  <FaShieldAlt className="text-green-400" />
                </div>
                <span className="text-sm">100% Secure & Insured</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                  <FaClock className="text-blue-400" />
                </div>
                <span className="text-sm">21-30 Days Delivery</span>
              </div>
            </div>
          </div>

          {/* Right Column - Contact & Features */}
          <div className="space-y-8">
            {/* Quick Contact */}
            <div className="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20">
              <h3 className="text-xl font-bold mb-4">Need Help? Contact Us</h3>
              <div className="space-y-3">
                <a href="tel:+256700123456" className="flex items-center gap-3 text-blue-100 hover:text-white transition-colors">
                  <div className="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                    <FaPhone className="text-blue-400" />
                  </div>
                  <div>
                    <div className="font-semibold">+256 700 123 456</div>
                    <div className="text-sm text-blue-200">24/7 Support Hotline</div>
                  </div>
                </a>
                <a href="mailto:info@shipwithglowie.com" className="flex items-center gap-3 text-blue-100 hover:text-white transition-colors">
                  <div className="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                    <FaEnvelope className="text-green-400" />
                  </div>
                  <div>
                    <div className="font-semibold">info@shipwithglowie.com</div>
                    <div className="text-sm text-blue-200">Email Support</div>
                  </div>
                </a>
                <a href="https://wa.me/256700123456" className="flex items-center gap-3 text-blue-100 hover:text-white transition-colors">
                  <div className="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                    <FaWhatsapp className="text-green-400" />
                  </div>
                  <div>
                    <div className="font-semibold">WhatsApp Chat</div>
                    <div className="text-sm text-blue-200">Instant Messaging</div>
                  </div>
                </a>
              </div>
            </div>

            {/* Process Steps */}
            <div className="bg-white/5 backdrop-blur-md rounded-2xl p-6 border border-white/10">
              <h3 className="text-xl font-bold mb-4">Simple 3-Step Process</h3>
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">1</div>
                  <span>Get instant quote & book online</span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                  <span>We handle pickup & shipping</span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
                  <span>Receive your car in Uganda</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Stats */}
        <div className="mt-16 pt-8 border-t border-white/10">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
              <div className="text-3xl font-bold gradient-text-accent mb-2">5,000+</div>
              <div className="text-blue-200 text-sm">Vehicles Shipped</div>
            </div>
            <div>
              <div className="text-3xl font-bold gradient-text-accent mb-2">15+</div>
              <div className="text-blue-200 text-sm">Years Experience</div>
            </div>
            <div>
              <div className="text-3xl font-bold gradient-text-accent mb-2">98%</div>
              <div className="text-blue-200 text-sm">On-Time Delivery</div>
            </div>
            <div>
              <div className="text-3xl font-bold gradient-text-accent mb-2">24/7</div>
              <div className="text-blue-200 text-sm">Customer Support</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default CTASection;
