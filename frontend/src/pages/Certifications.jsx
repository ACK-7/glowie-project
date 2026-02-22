import React from 'react';
import { FaCertificate, FaShieldAlt, FaFileAlt, FaCheckCircle } from 'react-icons/fa';

const Certifications = () => {
  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Certifications & Licensing</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Fully licensed, insured, and compliant with all Ugandan import regulations.
          </p>
        </div>
      </section>

      {/* Main Certifications */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* URA Approval */}
            <div className="premium-card">
              <div className="p-8">
                <div className="w-16 h-16 bg-gradient-primary rounded-2xl flex items-center justify-center text-white text-2xl mb-6 shadow-glow">
                  <FaCertificate />
                </div>
                <h3 className="text-2xl font-bold text-navy-900 mb-4">URA-Approved Customs Agent</h3>
                <p className="text-gray-600 mb-6">
                  Licensed by the Uganda Revenue Authority as an approved customs clearing and forwarding agent. 
                  License #CCA/2024/0428
                </p>
                <div className="space-y-2">
                  <div className="flex items-start gap-3">
                    <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                    <span className="text-gray-700">Authorized to clear vehicles at all Ugandan ports</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                    <span className="text-gray-700">Direct liaison with URA customs officials</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                    <span className="text-gray-700">Bonded warehouse facilities</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Freight Forwarding */}
            <div className="premium-card">
              <div className="p-8">
                <div className="w-16 h-16 bg-gradient-secondary rounded-2xl flex items-center justify-center text-white text-2xl mb-6 shadow-glow-red">
                  <FaFileAlt />
                </div>
                <h3 className="text-2xl font-bold text-navy-900 mb-4">International Freight Forwarder</h3>
                <p className="text-gray-600 mb-6">
                  Member of the Uganda Freight Forwarders Association (UFFA) and FIATA-accredited. 
                  Membership #UFFA-428-2024
                </p>
                <div className="space-y-2">
                  <div className="flex items-start gap-3">
                    <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                    <span className="text-gray-700">Global shipping network access</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                    <span className="text-gray-700">Preferential carrier rates</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <FaCheckCircle className="text-green-500 mt-1 flex-shrink-0" />
                    <span className="text-gray-700">Industry best practices compliance</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Insurance Coverage */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto">
            <div className="text-center mb-12">
              <div className="w-16 h-16 bg-gradient-accent rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-6 shadow-glow-gold">
                <FaShieldAlt />
              </div>
              <h2 className="text-3xl md:text-4xl font-bold mb-4">Comprehensive Insurance Coverage</h2>
              <p className="text-gray-600 text-lg">
                Your vehicle is protected every step of the journey.
              </p>
            </div>

            <div className="bg-white rounded-2xl p-8 shadow-lg">
              <h3 className="text-2xl font-bold text-navy-900 mb-6">Coverage Details</h3>
              
              <div className="space-y-6">
                <div>
                  <h4 className="font-bold text-navy-900 mb-2">Marine Cargo Insurance</h4>
                  <p className="text-gray-600 mb-2">
                    Full coverage during ocean freight from origin port to Mombasa.
                  </p>
                  <ul className="space-y-1 text-sm text-gray-600">
                    <li>• Provider: APA Insurance Company Ltd.</li>
                    <li>• Policy #: MC-SWG-2024-10428</li>
                    <li>• Coverage: Up to $500,000 per vehicle</li>
                  </ul>
                </div>

                <div className="border-t border-gray-200 pt-6">
                  <h4 className="font-bold text-navy-900 mb-2">Inland Transport Insurance</h4>
                  <p className="text-gray-600 mb-2">
                    Protection during road transport from Mombasa to Kampala.
                  </p>
                  <ul className="space-y-1 text-sm text-gray-600">
                    <li>• Provider: Jubilee Insurance Company of Uganda</li>
                    <li>• Policy #: IT-SWG-2024-8821</li>
                    <li>• Coverage: Comprehensive (theft, damage, accidents)</li>
                  </ul>
                </div>

                <div className="border-t border-gray-200 pt-6">
                  <h4 className="font-bold text-navy-900 mb-2">Professional Indemnity Insurance</h4>
                  <p className="text-gray-600 mb-2">
                    Covers customs errors, omissions, and professional liability.
                  </p>
                  <ul className="space-y-1 text-sm text-gray-600">
                    <li>• Provider: UAP Insurance Uganda</li>
                    <li>• Policy #: PI-SWG-2024-4428</li>
                    <li>• Coverage: Up to $1,000,000</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* UNBS Compliance */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto text-center">
            <h2 className="text-3xl md:text-4xl font-bold mb-6">UNBS Compliance Partner</h2>
            <p className="text-gray-600 text-lg mb-8">
              We work with UNBS-approved inspection agencies to ensure all vehicles meet Uganda's safety and environmental standards before shipping.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white rounded-xl p-6 shadow-md">
                <div className="text-4xl mb-4">🇯🇵</div>
                <h3 className="font-bold text-navy-900 mb-2">Japan Inspection</h3>
                <p className="text-sm text-gray-600">BIVAC International</p>
              </div>
              <div className="bg-white rounded-xl p-6 shadow-md">
                <div className="text-4xl mb-4">🇬🇧</div>
                <h3 className="font-bold text-navy-900 mb-2">UK Inspection</h3>
                <p className="text-sm text-gray-600">SGS United Kingdom Ltd.</p>
              </div>
              <div className="bg-white rounded-xl p-6 shadow-md">
                <div className="text-4xl mb-4">🇦🇪</div>
                <h3 className="font-bold text-navy-900 mb-2">UAE Inspection</h3>
                <p className="text-sm text-gray-600">Intertek International Limited</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Download Section */}
      <section className="section-padding bg-blue-50">
        <div className="container-custom text-center">
          <h2 className="text-3xl font-bold mb-6">Verify Our Credentials</h2>
          <p className="text-gray-600 mb-8 max-w-2xl mx-auto">
            Download copies of our licenses and insurance certificates for your records.
          </p>
          <div className="flex flex-wrap justify-center gap-4">
            <button className="btn-primary px-8">
              Download URA License
            </button>
            <button className="btn-outline px-8 bg-white">
              Download Insurance Certificates
            </button>
          </div>
        </div>
      </section>
    </div>
  );
};

export default Certifications;
