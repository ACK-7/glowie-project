import React, { useState } from 'react';
import { 
  FaPhone, 
  FaEnvelope, 
  FaMapMarkerAlt, 
  FaClock,
  FaWhatsapp,
  FaFacebookF,
  FaInstagram,
  FaTwitter,
  FaPaperPlane
} from 'react-icons/fa';

const Contact = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: ''
  });

  const [formStatus, setFormStatus] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setFormStatus('sending');
    
    // Simulate form submission
    setTimeout(() => {
      setFormStatus('success');
      setFormData({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: ''
      });
      
      setTimeout(() => setFormStatus(''), 3000);
    }, 1500);
  };

  const contactInfo = [
    {
      icon: FaPhone,
      title: 'Call Us',
      details: ['+1 800 567 8934', '+256 123 456 789'],
      link: 'tel:+18005678934'
    },
    {
      icon: FaEnvelope,
      title: 'Email Us',
      details: ['support@shipwithglowie.com', 'quotes@shipwithglowie.com'],
      link: 'mailto:support@shipwithglowie.com'
    },
    {
      icon: FaMapMarkerAlt,
      title: 'Visit Us',
      details: ['M2 San Pablo St, Nakawa', 'Kampala, Uganda'],
      link: 'https://maps.google.com'
    },
    {
      icon: FaClock,
      title: 'Working Hours',
      details: ['Mon - Fri: 8:00 AM - 6:00 PM', 'Sat: 9:00 AM - 4:00 PM'],
      link: null
    }
  ];

  const officeLocations = [
    {
      city: 'Kampala Office',
      address: 'M2 San Pablo St, Nakawa',
      phone: '+256 123 456 789',
      email: 'kampala@shipwithglowie.com'
    },
    {
      city: 'Tokyo Office',
      address: 'Shibuya, Tokyo, Japan',
      phone: '+81 3 1234 5678',
      email: 'tokyo@shipwithglowie.com'
    },
    {
      city: 'Dubai Office',
      address: 'Business Bay, Dubai, UAE',
      phone: '+971 4 123 4567',
      email: 'dubai@shipwithglowie.com'
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className="bg-gradient-to-br from-navy-900 via-blue-900 to-blue-800 text-white py-16">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-heading font-bold mb-3">
            Get in Touch
          </h1>
          <p className="text-lg text-blue-100 max-w-2xl mx-auto">
            Have questions? We're here to help! Reach out to us through any of the channels below
          </p>
        </div>
      </section>

      {/* Contact Info Cards */}
      <section className="py-12 bg-white">
        <div className="container-custom">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {contactInfo.map((info, index) => (
              <div
                key={index}
                className="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-blue-500 hover:shadow-lg transition-all duration-300 group"
              >
                <div className="inline-flex items-center justify-center w-14 h-14 bg-blue-100 rounded-full mb-4 group-hover:bg-blue-600 transition-colors">
                  <info.icon className="text-2xl text-blue-600 group-hover:text-white transition-colors" />
                </div>
                <h3 className="text-lg font-bold text-navy-900 mb-3">{info.title}</h3>
                {info.details.map((detail, idx) => (
                  <p key={idx} className="text-gray-600 mb-1">
                    {info.link && idx === 0 ? (
                      <a href={info.link} className="hover:text-blue-600 transition">
                        {detail}
                      </a>
                    ) : (
                      detail
                    )}
                  </p>
                ))}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Contact Form & Map Section */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="grid lg:grid-cols-2 gap-12">
            {/* Contact Form */}
            <div className="bg-white rounded-2xl shadow-xl p-8">
              <h2 className="text-3xl font-heading font-bold text-navy-900 mb-2">
                Send Us a Message
              </h2>
              <p className="text-gray-600 mb-8">
                Fill out the form below and we'll get back to you within 24 hours
              </p>

              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                      Your Name *
                    </label>
                    <input
                      type="text"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      required
                      className="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition"
                      placeholder="John Doe"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                      Email Address *
                    </label>
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      required
                      className="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition"
                      placeholder="john@example.com"
                    />
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                      Phone Number
                    </label>
                    <input
                      type="tel"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      className="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition"
                      placeholder="+256 123 456 789"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                      Subject *
                    </label>
                    <select
                      name="subject"
                      value={formData.subject}
                      onChange={handleChange}
                      required
                      className="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition"
                    >
                      <option value="">Select a subject</option>
                      <option value="shipping-quote">Shipping Quote</option>
                      <option value="tracking">Shipment Tracking</option>
                      <option value="customs">Customs & Documentation</option>
                      <option value="general">General Inquiry</option>
                      <option value="complaint">Complaint</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-semibold text-gray-700 mb-2">
                    Message *
                  </label>
                  <textarea
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    required
                    rows="6"
                    className="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none transition resize-none"
                    placeholder="Tell us how we can help you..."
                  ></textarea>
                </div>

                <button
                  type="submit"
                  disabled={formStatus === 'sending'}
                  className="w-full btn-primary py-4 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {formStatus === 'sending' ? (
                    <>
                      <span className="animate-spin">⏳</span>
                      Sending...
                    </>
                  ) : (
                    <>
                      <FaPaperPlane />
                      Send Message
                    </>
                  )}
                </button>

                {formStatus === 'success' && (
                  <div className="bg-green-50 border-2 border-green-500 text-green-700 px-4 py-3 rounded-lg">
                    ✅ Thank you! Your message has been sent successfully. We'll get back to you soon.
                  </div>
                )}
              </form>
            </div>

            {/* Map & Office Info */}
            <div className="space-y-6">
              {/* Map Placeholder */}
              <div className="bg-gray-200 rounded-2xl overflow-hidden h-80">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15959.028226562802!2d32.5825197!3d0.3475964!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177dbb8679c8f7e5%3A0x6b8d1b0b1b0b1b0b!2sNakawa%2C%20Kampala%2C%20Uganda!5e0!3m2!1sen!2sus!4v1234567890123"
                  width="100%"
                  height="100%"
                  style={{ border: 0 }}
                  allowFullScreen=""
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  title="Office Location"
                ></iframe>
              </div>

              {/* Office Locations */}
              <div className="bg-white rounded-2xl shadow-xl p-8">
                <h3 className="text-2xl font-heading font-bold text-navy-900 mb-6">
                  Our Offices
                </h3>

                <div className="space-y-6">
                  {officeLocations.map((office, index) => (
                    <div key={index} className="pb-6 border-b border-gray-200 last:border-b-0 last:pb-0">
                      <h4 className="font-bold text-lg text-navy-900 mb-3">{office.city}</h4>
                      <div className="space-y-2 text-gray-600">
                        <p className="flex items-start gap-2">
                          <FaMapMarkerAlt className="text-blue-600 mt-1 flex-shrink-0" />
                          <span>{office.address}</span>
                        </p>
                        <p className="flex items-center gap-2">
                          <FaPhone className="text-blue-600 flex-shrink-0" />
                          <a href={`tel:${office.phone}`} className="hover:text-blue-600 transition">
                            {office.phone}
                          </a>
                        </p>
                        <p className="flex items-center gap-2">
                          <FaEnvelope className="text-blue-600 flex-shrink-0" />
                          <a href={`mailto:${office.email}`} className="hover:text-blue-600 transition">
                            {office.email}
                          </a>
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Social Media */}
              <div className="bg-gradient-to-br from-navy-900 to-blue-900 rounded-2xl p-8 text-white text-center">
                <h3 className="text-xl font-bold mb-3">Connect With Us</h3>
                <p className="text-blue-100 mb-6">Follow us on social media for updates and offers</p>
                
                <div className="flex justify-center gap-4">
                  <a
                    href="#"
                    className="w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-white/20 transition"
                  >
                    <FaWhatsapp className="text-xl" />
                  </a>
                  <a
                    href="#"
                    className="w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-white/20 transition"
                  >
                    <FaFacebookF className="text-xl" />
                  </a>
                  <a
                    href="#"
                    className="w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-white/20 transition"
                  >
                    <FaInstagram className="text-xl" />
                  </a>
                  <a
                    href="#"
                    className="w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center hover:bg-white/20 transition"
                  >
                    <FaTwitter className="text-xl" />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ Quick Links */}
      <section className="py-16 bg-blue-50">
        <div className="container-custom text-center">
          <h2 className="text-3xl font-heading font-bold text-navy-900 mb-4">
            Quick Answers
          </h2>
          <p className="text-gray-600 mb-8 max-w-2xl mx-auto">
            Looking for instant answers? Check out our FAQ page for commonly asked questions
          </p>
          <a href="/faq" className="btn-primary inline-block px-8 py-4">
            Visit FAQ Page
          </a>
        </div>
      </section>
    </div>
  );
};

export default Contact;
