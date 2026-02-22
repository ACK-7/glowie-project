import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  FaChevronDown, 
  FaSearch, 
  FaQuestionCircle,
  FaShip,
  FaDollarSign,
  FaFileAlt,
  FaWrench,
  FaMapMarkerAlt,
  FaShieldAlt,
  FaCalendarAlt,
  FaCheckCircle
} from 'react-icons/fa';

const FAQ = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [openItems, setOpenItems] = useState({});
  const [activeCategory, setActiveCategory] = useState(0);

  const faqCategories = [
    {
      category: 'General Shipping Information',
      icon: FaShip,
      questions: [
        {
          question: 'How does your car shipping service work?',
          answer: 'Our process is simple: (1) Get an instant quote on our website, (2) Book your shipment and provide vehicle details, (3) We arrange pickup from Japan, UK, or UAE, (4) Your vehicle is shipped via ocean freight, (5) We handle customs clearance and URA taxes in Uganda, (6) Your car is delivered to your preferred location in Kampala.'
        },
        {
          question: 'Which countries do you ship from?',
          answer: 'We primarily ship vehicles from Japan, the United Kingdom, and the United Arab Emirates (UAE). These are the main markets for quality used and new vehicles destined for Uganda.'
        },
        {
          question: 'How long does shipping take?',
          answer: 'Shipping times vary by origin: Japan (4-6 weeks), UK (6-8 weeks), UAE (3-4 weeks). These timeframes include ocean transit, customs clearance, and inland transport to Kampala. We provide real-time tracking throughout the journey.'
        },
        {
          question: 'What types of vehicles can you ship?',
          answer: 'We ship all types of vehicles including sedans, SUVs, trucks, vans, motorcycles, and even heavy machinery. Whether it\'s a compact car or a large commercial vehicle, we have the experience and resources to handle it.'
        },
        {
          question: 'Do you ship motorcycles and other vehicles?',
          answer: 'Yes! In addition to cars, we ship motorcycles, ATVs, buses, trucks, construction equipment, and agricultural machinery. Our shipping containers can accommodate various vehicle types and sizes.'
        }
      ]
    },
    {
      category: 'Pricing & Payment',
      icon: FaDollarSign,
      questions: [
        {
          question: 'How much does it cost to ship a car to Uganda?',
          answer: 'Shipping costs depend on vehicle size, origin country, and destination. On average, expect $1,200-$2,500 from Japan, $1,800-$3,500 from UK, and $1,000-$2,000 from UAE. Use our instant quote calculator for an accurate estimate based on your specific vehicle.'
        },
        {
          question: 'What\'s included in the shipping cost?',
          answer: 'Our shipping cost includes: ocean freight, marine insurance, documentation fees, customs clearance, URA tax processing, port handling charges, and inland transport to Kampala. The only additional cost is the actual URA taxes and duties, which we calculate transparently.'
        },
        {
          question: 'Are there any hidden fees?',
          answer: 'Absolutely not! We believe in complete transparency. All fees are outlined in your quote before booking. The only variable is URA taxes, which are government-mandated and calculated based on your vehicle\'s value, age, and engine size.'
        },
        {
          question: 'What payment methods do you accept?',
          answer: 'We accept bank transfers, mobile money (MTN, Airtel), Western Union, MoneyGram, and major credit/debit cards. For international payments, we also accept PayPal and wire transfers.'
        },
        {
          question: 'When do I need to pay?',
          answer: 'Payment is split: 50% deposit when booking to secure your shipment, and the remaining 50% before customs clearance when your vehicle arrives in Uganda. This protects both parties and ensures smooth processing.'
        },
        {
          question: 'Can I get a discount for multiple vehicles?',
          answer: 'Yes! We offer volume discounts for customers shipping multiple vehicles. Contact us directly to discuss bulk shipping rates and special packages for car dealers and businesses.'
        }
      ]
    },
    {
      category: 'Customs & Documentation',
      icon: FaFileAlt,
      questions: [
        {
          question: 'What documents do I need to ship my car?',
          answer: 'You need: (1) Vehicle registration certificate, (2) Bill of sale or invoice, (3) Export certificate from origin country, (4) Your passport copy, (5) Uganda Tax ID (TIN). We\'ll guide you through obtaining any missing documents.'
        },
        {
          question: 'Do you handle customs clearance?',
          answer: 'Yes, we handle 100% of customs clearance on your behalf. Our experienced team navigates URA requirements, submits all documentation, pays duties, and ensures your vehicle clears customs without delays.'
        },
        {
          question: 'How do URA taxes work?',
          answer: 'URA calculates taxes based on vehicle value (COMESA or invoice value, whichever is higher), age, and engine size. Taxes typically range from 25-35% of vehicle value. We provide accurate tax estimates before shipping and handle all payments.'
        },
        {
          question: 'What are the import duties for Uganda?',
          answer: 'Import duties include: Import Duty (25%), VAT (18%), Withholding Tax (6%), Environmental Levy, Infrastructure Levy, and Registration fees. Total typically ranges 60-80% of vehicle value. We provide detailed breakdowns upfront.'
        },
        {
          question: 'Do you handle all the paperwork?',
          answer: 'Yes! From export certificates in the origin country to customs declarations, tax payments, and vehicle registration in Uganda - we manage all documentation. You just need to provide the initial vehicle and identity documents.'
        }
      ]
    },
    {
      category: 'Vehicle Condition & Requirements',
      icon: FaWrench,
      questions: [
        {
          question: 'Can I ship a car that doesn\'t run?',
          answer: 'Yes, we can ship non-running vehicles, but there may be additional handling charges at the port for loading/unloading. The vehicle must still be able to roll freely (not seized). Please inform us during booking.'
        },
        {
          question: 'Do you inspect the vehicle before shipping?',
          answer: 'We conduct a thorough pre-shipment inspection documenting the vehicle\'s condition with photos and videos. This protects you and ensures any transit damage is properly documented for insurance claims.'
        },
        {
          question: 'Can I ship personal items in the car?',
          answer: 'You can ship personal items up to 50kg inside the vehicle, but they must be declared and may incur additional customs duties. Do not ship prohibited items (weapons, drugs, hazardous materials). Items should be secured inside the trunk or cabin.'
        },
        {
          question: 'What should I do to prepare my car for shipping?',
          answer: 'Before shipping: (1) Clean the vehicle thoroughly, (2) Remove or secure loose items, (3) Disable car alarm, (4) Ensure fuel tank is only 1/4 full, (5) Document existing damage with photos, (6) Remove toll tags and personal items from glove compartment.'
        }
      ]
    },
    {
      category: 'Tracking & Delivery',
      icon: FaMapMarkerAlt,
      questions: [
        {
          question: 'Can I track my shipment?',
          answer: 'Yes! You receive a unique tracking code after booking. Our advanced tracking system provides real-time updates: vehicle pickup, port departure, ocean transit, port arrival, customs clearance, and final delivery. Access tracking 24/7 via our website or mobile app.'
        },
        {
          question: 'How will I know when my car arrives?',
          answer: 'We send automatic notifications via SMS, email, and WhatsApp at each major milestone. You\'ll be notified when your vehicle departs, arrives at Ugandan port, clears customs, and is ready for delivery or pickup.'
        },
        {
          question: 'Where do I pick up my car in Uganda?',
          answer: 'You can pick up from our Kampala office or we can deliver to your preferred location. Our main depot is near Nakawa, with easy access from all parts of Kampala. We also arrange upcountry delivery for an additional fee.'
        },
        {
          question: 'Do you offer door-to-door delivery?',
          answer: 'Yes! We offer complete door-to-door service. We pick up your vehicle from the seller in Japan/UK/UAE and deliver it directly to your home or office in Uganda. This premium service handles everything from start to finish.'
        }
      ]
    },
    {
      category: 'Insurance & Safety',
      icon: FaShieldAlt,
      questions: [
        {
          question: 'Is my vehicle insured during shipping?',
          answer: 'Yes, all vehicles are covered by comprehensive marine insurance from pickup to delivery. Our insurance covers theft, damage, and total loss during ocean transit. Coverage value is based on the declared vehicle value.'
        },
        {
          question: 'What happens if my car is damaged?',
          answer: 'In the rare event of damage, document it immediately with photos. File a claim within 24 hours of delivery. Our insurance processes claims quickly, typically within 2-4 weeks. We assist with all claim documentation and follow-up.'
        },
        {
          question: 'How do you protect my vehicle during transit?',
          answer: 'We use enclosed containers for maximum protection, professional lashing to prevent movement, plastic covering for paint protection, and climate-controlled storage where possible. Vehicles are inspected before and after transit with photographic evidence.'
        }
      ]
    },
    {
      category: 'Booking & Cancellation',
      icon: FaCalendarAlt,
      questions: [
        {
          question: 'How do I book a shipment?',
          answer: 'Booking is easy: (1) Get an instant quote using our calculator, (2) Click "Book Now" and fill in vehicle details, (3) Upload required documents, (4) Make deposit payment, (5) Receive booking confirmation. The entire process takes less than 10 minutes.'
        },
        {
          question: 'Can I cancel my booking?',
          answer: 'Yes, cancellations are allowed before vehicle pickup. If cancelled 7+ days before pickup, 90% refund. If 3-6 days before pickup, 70% refund. If less than 3 days or after pickup, 50% refund. Refunds are processed within 14 business days.'
        },
        {
          question: 'What\'s your cancellation policy?',
          answer: 'Our cancellation policy is fair and transparent: Full refund if we cancel for any reason. Client cancellations: 90% refund if 7+ days notice, 70% refund if 3-6 days notice, 50% refund if less than 3 days notice. No refund after vehicle departs origin port.'
        },
        {
          question: 'How far in advance should I book?',
          answer: 'We recommend booking 2-3 weeks before your desired shipping date. This allows time for documentation, vessel scheduling, and pickup arrangements. However, we can accommodate rush bookings within 3-5 days for an expedite fee.'
        }
      ]
    },
    {
      category: 'After Delivery',
      icon: FaCheckCircle,
      questions: [
        {
          question: 'What happens after my car arrives in Uganda?',
          answer: 'After arrival: (1) We notify you immediately, (2) Process customs clearance (2-5 days), (3) Pay URA taxes on your behalf, (4) Obtain clearance documentation, (5) Arrange final delivery or pickup, (6) Hand over all documents including customs papers and registration forms.'
        },
        {
          question: 'Do you help with vehicle registration?',
          answer: 'Yes! We provide full registration assistance. We prepare all URA documents, guide you through the registration process at URA offices, and can even handle the full registration on your behalf for a small service fee. Most vehicles are registered within 1-2 weeks.'
        },
        {
          question: 'What if there\'s an issue with my car?',
          answer: 'Contact us immediately if any issues arise. We provide 30-day post-delivery support for documentation or customs-related matters. For mechanical issues, we can recommend trusted mechanics. If the issue is shipping-related damage, we initiate insurance claims immediately.'
        }
      ]
    }
  ];

  // Filter questions based on search
  const filteredCategories = faqCategories.map(category => ({
    ...category,
    questions: category.questions.filter(
      q =>
        q.question.toLowerCase().includes(searchTerm.toLowerCase()) ||
        q.answer.toLowerCase().includes(searchTerm.toLowerCase())
    )
  })).filter(category => category.questions.length > 0);

  const toggleItem = (categoryIndex, questionIndex) => {
    const key = `${categoryIndex}-${questionIndex}`;
    setOpenItems(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };

  const scrollToCategory = (index) => {
    setActiveCategory(index);
    const element = document.getElementById(`category-${index}`);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 ms-32">
      {/* Hero Section */}
      <section className="bg-gradient-to-br from-navy-900 via-blue-900 to-blue-800 text-white py-16 p">
        <div className="container-custom text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-white/10 backdrop-blur-md rounded-full mb-4">
            <FaQuestionCircle className="text-3xl text-gold-400" />
          </div>
          <h1 className="text-4xl md:text-5xl font-heading font-bold mb-3">
            Frequently Asked Questions
          </h1>
          <p className="text-lg text-blue-100 max-w-2xl mx-auto">
            Find answers to common questions about our car shipping services
          </p>
        </div>
      </section>

      {/* Search Section */}
      <section className="py-8 bg-white border-b sticky top-0 z-40">
        <div className="container-custom">
          <div className="max-w-2xl mx-auto relative">
            <FaSearch className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search for questions..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none"
            />
          </div>
          {searchTerm && (
            <p className="mt-3 text-gray-600 text-center text-sm">
              Found {filteredCategories.reduce((acc, cat) => acc + cat.questions.length, 0)} results
            </p>
          )}
        </div>
      </section>

      {/* Main Content - Two Column Layout */}
      <section className="py-12">
        <div className="container-custom">
          <div className="flex gap-8">
            {/* Left Sidebar - Categories */}
            <aside className="hidden lg:block w-80 flex-shrink-0">
              <div className="sticky top-32">
                <h3 className="text-lg font-bold text-gray-900 mb-4 px-4">Categories</h3>
                <nav className="space-y-1">
                  {faqCategories.map((category, index) => (
                    <button
                      key={index}
                      onClick={() => scrollToCategory(index)}
                      className={`w-full text-left px-4 py-3 rounded-lg transition-all duration-200 flex items-center gap-3 ${
                        activeCategory === index
                          ? 'bg-blue-600 text-white shadow-md'
                          : 'text-gray-700 hover:bg-gray-100'
                      }`}
                    >
                      <category.icon className="text-2xl" />
                      <span className="font-medium text-sm">{category.category}</span>
                    </button>
                  ))}
                </nav>
              </div>
            </aside>

            {/* Right Content - Questions */}
            <div className="flex-1 max-w-4xl">
              {filteredCategories.length === 0 ? (
                <div className="text-center py-12 bg-white rounded-xl">
                  <p className="text-xl text-gray-600 mb-4">No questions found matching "{searchTerm}"</p>
                  <button
                    onClick={() => setSearchTerm('')}
                    className="text-blue-600 hover:text-blue-700 font-semibold"
                  >
                    Clear search
                  </button>
                </div>
              ) : (
                filteredCategories.map((category, categoryIndex) => (
                  <div key={categoryIndex} id={`category-${categoryIndex}`} className="mb-12">
                    {/* Category Header */}
                    <div className="flex items-center gap-3 mb-6">
                      <category.icon className="text-3xl" />
                      <h2 className="text-2xl font-heading font-bold text-navy-900">
                        {category.category}
                      </h2>
                    </div>

                    {/* Questions */}
                    <div className="space-y-3">
                      {category.questions.map((item, questionIndex) => {
                        const key = `${categoryIndex}-${questionIndex}`;
                        const isOpen = openItems[key];

                        return (
                          <div
                            key={questionIndex}
                            className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-all duration-200"
                          >
                            <button
                              onClick={() => toggleItem(categoryIndex, questionIndex)}
                              className="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition"
                            >
                              <span className="font-semibold text-gray-800 pr-4">
                                {item.question}
                              </span>
                              <FaChevronDown
                                className={`text-blue-600 flex-shrink-0 transition-transform duration-300 ${
                                  isOpen ? 'rotate-180' : ''
                                }`}
                              />
                            </button>
                            
                            {isOpen && (
                              <div className="px-5 pb-4 pt-2 bg-gray-50 border-t border-gray-100">
                                <p className="text-gray-700 leading-relaxed">
                                  {item.answer}
                                </p>
                              </div>
                            )}
                          </div>
                        );
                      })}
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Contact CTA Section */}
      <section className="py-16 bg-gradient-to-br from-navy-900 to-blue-900 text-white">
        <div className="container-custom max-w-4xl text-center">
          <h2 className="text-3xl md:text-4xl font-heading font-bold mb-4">
            Still Have Questions?
          </h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Can't find the answer you're looking for? Our customer support team is here to help!
          </p>
          
          <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <Link
              to="/contact"
              className="btn-primary inline-block px-8 py-4"
            >
              Contact Support
            </Link>
            <Link
              to="/quote"
              className="btn-outline inline-block px-8 py-4"
            >
              Get Instant Quote
            </Link>
          </div>

          {/* Contact Methods */}
          <div className="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="text-center">
              <div className="text-3xl mb-2">📞</div>
              <h3 className="font-semibold mb-1">Call Us</h3>
              <p className="text-blue-200">+1 800 567 8934</p>
            </div>
            <div className="text-center">
              <div className="text-3xl mb-2">✉️</div>
              <h3 className="font-semibold mb-1">Email Us</h3>
              <p className="text-blue-200">support@shipwithglowie.com</p>
            </div>
            <div className="text-center">
              <div className="text-3xl mb-2">💬</div>
              <h3 className="font-semibold mb-1">Live Chat</h3>
              <p className="text-blue-200">Available 24/7</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default FAQ;
