import React from 'react';
import { 
  FaRocket, 
  FaShieldAlt, 
  FaMapMarkerAlt, 
  FaClock,
  FaDollarSign,
  FaHeadset,
  FaCheckCircle,
  FaGlobe
} from 'react-icons/fa';

const WhyChooseUs = () => {
  const features = [
    {
      icon: FaRocket,
      title: 'Lightning Fast Process',
      description: 'Get instant quotes in under 60 seconds. Our AI-powered system streamlines booking, documentation, and tracking for maximum efficiency.',
      gradient: 'from-blue-500 to-blue-600',
      iconBg: 'bg-blue-100',
      iconColor: 'text-blue-600',
      stats: '< 60 sec quotes'
    },
    {
      icon: FaShieldAlt,
      title: 'Complete Protection',
      description: 'Full insurance coverage, secure handling, and transparent tracking. Your vehicle is protected every step of the journey.',
      gradient: 'from-green-500 to-green-600',
      iconBg: 'bg-green-100',
      iconColor: 'text-green-600',
      stats: '100% insured'
    },
    {
      icon: FaMapMarkerAlt,
      title: 'Local Expertise',
      description: 'Deep knowledge of Ugandan customs, URA procedures, and local regulations. We handle all paperwork and clearance processes.',
      gradient: 'from-purple-500 to-purple-600',
      iconBg: 'bg-purple-100',
      iconColor: 'text-purple-600',
      stats: '15+ years local'
    },
    {
      icon: FaClock,
      title: 'Reliable Timing',
      description: 'Consistent 21-30 day delivery times with real-time tracking. We keep you informed throughout the entire shipping process.',
      gradient: 'from-orange-500 to-orange-600',
      iconBg: 'bg-orange-100',
      iconColor: 'text-orange-600',
      stats: '98% on-time'
    },
    {
      icon: FaDollarSign,
      title: 'Transparent Pricing',
      description: 'No hidden fees, no surprises. All costs including shipping, insurance, and customs duties are clearly outlined upfront.',
      gradient: 'from-yellow-500 to-yellow-600',
      iconBg: 'bg-yellow-100',
      iconColor: 'text-yellow-600',
      stats: 'No hidden fees'
    },
    {
      icon: FaHeadset,
      title: '24/7 Support',
      description: 'Round-the-clock customer support in English and local languages. Get help whenever you need it, wherever you are.',
      gradient: 'from-pink-500 to-pink-600',
      iconBg: 'bg-pink-100',
      iconColor: 'text-pink-600',
      stats: '24/7 available'
    }
  ];

  const achievements = [
    {
      icon: FaCheckCircle,
      number: '5,000+',
      label: 'Vehicles Delivered',
      color: 'text-blue-600'
    },
    {
      icon: FaGlobe,
      number: '3',
      label: 'Countries Covered',
      color: 'text-green-600'
    },
    {
      icon: FaShieldAlt,
      number: '98%',
      label: 'Customer Satisfaction',
      color: 'text-purple-600'
    },
    {
      icon: FaClock,
      number: '21',
      label: 'Average Days Delivery',
      color: 'text-orange-600'
    }
  ];

  return (
    <section className="section-padding bg-white">
      <div className="container-custom">
        {/* Section Header */}
        <div className="text-center mb-16">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold mb-4">
            <FaCheckCircle className="text-blue-600" />
            WHY CHOOSE US
          </div>
          <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Why <span className="gradient-text">ShipWithGlowie</span> Stands Out
          </h2>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            We combine cutting-edge technology with deep local expertise to deliver 
            an unmatched vehicle shipping experience
          </p>
        </div>

        {/* Features Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
          {features.map((feature, index) => (
            <div key={index} className="group">
              <div className="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-100 h-full">
                <div className="flex items-start gap-4 mb-6">
                  <div className={`w-16 h-16 ${feature.iconBg} rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300`}>
                    <feature.icon className={`text-2xl ${feature.iconColor}`} />
                  </div>
                  <div className="flex-1">
                    <h3 className="text-xl font-bold text-gray-900 mb-2">
                      {feature.title}
                    </h3>
                    <div className={`inline-block px-3 py-1 bg-gradient-to-r ${feature.gradient} text-white text-xs font-semibold rounded-full`}>
                      {feature.stats}
                    </div>
                  </div>
                </div>
                <p className="text-gray-600 leading-relaxed">
                  {feature.description}
                </p>
                <div className={`h-1 bg-gradient-to-r ${feature.gradient} transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 mt-6 rounded-full`}></div>
              </div>
            </div>
          ))}
        </div>

        {/* Achievements Section */}
        <div className="bg-gradient-to-r from-gray-50 to-blue-50 rounded-3xl p-8 md:p-12">
          <div className="text-center mb-8">
            <h3 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
              Trusted by Thousands
            </h3>
            <p className="text-gray-600 text-lg">
              Our track record speaks for itself
            </p>
          </div>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {achievements.map((achievement, index) => (
              <div key={index} className="text-center">
                <div className={`w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg`}>
                  <achievement.icon className={`text-2xl ${achievement.color}`} />
                </div>
                <div className={`text-3xl md:text-4xl font-bold ${achievement.color} mb-2`}>
                  {achievement.number}
                </div>
                <div className="text-gray-600 font-medium">
                  {achievement.label}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};

export default WhyChooseUs;
