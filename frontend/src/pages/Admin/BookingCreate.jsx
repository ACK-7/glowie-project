import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { FaArrowLeft, FaSave, FaSpinner } from 'react-icons/fa';
import { showAlert } from '../../utils/sweetAlert';
import {
  getCustomers,
  getRoutes,
  getVehicles,
  createBooking,
} from '../../services/adminService';

const inputClass = 'w-full px-3 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
const labelClass = 'block text-sm font-medium text-gray-300 mb-2';

const BookingCreate = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [loadingOptions, setLoadingOptions] = useState(true);
  const [customers, setCustomers] = useState([]);
  const [routes, setRoutes] = useState([]);
  const [vehicles, setVehicles] = useState([]);

  const [formData, setFormData] = useState({
    customer_id: '',
    vehicle_id: '',
    route_id: '',
    status: 'pending',
    total_amount: '',
    currency: 'USD',
    recipient_country: '',
    recipient_city: '',
    recipient_address: '',
    recipient_name: '',
    recipient_phone: '',
    recipient_email: '',
    pickup_date: '',
    delivery_date: '',
    notes: '',
  });

  useEffect(() => {
    let cancelled = false;

    const load = async () => {
      setLoadingOptions(true);
      try {
        const [custRes, routesRes, vehiclesRes] = await Promise.all([
          getCustomers({ per_page: 100 }), // Max allowed is 100
          getRoutes(),
          getVehicles(),
        ]);

        if (cancelled) return;

        setCustomers(custRes?.data ?? []);
        setRoutes(routesRes?.data ?? []);
        setVehicles(vehiclesRes?.data ?? []);
      } catch (err) {
        if (cancelled) return;
        console.error('Failed to load options:', err);
        const msg = !err.response || err.code === 'ERR_NETWORK'
          ? 'Cannot reach the server. Please check that the backend is running.'
          : (err.response?.data?.message || err.message || 'Failed to load form options.');
        showAlert.error('Error', msg);
      } finally {
        if (!cancelled) setLoadingOptions(false);
      }
    };

    load();
    return () => { cancelled = true; };
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const buildPayload = () => {
    const p = {
      customer_id: parseInt(formData.customer_id, 10),
      vehicle_id: parseInt(formData.vehicle_id, 10),
      route_id: parseInt(formData.route_id, 10),
      status: formData.status || 'pending',
      total_amount: parseFloat(formData.total_amount, 10) || 0,
      currency: formData.currency || 'USD',
      recipient_country: formData.recipient_country.trim(),
      recipient_city: formData.recipient_city.trim(),
      recipient_address: formData.recipient_address.trim(),
    };
    if (formData.recipient_name?.trim()) p.recipient_name = formData.recipient_name.trim();
    if (formData.recipient_phone?.trim()) p.recipient_phone = formData.recipient_phone.trim();
    if (formData.recipient_email?.trim()) p.recipient_email = formData.recipient_email.trim();
    if (formData.pickup_date) p.pickup_date = formData.pickup_date;
    if (formData.delivery_date) p.delivery_date = formData.delivery_date;
    if (formData.notes?.trim()) p.notes = formData.notes.trim();
    return p;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const payload = buildPayload();
      await createBooking(payload);
      await showAlert.success('Booking Created', 'The booking has been created successfully.');
      navigate('/admin/bookings');
    } catch (err) {
      console.error('Create booking error:', err);
      const msg = !err.response || err.code === 'ERR_NETWORK'
        ? 'Cannot reach the server. Please check that the backend is running.'
        : (err.response?.data?.message || err.message || 'Failed to create booking.');
      await showAlert.error('Create Failed', msg);
    } finally {
      setLoading(false);
    }
  };

  const routeLabel = (r) =>
    r.origin_city && r.origin_country && r.destination_city && r.destination_country
      ? `${r.origin_city}, ${r.origin_country} → ${r.destination_city}, ${r.destination_country}`
      : `Route #${r.id}`;

  const vehicleLabel = (v) =>
    [v.year, v.make, v.model].filter(Boolean).join(' ') || `Vehicle #${v.id}`;

  if (loadingOptions) {
    return (
      <div className="w-full max-w-3xl mx-auto px-4 flex items-center justify-center py-16">
        <FaSpinner className="w-8 h-8 text-blue-400 animate-spin" />
      </div>
    );
  }

  return (
    <div className="w-full max-w-3xl mx-auto px-4 space-y-6">
      <div className="flex items-center gap-4">
        <Link
          to="/admin/bookings"
          className="inline-flex items-center text-gray-400 hover:text-white transition-colors"
        >
          <FaArrowLeft className="mr-2" />
          Back to Bookings
        </Link>
      </div>

      <div>
        <h1 className="text-2xl sm:text-3xl font-bold text-white">Create Booking</h1>
        <p className="text-gray-400 mt-1">Add a new vehicle shipping booking</p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Customer, Vehicle, Route */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className={labelClass}>Customer *</label>
            <select
              name="customer_id"
              value={formData.customer_id}
              onChange={handleChange}
              className={inputClass}
              required
            >
              <option value="">Select customer</option>
              {customers.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.full_name || [c.first_name, c.last_name].filter(Boolean).join(' ') || c.email || `#${c.id}`}
                </option>
              ))}
            </select>
            {customers.length === 0 && (
              <p className="text-amber-400 text-sm mt-1">No customers found. Create one first.</p>
            )}
          </div>

          <div>
            <label className={labelClass}>Vehicle *</label>
            <select
              name="vehicle_id"
              value={formData.vehicle_id}
              onChange={handleChange}
              className={inputClass}
              required
            >
              <option value="">Select vehicle</option>
              {vehicles.map((v) => (
                <option key={v.id} value={v.id}>{vehicleLabel(v)}</option>
              ))}
            </select>
            {vehicles.length === 0 && (
              <p className="text-amber-400 text-sm mt-1">No vehicles found.</p>
            )}
          </div>

          <div>
            <label className={labelClass}>Route *</label>
            <select
              name="route_id"
              value={formData.route_id}
              onChange={handleChange}
              className={inputClass}
              required
            >
              <option value="">Select route</option>
              {routes.map((r) => (
                <option key={r.id} value={r.id}>{routeLabel(r)}</option>
              ))}
            </select>
            {routes.length === 0 && (
              <p className="text-amber-400 text-sm mt-1">No active routes found.</p>
            )}
          </div>
        </div>

        {/* Status, Amount, Currency */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className={labelClass}>Status</label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}
              className={inputClass}
            >
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="in_transit">In Transit</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div>
            <label className={labelClass}>Total Amount *</label>
            <input
              type="number"
              name="total_amount"
              value={formData.total_amount}
              onChange={handleChange}
              step="0.01"
              min="0"
              className={inputClass}
              required
            />
          </div>

          <div>
            <label className={labelClass}>Currency</label>
            <select
              name="currency"
              value={formData.currency}
              onChange={handleChange}
              className={inputClass}
            >
              <option value="USD">USD</option>
              <option value="EUR">EUR</option>
              <option value="GBP">GBP</option>
            </select>
          </div>
        </div>

        {/* Dates */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className={labelClass}>Pickup Date</label>
            <input
              type="date"
              name="pickup_date"
              value={formData.pickup_date}
              onChange={handleChange}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>Delivery Date</label>
            <input
              type="date"
              name="delivery_date"
              value={formData.delivery_date}
              onChange={handleChange}
              className={inputClass}
            />
          </div>
        </div>

        {/* Recipient */}
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-white">Recipient / Delivery</h3>

          <div>
            <label className={labelClass}>Recipient Name</label>
            <input
              type="text"
              name="recipient_name"
              value={formData.recipient_name}
              onChange={handleChange}
              className={inputClass}
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className={labelClass}>Recipient Email</label>
              <input
                type="email"
                name="recipient_email"
                value={formData.recipient_email}
                onChange={handleChange}
                className={inputClass}
              />
            </div>
            <div>
              <label className={labelClass}>Recipient Phone</label>
              <input
                type="tel"
                name="recipient_phone"
                value={formData.recipient_phone}
                onChange={handleChange}
                className={inputClass}
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className={labelClass}>Country *</label>
              <input
                type="text"
                name="recipient_country"
                value={formData.recipient_country}
                onChange={handleChange}
                className={inputClass}
                required
              />
            </div>
            <div>
              <label className={labelClass}>City *</label>
              <input
                type="text"
                name="recipient_city"
                value={formData.recipient_city}
                onChange={handleChange}
                className={inputClass}
                required
              />
            </div>
            <div>
              <label className={labelClass}>Address *</label>
              <input
                type="text"
                name="recipient_address"
                value={formData.recipient_address}
                onChange={handleChange}
                className={inputClass}
                required
              />
            </div>
          </div>
        </div>

        {/* Notes */}
        <div>
          <label className={labelClass}>Notes</label>
          <textarea
            name="notes"
            value={formData.notes}
            onChange={handleChange}
            rows={3}
            className={inputClass}
            placeholder="Optional notes..."
          />
        </div>

        {/* Actions */}
        <div className="flex justify-end gap-3 pt-4 border-t border-gray-700">
          <Link
            to="/admin/bookings"
            className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            Cancel
          </Link>
          <button
            type="submit"
            disabled={loading}
            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            {loading ? (
              <>
                <FaSpinner className="mr-2 animate-spin" />
                Creating...
              </>
            ) : (
              <>
                <FaSave className="mr-2" />
                Create Booking
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default BookingCreate;
