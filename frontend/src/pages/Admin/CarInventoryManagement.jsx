import { useState, useEffect } from 'react';
import { 
  FaPlus, 
  FaEdit, 
  FaTrash, 
  FaEye, 
  FaSearch, 
  FaFilter,
  FaCar,
  FaTags,
  FaList,
  FaImage,
  FaSave,
  FaTimes,
  FaUpload,
  FaStar
} from 'react-icons/fa';
import DropdownMenu from '../../components/UI/DropdownMenu';
import { showAlert, showConfirm } from '../../utils/sweetAlert';

const CarInventoryManagement = () => {
  const [activeTab, setActiveTab] = useState('cars');
  const [cars, setCars] = useState([]);
  const [brands, setBrands] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showImageModal, setShowImageModal] = useState(false);
  const [modalType, setModalType] = useState(''); // 'car', 'brand', 'category'
  const [editingItem, setEditingItem] = useState(null);
  const [viewingItem, setViewingItem] = useState(null);
  const [managingImagesItem, setManagingImagesItem] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');

  // Form states
  const [carForm, setCarForm] = useState({
    brand_id: '',
    category_id: '',
    model: '',
    year: '',
    color: '',
    description: '',
    engine_type: '',
    fuel_type: 'petrol',
    transmission: 'automatic',
    mileage: '',
    drive_type: 'fwd',
    doors: 4,
    seats: 5,
    price: '',
    location_country: '',
    location_city: '',
    condition: 'used',
    is_featured: false,
    features: [],
    safety_features: [],
    images: []
  });

  const [brandForm, setBrandForm] = useState({
    name: '',
    country_of_origin: '',
    description: ''
  });

  const [categoryForm, setCategoryForm] = useState({
    name: '',
    description: ''
  });

  const [imageUpload, setImageUpload] = useState({
    file: null,
    alt_text: '',
    is_primary: false,
    uploading: false
  });

  useEffect(() => {
    fetchData();
    // Always fetch brands and categories for the car form dropdowns
    if (brands.length === 0) fetchBrands();
    if (categories.length === 0) fetchCategories();
  }, [activeTab]);

  const fetchData = async () => {
    setLoading(true);
    try {
      if (activeTab === 'cars') {
        await fetchCars();
      } else if (activeTab === 'brands') {
        await fetchBrands();
      } else if (activeTab === 'categories') {
        await fetchCategories();
      }
    } catch (error) {
      console.error('Error fetching data:', error);
      showAlert.error('Error', 'Failed to load data. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const fetchCars = async () => {
    try {
      const response = await fetch('http://localhost:8000/api/admin/inventory/cars?per_page=50', {
        headers: { 
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
        }
      });
      const data = await response.json();
      if (data.success) {
        setCars(data.data.data || data.data);
      } else {
        showAlert.error('Error', 'Failed to load cars: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error fetching cars:', error);
      showAlert.error('Error', 'Failed to load cars. Please check your connection.');
    }
  };

  const fetchBrands = async () => {
    try {
      const token = localStorage.getItem('admin_token');
      console.log('Fetching brands with token:', token ? 'Token exists' : 'No token');
      
      const response = await fetch('http://localhost:8000/api/admin/inventory/brands', {
        headers: { 
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      
      console.log('Brands response status:', response.status);
      console.log('Brands response headers:', Object.fromEntries(response.headers.entries()));
      
      const data = await response.json();
      console.log('Brands API response:', data);
      
      if (response.ok && data.success) {
        setBrands(data.data);
        console.log('Brands set successfully:', data.data.length, 'brands');
      } else {
        console.error('Brands API error:', response.status, data);
        if (response.status === 401) {
          console.error('Authentication failed - token might be invalid');
          showAlert.error('Authentication Error', 'Your session has expired. Please log in again.');
        } else if (response.status === 403) {
          console.error('Authorization failed - user might not have admin privileges');
          showAlert.error('Authorization Error', 'You do not have permission to access this resource.');
        } else {
          showAlert.error('Error', 'Failed to load brands: ' + (data.message || 'Unknown error'));
        }
      }
    } catch (error) {
      console.error('Error fetching brands:', error);
      showAlert.error('Error', 'Failed to load brands. Please check your connection.');
    }
  };

  const fetchCategories = async () => {
    try {
      const token = localStorage.getItem('admin_token');
      console.log('Fetching categories with token:', token ? 'Token exists' : 'No token');
      
      const response = await fetch('http://localhost:8000/api/admin/inventory/categories', {
        headers: { 
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      
      console.log('Categories response status:', response.status);
      const data = await response.json();
      console.log('Categories API response:', data);
      
      if (response.ok && data.success) {
        setCategories(data.data);
        console.log('Categories set successfully:', data.data.length, 'categories');
      } else {
        console.error('Categories API error:', response.status, data);
        if (response.status === 401) {
          console.error('Authentication failed - token might be invalid');
          showAlert.error('Authentication Error', 'Your session has expired. Please log in again.');
        } else if (response.status === 403) {
          console.error('Authorization failed - user might not have admin privileges');
          showAlert.error('Authorization Error', 'You do not have permission to access this resource.');
        } else {
          showAlert.error('Error', 'Failed to load categories: ' + (data.message || 'Unknown error'));
        }
      }
    } catch (error) {
      console.error('Error fetching categories:', error);
      showAlert.error('Error', 'Failed to load categories. Please check your connection.');
    }
  };

  const openModal = async (type, item = null) => {
    setModalType(type);
    setEditingItem(item);
    
    // Ensure brands and categories are loaded for car modal
    if (type === 'car') {
      console.log('Opening car modal, fetching brands and categories...');
      if (brands.length === 0) {
        console.log('Brands array is empty, fetching...');
        await fetchBrands();
      }
      if (categories.length === 0) {
        console.log('Categories array is empty, fetching...');
        await fetchCategories();
      }
      console.log('Current brands:', brands);
      console.log('Current categories:', categories);
    }
    
    if (type === 'car') {
      if (item) {
        setCarForm({
          brand_id: item.brand_id || '',
          category_id: item.category_id || '',
          model: item.model || '',
          year: item.year || '',
          color: item.color || '',
          description: item.description || '',
          engine_type: item.engine_type || '',
          fuel_type: item.fuel_type || 'petrol',
          transmission: item.transmission || 'automatic',
          mileage: item.mileage || '',
          drive_type: item.drive_type || 'fwd',
          doors: item.doors || 4,
          seats: item.seats || 5,
          price: item.price || '',
          location_country: item.location_country || '',
          location_city: item.location_city || '',
          condition: item.condition || 'used',
          is_featured: item.is_featured || false,
          features: item.features || [],
          safety_features: item.safety_features || [],
          images: item.images || []
        });
      } else {
        setCarForm({
          brand_id: '',
          category_id: '',
          model: '',
          year: '',
          color: '',
          description: '',
          engine_type: '',
          fuel_type: 'petrol',
          transmission: 'automatic',
          mileage: '',
          drive_type: 'fwd',
          doors: 4,
          seats: 5,
          price: '',
          location_country: '',
          location_city: '',
          condition: 'used',
          is_featured: false,
          features: [],
          safety_features: [],
          images: []
        });
      }
    } else if (type === 'brand') {
      if (item) {
        setBrandForm({
          name: item.name || '',
          country_of_origin: item.country_of_origin || '',
          description: item.description || ''
        });
      } else {
        setBrandForm({
          name: '',
          country_of_origin: '',
          description: ''
        });
      }
    } else if (type === 'category') {
      if (item) {
        setCategoryForm({
          name: item.name || '',
          description: item.description || ''
        });
      } else {
        setCategoryForm({
          name: '',
          description: ''
        });
      }
    }
    
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
    setModalType('');
    setEditingItem(null);
  };

  const handleSave = async () => {
    try {
      showAlert.loading('Saving...', 'Please wait while we save your changes.');
      
      let url, method, body, headers;
      
      if (modalType === 'car') {
        url = editingItem 
          ? `http://localhost:8000/api/admin/inventory/cars/${editingItem.id}`
          : 'http://localhost:8000/api/admin/inventory/cars';
        method = editingItem ? 'PUT' : 'POST';
        
        // Prepare car data without images
        const carData = {...carForm};
        delete carData.images;
        
        body = JSON.stringify(carData);
        headers = {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
        };
      } else if (modalType === 'brand') {
        url = editingItem 
          ? `http://localhost:8000/api/admin/inventory/brands/${editingItem.id}`
          : 'http://localhost:8000/api/admin/inventory/brands';
        method = editingItem ? 'PUT' : 'POST';
        body = JSON.stringify(brandForm);
        headers = {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
        };
      } else if (modalType === 'category') {
        url = editingItem 
          ? `http://localhost:8000/api/admin/inventory/categories/${editingItem.id}`
          : 'http://localhost:8000/api/admin/inventory/categories';
        method = editingItem ? 'PUT' : 'POST';
        body = JSON.stringify(categoryForm);
        headers = {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`
        };
      }

      const response = await fetch(url, {
        method,
        headers,
        body
      });

      const data = await response.json();
      
      showAlert.close();
      
      if (data.success) {
        // If it's a car and there are images to upload
        if (modalType === 'car' && carForm.images.length > 0 && data.data?.id) {
          showAlert.loading('Uploading images...', 'Please wait while we upload the images.');
          
          try {
            // Upload each image
            for (let i = 0; i < carForm.images.length; i++) {
              const img = carForm.images[i];
              if (img.file) {
                const formData = new FormData();
                formData.append('image', img.file);
                formData.append('alt_text', img.alt_text || `${carForm.model} image ${i + 1}`);
                formData.append('is_primary', img.is_primary ? '1' : '0');
                
                await fetch(`http://localhost:8000/api/admin/inventory/cars/${data.data.id}/images`, {
                  method: 'POST',
                  headers: {
                    'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
                    'Accept': 'application/json'
                  },
                  body: formData
                });
              }
            }
            showAlert.close();
          } catch (imageError) {
            console.error('Error uploading images:', imageError);
            showAlert.warning('Partial Success', 'Car created but some images failed to upload.');
          }
        }
        
        closeModal();
        fetchData();
        showAlert.success(
          'Success!', 
          `${modalType.charAt(0).toUpperCase() + modalType.slice(1)} ${editingItem ? 'updated' : 'created'} successfully!`
        );
      } else {
        // Handle validation errors
        let errorMessage = data.message || 'An error occurred while saving.';
        
        if (data.errors && typeof data.errors === 'object') {
          // Extract all validation error messages
          const errorMessages = Object.values(data.errors)
            .flat()
            .join('\n');
          
          if (errorMessages) {
            errorMessage = errorMessages;
          }
        }
        
        showAlert.error('Error', errorMessage);
      }
    } catch (error) {
      showAlert.close();
      console.error('Error saving:', error);
      showAlert.error('Error', 'An unexpected error occurred while saving.');
    }
  };

  const handleDelete = async (type, id, itemName = '') => {
    const confirmed = await showConfirm(
      'Delete Confirmation',
      `Are you sure you want to delete this ${type}${itemName ? ` "${itemName}"` : ''}? This action cannot be undone.`,
      'warning',
      'Yes, Delete',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      showAlert.loading('Deleting...', 'Please wait while we delete the item.');
      
      // Properly pluralize the type for the URL
      const pluralType = type === 'category' ? 'categories' : `${type}s`;
      const url = `http://localhost:8000/api/admin/inventory/${pluralType}/${id}`;
      
      const response = await fetch(url, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'Accept': 'application/json'
        }
      });

      const data = await response.json();
      
      showAlert.close();
      
      if (data.success) {
        fetchData();
        showAlert.success('Deleted!', `${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`);
      } else {
        showAlert.error('Error', data.message || 'An error occurred while deleting.');
      }
    } catch (error) {
      showAlert.close();
      console.error('Error deleting:', error);
      showAlert.error('Error', 'An unexpected error occurred while deleting.');
    }
  };

  const filteredData = () => {
    let data = [];
    if (activeTab === 'cars') data = cars;
    else if (activeTab === 'brands') data = brands;
    else if (activeTab === 'categories') data = categories;

    if (!searchTerm) return data;

    return data.filter(item => 
      item.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.model?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.brand?.name?.toLowerCase().includes(searchTerm.toLowerCase())
    );
  };

  const getDropdownItems = (item) => {
    const itemType = activeTab === 'categories' ? 'category' : 
                    activeTab === 'brands' ? 'brand' : 
                    activeTab === 'cars' ? 'car' : activeTab.slice(0, -1);
    
    const baseItems = [
      {
        label: 'View Details',
        icon: <FaEye className="w-4 h-4" />,
        onClick: () => handleView(item)
      },
      {
        label: 'Edit',
        icon: <FaEdit className="w-4 h-4" />,
        onClick: () => openModal(itemType, item)
      }
    ];

    // Add specific actions based on type
    if (activeTab === 'cars') {
      baseItems.push({
        label: 'Manage Images',
        icon: <FaImage className="w-4 h-4" />,
        onClick: () => handleManageImages(item)
      });
    }

    baseItems.push({
      label: 'Delete',
      icon: <FaTrash className="w-4 h-4" />,
      onClick: () => handleDelete(itemType, item.id, item.name || item.model),
      danger: true
    });

    return baseItems;
  };

  const handleDropdownAction = (action) => {
    // Actions are handled by individual onClick functions
    console.log('Dropdown action:', action);
  };

  const handleView = (item) => {
    setViewingItem(item);
    setShowViewModal(true);
  };

  const handleManageImages = (item) => {
    setManagingImagesItem(item);
    setShowImageModal(true);
  };

  const closeViewModal = () => {
    setShowViewModal(false);
    setViewingItem(null);
  };

  const closeImageModal = () => {
    setShowImageModal(false);
    setManagingImagesItem(null);
    setImageUpload({
      file: null,
      alt_text: '',
      is_primary: false,
      uploading: false
    });
  };

  const handleImageUpload = async () => {
    if (!imageUpload.file || !managingImagesItem) return;

    try {
      setImageUpload(prev => ({ ...prev, uploading: true }));
      showAlert.loading('Uploading Image...', 'Please wait while we upload your image.');

      const formData = new FormData();
      formData.append('image', imageUpload.file);
      formData.append('alt_text', imageUpload.alt_text);
      formData.append('is_primary', imageUpload.is_primary);

      const response = await fetch(`http://localhost:8000/api/admin/inventory/cars/${managingImagesItem.id}/images`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json();
      showAlert.close();

      if (data.success) {
        // Refresh the car data to get updated images
        await fetchCars();
        // Update the managingImagesItem with new images
        const updatedCar = cars.find(car => car.id === managingImagesItem.id);
        if (updatedCar) {
          setManagingImagesItem(updatedCar);
        }
        
        setImageUpload({
          file: null,
          alt_text: '',
          is_primary: false,
          uploading: false
        });
        
        showAlert.success('Success!', 'Image uploaded successfully!');
      } else {
        showAlert.error('Error', data.message || 'Failed to upload image.');
      }
    } catch (error) {
      showAlert.close();
      console.error('Error uploading image:', error);
      showAlert.error('Error', 'An unexpected error occurred while uploading the image.');
    } finally {
      setImageUpload(prev => ({ ...prev, uploading: false }));
    }
  };

  const handleImageDelete = async (imageId) => {
    const confirmed = await showConfirm(
      'Delete Image',
      'Are you sure you want to delete this image? This action cannot be undone.',
      'warning',
      'Yes, Delete',
      'Cancel'
    );

    if (!confirmed) return;

    try {
      showAlert.loading('Deleting Image...', 'Please wait while we delete the image.');

      const response = await fetch(`http://localhost:8000/api/admin/inventory/images/${imageId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'Accept': 'application/json'
        }
      });

      const data = await response.json();
      showAlert.close();

      if (data.success) {
        // Refresh the car data to get updated images
        await fetchCars();
        // Update the managingImagesItem with new images
        const updatedCar = cars.find(car => car.id === managingImagesItem.id);
        if (updatedCar) {
          setManagingImagesItem(updatedCar);
        }
        
        showAlert.success('Deleted!', 'Image deleted successfully!');
      } else {
        showAlert.error('Error', data.message || 'Failed to delete image.');
      }
    } catch (error) {
      showAlert.close();
      console.error('Error deleting image:', error);
      showAlert.error('Error', 'An unexpected error occurred while deleting the image.');
    }
  };

  const renderCarModal = () => (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-gray-800 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-700">
        <div className="p-6 border-b border-gray-700">
          <div className="flex items-center justify-between">
            <h2 className="text-2xl font-bold text-white">
              {editingItem ? 'Edit Car' : 'Add New Car'}
            </h2>
            <button onClick={closeModal} className="text-gray-400 hover:text-gray-300">
              <FaTimes className="text-xl" />
            </button>
          </div>
        </div>

        <div className="p-6 space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Basic Information */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Brand</label>
              <select
                value={carForm.brand_id}
                onChange={(e) => setCarForm({...carForm, brand_id: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white"
              >
                <option value="">Select Brand ({brands.length} available)</option>
                {brands.map(brand => (
                  <option key={brand.id} value={brand.id}>{brand.name}</option>
                ))}
              </select>
              {brands.length === 0 && (
                <p className="text-xs text-red-400 mt-1">No brands loaded. Check console for errors.</p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Category</label>
              <select
                value={carForm.category_id}
                onChange={(e) => setCarForm({...carForm, category_id: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white"
              >
                <option value="">Select Category ({categories.length} available)</option>
                {categories.map(category => (
                  <option key={category.id} value={category.id}>{category.name}</option>
                ))}
              </select>
              {categories.length === 0 && (
                <p className="text-xs text-red-400 mt-1">No categories loaded. Check console for errors.</p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Model</label>
              <input
                type="text"
                value={carForm.model}
                onChange={(e) => setCarForm({...carForm, model: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="e.g., Camry, X5, Civic"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Year</label>
              <input
                type="number"
                value={carForm.year}
                onChange={(e) => setCarForm({...carForm, year: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="2020"
                min="1990"
                max="2030"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Color</label>
              <input
                type="text"
                value={carForm.color}
                onChange={(e) => setCarForm({...carForm, color: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="White, Black, Silver"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Price (USD)</label>
              <input
                type="number"
                value={carForm.price}
                onChange={(e) => setCarForm({...carForm, price: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="25000"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Fuel Type</label>
              <select
                value={carForm.fuel_type}
                onChange={(e) => setCarForm({...carForm, fuel_type: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white"
              >
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="hybrid">Hybrid</option>
                <option value="electric">Electric</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Transmission</label>
              <select
                value={carForm.transmission}
                onChange={(e) => setCarForm({...carForm, transmission: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white"
              >
                <option value="automatic">Automatic</option>
                <option value="manual">Manual</option>
                <option value="cvt">CVT</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Condition</label>
              <select
                value={carForm.condition}
                onChange={(e) => setCarForm({...carForm, condition: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white"
              >
                <option value="new">New</option>
                <option value="used">Used</option>
                <option value="certified_pre_owned">Certified Pre-Owned</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Mileage (km)</label>
              <input
                type="number"
                value={carForm.mileage}
                onChange={(e) => setCarForm({...carForm, mileage: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="50000"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Location Country</label>
              <input
                type="text"
                value={carForm.location_country}
                onChange={(e) => setCarForm({...carForm, location_country: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="Japan, Germany, USA"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Location City</label>
              <input
                type="text"
                value={carForm.location_city}
                onChange={(e) => setCarForm({...carForm, location_city: e.target.value})}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                placeholder="Tokyo, Munich, Detroit"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Description</label>
            <textarea
              value={carForm.description}
              onChange={(e) => setCarForm({...carForm, description: e.target.value})}
              rows={4}
              className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
              placeholder="Detailed description of the vehicle..."
            />
          </div>

          {/* Image Upload Section */}
          <div className="border-t border-gray-700 pt-6">
            <label className="block text-sm font-medium text-gray-300 mb-3">Vehicle Images</label>
            
            {/* Image Upload Input */}
            <div className="mb-4">
              <div className="flex items-center gap-4">
                <label className="flex-1 cursor-pointer">
                  <div className="border-2 border-dashed border-gray-600 rounded-lg p-6 hover:border-blue-500 transition-colors">
                    <input
                      type="file"
                      accept="image/*"
                      multiple
                      onChange={(e) => {
                        const files = Array.from(e.target.files || []);
                        if (files.length > 0) {
                          const newImages = files.map(file => ({
                            file,
                            preview: URL.createObjectURL(file),
                            alt_text: '',
                            is_primary: carForm.images.length === 0
                          }));
                          setCarForm({
                            ...carForm,
                            images: [...carForm.images, ...newImages]
                          });
                        }
                      }}
                      className="hidden"
                    />
                    <div className="text-center">
                      <FaImage className="mx-auto text-4xl text-gray-400 mb-2" />
                      <p className="text-sm text-gray-400">
                        Click to upload images or drag and drop
                      </p>
                      <p className="text-xs text-gray-500 mt-1">
                        PNG, JPG, GIF up to 2MB each
                      </p>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            {/* Image Preview Grid */}
            {carForm.images.length > 0 && (
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {carForm.images.map((img, index) => (
                  <div key={index} className="relative group">
                    <img
                      src={img.preview || img.image_url}
                      alt={img.alt_text || `Car image ${index + 1}`}
                      className="w-full h-32 object-cover rounded-lg border border-gray-600"
                    />
                    <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          const newImages = [...carForm.images];
                          newImages[index].is_primary = true;
                          newImages.forEach((img, i) => {
                            if (i !== index) img.is_primary = false;
                          });
                          setCarForm({...carForm, images: newImages});
                        }}
                        className={`p-2 rounded-lg ${
                          img.is_primary 
                            ? 'bg-green-600 text-white' 
                            : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                        }`}
                        title="Set as primary"
                      >
                        <FaStar />
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          const newImages = carForm.images.filter((_, i) => i !== index);
                          if (img.preview) URL.revokeObjectURL(img.preview);
                          setCarForm({...carForm, images: newImages});
                        }}
                        className="p-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        title="Remove image"
                      >
                        <FaTrash />
                      </button>
                    </div>
                    {img.is_primary && (
                      <div className="absolute top-2 left-2 bg-green-600 text-white text-xs px-2 py-1 rounded">
                        Primary
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="flex items-center">
            <input
              type="checkbox"
              id="is_featured"
              checked={carForm.is_featured}
              onChange={(e) => setCarForm({...carForm, is_featured: e.target.checked})}
              className="mr-2 rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500"
            />
            <label htmlFor="is_featured" className="text-sm font-medium text-gray-300">
              Featured Vehicle
            </label>
          </div>
        </div>

        <div className="p-6 border-t border-gray-700 flex justify-end gap-3">
          <button
            onClick={closeModal}
            className="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handleSave}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
          >
            <FaSave />
            {editingItem ? 'Update' : 'Create'} Car
          </button>
        </div>
      </div>
    </div>
  );

  const renderBrandModal = () => (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-gray-800 rounded-2xl max-w-md w-full border border-gray-700">
        <div className="p-6 border-b border-gray-700">
          <div className="flex items-center justify-between">
            <h2 className="text-2xl font-bold text-white">
              {editingItem ? 'Edit Brand' : 'Add New Brand'}
            </h2>
            <button onClick={closeModal} className="text-gray-400 hover:text-gray-300">
              <FaTimes className="text-xl" />
            </button>
          </div>
        </div>

        <div className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Brand Name</label>
            <input
              type="text"
              value={brandForm.name}
              onChange={(e) => setBrandForm({...brandForm, name: e.target.value})}
              className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
              placeholder="Toyota, BMW, Honda"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Country of Origin</label>
            <input
              type="text"
              value={brandForm.country_of_origin}
              onChange={(e) => setBrandForm({...brandForm, country_of_origin: e.target.value})}
              className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
              placeholder="Japan, Germany, USA"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Description</label>
            <textarea
              value={brandForm.description}
              onChange={(e) => setBrandForm({...brandForm, description: e.target.value})}
              rows={3}
              className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
              placeholder="Brief description of the brand..."
            />
          </div>
        </div>

        <div className="p-6 border-t border-gray-700 flex justify-end gap-3">
          <button
            onClick={closeModal}
            className="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handleSave}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
          >
            <FaSave />
            {editingItem ? 'Update' : 'Create'} Brand
          </button>
        </div>
      </div>
    </div>
  );

  const renderViewModal = () => {
    if (!viewingItem) return null;

    return (
      <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div className="bg-gray-800 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-700">
          <div className="p-6 border-b border-gray-700">
            <div className="flex items-center justify-between">
              <h2 className="text-2xl font-bold text-white">
                {activeTab === 'cars' ? `${viewingItem.brand?.name} ${viewingItem.model}` : viewingItem.name}
              </h2>
              <button onClick={closeViewModal} className="text-gray-400 hover:text-gray-300">
                <FaTimes className="text-xl" />
              </button>
            </div>
          </div>

          <div className="p-6">
            {activeTab === 'cars' && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {/* Car Images */}
                <div>
                  <h3 className="text-lg font-semibold text-white mb-4">Images</h3>
                  {viewingItem.images && viewingItem.images.length > 0 ? (
                    <div className="grid grid-cols-2 gap-4">
                      {viewingItem.images.map((image, index) => (
                        <div key={index} className="relative">
                          <img
                            src={image.image_url}
                            alt={image.alt_text || `${viewingItem.model} image`}
                            className="w-full h-32 object-cover rounded-lg border border-gray-600"
                          />
                          {image.is_primary && (
                            <span className="absolute top-2 left-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">
                              Primary
                            </span>
                          )}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="bg-gray-700 rounded-lg p-8 text-center">
                      <FaCar className="mx-auto text-4xl text-gray-500 mb-2" />
                      <p className="text-gray-400">No images available</p>
                    </div>
                  )}
                </div>

                {/* Car Details */}
                <div className="space-y-6">
                  <div>
                    <h3 className="text-lg font-semibold text-white mb-4">Vehicle Information</h3>
                    <div className="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <span className="text-gray-400">Brand:</span>
                        <p className="text-white font-medium">{viewingItem.brand?.name}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Category:</span>
                        <p className="text-white font-medium">{viewingItem.category?.name}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Year:</span>
                        <p className="text-white font-medium">{viewingItem.year}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Color:</span>
                        <p className="text-white font-medium">{viewingItem.color}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Price:</span>
                        <p className="text-white font-medium">${viewingItem.price?.toLocaleString()}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Condition:</span>
                        <p className="text-white font-medium capitalize">{viewingItem.condition?.replace('_', ' ')}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Fuel Type:</span>
                        <p className="text-white font-medium capitalize">{viewingItem.fuel_type}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Transmission:</span>
                        <p className="text-white font-medium capitalize">{viewingItem.transmission}</p>
                      </div>
                      {viewingItem.mileage && (
                        <div>
                          <span className="text-gray-400">Mileage:</span>
                          <p className="text-white font-medium">{viewingItem.mileage?.toLocaleString()} km</p>
                        </div>
                      )}
                      {viewingItem.engine_type && (
                        <div>
                          <span className="text-gray-400">Engine:</span>
                          <p className="text-white font-medium">{viewingItem.engine_type}</p>
                        </div>
                      )}
                      <div>
                        <span className="text-gray-400">Doors:</span>
                        <p className="text-white font-medium">{viewingItem.doors}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Seats:</span>
                        <p className="text-white font-medium">{viewingItem.seats}</p>
                      </div>
                    </div>
                  </div>

                  <div>
                    <h3 className="text-lg font-semibold text-white mb-4">Location</h3>
                    <div className="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <span className="text-gray-400">Country:</span>
                        <p className="text-white font-medium">{viewingItem.location_country}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">City:</span>
                        <p className="text-white font-medium">{viewingItem.location_city}</p>
                      </div>
                    </div>
                  </div>

                  {viewingItem.description && (
                    <div>
                      <h3 className="text-lg font-semibold text-white mb-4">Description</h3>
                      <p className="text-gray-300 text-sm leading-relaxed">{viewingItem.description}</p>
                    </div>
                  )}

                  <div>
                    <h3 className="text-lg font-semibold text-white mb-4">Status</h3>
                    <div className="flex items-center gap-4">
                      <span className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${
                        viewingItem.is_featured ? 'bg-yellow-900/30 text-yellow-300' : 'bg-gray-700 text-gray-300'
                      }`}>
                        {viewingItem.is_featured ? 'Featured' : 'Regular'}
                      </span>
                      <span className="text-gray-400 text-sm">
                        Created: {new Date(viewingItem.created_at).toLocaleDateString()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'brands' && (
              <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h3 className="text-lg font-semibold text-white mb-4">Brand Information</h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Name:</span>
                        <p className="text-white font-medium text-lg">{viewingItem.name}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Country of Origin:</span>
                        <p className="text-white font-medium">{viewingItem.country_of_origin}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Active Cars:</span>
                        <p className="text-white font-medium">{viewingItem.active_cars_count || 0}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Created:</span>
                        <p className="text-white font-medium">{new Date(viewingItem.created_at).toLocaleDateString()}</p>
                      </div>
                    </div>
                  </div>
                  {viewingItem.description && (
                    <div>
                      <h3 className="text-lg font-semibold text-white mb-4">Description</h3>
                      <p className="text-gray-300 leading-relaxed">{viewingItem.description}</p>
                    </div>
                  )}
                </div>
              </div>
            )}

            {activeTab === 'categories' && (
              <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h3 className="text-lg font-semibold text-white mb-4">Category Information</h3>
                    <div className="space-y-3">
                      <div>
                        <span className="text-gray-400">Name:</span>
                        <p className="text-white font-medium text-lg">{viewingItem.name}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Active Cars:</span>
                        <p className="text-white font-medium">{viewingItem.active_cars_count || 0}</p>
                      </div>
                      <div>
                        <span className="text-gray-400">Created:</span>
                        <p className="text-white font-medium">{new Date(viewingItem.created_at).toLocaleDateString()}</p>
                      </div>
                    </div>
                  </div>
                  {viewingItem.description && (
                    <div>
                      <h3 className="text-lg font-semibold text-white mb-4">Description</h3>
                      <p className="text-gray-300 leading-relaxed">{viewingItem.description}</p>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>

          <div className="p-6 border-t border-gray-700 flex justify-end gap-3">
            <button
              onClick={() => {
                closeViewModal();
                openModal(activeTab.slice(0, -1), viewingItem);
              }}
              className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
              <FaEdit />
              Edit
            </button>
            <button
              onClick={closeViewModal}
              className="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    );
  };

  const renderImageManagementModal = () => {
    if (!managingImagesItem) return null;

    return (
      <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div className="bg-gray-800 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-700">
          <div className="p-6 border-b border-gray-700">
            <div className="flex items-center justify-between">
              <h2 className="text-2xl font-bold text-white">
                Manage Images - {managingImagesItem.brand?.name} {managingImagesItem.model}
              </h2>
              <button onClick={closeImageModal} className="text-gray-400 hover:text-gray-300">
                <FaTimes className="text-xl" />
              </button>
            </div>
          </div>

          <div className="p-6 space-y-6">
            {/* Upload New Image */}
            <div className="bg-gray-700/50 rounded-lg p-6">
              <h3 className="text-lg font-semibold text-white mb-4">Upload New Image</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">Select Image</label>
                  <input
                    type="file"
                    accept="image/*"
                    onChange={(e) => setImageUpload(prev => ({ ...prev, file: e.target.files[0] }))}
                    className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-300 mb-2">Alt Text (Optional)</label>
                  <input
                    type="text"
                    value={imageUpload.alt_text}
                    onChange={(e) => setImageUpload(prev => ({ ...prev, alt_text: e.target.value }))}
                    placeholder="Describe the image..."
                    className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
                  />
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    id="is_primary_upload"
                    checked={imageUpload.is_primary}
                    onChange={(e) => setImageUpload(prev => ({ ...prev, is_primary: e.target.checked }))}
                    className="mr-2 rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500"
                  />
                  <label htmlFor="is_primary_upload" className="text-sm font-medium text-gray-300">
                    Set as primary image
                  </label>
                </div>

                <button
                  onClick={handleImageUpload}
                  disabled={!imageUpload.file || imageUpload.uploading}
                  className="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                >
                  <FaUpload />
                  {imageUpload.uploading ? 'Uploading...' : 'Upload Image'}
                </button>
              </div>
            </div>

            {/* Existing Images */}
            <div>
              <h3 className="text-lg font-semibold text-white mb-4">
                Existing Images ({managingImagesItem.images?.length || 0})
              </h3>
              
              {managingImagesItem.images && managingImagesItem.images.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {managingImagesItem.images.map((image, index) => (
                    <div key={image.id || index} className="relative bg-gray-700 rounded-lg overflow-hidden">
                      <img
                        src={image.image_url}
                        alt={image.alt_text || `${managingImagesItem.model} image`}
                        className="w-full h-48 object-cover"
                      />
                      
                      {/* Image overlay with info and actions */}
                      <div className="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex flex-col justify-between p-3">
                        <div className="flex justify-between items-start">
                          {image.is_primary && (
                            <span className="bg-blue-600 text-white text-xs px-2 py-1 rounded">
                              Primary
                            </span>
                          )}
                          <button
                            onClick={() => handleImageDelete(image.id)}
                            className="bg-red-600 hover:bg-red-700 text-white p-2 rounded-lg transition-colors"
                          >
                            <FaTrash className="w-3 h-3" />
                          </button>
                        </div>
                        
                        <div className="text-white text-xs">
                          {image.alt_text && (
                            <p className="mb-1 truncate">{image.alt_text}</p>
                          )}
                          <p className="text-gray-300">Type: {image.type || 'exterior'}</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="bg-gray-700 rounded-lg p-8 text-center">
                  <FaImage className="mx-auto text-4xl text-gray-500 mb-2" />
                  <p className="text-gray-400">No images uploaded yet</p>
                  <p className="text-gray-500 text-sm mt-1">Upload your first image above</p>
                </div>
              )}
            </div>
          </div>

          <div className="p-6 border-t border-gray-700 flex justify-end">
            <button
              onClick={closeImageModal}
              className="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    );
  };

  const renderCategoryModal = () => (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-gray-800 rounded-2xl max-w-md w-full border border-gray-700">
        <div className="p-6 border-b border-gray-700">
          <div className="flex items-center justify-between">
            <h2 className="text-2xl font-bold text-white">
              {editingItem ? 'Edit Category' : 'Add New Category'}
            </h2>
            <button onClick={closeModal} className="text-gray-400 hover:text-gray-300">
              <FaTimes className="text-xl" />
            </button>
          </div>
        </div>

        <div className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Category Name</label>
            <input
              type="text"
              value={categoryForm.name}
              onChange={(e) => setCategoryForm({...categoryForm, name: e.target.value})}
              className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
              placeholder="SUV, Sedan, Hatchback"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Description</label>
            <textarea
              value={categoryForm.description}
              onChange={(e) => setCategoryForm({...categoryForm, description: e.target.value})}
              rows={3}
              className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-gray-400"
              placeholder="Brief description of the category..."
            />
          </div>
        </div>

        <div className="p-6 border-t border-gray-700 flex justify-end gap-3">
          <button
            onClick={closeModal}
            className="px-6 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-700 transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handleSave}
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
          >
            <FaSave />
            {editingItem ? 'Update' : 'Create'} Category
          </button>
        </div>
      </div>
    </div>
  );

  return (
    <div className="p-6 bg-[#0a0e13] min-h-screen text-white">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-white mb-2">Car Inventory Management</h1>
        <p className="text-gray-400">Manage cars, brands, and categories</p>
      </div>

      {/* Tabs */}
      <div className="mb-6">
        <div className="border-b border-gray-700">
          <nav className="flex space-x-8">
            {[
              { key: 'cars', label: 'Cars', icon: FaCar },
              { key: 'brands', label: 'Brands', icon: FaTags },
              { key: 'categories', label: 'Categories', icon: FaList }
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                  activeTab === tab.key
                    ? 'border-blue-500 text-blue-400'
                    : 'border-transparent text-gray-400 hover:text-gray-200 hover:border-gray-600'
                }`}
              >
                <tab.icon />
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Search and Add Button */}
      <div className="flex justify-between items-center mb-6">
        <div className="flex items-center gap-4">
          <div className="relative">
            <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder={`Search ${activeTab}...`}
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 pr-4 py-2 bg-gray-800 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-80 text-white placeholder-gray-400"
            />
          </div>
          
        </div>
        <button
          onClick={() => {
            const type = activeTab === 'categories' ? 'category' : 
                        activeTab === 'brands' ? 'brand' : 
                        activeTab === 'cars' ? 'car' : activeTab.slice(0, -1);
            openModal(type);
          }}
          className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
        >
          <FaPlus />
          Add {activeTab.slice(0, -1).charAt(0).toUpperCase() + activeTab.slice(1, -1)}
        </button>
      </div>

      {/* Content */}
      <div className="bg-gray-800 rounded-lg shadow-xl overflow-hidden border border-gray-700">
        {loading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-2 text-gray-400">Loading...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-700">
              <thead className="bg-gray-900">
                <tr>
                  {activeTab === 'cars' && (
                    <>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Vehicle</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Brand</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Category</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Price</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </>
                  )}
                  {activeTab === 'brands' && (
                    <>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Brand</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Country</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Cars Count</th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </>
                  )}
                  {activeTab === 'categories' && (
                    <>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Category</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Description</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Cars Count</th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </>
                  )}
                </tr>
              </thead>
              <tbody className="bg-gray-800 divide-y divide-gray-700">
                {filteredData().map((item) => (
                  <tr key={item.id} className="hover:bg-gray-700">
                    {activeTab === 'cars' && (
                      <>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            <div className="flex-shrink-0 h-10 w-10">
                              {item.images && item.images[0] ? (
                                <img className="h-10 w-10 rounded-full object-cover" src={item.images[0].image_url} alt="" />
                              ) : (
                                <div className="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center">
                                  <FaCar className="text-gray-400" />
                                </div>
                              )}
                            </div>
                            <div className="ml-4">
                              <div className="text-sm font-medium text-white">{item.model}</div>
                              <div className="text-sm text-gray-400">{item.year}</div>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{item.brand?.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{item.category?.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">${item.price?.toLocaleString()}</td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                            item.status === 'available' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300'
                          }`}>
                            {item.status}
                          </span>
                        </td>
                      </>
                    )}
                    {activeTab === 'brands' && (
                      <>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">{item.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{item.country_of_origin}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{item.active_cars_count || 0}</td>
                      </>
                    )}
                    {activeTab === 'categories' && (
                      <>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">{item.name}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{item.description}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-300">{item.active_cars_count || 0}</td>
                      </>
                    )}
                    <td className="px-6 py-4 whitespace-nowrap text-right">
                      <DropdownMenu
                        items={getDropdownItems(item)}
                        onItemClick={handleDropdownAction}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modals */}
      {showModal && modalType === 'car' && renderCarModal()}
      {showModal && modalType === 'brand' && renderBrandModal()}
      {showModal && modalType === 'category' && renderCategoryModal()}
      {showViewModal && renderViewModal()}
      {showImageModal && renderImageManagementModal()}
    </div>
  );
};

export default CarInventoryManagement;