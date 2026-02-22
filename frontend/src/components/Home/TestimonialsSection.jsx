import React, { useState } from 'react';
import { FaStar, FaQuoteLeft, FaUser, FaMapMarkerAlt, FaShip, FaCheckCircle } from 'react-icons/fa';

const TestimonialsSection = () => {
  const [activeTestimonial, setActiveTestimonial] = useState(0);

  const testimonials = [
    {
      name: 'Peter Ssemakula',
      location: 'Kampala, Uganda',
      route: 'Japan → Uganda',
      rating: 5,
      text: 'Exceptional service from start to finish. My Toyota Land Cruiser arrived in perfect condition and ahead of schedule. The team handled all the URA paperwork flawlessly, and I was kept informed every step of the way.',
      vehicle: '2019 Toyota Land Cruiser',
      avatar: 'PS',
      color: 'bg-blue-500',
      deliveryTime: '19 days'
    },
    {
      name: 'Mary Nakato',
      location: 'Entebbe, Uganda',
      route: 'UK → Uganda',
      rating: 5,
      text: 'ShipWithGlowie made importing my Range Rover incredibly easy. The real-time tracking gave me complete peace of mind, and their customs clearance expertise saved me weeks of hassle and potential delays.',
      vehicle: '2020 Range Rover Sport',
      avatar: 'MN',
      color: 'bg-purple-500',
      deliveryTime: '23 days'
    },
    {
      name: 'James Okello',
      location: 'Jinja, Uganda',
      route: 'UAE → Uganda',
      rating: 5,
      text: 'Fastest shipping I\'ve ever experienced! My Nissan Patrol arrived from Dubai in just 3 weeks. The inland transport from Mombasa was smooth and professional. Highly recommend their services.',
      vehicle: '2021 Nissan Patrol',
      avatar: 'JO',
      color: 'bg-green-500',
      deliveryTime: '21 days'
    },
    {
      name: 'Sarah Namukasa',
      location: 'Mbarara, Uganda',
      route: 'Japan → Uganda',
      rating: 5,
      text: 'Outstanding customer service and transparent pricing. No hidden fees, no surprises. My Honda CR-V arrived exactly as described. The team went above and beyond to ensure everything was perfect.',
      vehicle: '2020 Honda CR-V',
      avatar: 'SN',
      color: 'bg-pink-500',
      deliveryTime: '25 days'
    },
    {
      name: 'David Mukasa',
      location: 'Gulu, Uganda',
      route: 'UK → Uganda',
      rating: 5,
      text: 'Professional, reliable, and trustworthy. They handled my BMW X5 with care and kept me updated throughout the journey. The documentation process was seamless. Will definitely use them again.',
      vehicle: '2019 BMW X5',
      avatar: 'DM',
      color: 'bg-indigo-500',
      deliveryTime: '22 days'
    }
  ];

  const stats = [
    {
      icon: FaStar,
      number: '4.9/5',
      label: 'Average Rating',
      color: 'text-yellow-500'
    },
    {
      icon: FaUser,
      number: '5,000+',
      label: 'Happy Customers',
      color: 'text-blue-500'
    },
    {
      icon: FaCheckCircle,
      number: '98%',
      label: 'Would Recommend',
      color: 'text-green-500'
    },
    {
      icon: FaShip,
      number: '21 days',
      label: 'Average Delivery',
      color: 'text-purple-500'
    }
  ];

  return (
    <section className="section-padding bg-gradient-to-b from-white to-gray-50">
      <div className="container-custom">
        {/* Section Header */}
        <div className="text-center mb-16">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold mb-4">
            <FaStar className="text-yellow-600" />
            CUSTOMER TESTIMONIALS
          </div>
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            What Our <span className="gradient-text">Customers Say</span>
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Real stories from real customers who trusted ShipWithGlowie with their vehicle imports
          </p>
        </div>

        {/* Stats Section */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
          {stats.map((stat, index) => (
            <div key={index} className="bg-white rounded-2xl p-6 text-center shadow-lg hover:shadow-xl transition-shadow duration-300">
              <div className={`w-16 h-16 ${stat.color.replace('text-', 'bg-').replace('-500', '-100')} rounded-2xl flex items-center justify-center mx-auto mb-4`}>
                <stat.icon className={`text-2xl ${stat.color}`} />
              </div>
              <div className={`text-3xl font-bold ${stat.color} mb-2`}>
                {stat.number}
              </div>
              <div className="text-gray-600 font-medium">
                {stat.label}
              </div>
            </div>
          ))}
        </div>

        {/* Featured Testimonial */}
        <div className="bg-white rounded-3xl shadow-2xl p-8 md:p-12 mb-12 border border-gray-100">
          <div className="grid md:grid-cols-2 gap-8 items-center">
            <div>
              <div className="flex items-center gap-4 mb-6">
                <div className={`w-20 h-20 ${testimonials[activeTestimonial].color} rounded-full flex items-center justify-center text-white text-2xl font-bold`}>
                  {testimonials[activeTestimonial].avatar}
                </div>
                <div>
                  <h3 className="text-2xl font-bold text-gray-900">{testimonials[activeTestimonial].name}</h3>
                  <div className="flex items-center gap-2 text-gray-600">
                    <FaMapMarkerAlt className="text-sm" />
                    <span>{testimonials[activeTestimonial].location}</span>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-1 mb-6">
                {[...Array(testimonials[activeTestimonial].rating)].map((_, i) => (
                  <FaStar key={i} className="text-yellow-400 text-xl" />
                ))}
              </div>

              <div className="relative mb-6">
                <FaQuoteLeft className="text-blue-200 text-4xl absolute -top-4 -left-2" />
                <p className="text-lg text-gray-700 leading-relaxed pl-8 italic">
                  "{testimonials[activeTestimonial].text}"
                </p>
              </div>
            </div>

            <div className="space-y-4">
              <div className="bg-gray-50 rounded-2xl p-6">
                <h4 className="font-semibold text-gray-900 mb-4">Shipment Details</h4>
                <div className="space-y-3">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Vehicle:</span>
                    <span className="font-medium">{testimonials[activeTestimonial].vehicle}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Route:</span>
                    <span className="font-medium">{testimonials[activeTestimonial].route}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Delivery Time:</span>
                    <span className="font-medium text-green-600">{testimonials[activeTestimonial].deliveryTime}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Testimonial Navigation */}
        <div className="flex justify-center gap-4 mb-12">
          {testimonials.map((_, index) => (
            <button
              key={index}
              onClick={() => setActiveTestimonial(index)}
              className={`w-4 h-4 rounded-full transition-all duration-300 ${
                index === activeTestimonial 
                  ? 'bg-blue-600 scale-125' 
                  : 'bg-gray-300 hover:bg-gray-400'
              }`}
            />
          ))}
        </div>

        {/* All Testimonials Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {testimonials.slice(0, 3).map((testimonial, index) => (
            <div key={index} className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
              <div className="flex items-center gap-3 mb-4">
                <div className={`w-12 h-12 ${testimonial.color} rounded-full flex items-center justify-center text-white font-bold`}>
                  {testimonial.avatar}
                </div>
                <div>
                  <h4 className="font-semibold text-gray-900">{testimonial.name}</h4>
                  <p className="text-sm text-gray-600">{testimonial.location}</p>
                </div>
              </div>

              <div className="flex items-center gap-1 mb-3">
                {[...Array(testimonial.rating)].map((_, i) => (
                  <FaStar key={i} className="text-yellow-400 text-sm" />
                ))}
              </div>

              <p className="text-gray-600 text-sm leading-relaxed mb-4">
                {testimonial.text.substring(0, 120)}...
              </p>

              <div className="pt-3 border-t border-gray-100">
                <div className="flex justify-between text-xs text-gray-500">
                  <span>{testimonial.vehicle}</span>
                  <span>{testimonial.deliveryTime}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default TestimonialsSection;
