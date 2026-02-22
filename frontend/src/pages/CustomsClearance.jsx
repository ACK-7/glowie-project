import React from 'react';
import { Link } from 'react-router-dom';
import { FaFileAlt, FaCheckCircle, FaCalculator, FaHandshake, FaExclamationTriangle } from 'react-icons/fa';

const CustomsClearance = () => {
  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Customs & Clearance</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Navigate Ugandan customs with confidence - we guide you through every step
          </p>
        </div>
      </section>

      {/* Two-Part Clearance Process */}
      <section className="section-padding">
        <div className="container-custom max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">The Two-Part Clearance Process</h2>
            <p className="text-gray-600 text-lg max-w-3xl mx-auto">
              Understanding the two distinct clearance stages ensures a smooth, predictable process
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {/* Part 1 - Transit Clearance */}
            <div className="premium-card p-8 border-l-4 border-blue-500">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center">
                  <FaCheckCircle className="text-blue-600 text-2xl" />
                </div>
                <div>
                  <span className="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mb-2">
                    Part 1
                  </span>
                  <h3 className="text-2xl font-bold text-navy-900">Transit Clearance</h3>
                </div>
              </div>

              <div className="mb-6">
                <p className="text-lg font-semibold text-blue-600 mb-3">
                  ✅ ShipWithGlowie's Responsibility
                </p>
                <p className="text-gray-600 leading-relaxed mb-4">
                  Getting your car out of Mombasa/Dar-es-Salaam port and safely into a URA-approved 
                  bond in Kampala. This is <strong>fully handled by us</strong> and included in your service fee.
                </p>
              </div>

              <div className="bg-blue-50 rounded-lg p-6">
                <p className="font-bold text-navy-900 mb-3">What We Handle:</p>
                <ul className="space-y-2 text-gray-700">
                  <li className="flex items-start gap-2">
                    <span className="text-blue-500 mt-1">•</span>
                    <span>Port authority clearance at Mombasa/Dar-es-Salaam</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-blue-500 mt-1">•</span>
                    <span>Transit bond documentation through Kenya/Tanzania</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-blue-500 mt-1">•</span>
                    <span>Border crossing paperwork (Malaba or Mutukula)</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-blue-500 mt-1">•</span>
                    <span>Delivery to designated URA bond in Kampala</span>
                  </li>
                </ul>
              </div>

              <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p className="text-sm text-green-800">
                  <strong>No Action Needed:</strong> This entire stage is handled by our expert team. 
                  You'll receive tracking updates throughout.
                </p>
              </div>
            </div>

            {/* Part 2 - Final URA Clearance */}
            <div className="premium-card p-8 border-l-4 border-orange-500">
              <div className="flex items-center gap-4 mb-6">
                <div className="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center">
                  <FaFileAlt className="text-orange-600 text-2xl" />
                </div>
                <div>
                  <span className="inline-block bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium mb-2">
                    Part 2
                  </span>
                  <h3 className="text-2xl font-bold text-navy-900">Final URA Clearance</h3>
                </div>
              </div>

              <div className="mb-6">
                <p className="text-lg font-semibold text-orange-600 mb-3">
                  👤 Customer's Responsibility
                </p>
                <p className="text-gray-600 leading-relaxed mb-4">
                  Calculating and paying Ugandan taxes and duties to the Uganda Revenue Authority (URA). 
                  This <strong>must be done by you</strong> (the vehicle owner) or your appointed clearing agent, 
                  using <strong>your Tax Identification Number (TIN)</strong>.
                </p>
              </div>

              <div className="bg-orange-50 rounded-lg p-6">
                <p className="font-bold text-navy-900 mb-3">Your Responsibilities:</p>
                <ul className="space-y-2 text-gray-700">
                  <li className="flex items-start gap-2">
                    <span className="text-orange-500 mt-1">•</span>
                    <span>Obtain Tax Identification Number (TIN) if you don't have one</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-orange-500 mt-1">•</span>
                    <span>Submit required documents to URA for assessment</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-orange-500 mt-1">•</span>
                    <span>Pay calculated Import Duty, VAT, Withholding Tax, and levies</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-orange-500 mt-1">•</span>
                    <span>Collect release documents to pick up your vehicle</span>
                  </li>
                </ul>
              </div>

              <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p className="text-sm text-blue-800">
                  <strong>We Can Help:</strong> While this is legally your responsibility, we offer clearing 
                  support services through our URA-licensed partners (see below).
                </p>
              </div>
            </div>
          </div>

          <div className="mt-8 p-6 bg-gray-50 rounded-xl border-2 border-gray-200">
            <div className="flex items-start gap-4">
              <FaExclamationTriangle className="text-yellow-600 text-3xl flex-shrink-0 mt-1" />
              <div>
                <h4 className="font-bold text-navy-900 mb-2 text-lg">Why This Separation Matters</h4>
                <p className="text-gray-700">
                  Ugandan law requires that import taxes be paid by the actual vehicle owner using their personal TIN. 
                  This prevents fraud and ensures proper tax collection. We handle everything up to this point, 
                  then hand over to you with full documentation and support to complete the final step.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Mandatory Pre-Shipment Inspection */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Mandatory Pre-Shipment Inspection</h2>
            <p className="text-gray-600 text-lg">
              UNBS Roadworthiness Certificate - A legal requirement for all vehicle imports
            </p>
          </div>

          <div className="bg-white rounded-2xl shadow-xl p-8 md:p-12">
            <div className="flex items-start gap-6 mb-8">
              <div className="w-20 h-20 bg-green-100 rounded-2xl flex items-center justify-center flex-shrink-0">
                <FaCheckCircle className="text-green-600 text-3xl" />
              </div>
              <div>
                <h3 className="text-2xl font-bold text-navy-900 mb-4">JEVIC / QISJ Inspection</h3>
                <p className="text-gray-600 leading-relaxed mb-6">
                  The Uganda National Bureau of Standards (UNBS) requires all imported vehicles to undergo a 
                  <strong> pre-export roadworthiness inspection</strong> before they leave the country of origin. 
                  This inspection is conducted by authorized agencies:
                </p>
                <ul className="space-y-2 text-gray-700 mb-6">
                  <li className="flex items-start gap-3">
                    <span className="text-green-500 font-bold">•</span>
                    <span><strong>JEVIC</strong> (Japan Export Vehicle Inspection Center) - For vehicles from Japan</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="text-green-500 font-bold">•</span>
                    <span><strong>QISJ</strong> (Quality Inspection Services Japan) - Alternative for Japan</span>
                  </li>
                  <li className="flex items-start gap-3">
                    <span className="text-green-500 font-bold">•</span>
                    <span><strong>Bureau Veritas / Intertek</strong> - For UK and UAE vehicles</span>
                  </li>
                </ul>
              </div>
            </div>

            <div className="border-t border-gray-200 pt-8">
              <div className="bg-gradient-primary text-white rounded-xl p-6">
                <h4 className="text-xl font-bold mb-3">✅ Our Role: We Handle This Completely</h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p className="font-medium mb-2">What We Do:</p>
                    <ul className="space-y-1 text-sm text-blue-100">
                      <li>• Book the inspection appointment</li>
                      <li>• Coordinate with the inspection agency</li>
                      <li>• Ensure all safety and environmental standards are met</li>
                      <li>• Obtain the official UNBS certificate</li>
                    </ul>
                  </div>
                  <div>
                    <p className="font-medium mb-2">Certificate Includes:</p>
                    <ul className="space-y-1 text-sm text-blue-100">
                      <li>• Vehicle identification details (VIN, make, model)</li>
                      <li>• Roadworthiness assessment results</li>
                      <li>• Environmental compliance confirmation</li>
                      <li>• Valid for customs clearance in Uganda</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <p className="text-sm text-gray-800">
                <strong>Important:</strong> Without this certificate, your vehicle <strong>cannot</strong> be cleared 
                through Ugandan customs. We ensure it's completed before shipping begins.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Key Documentation */}
      <section className="section-padding">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Key Ugandan Documentation</h2>
            <p className="text-gray-600 text-lg">
              Essential documents required for final URA clearance
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <FaFileAlt className="text-blue-600 text-xl" />
                </div>
                <h3 className="font-bold text-lg">Bill of Lading (B/L)</h3>
              </div>
              <p className="text-gray-600 text-sm mb-2">
                The official shipping document proving your vehicle arrived by sea.
              </p>
              <p className="text-xs text-gray-500">
                <strong>Provided by:</strong> ShipWithGlowie (we upload this to your portal)
              </p>
            </div>

            <div className="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <FaFileAlt className="text-green-600 text-xl" />
                </div>
                <h3 className="font-bold text-lg">Commercial Invoice / Bill of Sale</h3>
              </div>
              <p className="text-gray-600 text-sm mb-2">
                Shows the purchase price and vehicle details - used to calculate taxes.
              </p>
              <p className="text-xs text-gray-500">
                <strong>Provided by:</strong> Vehicle seller or auction house
              </p>
            </div>

            <div className="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                  <FaFileAlt className="text-orange-600 text-xl" />
                </div>
                <h3 className="font-bold text-lg">Tax Identification Number (TIN)</h3>
              </div>
              <p className="text-gray-600 text-sm mb-2">
                Your personal Ugandan tax number - required for all import payments.
              </p>
              <p className="text-xs text-gray-500">
                <strong>How to get:</strong> Register at URA offices or online at ura.go.ug
              </p>
            </div>

            <div className="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                  <FaCheckCircle className="text-purple-600 text-xl" />
                </div>
                <h3 className="font-bold text-lg">UNBS Inspection Certificate</h3>
              </div>
              <p className="text-gray-600 text-sm mb-2">
                JEVIC/QISJ certificate proving roadworthiness and safety compliance.
              </p>
              <p className="text-xs text-gray-500">
                <strong>Provided by:</strong> ShipWithGlowie (arranged before shipping)
              </p>
            </div>
          </div>

          <div className="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h4 className="font-bold text-navy-900 mb-3">📋 Document Checklist</h4>
            <p className="text-gray-700 mb-4">
              Before heading to URA for final clearance, ensure you have:
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label className="flex items-center gap-3 text-gray-700">
                <input type="checkbox" className="w-5 h-5 text-blue-600 rounded" disabled />
                <span>Original Bill of Lading</span>
              </label>
              <label className="flex items-center gap-3 text-gray-700">
                <input type="checkbox" className="w-5 h-5 text-blue-600 rounded" disabled />
                <span>Commercial Invoice</span>
              </label>
              <label className="flex items-center gap-3 text-gray-700">
                <input type="checkbox" className="w-5 h-5 text-blue-600 rounded" disabled />
                <span>Your TIN Certificate</span>
              </label>
              <label className="flex items-center gap-3 text-gray-700">
                <input type="checkbox" className="w-5 h-5 text-blue-600 rounded" disabled />
                <span>UNBS Inspection Certificate</span>
              </label>
              <label className="flex items-center gap-3 text-gray-700">
                <input type="checkbox" className="w-5 h-5 text-blue-600 rounded" disabled />
                <span>Valid Passport/National ID</span>
              </label>
              <label className="flex items-center gap-3 text-gray-700">
                <input type="checkbox" className="w-5 h-5 text-blue-600 rounded" disabled />
                <span>Insurance Certificate (Transit)</span>
              </label>
            </div>
          </div>
        </div>
      </section>

      {/* Understanding URA Taxes */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Understanding URA Taxes & Duties</h2>
            <p className="text-gray-600 text-lg">
              How import taxes are calculated in Uganda
            </p>
          </div>

          <div className="bg-white rounded-2xl shadow-xl p-8 md:p-12">
            <div className="mb-8">
              <h3 className="text-2xl font-bold text-navy-900 mb-6">Tax Calculation Factors</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="border-l-4 border-blue-500 bg-blue-50 p-6 rounded-r-lg">
                  <h4 className="font-bold text-navy-900 mb-3">1. Vehicle Value (CIF)</h4>
                  <p className="text-gray-700 text-sm mb-3">
                    <strong>CIF = Cost + Insurance + Freight</strong>
                  </p>
                  <p className="text-gray-600 text-sm">
                    URA calculates taxes based on the total landed cost of your vehicle, including 
                    purchase price, shipping fees, and insurance.
                  </p>
                </div>

                <div className="border-l-4 border-green-500 bg-green-50 p-6 rounded-r-lg">
                  <h4 className="font-bold text-navy-900 mb-3">2. Vehicle Age</h4>
                  <p className="text-gray-700 text-sm mb-3">
                    <strong>Based on Date of Manufacture</strong>
                  </p>
                  <p className="text-gray-600 text-sm">
                    Older vehicles may attract higher duty rates. Generally, vehicles over 8 years 
                    old face stricter requirements and higher environmental levies.
                  </p>
                </div>
              </div>
            </div>

            <div className="mb-8">
              <h3 className="text-xl font-bold text-navy-900 mb-6">Typical Tax Components</h3>
              <div className="space-y-4">
                <div className="flex justify-between items-center border-b pb-3">
                  <div>
                    <p className="font-medium text-gray-900">Import Duty</p>
                    <p className="text-sm text-gray-600">On CIF value</p>
                  </div>
                  <span className="text-2xl font-bold text-blue-600">25-35%</span>
                </div>
                <div className="flex justify-between items-center border-b pb-3">
                  <div>
                    <p className="font-medium text-gray-900">Value Added Tax (VAT)</p>
                    <p className="text-sm text-gray-600">On (CIF + Duty)</p>
                  </div>
                  <span className="text-2xl font-bold text-blue-600">18%</span>
                </div>
                <div className="flex justify-between items-center border-b pb-3">
                  <div>
                    <p className="font-medium text-gray-900">Withholding Tax</p>
                    <p className="text-sm text-gray-600">Advance income tax</p>
                  </div>
                  <span className="text-2xl font-bold text-blue-600">6%</span>
                </div>
                <div className="flex justify-between items-center border-b pb-3">
                  <div>
                    <p className="font-medium text-gray-900">Environmental Levy</p>
                    <p className="text-sm text-gray-600">Based on engine size and age</p>
                  </div>
                  <span className="text-2xl font-bold text-blue-600">Variable</span>
                </div>
                <div className="flex justify-between items-center">
                  <div>
                    <p className="font-medium text-gray-900">Infrastructure Levy</p>
                    <p className="text-sm text-gray-600">Road maintenance fund</p>
                  </div>
                  <span className="text-2xl font-bold text-blue-600">1.5%</span>
                </div>
              </div>
            </div>

            <div className="bg-gradient-primary text-white rounded-xl p-6 text-center">
              <FaCalculator className="text-5xl mx-auto mb-4 opacity-90" />
              <h4 className="text-2xl font-bold mb-3">Calculate Your Taxes</h4>
              <p className="mb-6 text-blue-100">
                Use the official URA Motor Vehicle Tax Calculator to estimate your import costs
              </p>
              <a 
                href="https://www.ura.go.ug" 
                target="_blank" 
                rel="noopener noreferrer"
                className="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition"
              >
                Visit URA Calculator →
              </a>
            </div>

            <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <p className="text-sm text-gray-800">
                <strong>Note:</strong> Tax rates and calculation methods may change. Always verify with URA 
                for the most current rates before making payment plans.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Our Clearing Support */}
      <section className="section-padding">
        <div className="container-custom max-w-5xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold mb-4">Our Clearing Support</h2>
            <p className="text-gray-600 text-lg">
              You're not alone in the final clearance process
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div className="premium-card p-8">
              <div className="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 text-3xl mb-6">
                <FaHandshake />
              </div>
              <h3 className="text-2xl font-bold text-navy-900 mb-4">Dedicated URA Agents</h3>
              <p className="text-gray-600 leading-relaxed mb-6">
                While you must use your own TIN for legal compliance, we work with <strong>URA-licensed and 
                bonded clearing agents</strong> in Kampala who can handle the entire process on your behalf.
              </p>
              <div className="bg-blue-50 rounded-lg p-4">
                <p className="font-medium text-navy-900 mb-2">Agent Services Include:</p>
                <ul className="space-y-2 text-sm text-gray-700">
                  <li>✓ Document submission to URA</li>
                  <li>✓ Tax assessment follow-up</li>
                  <li>✓ Payment coordination</li>
                  <li>✓ Release documentation collection</li>
                </ul>
              </div>
            </div>

            <div className="premium-card p-8">
              <div className="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center text-green-600 text-3xl mb-6">
                💳
              </div>
              <h3 className="text-2xl font-bold text-navy-900 mb-4">Simplified Tax Payment</h3>
              <p className="text-gray-600 leading-relaxed mb-6">
                Once URA completes the assessment, you receive a <strong>Payment Reference Number (PRN)</strong>. 
                This streamlines the payment process significantly.
              </p>
              <div className="space-y-4">
                <div className="border-l-4 border-green-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 1: Assessment</p>
                  <p className="text-sm text-gray-600">URA calculates your total tax obligation</p>
                </div>
                <div className="border-l-4 border-green-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 2: PRN Issuance</p>
                  <p className="text-sm text-gray-600">You receive a unique payment reference number</p>
                </div>
                <div className="border-l-4 border-green-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 3: Payment</p>
                  <p className="text-sm text-gray-600">Pay directly to URA via bank or mobile money</p>
                </div>
                <div className="border-l-4 border-green-500 pl-4">
                  <p className="font-medium text-gray-900 mb-1">Step 4: Release</p>
                  <p className="text-sm text-gray-600">Vehicle cleared for pickup within 24-48 hours</p>
                </div>
              </div>
            </div>
          </div>

          <div className="mt-8 bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white rounded-2xl p-8 text-center">
            <h3 className="text-2xl font-bold mb-4">Need Clearing Agent Support?</h3>
            <p className="text-blue-100 mb-6 max-w-2xl mx-auto">
              Our partner clearing agents can handle the entire URA process for you, ensuring fast, 
              accurate clearance with minimal hassle.
            </p>
<Link to="/contact" className="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition">
              Request Clearing Support
            </Link>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom max-w-3xl mx-auto text-center">
          <h2 className="text-3xl font-bold mb-4">Ready to Start Your Import?</h2>
          <p className="text-gray-600 mb-8 text-lg">
            We guide you through every step of the customs process
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/services/request-quote" className="btn-primary px-12">
              Get Free Quote
            </Link>
            <Link to="/faq" className="btn-outline px-12">
              View FAQ
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default CustomsClearance;
