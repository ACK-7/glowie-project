import React, { useState, useEffect } from 'react';
import { 
  FaShip, 
  FaMapMarkerAlt, 
  FaClock, 
  FaCheckCircle, 
  FaTruck,
  FaExclamationTriangle,
  FaEye,
  FaEdit,
  FaSearch,
  FaFilter,
  FaSyncAlt,
  FaChevronLeft,
  FaChevronRight,
  FaCalendarAlt,
  FaRoute
} from 'react-icons/fa';
import { 
  getShipments, 
  updateShipmentStatus,
  updateEstimatedArrival,
  getShipmentStatistics
} from '../../services/adminService';
import { showAlert, showConfirm } from '../../utils/sweetAlert';
import ShipmentViewModal from '../../components/Admin/ShipmentViewModal';
import ShipmentEditModal from '../../components/Admin/ShipmentEditModal';

const ShipmentManagement = () => {
  const [shipments, setShipments] = useState([]);
  const [filteredShipments, setFilteredShipments] = useState([]);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [carrierFilter, setCarrierFilter] = useState('all');

  // Modal states
  const [showViewModal, setShowViewModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedShipment, setSelectedShipment] = useState(null);

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalShipments, setTotalShipments] = useState(0);
  const [perPage, setPerPage] = useState(15);
  const [paginationInfo, setPaginationInfo] = useState(null);

  useEffect(() => {
    fetchData();
  }, [currentPage, perPage]);

  useEffect(() => {
    filterShipments();
  }, [shipments, searchTerm, statusFilter, carrierFilter]);

  const fetchData = async () => {
    try {
      setLoading(true);
      console.log('Fetching shipments data...', { currentPage, perPage });
      
      const params = {
        page: currentPage,
        per_page: perPage,
        with: 'booking,booking.customer,booking.vehicle,booking.route'
      };

      if (statusFilter !== 'all') {
        params.status = statusFilter;
      }

      const [shipmentsResponse, statsResponse] = await Promise.all([
        getShipments(params),
        getShipmentStatistics().catch(() => null)
      ]);
      
      console.log('Raw shipments response:', shipmentsResponse);
      
      // Handle response structure
      let shipmentsData = [];
      let paginationData = null;
      
      if (shipmentsResponse?.data) {
        if (shipmentsResponse.success && Array.isArray(shipmentsResponse.data)) {
          shipmentsData = shipmentsResponse.data;
          if (shipmentsResponse.meta?.pagination) {
            paginationData = shipmentsResponse.meta.pagination;
          }
        } else if (shipmentsResponse.data.data && Array.isArray(shipmentsResponse.data.data)) {
          shipmentsData = shipmentsResponse.data.data;
          paginationData = {
            current_page: shipmentsResponse.data.current_page,
            last_page: shipmentsResponse.data.last_page,
            per_page: shipmentsResponse.data.per_page,
            total: shipmentsResponse.data.total,
            from: shipmentsResponse.data.from,
            to: shipmentsResponse.data.to
          };
        } else if (Array.isArray(shipmentsResponse.data)) {
          shipmentsData = shipmentsResponse.data;
        }
      } else if (Array.isArray(shipmentsResponse)) {
        shipmentsData = shipmentsResponse;
      }
      
      console.log('Processed shipments data:', shipmentsData);
      
      const safeShipmentsData = Array.isArray(shipmentsData) ? shipmentsData : [];
      
      setShipments(safeShipmentsData);
      
      // Update pagination state
      if (paginationData) {
        setCurrentPage(paginationData.current_page);
        setTotalPages(paginationData.last_page);
        setTotalShipments(paginationData.total);
        setPerPage(paginationData.per_page);
        setPaginationInfo(paginationData);
      } else {
        setTotalShipments(shipmentsData.length);
        setTotalPages(1);
        setPaginationInfo(null);
      }
      
      setStatistics(statsResponse?.data || null);
      setError(null);
    } catch (err) {
      console.error('Failed to fetch shipments data:', err);
      setError('Failed to load shipments. Please ensure you are logged in as Admin.');
      setShipments([]);
      setTotalShipments(0);
      setTotalPages(1);
      setPaginationInfo(null);
    } finally {
      setLoading(false);
    }
  };

  const filterShipments = () => {
    let filtered = [...shipments];

    // Search filter
    if (searchTerm) {
      filtered = filtered.filter(shipment =>
        shipment.tracking_number?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        shipment.carrier_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        shipment.vessel_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        shipment.container_number?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        shipment.current_location?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        shipment.booking?.customer?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        shipment.booking?.customer?.email?.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter(shipment => shipment.status === statusFilter);
    }

    // Carrier filter
    if (carrierFilter !== 'all') {
      filtered = filtered.filter(shipment => 
        shipment.carrier_name?.toLowerCase().includes(carrierFilter.toLowerCase())
      );
    }

    setFilteredShipments(filtered);
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
      setCurrentPage(page);
    }
  };

  const handlePerPageChange = (newPerPage) => {
    setPerPage(newPerPage);
    setCurrentPage(1);
  };

  const handleViewShipment = (shipment) => {
    setSelectedShipment(shipment);
    setShowViewModal(true);
  };

  const handleEditShipment = (shipment) => {
    setSelectedShipment(shipment);
    setShowEditModal(true);
  };

  const handleUpdateStatus = async (shipment, newStatus) => {
    const confirmed = await showConfirm(
      'Update Status',
      `Change shipment ${shipment.tracking_number} status to ${newStatus}?`,
      'question'
    );

    if (confirmed) {
      try {
        await updateShipmentStatus(shipment.id, newStatus);
        await showAlert('Success', 'Shipment status updated successfully', 'success');
        fetchData();
      } catch (error) {
        console.error('Update status failed:', error);
        await showAlert('Error', 'Failed to update shipment status', 'error');
      }
    }
  };

  const getStatusBadge = (status) => {
    const config = {
      preparing: { bg: 'bg-yellow-900/30', text: 'text-yellow-400', label: 'Preparing', icon: FaClock },
      in_transit: { bg: 'bg-blue-900/30', text: 'text-blue-400', label: 'In Transit', icon: FaTruck },
      customs: { bg: 'bg-orange-900/30', text: 'text-orange-400', label: 'At Customs', icon: FaMapMarkerAlt },
      delivered: { bg: 'bg-green-900/30', text: 'text-green-400', label: 'Delivered', icon: FaCheckCircle },
      delayed: { bg: 'bg-red-900/30', text: 'text-red-400', label: 'Delayed', icon: FaExclamationTriangle }
    };
    const c = config[status] || config.preparing;
    const IconComponent = c.icon;
    return (
      <span className={`${c.bg} ${c.text} px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1`}>
        <IconComponent className="text-xs" />
        {c.label}
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getUniqueCarriers = () => {
    const carriers = shipments
      .map(s => s.carrier_name)
      .filter(Boolean)
      .filter((value, index, self) => self.indexOf(value) === index);
    return carriers;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="max-w-[calc(100vw-2rem)] mx-auto">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-2">Shipment Tracking</h1>
          <p className="text-gray-400">
            Real-time tracking and management of all shipments
            {paginationInfo ? (
              ` (${paginationInfo.from}-${paginationInfo.to} of ${paginationInfo.total} shipments)`
            ) : totalShipments > 0 ? (
              ` (${totalShipments} shipments loaded)`
            ) : ''}
          </p>
        </div>
        <button
          onClick={fetchData}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
        >
          <FaSyncAlt />
          Refresh
        </button>
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
                <p className="text-2xl font-bold text-white">{statistics.in_transit_shipments || 0}</p>
              </div>
              <FaTruck className="text-purple-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Delivered</p>
                <p className="text-2xl font-bold text-white">{statistics.delivered_shipments || 0}</p>
              </div>
              <FaCheckCircle className="text-green-500 text-2xl" />
            </div>
          </div>
          <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">Delayed</p>
                <p className="text-2xl font-bold text-white">{statistics.delayed_shipments || 0}</p>
              </div>
              <FaExclamationTriangle className="text-red-500 text-2xl" />
            </div>
          </div>
        </div>
      )}

      {/* Search and Filters */}
      <div className="bg-[#1a1f28] border border-gray-800 rounded-xl p-6 mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search by tracking number, carrier, vessel, customer..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
              />
            </div>
          </div>

          {/* Filters */}
          <div className="flex flex-wrap gap-4">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="all">All Status</option>
              <option value="preparing">Preparing</option>
              <option value="in_transit">In Transit</option>
              <option value="customs">At Customs</option>
              <option value="delivered">Delivered</option>
              <option value="delayed">Delayed</option>
            </select>

            <select
              value={carrierFilter}
              onChange={(e) => setCarrierFilter(e.target.value)}
              className="px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-blue-500"
            >
              <option value="all">All Carriers</option>
              {getUniqueCarriers().map(carrier => (
                <option key={carrier} value={carrier}>{carrier}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Shipments Table */}
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
                    Route & Carrier
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Status & Location
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Timeline
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-800">
                {!Array.isArray(filteredShipments) || filteredShipments.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="px-6 py-12 text-center text-gray-400">
                      {loading ? 'Loading shipments...' : 'No shipments found'}
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
                            ID: {shipment.id}
                          </p>
                          {shipment.container_number && (
                            <p className="text-gray-400 text-sm">
                              Container: {shipment.container_number}
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {shipment.booking?.customer?.name || 
                             (shipment.booking?.customer?.first_name && shipment.booking?.customer?.last_name 
                               ? `${shipment.booking.customer.first_name} ${shipment.booking.customer.last_name}` 
                               : 'Unknown Customer')}
                          </p>
                          <p className="text-gray-400 text-sm">
                            {shipment.booking?.customer?.email || 'No email'}
                          </p>
                          {shipment.booking?.vehicle && (
                            <p className="text-gray-300 text-sm">
                              {shipment.booking.vehicle.year} {shipment.booking.vehicle.make} {shipment.booking.vehicle.model}
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white font-medium">
                            {shipment.departure_port || 'N/A'} → {shipment.arrival_port || 'N/A'}
                          </p>
                          <p className="text-gray-400 text-sm">
                            Carrier: {shipment.carrier_name || 'N/A'}
                          </p>
                          {shipment.vessel_name && (
                            <p className="text-gray-300 text-sm">
                              Vessel: {shipment.vessel_name}
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="space-y-2">
                          {getStatusBadge(shipment.status)}
                          <p className="text-gray-300 text-sm">
                            {shipment.current_location || 'Location not updated'}
                          </p>
                          {shipment.is_delayed && (
                            <p className="text-red-400 text-xs">
                              Delayed by {shipment.days_delayed || 0} days
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <p className="text-white text-sm">
                            Departed: {formatDate(shipment.departure_date)}
                          </p>
                          <p className="text-gray-400 text-sm">
                            ETA: {formatDate(shipment.estimated_arrival)}
                          </p>
                          {shipment.actual_arrival && (
                            <p className="text-green-400 text-sm">
                              Delivered: {formatDate(shipment.actual_arrival)}
                            </p>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => handleViewShipment(shipment)}
                            className="p-2 text-blue-400 hover:text-blue-300 hover:bg-blue-900/20 rounded-lg transition-colors"
                            title="View Details"
                          >
                            <FaEye />
                          </button>
                          <button
                            onClick={() => handleEditShipment(shipment)}
                            className="p-2 text-green-400 hover:text-green-300 hover:bg-green-900/20 rounded-lg transition-colors"
                            title="Edit Shipment"
                          >
                            <FaEdit />
                          </button>
                          {shipment.status !== 'delivered' && (
                            <div className="relative group">
                              <button className="p-2 text-yellow-400 hover:text-yellow-300 hover:bg-yellow-900/20 rounded-lg transition-colors">
                                <FaTruck />
                              </button>
                              <div className="absolute right-0 top-full mt-1 bg-gray-800 border border-gray-700 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                                <div className="p-2 space-y-1 min-w-32">
                                  {shipment.status === 'preparing' && (
                                    <button
                                      onClick={() => handleUpdateStatus(shipment, 'in_transit')}
                                      className="w-full text-left px-3 py-2 text-sm text-white hover:bg-gray-700 rounded"
                                    >
                                      Set In Transit
                                    </button>
                                  )}
                                  {shipment.status === 'in_transit' && (
                                    <button
                                      onClick={() => handleUpdateStatus(shipment, 'customs')}
                                      className="w-full text-left px-3 py-2 text-sm text-white hover:bg-gray-700 rounded"
                                    >
                                      At Customs
                                    </button>
                                  )}
                                  {(shipment.status === 'customs' || shipment.status === 'in_transit') && (
                                    <button
                                      onClick={() => handleUpdateStatus(shipment, 'delivered')}
                                      className="w-full text-left px-3 py-2 text-sm text-white hover:bg-gray-700 rounded"
                                    >
                                      Mark Delivered
                                    </button>
                                  )}
                                  {shipment.status !== 'delayed' && (
                                    <button
                                      onClick={() => handleUpdateStatus(shipment, 'delayed')}
                                      className="w-full text-left px-3 py-2 text-sm text-red-400 hover:bg-gray-700 rounded"
                                    >
                                      Mark Delayed
                                    </button>
                                  )}
                                </div>
                              </div>
                            </div>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
          
          {/* Pagination Controls */}
          {totalPages > 1 && (
            <div className="px-6 py-4 border-t border-gray-800">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="flex items-center gap-2">
                    <span className="text-gray-400 text-sm">Show:</span>
                    <select
                      value={perPage}
                      onChange={(e) => handlePerPageChange(Number(e.target.value))}
                      className="px-3 py-1 bg-gray-800 border border-gray-700 rounded text-white text-sm focus:outline-none focus:border-blue-500"
                    >
                      <option value={10}>10</option>
                      <option value={15}>15</option>
                      <option value={25}>25</option>
                      <option value={50}>50</option>
                    </select>
                  </div>
                  {paginationInfo && (
                    <span className="text-gray-400 text-sm">
                      Showing {paginationInfo.from} to {paginationInfo.to} of {paginationInfo.total} results
                    </span>
                  )}
                </div>
                
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => handlePageChange(currentPage - 1)}
                    disabled={currentPage <= 1}
                    className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <FaChevronLeft />
                  </button>
                  
                  <div className="flex items-center gap-1">
                    {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                      const page = i + 1;
                      return (
                        <button
                          key={page}
                          onClick={() => handlePageChange(page)}
                          className={`px-3 py-2 text-sm rounded-lg transition-colors ${
                            currentPage === page
                              ? 'bg-blue-600 text-white'
                              : 'text-gray-400 hover:text-white hover:bg-gray-700'
                          }`}
                        >
                          {page}
                        </button>
                      );
                    })}
                  </div>
                  
                  <button
                    onClick={() => handlePageChange(currentPage + 1)}
                    disabled={currentPage >= totalPages}
                    className="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <FaChevronRight />
                  </button>
                </div>
              </div>
            </div>
          )}
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

export default ShipmentManagement;