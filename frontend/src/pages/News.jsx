import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { FaCalendarAlt, FaClock, FaUser, FaArrowRight, FaSearch } from 'react-icons/fa';

const News = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('All');

  const categories = ['All', 'Shipping News', 'Automotive Industry', 'Import Regulations', 'Company Updates'];

  const newsArticles = [
    {
      id: 1,
      title: 'New Direct Shipping Route from Japan to Uganda',
      excerpt: 'We\'re excited to announce a new direct shipping route that reduces transit time by 5-7 days, making your car imports faster than ever.',
      category: 'Company Updates',
      author: 'John Kamau',
      date: 'December 5, 2024',
      readTime: '4 min read',
      image: 'https://images.unsplash.com/photo-1494412519320-aa613dfb7738?w=800&auto=format&fit=crop',
      featured: true
    },
    {
      id: 2,
      title: 'Understanding URA Import Tax Changes for 2024',
      excerpt: 'The Uganda Revenue Authority has announced new tax structures for vehicle imports. Here\'s everything you need to know about how it affects your car purchase.',
      category: 'Import Regulations',
      author: 'Sarah Namukasa',
      date: 'December 3, 2024',
      readTime: '6 min read',
      image: 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 3,
      title: 'Top 5 Most Imported Car Models in Uganda 2024',
      excerpt: 'Discover which vehicle models Ugandans are importing the most this year. From Toyota to Mercedes, see the trends shaping our automotive market.',
      category: 'Automotive Industry',
      author: 'David Okello',
      date: 'November 28, 2024',
      readTime: '5 min read',
      image: 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 4,
      title: 'How to Inspect Your Car Before Shipping',
      excerpt: 'A comprehensive guide to inspecting your vehicle before international shipping to ensure smooth customs clearance and avoid surprises.',
      category: 'Shipping News',
      author: 'Mary Atim',
      date: 'November 25, 2024',
      readTime: '7 min read',
      image: 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 5,
      title: 'Electric Vehicles: Import Regulations in Uganda',
      excerpt: 'As electric vehicles gain popularity, Uganda updates import policies. Learn about tax benefits and infrastructure developments for EVs.',
      category: 'Import Regulations',
      author: 'James Mutebi',
      date: 'November 20, 2024',
      readTime: '5 min read',
      image: 'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 6,
      title: 'Shipping Container Security: Our Advanced Measures',
      excerpt: 'Learn about the state-of-the-art security measures we employ to protect your vehicle during ocean transit from purchase to delivery.',
      category: 'Company Updates',
      author: 'Grace Nakato',
      date: 'November 15, 2024',
      readTime: '4 min read',
      image: 'https://images.unsplash.com/photo-1578575437130-527eed3abbec?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 7,
      title: 'Japanese Used Cars: Why They\'re the Best Choice',
      excerpt: 'Discover why Japanese used cars remain the top choice for Ugandan buyers: reliability, affordability, and exceptional maintenance history.',
      category: 'Automotive Industry',
      author: 'Patrick Ssemakula',
      date: 'November 10, 2024',
      readTime: '6 min read',
      image: 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 8,
      title: 'Customs Clearance Made Easy: Step-by-Step Guide',
      excerpt: 'Navigate Uganda\'s customs process with confidence. Our detailed guide covers every step from port arrival to vehicle registration.',
      category: 'Shipping News',
      author: 'Rebecca Nabirye',
      date: 'November 5, 2024',
      readTime: '8 min read',
      image: 'https://images.unsplash.com/photo-1590283603385-17ffb3a7a12d?w=800&auto=format&fit=crop',
      featured: false
    },
    {
      id: 9,
      title: 'Celebrating 1000+ Successful Car Deliveries',
      excerpt: 'We\'ve reached a major milestone! Thank you to all our customers who trusted us to ship their dream vehicles from around the world.',
      category: 'Company Updates',
      date: 'October 28, 2024',
      author: 'Management Team',
      readTime: '3 min read',
      image: 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&auto=format&fit=crop',
      featured: false
    }
  ];

  // Filter articles based on search and category
  const filteredArticles = newsArticles.filter(article => {
    const matchesSearch = article.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         article.excerpt.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = selectedCategory === 'All' || article.category === selectedCategory;
    return matchesSearch && matchesCategory;
  });

  const featuredArticle = newsArticles.find(article => article.featured);
  const regularArticles = filteredArticles.filter(article => !article.featured);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <section className="bg-gradient-to-br from-navy-900 via-blue-900 to-blue-800 text-white py-16">
        <div className="container-custom text-center">
          <h1 className="text-4xl md:text-5xl font-heading font-bold mb-3">
            News & Updates
          </h1>
          <p className="text-lg text-blue-100 max-w-2xl mx-auto">
            Stay informed with the latest automotive news, shipping updates, and industry insights
          </p>
        </div>
      </section>

      {/* Search and Filter Section */}
      <section className="py-8 bg-white border-b sticky top-0 z-40">
        <div className="container-custom">
          <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
            {/* Search */}
            <div className="relative w-full md:w-96">
              <FaSearch className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search articles..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:outline-none"
              />
            </div>

            {/* Category Filter */}
            <div className="flex gap-2 overflow-x-auto w-full md:w-auto">
              {categories.map((category) => (
                <button
                  key={category}
                  onClick={() => setSelectedCategory(category)}
                  className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all ${
                    selectedCategory === category
                      ? 'bg-blue-600 text-white shadow-md'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  {category}
                </button>
              ))}
            </div>
          </div>

          {searchTerm && (
            <p className="mt-3 text-gray-600 text-sm">
              Found {filteredArticles.length} article{filteredArticles.length !== 1 ? 's' : ''}
            </p>
          )}
        </div>
      </section>

      {/* Featured Article */}
      {featuredArticle && selectedCategory === 'All' && !searchTerm && (
        <section className="py-12 bg-white">
          <div className="container-custom">
            <div className="flex items-center gap-2 mb-6">
              <span className="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">FEATURED</span>
              <h2 className="text-2xl font-heading font-bold text-navy-900">Top Story</h2>
            </div>
            
            <div className="grid md:grid-cols-2 gap-8 bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-shadow duration-300">
              <div className="relative h-80 md:h-auto">
                <img
                  src={featuredArticle.image}
                  alt={featuredArticle.title}
                  className="w-full h-full object-cover"
                />
                <span className="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                  {featuredArticle.category}
                </span>
              </div>
              
              <div className="p-8 flex flex-col justify-center">
                <h3 className="text-3xl font-heading font-bold text-navy-900 mb-4 hover:text-blue-600 transition">
                  <Link to={`/news/${featuredArticle.id}`}>{featuredArticle.title}</Link>
                </h3>
                
                <p className="text-gray-600 mb-6 leading-relaxed text-lg">
                  {featuredArticle.excerpt}
                </p>
                
                <div className="flex items-center gap-6 text-sm text-gray-500 mb-6">
                  <div className="flex items-center gap-2">
                    <FaUser className="text-blue-600" />
                    <span>{featuredArticle.author}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <FaCalendarAlt className="text-blue-600" />
                    <span>{featuredArticle.date}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <FaClock className="text-blue-600" />
                    <span>{featuredArticle.readTime}</span>
                  </div>
                </div>
                
                <Link
                  to={`/news/${featuredArticle.id}`}
                  className="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold transition group"
                >
                  Read Full Article
                  <FaArrowRight className="group-hover:translate-x-1 transition-transform" />
                </Link>
              </div>
            </div>
          </div>
        </section>
      )}

      {/* Articles Grid */}
      <section className="section-padding">
        <div className="container-custom">
          {regularArticles.length === 0 ? (
            <div className="text-center py-12 bg-white rounded-xl">
              <p className="text-xl text-gray-600 mb-4">No articles found matching your criteria</p>
              <button
                onClick={() => {
                  setSearchTerm('');
                  setSelectedCategory('All');
                }}
                className="text-blue-600 hover:text-blue-700 font-semibold"
              >
                Clear filters
              </button>
            </div>
          ) : (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {regularArticles.map((article) => (
                <article
                  key={article.id}
                  className="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group"
                >
                  {/* Article Image */}
                  <div className="relative h-48 overflow-hidden">
                    <img
                      src={article.image}
                      alt={article.title}
                      className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                    />
                    <span className="absolute top-4 left-4 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                      {article.category}
                    </span>
                  </div>
                  
                  {/* Article Content */}
                  <div className="p-6">
                    <h3 className="text-xl font-heading font-bold text-navy-900 mb-3 group-hover:text-blue-600 transition line-clamp-2">
                      <Link to={`/news/${article.id}`}>{article.title}</Link>
                    </h3>
                    
                    <p className="text-gray-600 mb-4 line-clamp-3 leading-relaxed">
                      {article.excerpt}
                    </p>
                    
                    <div className="flex items-center gap-4 text-xs text-gray-500 mb-4">
                      <div className="flex items-center gap-1">
                        <FaCalendarAlt className="text-blue-600" />
                        <span>{article.date}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <FaClock className="text-blue-600" />
                        <span>{article.readTime}</span>
                      </div>
                    </div>
                    
                    <Link
                      to={`/news/${article.id}`}
                      className="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold text-sm transition group"
                    >
                      Read More
                      <FaArrowRight className="group-hover:translate-x-1 transition-transform" />
                    </Link>
                  </div>
                </article>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* Newsletter CTA */}
      <section className="py-16 bg-gradient-to-br from-navy-900 to-blue-900 text-white">
        <div className="container-custom max-w-4xl text-center">
          <h2 className="text-3xl md:text-4xl font-heading font-bold mb-4">
            Stay Updated
          </h2>
          <p className="text-xl text-blue-100 mb-8">
            Subscribe to our newsletter for the latest news, shipping updates, and exclusive offers
          </p>
          
          <form className="flex flex-col sm:flex-row gap-4 max-w-xl mx-auto">
            <input
              type="email"
              placeholder="Enter your email address"
              className="flex-1 px-6 py-4 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-gold-400"
            />
            <button
              type="submit"
              className="btn-primary px-8 py-4 whitespace-nowrap"
            >
              Subscribe
            </button>
          </form>
        </div>
      </section>
    </div>
  );
};

export default News;
