import { useState } from 'react';
import { FaHeart, FaCompressArrowsAlt, FaSearch } from 'react-icons/fa';
import FavoritesManager from '../components/UserFeatures/FavoritesManager';

const Favorites = () => {
  const [activeTab, setActiveTab] = useState('favorites');

  return (
    <div className="min-h-screen bg-gradient-to-b from-gray-50 to-white">
      <div className="container-custom py-20">
        {/* Header */}
        <div className="text-center mb-12">
          <div className="inline-flex items-center gap-2 px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold mb-4">
            <FaHeart className="text-red-600" />
            YOUR FAVORITES
          </div>
          <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Saved <span className="gradient-text">Vehicles</span>
          </h1>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Manage your favorite vehicles and compare them to find your perfect match
          </p>
        </div>

        {/* Tabs */}
        <div className="flex justify-center mb-12">
          <div className="bg-white rounded-2xl p-2 shadow-lg border border-gray-200">
            <div className="flex gap-2">
              {[
                { key: 'favorites', label: 'My Favorites', icon: FaHeart },
                { key: 'comparison', label: 'Compare', icon: FaCompressArrowsAlt },
                { key: 'searches', label: 'Saved Searches', icon: FaSearch }
              ].map((tab) => (
                <button
                  key={tab.key}
                  onClick={() => setActiveTab(tab.key)}
                  className={`px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center gap-2 ${
                    activeTab === tab.key
                      ? 'bg-blue-600 text-white shadow-lg'
                      : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                  }`}
                >
                  <tab.icon />
                  {tab.label}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="bg-white rounded-2xl shadow-lg p-8">
          {activeTab === 'favorites' && <FavoritesManager />}
          
          {activeTab === 'comparison' && (
            <div className="text-center py-12">
              <FaCompressArrowsAlt className="text-6xl text-gray-300 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-900 mb-2">Vehicle Comparison</h3>
              <p className="text-gray-600 mb-6">
                Select vehicles from your favorites to compare their specifications side by side
              </p>
              <button
                onClick={() => setActiveTab('favorites')}
                className="btn-primary"
              >
                Go to Favorites
              </button>
            </div>
          )}
          
          {activeTab === 'searches' && (
            <div className="text-center py-12">
              <FaSearch className="text-6xl text-gray-300 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-900 mb-2">Saved Searches</h3>
              <p className="text-gray-600 mb-6">
                Save your search criteria to quickly find vehicles that match your preferences
              </p>
              <p className="text-sm text-gray-500">Coming soon...</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Favorites;