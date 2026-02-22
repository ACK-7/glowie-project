import React from 'react';

// Import Home Page Components
import HeroSection from '../components/Home/HeroSection';
import BrowseByBrand from '../components/Home/BrowseByBrand';
import ExploreVehicles from '../components/Home/ExploreVehicles';
import WhyChooseUs from '../components/Home/WhyChooseUs';
import TestimonialsSection from '../components/Home/TestimonialsSection';
import CTASection from '../components/Home/CTASection';

const Home = () => {
  return (
    <div className="bg-white">
      <HeroSection />
      <BrowseByBrand />
      <ExploreVehicles />
      <WhyChooseUs />
      <TestimonialsSection />
      <CTASection />
    </div>
  );
};

export default Home;


