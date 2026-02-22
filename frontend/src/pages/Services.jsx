import React from 'react';
import { Link } from 'react-router-dom';
import { FaCheckCircle } from 'react-icons/fa';

const Services = () => {
  return (
    <div className="bg-white">
      <div className="bg-blue-900 text-white py-16">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-4xl font-bold mb-4">Our Services</h1>
          <p className="text-xl text-blue-100 max-w-2xl mx-auto">
            Comprehensive shipping solutions tailored for the Ugandan market.
          </p>
        </div>
      </div>

      <div className="container mx-auto px-4 py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-20">
          <div>
            <h2 className="text-3xl font-bold text-gray-800 mb-6">International Car Shipping</h2>
            <p className="text-gray-600 text-lg mb-6">
              We specialize in shipping vehicles from major global hubs directly to Kampala. Whether you're buying from Japan, the UK, or the UAE, we handle the logistics so you don't have to.
            </p>
            <ul className="space-y-3 mb-8">
              <li className="flex items-center text-gray-700">
                <FaCheckCircle className="text-green-500 mr-3 mt-1 flex-shrink-0" /> Secure RoRo and Container shipping
              </li>
              <li className="flex items-center text-gray-700">
                <FaCheckCircle className="text-green-500 mr-3 mt-1 flex-shrink-0" /> Pre-shipment inspection
              </li>
              <li className="flex items-center text-gray-700">
                <FaCheckCircle className="text-green-500 mr-3 mt-1 flex-shrink-0" /> Marine insurance included
              </li>
            </ul>
            <Link to="/quote" className="text-blue-600 font-bold hover:underline">
              Get a Shipping Quote &rarr;
            </Link>
          </div>
          <div className="bg-gray-200 h-80 rounded-lg flex items-center justify-center">
            <span className="text-gray-400 font-medium">Image: Car being loaded onto ship</span>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-20 flex-row-reverse">
          <div className="order-2 md:order-1 bg-gray-200 h-80 rounded-lg flex items-center justify-center">
             <span className="text-gray-400 font-medium">Image: Customs documents</span>
          </div>
          <div className="order-1 md:order-2">
            <h2 className="text-3xl font-bold text-gray-800 mb-6">Customs Clearance & Taxes</h2>
            <p className="text-gray-600 text-lg mb-6">
              Navigating URA taxes and customs procedures can be a nightmare. Our team of experts handles all the paperwork, ensuring your vehicle is cleared quickly and correctly.
            </p>
            <ul className="space-y-3 mb-8">
              <li className="flex items-center text-gray-700">
                <span className="text-green-500 mr-3">✓</span> Accurate tax assessment
              </li>
              <li className="flex items-center text-gray-700">
                <span className="text-green-500 mr-3">✓</span> Document verification
              </li>
              <li className="flex items-center text-gray-700">
                <span className="text-green-500 mr-3">✓</span> Fast-track clearance options
              </li>
            </ul>
          </div>
        </div>

        <div className="bg-gray-50 rounded-xl p-10 text-center">
          <h2 className="text-3xl font-bold text-gray-800 mb-6">Ready to Ship?</h2>
          <p className="text-gray-600 max-w-2xl mx-auto mb-8">
            Join thousands of satisfied Ugandans who have trusted ShipWithGlowie to bring their dream cars home.
          </p>
          <Link 
            to="/quote" 
            className="bg-blue-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-blue-700 transition inline-block"
          >
            Start Your Booking
          </Link>
        </div>
      </div>
    </div>
  );
};

export default Services;
