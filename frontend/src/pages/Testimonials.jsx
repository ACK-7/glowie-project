import React, { useState } from 'react';
import { FaStar, FaQuoteLeft } from 'react-icons/fa';

const Testimonials = () => {
  const [activeFilter, setActiveFilter] = useState('all');

  const testimonials = [
    {
      name: 'Peter Ssemakula',
      location: 'Kampala',
      route: 'Japan → Uganda',
      rating: 5,
      date: 'November 2024',
      text: 'Exceptional service from start to finish. My Toyota Land Cruiser arrived in perfect condition. The team handled all the URA paperwork flawlessly. Highly recommend!',
      vehicle: '2019 Toyota Land Cruiser',
      category: 'japan'
    },
    {
      name: 'Mary Nakato',
      location: 'Entebbe',
      route: 'UK → Uganda',
      rating: 5,
      date: 'October 2024',
      text: 'ShipWithGlowie made importing my Range Rover so easy. Real-time tracking gave me peace of mind, and their customs clearance expertise saved me weeks of hassle.',
      vehicle: '2020 Range Rover Sport',
      category: 'uk'
    },
    {
      name: 'James Okello',
      location: 'Jinja',
      route: 'UAE → Uganda',
      rating: 5,
      date: 'September 2024',
      text: 'Fastest shipping I\'ve experienced! My Nissan Patrol arrived from Dubai in just 3 weeks. The inland transport from Mombasa was smooth and professional.',
      vehicle: '2021 Nissan Patrol',
      category: 'uae'
    },
    {
      name: 'Susan Nambi',
      location: 'Kampala',
      route: 'Japan → Uganda',
      rating: 5,
      date: 'August 2024',
      text: 'Transparent pricing with no hidden fees. I knew exactly what I was paying for. The customer support team answered all my questions promptly. Thank you!',
      vehicle: '2018 Honda CR-V',
      category: 'japan'
    },
    {
      name: 'David Mugisha',
      location: 'Mbarara',
      route: 'UK → Uganda',
      rating: 5,
      date: 'July 2024',
      text: 'Professional team that knows the customs process inside out. They handled UNBS compliance and all documentation. My BMW arrived exactly as described.',
      vehicle: '2019 BMW X5',
      category: 'uk'
    },
    {
      name: 'Grace Achieng',
      location: 'Kampala',
      route: 'UAE → Uganda',
      rating: 5,
      date: 'June 2024',
      text: 'Best decision choosing ShipWithGlowie. The AI quote was accurate, tracking was real-time, and delivery to my doorstep in Kampala was on schedule.',
      vehicle: '2020 Mercedes C-Class',
      category: 'uae'
    }
  ];

  const filteredTestimonials = activeFilter === 'all' 
    ? testimonials 
    : testimonials.filter(t => t.category === activeFilter);

  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Client Testimonials</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Real stories from real customers who trusted ShipWithGlowie with their vehicle imports.
          </p>
        </div>
      </section>

      {/* Stats Bar */}
      <section className="bg-blue-50 py-8">
        <div className="container-custom">
          <div className="grid grid-cols-3 gap-8 text-center">
            <div>
              <div className="text-3xl font-bold text-blue-600 mb-1">4.9/5</div>
              <div className="text-gray-600">Average Rating</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-blue-600 mb-1">400+</div>
              <div className="text-gray-600">Happy Clients</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-blue-600 mb-1">98%</div>
              <div className="text-gray-600">Would Recommend</div>
            </div>
          </div>
        </div>
      </section>

      {/* Filter Tabs */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="flex justify-center gap-4 mb-12 flex-wrap">
            <button
              onClick={() => setActiveFilter('all')}
              className={`px-6 py-3 rounded-lg font-medium transition ${
                activeFilter === 'all'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              All Reviews
            </button>
            <button
              onClick={() => setActiveFilter('japan')}
              className={`px-6 py-3 rounded-lg font-medium transition ${
                activeFilter === 'japan'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              Japan Route
            </button>
            <button
              onClick={() => setActiveFilter('uk')}
              className={`px-6 py-3 rounded-lg font-medium transition ${
                activeFilter === 'uk'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              UK Route
            </button>
            <button
              onClick={() => setActiveFilter('uae')}
              className={`px-6 py-3 rounded-lg font-medium transition ${
                activeFilter === 'uae'
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              UAE Route
            </button>
          </div>

          {/* Testimonials Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {filteredTestimonials.map((testimonial, index) => (
              <div key={index} className="bg-white rounded-xl shadow-lg p-8 hover:shadow-xl transition-shadow duration-300">
                <div className="flex items-center gap-4 mb-6">
                  <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-2xl">
                    👤
                  </div>
                  <div>
                    <h3 className="font-bold text-navy-900">{testimonial.name}</h3>
                    <p className="text-sm text-gray-600">{testimonial.location}</p>
                  </div>
                </div>

                <div className="flex items-center gap-1 mb-4">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <FaStar key={i} className="text-yellow-400" />
                  ))}
                </div>

                <div className="relative mb-4">
                  <FaQuoteLeft className="text-blue-200 text-3xl absolute -top-2 -left-2" />
                  <p className="text-gray-600 leading-relaxed pl-6">{testimonial.text}</p>
                </div>

                <div className="pt-4 border-t border-gray-100">
                  <p className="text-sm text-gray-600 mb-1">
                    <span className="font-medium">Vehicle:</span> {testimonial.vehicle}
                  </p>
                  <p className="text-sm text-gray-600 mb-1">
                    <span className="font-medium">Route:</span> {testimonial.route}
                  </p>
                  <p className="text-sm text-gray-500">{testimonial.date}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section-padding bg-navy-900 text-white">
        <div className="container-custom text-center">
          <h2 className="text-3xl md:text-4xl font-bold mb-6">Ready to Join Our Happy Clients?</h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Experience the same excellent service that earned us these 5-star reviews.
          </p>
          <a href="/services/request-quote" className="btn-secondary inline-block px-12">
            Get Your Free Quote
          </a>
        </div>
      </section>
    </div>
  );
};

export default Testimonials;
