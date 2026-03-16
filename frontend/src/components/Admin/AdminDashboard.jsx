import React, { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import {
  FaShip,
  FaQuoteLeft,
  FaUsers,
  FaDollarSign,
  FaTruck,
  FaFileAlt,
  FaExclamationTriangle,
  FaCheckCircle,
  FaClock,
  FaArrowUp,
  FaArrowDown,
  FaEye,
  FaEdit,
  FaChartLine,
  FaCalendarAlt,
  FaMapMarkerAlt,
} from "react-icons/fa";

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api";
import { Line, Bar, Doughnut } from "react-chartjs-2";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from "chart.js";
import StatCard from "./StatCard";

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
);

const AdminDashboard = () => {
  const navigate = useNavigate();
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [timeRange, setTimeRange] = useState("30"); // days
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    fetchDashboardData();

    // Set up auto-refresh every 5 minutes
    const interval = setInterval(fetchDashboardData, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, [timeRange]);

  const fetchDashboardData = async () => {
    try {
      setRefreshing(true);
      const token = localStorage.getItem("admin_token");

      const period = timeRange === '7' ? '7_days' : timeRange === '90' ? '90_days' : '30_days';

      // Fetch dashboard statistics
      const [statsResponse, kpiResponse, activityResponse, revenueTrendResponse, bookingStatusResponse] =
        await Promise.all([
          fetch(`${API_BASE_URL}/admin/dashboard/statistics`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
          fetch(`${API_BASE_URL}/admin/dashboard/kpis`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
          fetch(`${API_BASE_URL}/admin/dashboard/recent-activity`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
          fetch(`${API_BASE_URL}/admin/dashboard/chart-data?type=revenue_trend&period=${period}`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
          fetch(`${API_BASE_URL}/admin/dashboard/chart-data?type=booking_status`, {
            headers: { Authorization: `Bearer ${token}` },
          }),
        ]);

      if (!statsResponse.ok || !kpiResponse.ok || !activityResponse.ok) {
        throw new Error("Failed to fetch dashboard data");
      }

      const [stats, kpis, activity] = await Promise.all([
        statsResponse.json(),
        kpiResponse.json(),
        activityResponse.json(),
      ]);

      // Parse chart data (may fail independently)
      let revenueTrend = [];
      let bookingStatus = [];
      try {
        if (revenueTrendResponse.ok) {
          const rt = await revenueTrendResponse.json();
          // Backend returns { data: { period, data: [...], total_revenue } }
          revenueTrend = rt.data?.data || rt.data || [];
        }
      } catch (e) { /* chart data unavailable */ }
      try {
        if (bookingStatusResponse.ok) {
          const bs = await bookingStatusResponse.json();
          // Backend returns { data: [...] } directly for status distribution
          bookingStatus = Array.isArray(bs.data) ? bs.data : [];
        }
      } catch (e) { /* chart data unavailable */ }

      // Transform raw backend data into Chart.js format
      const bookingTrends = {
        labels: Array.isArray(revenueTrend) ? revenueTrend.map(d => d.date_label) : [],
        datasets: [{
          label: 'Revenue ($)',
          data: Array.isArray(revenueTrend) ? revenueTrend.map(d => d.revenue) : [],
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.4,
          fill: true,
        }]
      };

      const revenueAnalytics = {
        labels: Array.isArray(revenueTrend) ? revenueTrend.map(d => d.date_label) : [],
        datasets: [{
          label: 'Revenue ($)',
          data: Array.isArray(revenueTrend) ? revenueTrend.map(d => d.revenue) : [],
          backgroundColor: 'rgba(34, 197, 94, 0.8)',
        }]
      };

      const statusColors = {
        pending: 'rgba(251, 191, 36, 0.8)',
        confirmed: 'rgba(59, 130, 246, 0.8)',
        in_transit: 'rgba(147, 51, 234, 0.8)',
        delivered: 'rgba(34, 197, 94, 0.8)',
        cancelled: 'rgba(239, 68, 68, 0.8)',
      };
      const statusDistribution = {
        labels: Array.isArray(bookingStatus) ? bookingStatus.map(d => d.status_label) : [],
        datasets: [{
          data: Array.isArray(bookingStatus) ? bookingStatus.map(d => d.count) : [],
          backgroundColor: Array.isArray(bookingStatus) ? bookingStatus.map(d => statusColors[d.status] || 'rgba(156,163,175,0.8)') : [],
        }]
      };

      // Map nested analytics response to flat structure expected by cards
      const overview = stats.data?.overview || {};
      const operational = stats.data?.operational || {};
      setDashboardData({
        statistics: {
          total_bookings:
            overview.bookings?.total ?? stats.data?.total_bookings ?? 0,
          active_shipments:
            operational.shipment_metrics?.in_transit ?? overview.bookings?.active ?? stats.data?.active_shipments ?? 0,
          pending_quotes:
            overview.quotes?.pending ?? stats.data?.pending_quotes ?? 0,
          total_revenue:
            overview.revenue?.total ??
            stats.data?.revenue?.total_revenue ??
            stats.data?.total_revenue ??
            0,
          monthly_growth:
            overview.bookings?.this_month ?? stats.data?.monthly_growth ?? 0,
          revenue_growth:
            stats.data?.revenue?.revenue_growth_rate ?? stats.data?.revenue_growth ?? 0,
          customer_satisfaction:
            overview.customers?.retention_rate ??
            stats.data?.customer_satisfaction ??
            0,
        },
        kpis: {
          booking_conversion_rate: kpis.data?.bookings?.conversion_rate ?? kpis.data?.quotes?.conversion_rate ?? kpis.data?.booking_conversion_rate ?? 0,
          average_delivery_time: kpis.data?.shipments?.average_transit_time ?? kpis.data?.average_delivery_time ?? 0,
          on_time_delivery_rate: kpis.data?.shipments?.on_time_delivery_rate ?? kpis.data?.on_time_delivery_rate ?? 0,
          customer_retention_rate: kpis.data?.customers?.retention_rate ?? kpis.data?.customer_retention_rate ?? 0,
        },
        recentActivity: {
          bookings: activity.data?.recent_bookings || activity.data?.bookings || [],
          quotes: activity.data?.recent_quotes || activity.data?.quotes || [],
          shipments: activity.data?.recent_shipments || activity.data?.shipments || [],
        },
        charts: { bookingTrends, revenueAnalytics, statusDistribution },
      });

      setError(null);
    } catch (err) {
      console.error("Dashboard fetch error:", err);
      setError(err.message);

      // Fallback mock data for development
      setDashboardData({
        statistics: {
          total_bookings: 156,
          active_shipments: 23,
          pending_quotes: 12,
          total_revenue: 125000,
          monthly_growth: 15.2,
          customer_satisfaction: 4.8,
        },
        kpis: {
          booking_conversion_rate: 68.5,
          average_delivery_time: 21,
          on_time_delivery_rate: 94.2,
          customer_retention_rate: 87.3,
        },
        recentActivity: {
          bookings: [
            {
              id: 1,
              customer: "John Doe",
              vehicle: "2020 Toyota Camry",
              status: "confirmed",
              created_at: "2024-01-20T10:30:00Z",
            },
            {
              id: 2,
              customer: "Jane Smith",
              vehicle: "2019 Honda Civic",
              status: "in_transit",
              created_at: "2024-01-20T09:15:00Z",
            },
          ],
          quotes: [
            {
              id: 1,
              customer: "Mike Johnson",
              route: "Japan → Uganda",
              amount: 2500,
              status: "pending",
              created_at: "2024-01-20T11:00:00Z",
            },
          ],
          shipments: [
            {
              id: 1,
              tracking_number: "SWG-001",
              status: "delivered",
              location: "Kampala",
              updated_at: "2024-01-20T08:45:00Z",
            },
          ],
        },
        charts: {
          bookingTrends: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [
              {
                label: "Bookings",
                data: [12, 19, 15, 25, 22, 30],
                borderColor: "rgb(59, 130, 246)",
                backgroundColor: "rgba(59, 130, 246, 0.1)",
                tension: 0.4,
              },
            ],
          },
          revenueAnalytics: {
            labels: ["Week 1", "Week 2", "Week 3", "Week 4"],
            datasets: [
              {
                label: "Revenue ($)",
                data: [25000, 32000, 28000, 40000],
                backgroundColor: "rgba(34, 197, 94, 0.8)",
              },
            ],
          },
          statusDistribution: {
            labels: ["Pending", "Confirmed", "In Transit", "Delivered"],
            datasets: [
              {
                data: [15, 25, 35, 25],
                backgroundColor: [
                  "rgba(251, 191, 36, 0.8)",
                  "rgba(59, 130, 246, 0.8)",
                  "rgba(139, 92, 246, 0.8)",
                  "rgba(34, 197, 94, 0.8)",
                ],
              },
            ],
          },
        },
      });
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const formatCurrency = (amount) => {
    const num = Number(amount) || 0;
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(Math.round(num));
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const getStatusBadge = (status) => {
    const config = {
      pending: {
        bg: "bg-yellow-900/30",
        text: "text-yellow-300",
        label: "Pending",
      },
      confirmed: {
        bg: "bg-blue-900/30",
        text: "text-blue-300",
        label: "Confirmed",
      },
      in_transit: {
        bg: "bg-purple-900/30",
        text: "text-purple-300",
        label: "In Transit",
      },
      delivered: {
        bg: "bg-green-900/30",
        text: "text-green-300",
        label: "Delivered",
      },
      cancelled: {
        bg: "bg-red-900/30",
        text: "text-red-300",
        label: "Cancelled",
      },
    };
    const c = config[status] || config.pending;
    return (
      <span
        className={`${c.bg} ${c.text} px-2 py-1 rounded-full text-xs font-medium border border-gray-600`}
      >
        {c.label}
      </span>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64 bg-[#0a0e13] text-white">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error && !dashboardData) {
    return (
      <div className="bg-red-900/20 border border-red-700 rounded-lg p-6 text-center bg-[#0a0e13] text-white">
        <FaExclamationTriangle className="h-12 w-12 text-red-400 mx-auto mb-4" />
        <h3 className="text-lg font-semibold text-red-300 mb-2">
          Failed to Load Dashboard
        </h3>
        <p className="text-red-400 mb-4">{error}</p>
        <button
          onClick={fetchDashboardData}
          className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
        >
          Retry
        </button>
      </div>
    );
  }

  const { statistics, kpis, recentActivity, charts: rawCharts } = dashboardData;

  // Provide default chart structures to prevent Chart.js crashes
  const emptyLineChart = { labels: [], datasets: [{ label: '', data: [], borderColor: 'rgb(59,130,246)', tension: 0.4 }] };
  const emptyBarChart = { labels: [], datasets: [{ label: '', data: [], backgroundColor: 'rgba(34,197,94,0.8)' }] };
  const emptyDoughnutChart = { labels: [], datasets: [{ data: [], backgroundColor: [] }] };
  const charts = {
    bookingTrends: rawCharts?.bookingTrends || emptyLineChart,
    revenueAnalytics: rawCharts?.revenueAnalytics || emptyBarChart,
    statusDistribution: rawCharts?.statusDistribution || emptyDoughnutChart,
  };

  return (
    <div className="space-y-6 bg-[#0a0e13] min-h-screen text-white p-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-white">Dashboard Overview</h1>
          <p className="text-gray-400 mt-1">
            Welcome back! Here's what's happening with your business.
          </p>
        </div>
        <div className="flex items-center gap-4">
          <select
            value={timeRange}
            onChange={(e) => setTimeRange(e.target.value)}
            className="bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-white"
          >
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
          </select>
          <button
            onClick={fetchDashboardData}
            disabled={refreshing}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <FaChartLine className={refreshing ? "animate-spin" : ""} />
            {refreshing ? "Refreshing..." : "Refresh"}
          </button>
        </div>
      </div>

      {/* Key Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
          title="Total Bookings"
          value={statistics.total_bookings}
          icon={FaShip}
          color="blue"
          trend={statistics.booking_growth}
          trendLabel="vs last month"
        />
        <StatCard
          title="Active Shipments"
          value={statistics.active_shipments}
          icon={FaTruck}
          color="purple"
          trend={statistics.shipment_growth}
          trendLabel="in transit"
        />
        <StatCard
          title="Pending Quotes"
          value={statistics.pending_quotes}
          icon={FaQuoteLeft}
          color="yellow"
          trend={statistics.quote_growth}
          trendLabel="awaiting approval"
        />
        <StatCard
          title="Total Revenue"
          value={formatCurrency(statistics.total_revenue)}
          icon={FaDollarSign}
          color="green"
          trend={statistics.revenue_growth}
          trendLabel="vs last month"
        />
      </div>

      {/* KPI Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-400">
                Conversion Rate
              </p>
              <p className="text-2xl font-bold text-white">
                {typeof kpis.booking_conversion_rate === 'number' ? Math.round(kpis.booking_conversion_rate * 10) / 10 : kpis.booking_conversion_rate}%
              </p>
            </div>
            <div className="bg-blue-900/30 p-3 rounded-full">
              <FaChartLine className="h-6 w-6 text-blue-400" />
            </div>
          </div>
        </div>

        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-400">
                Avg Delivery Time
              </p>
              <p className="text-2xl font-bold text-white">
                {typeof kpis.average_delivery_time === 'number' ? Math.round(kpis.average_delivery_time) : kpis.average_delivery_time} days
              </p>
            </div>
            <div className="bg-green-900/30 p-3 rounded-full">
              <FaClock className="h-6 w-6 text-green-400" />
            </div>
          </div>
        </div>

        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-400">
                On-Time Delivery
              </p>
              <p className="text-2xl font-bold text-white">
                {typeof kpis.on_time_delivery_rate === 'number' ? Math.round(kpis.on_time_delivery_rate * 10) / 10 : kpis.on_time_delivery_rate}%
              </p>
            </div>
            <div className="bg-purple-900/30 p-3 rounded-full">
              <FaCheckCircle className="h-6 w-6 text-purple-400" />
            </div>
          </div>
        </div>

        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-400">
                Customer Retention
              </p>
              <p className="text-2xl font-bold text-white">
                {typeof kpis.customer_retention_rate === 'number' ? Math.round(kpis.customer_retention_rate * 10) / 10 : kpis.customer_retention_rate}%
              </p>
            </div>
            <div className="bg-yellow-900/30 p-3 rounded-full">
              <FaUsers className="h-6 w-6 text-yellow-400" />
            </div>
          </div>
        </div>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Booking Trends */}
        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-white">Booking Trends</h3>
            <FaChartLine className="h-5 w-5 text-gray-400" />
          </div>
          <div className="h-64">
            <Line
              data={charts.bookingTrends}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false,
                  },
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    ticks: {
                      color: "#9CA3AF",
                    },
                    grid: {
                      color: "#374151",
                    },
                  },
                  x: {
                    ticks: {
                      color: "#9CA3AF",
                    },
                    grid: {
                      color: "#374151",
                    },
                  },
                },
              }}
            />
          </div>
        </div>

        {/* Revenue Analytics */}
        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-white">
              Revenue Analytics
            </h3>
            <FaDollarSign className="h-5 w-5 text-gray-400" />
          </div>
          <div className="h-64">
            <Bar
              data={charts.revenueAnalytics}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false,
                  },
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    ticks: {
                      color: "#9CA3AF",
                    },
                    grid: {
                      color: "#374151",
                    },
                  },
                  x: {
                    ticks: {
                      color: "#9CA3AF",
                    },
                    grid: {
                      color: "#374151",
                    },
                  },
                },
              }}
            />
          </div>
        </div>
      </div>

      {/* Status Distribution and Recent Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Status Distribution */}
        <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <h3 className="text-lg font-semibold text-white mb-4">
            Shipment Status
          </h3>
          <div className="h-64">
            <Doughnut
              data={charts.statusDistribution}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    position: "bottom",
                    labels: {
                      color: "#9CA3AF",
                    },
                  },
                },
              }}
            />
          </div>
        </div>

        {/* Recent Activity */}
        <div className="lg:col-span-2 bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
          <h3 className="text-lg font-semibold text-white mb-4">
            Recent Activity
          </h3>
          <div className="space-y-4 max-h-64 overflow-y-auto">
            {/* Recent Bookings */}
            {recentActivity.bookings?.map((booking) => (
              <div
                key={`booking-${booking.id}`}
                className="flex items-center justify-between p-3 bg-blue-900/20 rounded-lg border border-blue-800/30"
              >
                <div className="flex items-center gap-3">
                  <div className="bg-blue-900/30 p-2 rounded-full">
                    <FaShip className="h-4 w-4 text-blue-400" />
                  </div>
                  <div>
                    <p className="font-medium text-white">{booking.customer_name || booking.customer}</p>
                    <p className="text-sm text-gray-400">{booking.vehicle}</p>
                  </div>
                </div>
                <div className="text-right">
                  {getStatusBadge(booking.status)}
                  <p className="text-xs text-gray-500 mt-1">
                    {formatDate(booking.created_at)}
                  </p>
                </div>
              </div>
            ))}

            {/* Recent Quotes */}
            {recentActivity.quotes?.map((quote) => (
              <div
                key={`quote-${quote.id}`}
                className="flex items-center justify-between p-3 bg-yellow-900/20 rounded-lg border border-yellow-800/30"
              >
                <div className="flex items-center gap-3">
                  <div className="bg-yellow-900/30 p-2 rounded-full">
                    <FaQuoteLeft className="h-4 w-4 text-yellow-400" />
                  </div>
                  <div>
                    <p className="font-medium text-white">{quote.customer_name || quote.customer}</p>
                    <p className="text-sm text-gray-400">{quote.route}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-white">
                    {formatCurrency(quote.amount)}
                  </p>
                  <p className="text-xs text-gray-500">
                    {formatDate(quote.created_at)}
                  </p>
                </div>
              </div>
            ))}

            {/* Recent Shipments */}
            {recentActivity.shipments?.map((shipment) => (
              <div
                key={`shipment-${shipment.id}`}
                className="flex items-center justify-between p-3 bg-green-900/20 rounded-lg border border-green-800/30"
              >
                <div className="flex items-center gap-3">
                  <div className="bg-green-900/30 p-2 rounded-full">
                    <FaTruck className="h-4 w-4 text-green-400" />
                  </div>
                  <div>
                    <p className="font-medium text-white">
                      {shipment.tracking_number}
                    </p>
                    <p className="text-sm text-gray-400 flex items-center gap-1">
                      <FaMapMarkerAlt className="h-3 w-3" />
                      {shipment.current_location || shipment.location}
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  {getStatusBadge(shipment.status)}
                  <p className="text-xs text-gray-500 mt-1">
                    {formatDate(shipment.updated_at)}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6">
        <h3 className="text-lg font-semibold text-white mb-4">Quick Actions</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <button onClick={() => navigate('/admin/bookings/new')} className="flex items-center gap-3 p-4 bg-blue-900/20 rounded-lg hover:bg-blue-900/30 transition-colors border border-blue-800/30">
            <FaShip className="h-5 w-5 text-blue-400" />
            <span className="font-medium text-blue-300">New Booking</span>
          </button>
          <button onClick={() => navigate('/admin/quotes')} className="flex items-center gap-3 p-4 bg-green-900/20 rounded-lg hover:bg-green-900/30 transition-colors border border-green-800/30">
            <FaQuoteLeft className="h-5 w-5 text-green-400" />
            <span className="font-medium text-green-300">Create Quote</span>
          </button>
          <button onClick={() => navigate('/admin/customers')} className="flex items-center gap-3 p-4 bg-purple-900/20 rounded-lg hover:bg-purple-900/30 transition-colors border border-purple-800/30">
            <FaUsers className="h-5 w-5 text-purple-400" />
            <span className="font-medium text-purple-300">Add Customer</span>
          </button>
          <button onClick={() => navigate('/admin/reports')} className="flex items-center gap-3 p-4 bg-yellow-900/20 rounded-lg hover:bg-yellow-900/30 transition-colors border border-yellow-800/30">
            <FaFileAlt className="h-5 w-5 text-yellow-400" />
            <span className="font-medium text-yellow-300">Generate Report</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
