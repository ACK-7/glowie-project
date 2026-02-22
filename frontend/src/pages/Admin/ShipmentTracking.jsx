import React, { useState, useEffect } from 'react';
import { 
  FaShip, 
  FaMapMarkerAlt, 
  FaClock, 
  FaCheckCircle, 
  FaExclamationCircle, 
  FaSpinner,
  FaSearch,
  FaEye,
  FaEdit,
  FaTrash,
  FaSyncAlt,
  FaTruck
} from 'react-icons/fa';
import { 
  getShipments, 
  getShipment, 
  getShipmentStatistics,
  getShipmentsRequiringAttention,
  updateShipmentStatus,
  deleteShipment,
  searchShipments
} from '../../services/adminService';
import DropdownMenu from '../../components/UI/DropdownMenu';
import ShipmentViewModal from '../../components/Admin/ShipmentViewModal';
import ShipmentEditModal from '../../components/Admin/ShipmentEditModal';
import { showAlert, showConfirm } from '../../utils/sweetAlert';

const ShipmentTracking = () => {
  const [shipments, setShipments] = useState([]);
  const [filteredShipments, setFilteredShipments] = useState([]);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [carrierFilter, setCarrierFilter] = useState('all');
  const [showRequiringAttention, setShowRequiringAttention] = useState(false);
  
  // Modal states
  const [selectedShipment, setSelectedShipment] = useState(null);
  const [showViewModal, setShowViewModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);

  useEffect(() => {
    fetchData();
  }, []);

  useEffect(() => {
    filterShipments();
  }, [shipments, searchTerm, statusFilter, carrierFilter, showRequiringAttention]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [shipmentsResponse, statsResponse] = await Promise.all([
        getShipments(),
        getShipmentStatistics().catch(() => null)
      ]);
      
      const shipmentsData = shipmentsResponse.data || shipmentsResponse || [];
      setShipments(shipmentsData);
      setStatistics(statsResponse?.data || null);
      setError(null);
    } catch (err) {
      console.error('Failed to fetch tracking data:', err);
      setError('Failed to load tracking data. Please ensure you are logged in as Admin.');
      setShipments([]);
    } finally {
      setLoading(false);
    }
  };

  const filterShipments = async () => {
    let filtered = [...shipments];

    // Handle requiring attention filter
    if (showRequiringAttention) {
      try {
        const attentionResponse = await getShipmentsRequiringAttention();
        const attentionData = attentionResponse.data || attentionResponse || [];
        filtered = attentionData;
      } catch (err) {
        console.error('Failed to fetch shipments requiring attention:', err);
      }
    }

    // Search filter
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(shipment => 
        (shipment.tracking_number || '').toLowerCase().includes(term) ||
        (shipment.booking?.customer?.name || '').toLowerCase().includes(term) ||
        (shipment.booking?.vehicle?.make || '').toLowerCase().includes(term) ||
        (shipment.booking?.vehicle?.model || '').toLowerCase().includes(term) ||
        (shipment.carrier_name || '').toLowerCase().includes(term) ||
        (shipment.current_location || '').toLowerCase().includes(term)
      );
    }

    // Status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(shipment => shipment.status === statusFilter);
    }

    // Carrier filter
    if (carrierFilter !== 'all') {
      filtered = filtered.filter(shipment => shipment.carrier_name === carrierFilter);
    }

    setFilteredShipments(filtered);
  };

  const handleSearch = async (term) => {
    if (term.length >= 2) {
      try {
        const response = await searchShipments(term);
        const searchResults = response.data || response || [];
        setFilteredShipments(searchResults);
      } catch (err) {
        console.error('Search failed:', err);
      }
    } else {
      filterShipments();
    }
  };

  const handleView = (shipment) => {
    setSelectedShipment(shipment);
    setShowViewModal(true);
  };

  const handleEdit = (shipment) => {
    setSelectedShipment(shipment);
    setShowEditModal(true);
  };

  const handleDelete = async (shipment) => {
    const confirmed = await showConfirm(
      'Delete Shipment',
      `Are you sure you want to delete shipment ${shipment.tracking_number}?`,
      'warning'
    );

    if (confirmed) {
      try {
        await deleteShipment(shipment.id);
        await showAlert('Success', 'Shipment deleted successfully', 'success');
        fetchData();
      } catch (error) {
        console.error('Delete failed:', error);
        await showAlert('Error', 'Failed to delete shipment', 'error');
      }
    }
  };

  const handleStatusUpdate = async (shipment, newStatus) => {
    try {
      await updateShipmentStatus(shipment.id, newStatus);
      await showAlert('Success', 'Shipment status updated successfully', 'success');
      fetchData();
    } catch (error) {
      console.error('Status update failed:', error);
      await showAlert('Error', 'Failed to update shipment status', 'error');
    }
  };

  const getStatusBadge = (status) => {
    const config = {
      pending: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Pending', icon: FaClock },
      confirmed: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'Confirmed', icon: FaCheckCircle },
      in_transit: { bg: 'bg-purple-900/30', text: 'text-purple-400', label: 'In Transit', icon: FaTruck },
      on_route: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'On Route', icon: FaShip },
      customs: { bg: 'bg-orange-900/30', text: 'text-orange-400', label: 'At Customs', icon: FaAnchor },
      delivered: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Delivered', icon: FaCheckCircle },
      delayed: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Delayed', icon: FaExclamationCircle }
    };
    const c = config[status] || config.pending;
    const IconComponent = c.icon;
    return (
      <span className={`${c.bg} ${c.text} px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1`}>
        <IconComponent className="text-xs" />
        {c.label}
      </span>
    );
  };

  const getProgressColor = (percentage) => {
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 50) return 'bg-blue-500';
    if (percentage >= 25) return 'bg-yellow-500';
    return 'bg-red-500';
  };

  const uniqueCarriers = [...new Set(shipments.map(s => s.carrier_name).filter(Boolean))];

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <FaSpinner className="animate-spin text-blue-500 text-4xl" />
      </div>
    );
  }

  return (
    <div className="max-w-[calc(100vw-2rem)] mx-auto">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-white mb-2">Shipment Tracking</h1>
        <p className="text-gray-400">Real-time tracking and management of all shipments</p>
      </div>

      {/* Statistics Cards */}
      {statistics && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Total Shipments</p>
                <p className="text-2xl font-bold text-white">{statistics.total_shipments || 0}</p>
              </div>
              <FaShip className="text-blue-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">In Transit</p>
                <p className="text-2xl font-bold text-white">{statistics.in_transit || 0}</p>
              </div>
              <FaTruck className="text-purple-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Delivered</p>
                <p className="text-2xl font-bold text-white">{statistics.delivered || 0}</p>
              </div>
              <FaCheckCircle className="text-green-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Requiring Attention</p>
                <p className="text-2xl font-bold text-white">{statistics.requires_attention || 0}</p>
              </div>
              <FaExclamationCircle className="text-red-500 text-2xl" />
            </div>
          </div>
        </div>
      )}

      {/* Filters and Search */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6 mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search by tracking number, customer, vehicle, or carrier..."
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value);
                  handleSearch(e.target.value);
                }}
                className="w-full pl-10 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>

          {/* Filters */}
          <div className="flex flex-wrap gap-4">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="in_transit">In Transit</option>
              <option value="on_route">On Route</option>
              <option value="customs">At Customs</option>
              <option value="delivered">Delivered</option>
              <option value="delayed">Delayed</option>
            </select>

            <select
              value={carrierFilter}
              onChange={(e) => setCarrierFilter(e.target.value)}
              className="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="all">All Carriers</option>
              {uniqueCarriers.map(carrier => (
                <option key={carrier} value={carrier}>{carrier}</option>
              ))}
            </select>

            <button
              onClick={() => setShowRequiringAttention(!showRequiringAttention)}
              className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                showRequiringAttention
                  ? 'bg-red-600 text-white'
                  : 'bg-gray-800 text-gray-300 hover:bg-gray-700'
              }`}
            >
              <FaExclamationCircle className="inline mr-2" />
              Requires Attention
            </button>

            <button
              onClick={fetchData}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              <FaSyncAlt className="inline mr-2" />
              Refresh
            </button>
          </div>
        </div>
      </div>

      {error ? (
        <div className="bg-red-900/20 border border-red-700/50 rounded-xl p-6 text-center">
          <p className="text-red-400">{error}</p>
        </div>
      ) : (
        <div className="bg-[#1a1f28] border border-gray-800 rounded-xl overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-800/50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Tracking Info
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Customer & Vehicle
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Route
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Progress
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    ETA
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-800">
                {filteredShipments.length === 0 ? (
                  <tr>
                    <td colSpan="7" className="px-6 py-12 text-center text-gray-400">
                      No shipments found
                    </td>
                  </tr>
                ) : (
                  filteredShipments.map((shipment) => (
                    <tr key={shipment.id} className="hover:bg-gray-800/30 transition-colors">
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-blue-400 font-semibold">
                            {shipment.tracking_number || `SWG-${shipment.id}`}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {shipment.carrier_name || 'N/A'}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {shipment.booking?.customer?.name || 'N/A'}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {shipment.booking?.vehicle 
                              ? `${shipment.booking.vehicle.make} ${shipment.booking.vehicle.model} ${shipment.booking.vehicle.year}`
                              : 'N/A'}
                          </p>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2 text-sm">
                          <span className="text-gray-300">{shipment.departure_port || 'N/A'}</span>
                          <span className="text-gray-500">→</span>
                          <span className="text-gray-300">{shipment.arrival_port || 'N/A'}</span>
                        </div>
                        <p className="text-gray-400 text-xs mt-1">
                          Current: {shipment.current_location || 'N/A'}
                        </p>
                      </td>
                      <td className="px-6 py-4">
                        <div className="w-full">
                          <div className="flex items-center justify-between text-sm mb-1">
                            <span className="text-gray-400">Progress</span>
                            <span className="text-white font-semibold">
                              {shipment.progress_percentage || 0}%
                            </span>
                          </div>
                          <div className="w-full bg-gray-700 rounded-full h-2">
                            <div
                              className={`h-2 rounded-full transition-all ${getProgressColor(shipment.progress_percentage || 0)}`}
                              style={{ width: `${shipment.progress_percentage || 0}%` }}
                            ></div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        {getStatusBadge(shipment.status)}
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white text-sm">
                            {shipment.estimated_arrival 
                              ? new Date(shipment.estimated_arrival).toLocaleDateString()
                              : 'N/A'}
                          </p>
                          {shipment.is_delayed && (
                            <p className="text-red-400 text-xs">
                              Delayed by {shipment.days_delayed || 0} days
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <DropdownMenu
                          trigger={
                            <button className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors text-lg font-bold">
                              ⋮
                            </button>
                          }
                          items={[
                            {
                              label: 'View Details',
                              icon: FaEye,
                              onClick: () => handleView(shipment)
                            },
                            {
                              label: 'Edit Shipment',
                              icon: FaEdit,
                              onClick: () => handleEdit(shipment)
                            },
                            {
                              label: 'Update Status',
                              icon: FaCheckCircle,
                              submenu: [
                                { label: 'Confirmed', onClick: () => handleStatusUpdate(shipment, 'confirmed') },
                                { label: 'In Transit', onClick: () => handleStatusUpdate(shipment, 'in_transit') },
                                { label: 'On Route', onClick: () => handleStatusUpdate(shipment, 'on_route') },
                                { label: 'At Customs', onClick: () => handleStatusUpdate(shipment, 'customs') },
                                { label: 'Delivered', onClick: () => handleStatusUpdate(shipment, 'delivered') }
                              ]
                            },
                            {
                              label: 'Delete',
                              icon: FaTrash,
                              onClick: () => handleDelete(shipment),
                              className: 'text-red-400 hover:text-red-300'
                            }
                          ]}
                        />
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Modals */}
      {showViewModal && selectedShipment && (
        <ShipmentViewModal
          shipment={selectedShipment}
          onClose={() => {
            setShowViewModal(false);
            setSelectedShipment(null);
          }}
        />
      )}

      {showEditModal && selectedShipment && (
        <ShipmentEditModal
          shipment={selectedShipment}
          onClose={() => {
            setShowEditModal(false);
            setSelectedShipment(null);
          }}
          onSave={() => {
            setShowEditModal(false);
            setSelectedShipment(null);
            fetchData();
          }}
        />
      )}
    </div>
  );
};

export default ShipmentTracking;
