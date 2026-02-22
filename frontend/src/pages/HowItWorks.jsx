import React from 'react';
import { Link } from 'react-router-dom';
import { FaFileAlt, FaShip, FaTruck, FaMoneyCheckAlt, FaCheckCircle } from 'react-icons/fa';

const HowItWorks = () => {
  const stages = [
    {
      stage: 'I',
      title: 'Pre-Shipment',
      subtitle: 'Inspection & Documentation',
      icon: <FaFileAlt />,
      role: 'ShipWithGlowie Handles',
      description: 'We arrange the mandatory JEVIC or QISJ pre-export roadworthiness inspection required by the Uganda National Bureau of Standards (UNBS). This ensures your vehicle meets all safety and environmental standards before shipping.',
      details: [
        'JEVIC/QISJ inspection booking',
        'UNBS compliance verification',
        'Certificate of Roadworthiness issuance',
        'Export documentation preparation'
      ],
      color: 'blue'
    },
    {
      stage: 'II',
      title: 'Sea Freight',
      subtitle: 'Vessel Transit',
      icon: <FaShip />,
      role: 'ShipWithGlowie Handles',
      description: 'We book the shipping space and provide you with the Bill of Lading (B/L) and real-time tracking link. Your car ships to the nearest port - Mombasa, Kenya, or Dar-es-Salaam, Tanzania.',
      details: [
        'Vessel space booking (RoRo or Container)',
        'Bill of Lading (B/L) issuance',
        'Real-time tracking link provision',
        'Transit to Mombasa or Dar-es-Salaam port'
      ],
      color: 'indigo'
    },
    {
      stage: 'III',
      title: 'Transit Clearance',
      subtitle: 'Port & Border Movement',
      icon: <FaTruck />,
      role: 'ShipWithGlowie Handles',
      description: 'Our agents clear your car from the port and arrange secure inland transport via car carrier to a URA-approved bond/terminal in Kampala. We handle all border procedures and documentation.',
      details: [
        'Port clearance at Mombasa/Dar-es-Salaam',
        'Border crossing documentation',
        'Inland transport to Kampala',
        'Delivery to URA-approved bond/terminal'
      ],
      color: 'purple'
    },
    {
      stage: 'IV',
      title: 'Final Clearance',
      subtitle: 'URA Tax & Registration',
      icon: <FaMoneyCheckAlt />,
      role: 'Customer Action Required',
      description: 'You (or your chosen clearing agent) use your Tax Identification Number (TIN) to pay the final Uganda Revenue Authority (URA) taxes and duties before the vehicle is released for registration.',
      details: [
        'URA tax assessment',
        'Payment of Import Duty, VAT, Withholding Tax',
        'Environmental and Infrastructure levies',
        'Vehicle release for registration'
      ],
      color: 'green',
      customerAction: true
    }
  ];

  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">How It Works</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            From inspection to delivery - a transparent, step-by-step process for shipping your car to Uganda.
          </p>
        </div>
      </section>

      {/* Process Overview */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="max-w-5xl mx-auto">
            {stages.map((stage, index) => (
              <div key={index} className="mb-16 last:mb-0">
                <div className={`premium-card border-l-4 ${
                  stage.color === 'blue' ? 'border-blue-600' :
                  stage.color === 'indigo' ? 'border-indigo-600' :
                  stage.color === 'purple' ? 'border-purple-600' :
                  'border-green-600'
                }`}>
                  <div className="p-8">
                    {/* Stage Header */}
                    <div className="flex items-start gap-6 mb-6">
                      <div className={`flex-shrink-0 w-20 h-20 rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg ${
                        stage.color === 'blue' ? 'bg-gradient-to-br from-blue-500 to-blue-700' :
                        stage.color === 'indigo' ? 'bg-gradient-to-br from-indigo-500 to-indigo-700' :
                        stage.color === 'purple' ? 'bg-gradient-to-br from-purple-500 to-purple-700' :
                        'bg-gradient-to-br from-green-500 to-green-700'
                      }`}>
                        {stage.icon}
                      </div>
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <span className={`text-sm font-bold px-3 py-1 rounded-full ${
                            stage.color === 'blue' ? 'bg-blue-100 text-blue-700' :
                            stage.color === 'indigo' ? 'bg-indigo-100 text-indigo-700' :
                            stage.color === 'purple' ? 'bg-purple-100 text-purple-700' :
                            'bg-green-100 text-green-700'
                          }`}>
                            Stage {stage.stage}
                          </span>
                          <span className={`text-sm font-medium px-3 py-1 rounded-full ${
                            stage.customerAction 
                              ? 'bg-orange-100 text-orange-700' 
                              : 'bg-gray-100 text-gray-700'
                          }`}>
                            {stage.role}
                          </span>
                        </div>
                        <h3 className="text-2xl md:text-3xl font-bold text-navy-900 mb-2">
                          {stage.title}
                        </h3>
                        <p className={`text-lg font-medium ${
                          stage.color === 'blue' ? 'text-blue-600' :
                          stage.color === 'indigo' ? 'text-indigo-600' :
                          stage.color === 'purple' ? 'text-purple-600' :
                          'text-green-600'
                        }`}>
                          {stage.subtitle}
                        </p>
                      </div>
                    </div>

                    {/* Description */}
                    <p className="text-gray-600 text-lg leading-relaxed mb-6">
                      {stage.description}
                    </p>

                    {/* Details Checklist */}
                    <div className="bg-gray-50 rounded-xl p-6">
                      <h4 className="font-bold text-navy-900 mb-4">Key Activities:</h4>
                      <ul className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        {stage.details.map((detail, idx) => (
                          <li key={idx} className="flex items-start gap-3">
                            <FaCheckCircle className={`mt-1 flex-shrink-0 ${
                              stage.color === 'blue' ? 'text-blue-500' :
                              stage.color === 'indigo' ? 'text-indigo-500' :
                              stage.color === 'purple' ? 'text-purple-500' :
                              'text-green-500'
                            }`} />
                            <span className="text-gray-700">{detail}</span>
                          </li>
                        ))}
                      </ul>
                    </div>

                    {/* Customer Action Alert */}
                    {stage.customerAction && (
                      <div className="mt-6 bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div className="flex items-start gap-3">
                          <span className="text-2xl">⚠️</span>
                          <div>
                            <h5 className="font-bold text-orange-900 mb-1">Your Action Required</h5>
                            <p className="text-orange-800 text-sm">
                              This stage requires your personal Tax Identification Number (TIN) and payment. 
                              You can handle this yourself or appoint us as your clearing agent.
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* Connection Line */}
                {index < stages.length - 1 && (
                  <div className="flex justify-center my-8">
                    <div className="w-1 h-12 bg-gradient-to-b from-blue-400 to-blue-200"></div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Timeline Summary */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl md:text-4xl font-bold text-center mb-12">Estimated Timeline</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div className="bg-white rounded-xl p-6 text-center shadow-md">
                <div className="text-3xl font-bold text-blue-600 mb-2">3-5 days</div>
                <div className="text-sm text-gray-600">Pre-Shipment Inspection</div>
              </div>
              <div className="bg-white rounded-xl p-6 text-center shadow-md">
                <div className="text-3xl font-bold text-indigo-600 mb-2">20-60 days</div>
                <div className="text-sm text-gray-600">Ocean Transit</div>
              </div>
              <div className="bg-white rounded-xl p-6 text-center shadow-md">
                <div className="text-3xl font-bold text-purple-600 mb-2">3-5 days</div>
                <div className="text-sm text-gray-600">Port to Kampala</div>
              </div>
              <div className="bg-white rounded-xl p-6 text-center shadow-md">
                <div className="text-3xl font-bold text-green-600 mb-2">2-7 days</div>
                <div className="text-sm text-gray-600">URA Clearance</div>
              </div>
            </div>

            <div className="mt-8 text-center">
              <p className="text-gray-600 text-lg">
                <strong>Total Average Time:</strong> 28-77 days depending on route
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Documents Required */}
      <section className="section-padding">
        <div className="container-custom max-w-4xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-bold text-center mb-12">Documents You'll Need</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-white rounded-xl p-6 shadow-md border border-gray-200">
              <h3 className="font-bold text-navy-900 mb-4 flex items-center gap-2">
                <span className="text-blue-600">📄</span>
                Pre-Shipment Documents
              </h3>
              <ul className="space-y-2 text-gray-700">
                <li>• Original vehicle title/logbook</li>
                <li>• Purchase invoice/receipt</li>
                <li>• Valid passport copy</li>
                <li>• UNBS inspection certificate</li>
              </ul>
            </div>

            <div className="bg-white rounded-xl p-6 shadow-md border border-gray-200">
              <h3 className="font-bold text-navy-900 mb-4 flex items-center gap-2">
                <span className="text-green-600">📋</span>
                Clearance Documents
              </h3>
              <ul className="space-y-2 text-gray-700">
                <li>• Bill of Lading (B/L)</li>
                <li>• Tax Identification Number (TIN)</li>
                <li>• Import permit (if applicable)</li>
                <li>• Insurance certificate</li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section-padding bg-blue-900 text-white">
        <div className="container-custom text-center">
          <h2 className="text-3xl md:text-4xl font-bold mb-6">Ready to Get Started?</h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Let us handle the complex process while you track your car's journey every step of the way.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/services/request-quote" className="btn-secondary inline-block px-12">
              Get Free Quote Now
            </Link>
            <Link to="/faq" className="btn-outline inline-block px-12 bg-white text-blue-900 hover:bg-gray-100">
              View FAQ
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HowItWorks;
