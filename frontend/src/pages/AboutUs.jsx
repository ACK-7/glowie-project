import React from 'react';
import { Link } from 'react-router-dom';
import { FaUsers, FaStar, FaCertificate, FaArrowRight } from 'react-icons/fa';

const AboutUs = () => {
  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">About ShipWithGlowie</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Uganda's trusted partner in international car shipping. Local expertise, global reach.
          </p>
        </div>
      </section>

      {/* Mission & Vision */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">Our Mission</h2>
            <p className="text-gray-600 text-lg leading-relaxed">
              To make international car shipping to Uganda transparent, reliable, and stress-free. 
              We bridge the gap between global car markets and Ugandan buyers with cutting-edge technology 
              and deep local expertise.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div className="text-center p-6">
              <div className="text-5xl mb-4">🎯</div>
              <h3 className="text-xl font-bold text-navy-900 mb-3">Transparency First</h3>
              <p className="text-gray-600">
                No hidden fees. Clear communication. You know exactly what you're paying for.
              </p>
            </div>
            <div className="text-center p-6">
              <div className="text-5xl mb-4">🤝</div>
              <h3 className="text-xl font-bold text-navy-900 mb-3">Local Expertise</h3>
              <p className="text-gray-600">
                We understand URA regulations, UNBS requirements, and the Mombasa-Kampala route better than anyone.
              </p>
            </div>
            <div className="text-center p-6">
              <div className="text-5xl mb-4">⚡</div>
              <h3 className="text-xl font-bold text-navy-900 mb-3">Technology Driven</h3>
              <p className="text-gray-600">
                AI-powered quotes, real-time tracking, and automated processes save you time and money.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Sub-Pages Links */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Our Story */}
            <Link to="/about/our-story" className="premium-card group">
              <div className="p-8">
                <div className="w-16 h-16 bg-gradient-primary rounded-2xl flex items-center justify-center text-white text-2xl mb-6 group-hover:scale-110 transition-transform duration-300 shadow-glow">
                  <FaUsers />
                </div>
                <h3 className="text-2xl font-bold text-navy-900 mb-4">Our Story & Team</h3>
                <p className="text-gray-600 mb-6">
                  Learn about our journey and meet the team making your car shipping dreams a reality.
                </p>
                <div className="flex items-center gap-2 text-blue-600 font-medium">
                  Learn More <FaArrowRight className="text-sm" />
                </div>
              </div>
            </Link>

            {/* Testimonials */}
            <Link to="/about/testimonials" className="premium-card group">
              <div className="p-8">
                <div className="w-16 h-16 bg-gradient-secondary rounded-2xl flex items-center justify-center text-white text-2xl mb-6 group-hover:scale-110 transition-transform duration-300 shadow-glow-red">
                  <FaStar />
                </div>
                <h3 className="text-2xl font-bold text-navy-900 mb-4">Client Testimonials</h3>
                <p className="text-gray-600 mb-6">
                  See what our satisfied customers have to say about their experience with ShipWithGlowie.
                </p>
                <div className="flex items-center gap-2 text-blue-600 font-medium">
                  Read Reviews <FaArrowRight className="text-sm" />
                </div>
              </div>
            </Link>

            {/* Certifications */}
            <Link to="/about/certifications" className="premium-card group">
              <div className="p-8">
                <div className="w-16 h-16 bg-gradient-accent rounded-2xl flex items-center justify-center text-white text-2xl mb-6 group-hover:scale-110 transition-transform duration-300 shadow-glow-gold">
                  <FaCertificate />
                </div>
                <h3 className="text-2xl font-bold text-navy-900 mb-4">Certifications</h3>
                <p className="text-gray-600 mb-6">
                  View our URA approval, freight forwarding licenses, and insurance coverage details.
                </p>
                <div className="flex items-center gap-2 text-blue-600 font-medium">
                  View Credentials <FaArrowRight className="text-sm" />
                </div>
              </div>
            </Link>
          </div>
        </div>
      </section>

      {/* Stats */}
      <section className="section-padding bg-navy-900 text-white">
        <div className="container-custom">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
              <div className="text-4xl md:text-5xl font-bold gradient-text-accent mb-2">40+</div>
              <div className="text-blue-200">Years Experience</div>
            </div>
            <div>
              <div className="text-4xl md:text-5xl font-bold gradient-text-accent mb-2">5,000+</div>
              <div className="text-blue-200">Vehicles Shipped</div>
            </div>
            <div>
              <div className="text-4xl md:text-5xl font-bold gradient-text-accent mb-2">98%</div>
              <div className="text-blue-200">Customer Satisfaction</div>
            </div>
            <div>
              <div className="text-4xl md:text-5xl font-bold gradient-text-accent mb-2">24/7</div>
              <div className="text-blue-200">Customer Support</div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default AboutUs;
