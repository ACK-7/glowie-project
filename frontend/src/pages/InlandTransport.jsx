import React from 'react';
import { Link } from 'react-router-dom';
import { FaTruck, FaShieldAlt, FaMapMarkedAlt, FaClock, FaCheckCircle, FaRoute } from 'react-icons/fa';

const InlandTransport = () => {
  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Inland Transport</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Expert handling of the crucial final leg: Mombasa/Dar-es-Salaam to Kampala
          </p>
        </div>
      </section>

      {/* The Crucial Final Leg */}
      <section className="section-padding">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">The Crucial Final Leg</h2>
            <p className="text-gray-600 text-lg max-w-3xl mx-auto">
              The 1,000+ km journey from the coast to Kampala is the most critical part of your car's voyage
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div className="premium-card p-8">
              <div className="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 text-3xl mb-6">
                🎯
              </div>
              <h3 className="text-2xl font-bold text-navy-900 mb-4">Why This Leg Matters Most</h3>
              <p className="text-gray-600 leading-relaxed">
                While ocean freight gets your car to Africa, the overland journey through Kenya or Tanzania presents 
                unique challenges: multiple border crossings, customs transit bonds, security concerns, and road conditions. 
                This is where our <strong>40+ years of local expertise</strong> becomes invaluable.
              </p>
            </div>

            <div className="premium-card p-8">
              <div className="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center text-green-600 text-3xl mb-6">
                ⭐
              </div>
              <h3 className="text-2xl font-bold text-navy-900 mb-4">What Sets Us Apart</h3>
              <ul className="space-y-3 text-gray-600">
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                  <span>Deep relationships with port authorities in Mombasa & Dar-es-Salaam</span>
                </li>
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                  <span>Licensed customs agents at all border posts (Malaba, Mutukula)</span>
                </li>
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                  <span>Direct partnerships with URA-approved bonds in Kampala</span>
                </li>
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                  <span>Real-time GPS tracking from port to final destination</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* Transport Methods */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Our Inland Transport Methods</h2>
            <p className="text-gray-600 text-lg">
              Professional, safe, and secure vehicle transportation
            </p>
          </div>

          <div className="space-y-8">
            {/* Dedicated Car Carriers */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="flex items-start gap-6">
                <div className="flex-shrink-0 w-20 h-20 bg-gradient-primary rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg">
                  <FaTruck />
                </div>
                <div className="flex-1">
                  <h3 className="text-2xl font-bold text-navy-900 mb-4">Dedicated Car Carriers</h3>
                  <p className="text-gray-600 mb-6 leading-relaxed">
                    Your vehicle is <strong>loaded onto multi-car trailers</strong> – it is <strong>NOT driven</strong> by anyone. 
                    This eliminates mileage addition, mechanical wear, and the risk of accidents during transit. Our professional 
                    carriers can transport 6-10 vehicles simultaneously, ensuring your car arrives in the exact condition it left the port.
                  </p>
                  
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="bg-blue-50 rounded-lg p-4">
                      <p className="text-sm text-gray-600 mb-1">Carrier Type</p>
                      <p className="font-bold text-navy-900">Multi-Car Trailers</p>
                    </div>
                    <div className="bg-blue-50 rounded-lg p-4">
                      <p className="text-sm text-gray-600 mb-1">Capacity</p>
                      <p className="font-bold text-navy-900">6-10 Vehicles</p>
                    </div>
                    <div className="bg-blue-50 rounded-lg p-4">
                      <p className="text-sm text-gray-600 mb-1">Mileage Added</p>
                      <p className="font-bold text-green-600">Zero</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Secured Transit Routes */}
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="flex items-start gap-6">
                <div className="flex-shrink-0 w-20 h-20 bg-gradient-secondary rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg">
                  <FaRoute />
                </div>
                <div className="flex-1">
                  <h3 className="text-2xl font-bold text-navy-900 mb-4">Secured Transit Routes</h3>
                  <p className="text-gray-600 mb-6 leading-relaxed">
                    We use established, secure corridors with proven reliability and minimal delays.
                  </p>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="border-l-4 border-blue-500 bg-blue-50 p-6 rounded-r-lg">
                      <div className="flex items-center gap-3 mb-3">
                        <FaMapMarkedAlt className="text-blue-600 text-2xl" />
                        <h4 className="font-bold text-navy-900 text-lg">Northern Corridor</h4>
                      </div>
                      <p className="text-gray-700 mb-2">
                        <strong>Route:</strong> Mombasa → Nairobi → Malaba Border → Kampala
                      </p>
                      <p className="text-gray-600 text-sm">
                        Most commonly used route. Well-maintained highways with multiple checkpoints 
                        and established customs procedures at Malaba.
                      </p>
                    </div>

                    <div className="border-l-4 border-purple-500 bg-purple-50 p-6 rounded-r-lg">
                      <div className="flex items-center gap-3 mb-3">
                        <FaMapMarkedAlt className="text-purple-600 text-2xl" />
                        <h4 className="font-bold text-navy-900 text-lg">Southern Corridor</h4>
                      </div>
                      <p className="text-gray-700 mb-2">
                        <strong>Route:</strong> Dar-es-Salaam → Mutukula Border → Kampala
                      </p>
                      <p className="text-gray-600 text-sm">
                        Alternative route via Tanzania. Preferred for shipments arriving at Dar-es-Salaam port. 
                        Shorter distance but can have seasonal delays.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Security & Insurance */}
      <section className="section-padding">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Security & Insurance</h2>
            <p className="text-gray-600 text-lg">
              Your peace of mind is our priority
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div className="premium-card p-8 border-l-4 border-orange-500">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center">
                  <FaShieldAlt className="text-orange-600 text-2xl" />
                </div>
                <h3 className="text-2xl font-bold text-navy-900">Transit Bond</h3>
              </div>
              <p className="text-gray-600 leading-relaxed mb-4">
                Every vehicle travels under a <strong>government-backed transit bond</strong> secured by ShipWithGlowie. 
                This legal document ensures:
              </p>
              <ul className="space-y-2 text-gray-700">
                <li className="flex items-start gap-2">
                  <span className="text-orange-500 mt-1">•</span>
                  <span>The vehicle can legally cross international borders without immediate duty payment</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-orange-500 mt-1">•</span>
                  <span>Protection against seizure by customs authorities en route</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-orange-500 mt-1">•</span>
                  <span>Accountability trail from port to final URA bond in Uganda</span>
                </li>
              </ul>
              <div className="mt-6 p-4 bg-orange-50 rounded-lg">
                <p className="text-sm text-gray-700">
                  <strong>Important:</strong> The bond is released only when the vehicle reaches our URA-approved 
                  bond in Kampala and is properly logged.
                </p>
              </div>
            </div>

            <div className="premium-card p-8 border-l-4 border-green-500">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center">
                  <FaShieldAlt className="text-green-600 text-2xl" />
                </div>
                <h3 className="text-2xl font-bold text-navy-900">Transit Insurance</h3>
              </div>
              <p className="text-gray-600 leading-relaxed mb-4">
                Separate <strong>Inland Transit Insurance</strong> covers your vehicle from the moment it's discharged 
                from the ship until delivery at our Kampala bond. Coverage includes:
              </p>
              <ul className="space-y-2 text-gray-700">
                <li className="flex items-start gap-2">
                  <span className="text-green-500 mt-1">•</span>
                  <span>Theft or hijacking during transit</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-green-500 mt-1">•</span>
                  <span>Accidental damage during loading/unloading</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-green-500 mt-1">•</span>
                  <span>Road accidents involving the carrier</span>
                </li>
                <li className="flex items-start gap-2">
                  <span className="text-green-500 mt-1">•</span>
                  <span>Natural disasters or unforeseen events</span>
                </li>
              </ul>
              <div className="mt-6 p-4 bg-green-50 rounded-lg">
                <p className="text-sm text-gray-700">
                  <strong>Coverage Amount:</strong> Full declared value of the vehicle as per your shipping contract.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Destination Options */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Destination Options in Uganda</h2>
            <p className="text-gray-600 text-lg">
              Flexible delivery to suit your needs
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 text-3xl mb-6">
                📍
              </div>
              <h3 className="text-xl font-bold text-navy-900 mb-4">Primary Delivery Locations</h3>
              <p className="text-gray-600 mb-6">
                We deliver to URA-approved customs bonds where final clearance takes place:
              </p>
              <ul className="space-y-3">
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-blue-500 mt-1 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Nakawa Bond, Kampala</p>
                    <p className="text-sm text-gray-600">Main URA clearance center</p>
                  </div>
                </li>
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-blue-500 mt-1 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Entebbe Bond</p>
                    <p className="text-sm text-gray-600">Alternative for Entebbe-area customers</p>
                  </div>
                </li>
                <li className="flex items-start gap-3">
                  <FaCheckCircle className="text-blue-500 mt-1 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Other Licensed Bonds</p>
                    <p className="text-sm text-gray-600">Based on customer preference</p>
                  </div>
                </li>
              </ul>
            </div>

            <div className="bg-white rounded-xl shadow-md p-8">
              <div className="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center text-purple-600 text-3xl mb-6">
                🚗
              </div>
              <h3 className="text-xl font-bold text-navy-900 mb-4">Customer Pickup Process</h3>
              <p className="text-gray-600 mb-6">
                Once your vehicle arrives at the bond and URA clearance is complete:
              </p>
              <div className="space-y-4">
                <div className="border-l-4 border-purple-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 1: Notification</p>
                  <p className="text-sm text-gray-600">We notify you immediately when the vehicle arrives</p>
                </div>
                <div className="border-l-4 border-purple-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 2: URA Clearance</p>
                  <p className="text-sm text-gray-600">Pay URA taxes and duties (typically 2-7 days)</p>
                </div>
                <div className="border-l-4 border-purple-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 3: Vehicle Release</p>
                  <p className="text-sm text-gray-600">Pick up your car from the bond with all documents</p>
                </div>
              </div>
              <div className="mt-6 p-4 bg-purple-50 rounded-lg">
                <p className="text-sm text-gray-700">
                  <strong>Additional Service:</strong> We can arrange delivery to other Ugandan cities for an extra fee.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Transit Time */}
      <section className="section-padding">
        <div className="container-custom max-w-4xl mx-auto">
          <div className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white rounded-2xl p-12 text-center">
            <FaClock className="text-6xl mx-auto mb-6 opacity-80" />
            <h2 className="text-3xl md:text-4xl font-bold mb-6">Transit Time Estimates</h2>
            <div className="max-w-2xl mx-auto">
              <div className="bg-white/10 rounded-xl p-6 mb-6">
                <p className="text-5xl font-bold mb-2">5-8</p>
                <p className="text-xl text-blue-100">Working Days</p>
              </div>
              <p className="text-lg text-blue-100 leading-relaxed">
                From port clearance in Mombasa or Dar-es-Salaam to delivery at our URA-approved bond in Kampala. 
                This timeline accounts for border processing, customs documentation, and road transit.
              </p>
            </div>

            <div className="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white/10 rounded-lg p-4">
                <p className="text-3xl mb-2">1-2</p>
                <p className="text-sm text-blue-100">Days: Port Clearance</p>
              </div>
              <div className="bg-white/10 rounded-lg p-4">
                <p className="text-3xl mb-2">2-4</p>
                <p className="text-sm text-blue-100">Days: Road Transit</p>
              </div>
              <div className="bg-white/10 rounded-lg p-4">
                <p className="text-3xl mb-2">1-2</p>
                <p className="text-sm text-blue-100">Days: Border Processing</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom max-w-3xl mx-auto text-center">
          <h2 className="text-3xl font-bold mb-4">Ready to Ship Your Car?</h2>
          <p className="text-gray-600 mb-8 text-lg">
            Let our 40+ years of inland transport expertise work for you
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/services/request-quote" className="btn-primary px-12">
              Get Free Quote
            </Link>
            <Link to="/contact" className="btn-outline px-12">
              Contact Us
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default InlandTransport;
