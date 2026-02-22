import React from 'react';
import { Link } from 'react-router-dom';
import { FaPhone, FaEnvelope, FaMapMarkerAlt, FaFacebookF, FaInstagram, FaTwitter, FaLinkedinIn, FaChevronRight } from 'react-icons/fa';

const Footer = () => {
  return (
    <footer className="bg-navy-900 text-white relative">
      {/* Gradient Top Border */}
      <div className="h-1 bg-gradient-accent"></div>
      
      <div className="container-custom py-16">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
          {/* Company Info */}
          <div className="md:col-span-2">
            <h3 className="text-2xl font-heading font-bold mb-4">
              <span className="gradient-text">ShipWith</span>
              <span className="text-white">Glowie</span>
            </h3>
            <p className="text-gray-400 leading-relaxed mb-6 max-w-md">
              Simplifying international car shipping to Uganda with reliability, 
              transparency, and cutting-edge automation. Your trusted partner for 
              seamless vehicle imports.
            </p>
            <div className="flex gap-4">
              <a 
                href="#" 
                className="w-10 h-10 bg-white/10 hover:bg-gradient-primary rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110"
              >
                📘
              </a>
              <a 
                href="#" 
                className="w-10 h-10 bg-white/10 hover:bg-gradient-primary rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110"
              >
                📷
              </a>
              <a 
                href="#" 
                className="w-10 h-10 bg-white/10 hover:bg-gradient-primary rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110"
              >
                🐦
              </a>
              <a 
                href="#" 
                className="w-10 h-10 bg-white/10 hover:bg-gradient-primary rounded-full flex items-center justify-center transition-all duration-300 hover:scale-110"
              >
                💼
              </a>
            </div>
          </div>
          
          {/* Quick Links */}
          <div>
            <h4 className="text-lg font-heading font-semibold mb-4 text-gold-400">Quick Links</h4>
            <ul className="space-y-3">
              <li>
                <Link to="/" className="text-gray-400 hover:text-white hover:translate-x-2 inline-block transition-all duration-300">
                  <FaChevronRight className="text-xs mr-2" /> Home
                </Link>
              </li>
              <li>
                <Link to="/services" className="text-gray-400 hover:text-white hover:translate-x-2 inline-block transition-all duration-300">
                  <FaChevronRight className="text-xs mr-2" /> Services
                </Link>
              </li>
              <li>
                <Link to="/quote" className="text-gray-400 hover:text-white hover:translate-x-2 inline-block transition-all duration-300">
                  <FaChevronRight className="text-xs mr-2" /> Get Quote
                </Link>
              </li>
              <li>
                <Link to="/manage-booking" className="text-gray-400 hover:text-white hover:translate-x-2 inline-block transition-all duration-300">
                  <FaChevronRight className="text-xs mr-2" /> Track Shipment
                </Link>
              </li>
            </ul>
          </div>
          
          {/* Contact Info */}
          <div>
            <h4 className="text-lg font-heading font-semibold mb-4 text-gold-400">Contact Us</h4>
            <ul className="space-y-3 text-gray-400">
              <li className="flex items-start gap-2">
                <span>📍</span>
                <span>Kampala, Uganda</span>
              </li>
              <li className="flex items-start gap-2">
                <span>✉️</span>
                <a href="mailto:support@shipwithglowie.com" className="hover:text-white transition">
                  support@shipwithglowie.com
                </a>
              </li>
              <li className="flex items-start gap-2">
                <FaPhone className="text-sm mt-1" />
                <a href="tel:+256700000000" className="hover:text-white transition">
                  +256 700 000 000
                </a>
              </li>
            </ul>
          </div>
        </div>
        
        {/* Bottom Bar */}
        <div className="border-t border-white/10 pt-8">
          <div className="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-400">
            <p>
              © {new Date().getFullYear()} <span className="text-gold-400 font-semibold">ShipWithGlowie Auto</span>. All rights reserved.
            </p>
            <div className="flex gap-6">
              <a href="#" className="hover:text-white transition">Privacy Policy</a>
              <a href="#" className="hover:text-white transition">Terms of Service</a>
              <a href="#" className="hover:text-white transition">Cookie Policy</a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
