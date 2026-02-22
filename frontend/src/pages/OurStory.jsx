import React from 'react';
import { FaLinkedin } from 'react-icons/fa';

const OurStory = () => {
  const teamMembers = [
    {
      name: 'Joseph Mukasa',
      role: 'Founder & CEO',
      image: '/images/team/placeholder.jpg',
      bio: '40+ years in freight forwarding and customs brokerage. Deep knowledge of URA regulations.',
    },
    {
      name: 'Sarah Nambi',
      role: 'Operations Director',
      image: '/images/team/placeholder.jpg',
      bio: 'Expert in logistics coordination. Ensures smooth Mombasa-Kampala deliveries.',
    },
    {
      name: 'David Okello',
      role: 'Customs Compliance Manager',
      image: '/images/team/placeholder.jpg',
      bio: 'Former URA officer. Specializes in vehicle import regulations and UNBS standards.',
    },
    {
      name: 'Grace Nakato',
      role: 'Customer Success Lead',
      image: '/images/team/placeholder.jpg',
      bio: 'Dedicated to ensuring every client has a smooth, stress-free shipping experience.',
    },
  ];

  return (
    <div className="bg-white">
      {/* Hero */}
      <section className="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-20">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-bold mb-6">Our Story</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Four decades of excellence in connecting Ugandans with quality vehicles from around the world.
          </p>
        </div>
      </section>

      {/* Company History */}
      <section className="section-padding">
        <div className="container-custom">
          <div className="max-w-4xl mx-auto">
            <h2 className="text-3xl md:text-4xl font-bold mb-8 text-center">How It All Started</h2>
            
            <div className="prose prose-lg max-w-none text-gray-600">
              <p className="text-lg leading-relaxed mb-6">
                Founded in 1984, ShipWithGlowie began as a small freight forwarding operation in Kampala. 
                Our founder, Joseph Mukasa, saw a growing need for reliable, transparent car shipping services 
                as more Ugandans looked to import quality vehicles from overseas markets.
              </p>

              <p className="text-lg leading-relaxed mb-6">
                What started as a modest office with just three employees has grown into Uganda's most trusted 
                car shipping company. We've shipped over 5,000 vehicles from Japan, the UK, and the UAE, 
                helping families and businesses get the vehicles they need.
              </p>

              <p className="text-lg leading-relaxed mb-6">
                Our secret? We understand both sides of the equation. We know the international shipping industry, 
                but more importantly, we live and breathe Ugandan customs, regulations, and the unique challenges 
                of the Mombasa-Kampala route.
              </p>

              <p className="text-lg leading-relaxed">
                Today, we're proud to combine decades of experience with cutting-edge technology. Our AI-powered 
                quote system and real-time tracking make shipping a car easier than ever, while our personal touch 
                ensures you're never just a number.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Timeline */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom">
          <h2 className="text-3xl md:text-4xl font-bold mb-12 text-center">Our Journey</h2>
          
          <div className="max-w-3xl mx-auto space-y-8">
            <div className="flex gap-6">
              <div className="flex-shrink-0 w-24 text-right">
                <div className="text-2xl font-bold text-blue-600">1984</div>
              </div>
              <div className="flex-1 pb-8 border-l-2 border-blue-200 pl-8">
                <h3 className="text-xl font-bold text-navy-900 mb-2">Company Founded</h3>
                <p className="text-gray-600">Joseph Mukasa establishes ShipWithGlowie in Kampala.</p>
              </div>
            </div>

            <div className="flex gap-6">
              <div className="flex-shrink-0 w-24 text-right">
                <div className="text-2xl font-bold text-blue-600">1995</div>
              </div>
              <div className="flex-1 pb-8 border-l-2 border-blue-200 pl-8">
                <h3 className="text-xl font-bold text-navy-900 mb-2">URA Approval</h3>
                <p className="text-gray-600">Became one of the first URA-approved customs agents for vehicle imports.</p>
              </div>
            </div>

            <div className="flex gap-6">
              <div className="flex-shrink-0 w-24 text-right">
                <div className="text-2xl font-bold text-blue-600">2010</div>
              </div>
              <div className="flex-1 pb-8 border-l-2 border-blue-200 pl-8">
                <h3 className="text-xl font-bold text-navy-900 mb-2">1,000th Vehicle Milestone</h3>
                <p className="text-gray-600">Celebrated shipping our 1,000th vehicle to Uganda.</p>
              </div>
            </div>

            <div className="flex gap-6">
              <div className="flex-shrink-0 w-24 text-right">
                <div className="text-2xl font-bold text-blue-600">2020</div>
              </div>
              <div className="flex-1 pb-8 border-l-2 border-blue-200 pl-8">
                <h3 className="text-xl font-bold text-navy-900 mb-2">Digital Transformation</h3>
                <p className="text-gray-600">Launched AI-powered quote system and real-time tracking platform.</p>
              </div>
            </div>

            <div className="flex gap-6">
              <div className="flex-shrink-0 w-24 text-right">
                <div className="text-2xl font-bold text-blue-600">2024</div>
              </div>
              <div className="flex-1 pl-8">
                <h3 className="text-xl font-bold text-navy-900 mb-2">5,000+ Vehicles & Counting</h3>
                <p className="text-gray-600">Continuing to serve Uganda with excellence and innovation.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Team */}
      <section className="section-padding">
        <div className="container-custom">
          <h2 className="text-3xl md:text-4xl font-bold mb-4 text-center">Meet Our Team</h2>
          <p className="text-gray-600 text-center mb-12 max-w-2xl mx-auto">
            The experts who make your car shipping experience seamless and stress-free.
          </p>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {teamMembers.map((member, index) => (
              <div key={index} className="bg-white rounded-xl shadow-lg overflow-hidden group hover:shadow-xl transition-shadow duration-300">
                <div className="aspect-square bg-gray-200 flex items-center justify-center">
                  <span className="text-6xl">👤</span>
                </div>
                <div className="p-6">
                  <h3 className="text-xl font-bold text-navy-900 mb-1">{member.name}</h3>
                  <p className="text-blue-600 font-medium mb-3">{member.role}</p>
                  <p className="text-gray-600 text-sm mb-4">{member.bio}</p>
                  <button className="text-blue-600 hover:text-blue-700 transition">
                    <FaLinkedin className="text-2xl" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

export default OurStory;
