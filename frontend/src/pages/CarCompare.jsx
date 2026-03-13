import { useState, useEffect } from "react";
import { Link, useSearchParams } from "react-router-dom";
import {
  FaArrowLeft,
  FaShip,
  FaGasPump,
  FaTachometerAlt,
  FaCogs,
  FaMapMarkerAlt,
  FaCheckCircle,
  FaTimesCircle,
  FaBalanceScale,
} from "react-icons/fa";

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api";

const CarCompare = () => {
  const [searchParams] = useSearchParams();
  const ids = (searchParams.get("ids") || "")
    .split(",")
    .filter(Boolean)
    .slice(0, 3);
  const [cars, setCars] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (ids.length === 0) {
      setLoading(false);
      return;
    }
    const fetchCars = async () => {
      try {
        const results = await Promise.all(
          ids.map((id) =>
            fetch(`${API_BASE_URL}/cars/${id}`, {
              headers: { Accept: "application/json" },
            }).then((r) => r.json()),
          ),
        );
        setCars(results.map((r) => r.data || r).filter(Boolean));
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    fetchCars();
  }, [ids.join(",")]);

  const formatPrice = (price) =>
    new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
    }).format(price);

  const specs = [
    { label: "Price", key: "price", format: formatPrice },
    { label: "Year", key: "year" },
    {
      label: "Condition",
      key: "condition",
      format: (v) => v?.replace(/_/g, " "),
    },
    { label: "Fuel Type", key: "fuel_type" },
    { label: "Transmission", key: "transmission" },
    {
      label: "Mileage",
      key: "mileage",
      format: (v) => (v ? `${v.toLocaleString()} km` : "N/A"),
    },
    { label: "Engine Size", key: "engine_size" },
    { label: "Color", key: "color" },
    { label: "Location", key: "location_country" },
    {
      label: "Shipping Time",
      key: null,
      format: (_, car) =>
        car?.estimated_shipping_days_min
          ? `${car.estimated_shipping_days_min}-${car.estimated_shipping_days_max} days`
          : "21-30 days",
    },
  ];

  if (loading)
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );

  if (cars.length === 0)
    return (
      <div className="min-h-screen flex flex-col items-center justify-center gap-4">
        <FaBalanceScale className="text-6xl text-gray-300" />
        <p className="text-xl text-gray-600">
          No vehicles selected for comparison.
        </p>
        <Link
          to="/cars"
          className="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
        >
          Browse Vehicles
        </Link>
      </div>
    );

  return (
    <div className="min-h-screen bg-gray-50 py-12">
      <div className="container-custom">
        <div className="flex items-center gap-4 mb-8">
          <Link
            to="/cars"
            className="flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium"
          >
            <FaArrowLeft />
            Back to Vehicles
          </Link>
          <div className="flex items-center gap-2">
            <FaBalanceScale className="text-blue-600 text-xl" />
            <h1 className="text-2xl font-bold text-gray-900">
              Vehicle Comparison
            </h1>
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
          {/* Car images header */}
          <div
            className={`grid gap-0 border-b border-gray-200`}
            style={{ gridTemplateColumns: `200px repeat(${cars.length}, 1fr)` }}
          >
            <div className="p-4 bg-gray-50 flex items-center justify-center">
              <span className="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                Specification
              </span>
            </div>
            {cars.map((car) => (
              <div key={car.id} className="p-4 border-l border-gray-200">
                <div className="aspect-video rounded-xl overflow-hidden mb-3">
                  <img
                    src={
                      car.images?.[0]?.image_url ||
                      "https://images.unsplash.com/photo-1619405399517-d7fce0f13302?w=400&auto=format&fit=crop"
                    }
                    alt={`${car.brand?.name} ${car.model}`}
                    className="w-full h-full object-cover"
                  />
                </div>
                <h3 className="font-bold text-gray-900 text-lg">
                  {car.brand?.name} {car.model}
                </h3>
                <p className="text-blue-600 font-bold text-xl">
                  {formatPrice(car.price)}
                </p>
                <div className="mt-3 flex gap-2">
                  <Link
                    to={`/cars/${car.slug}`}
                    className="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg text-sm font-semibold transition-colors"
                  >
                    Details
                  </Link>
                  <Link
                    to={`/quote?vehicle=${car.slug}`}
                    className="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors"
                  >
                    Get Quote
                  </Link>
                </div>
              </div>
            ))}
          </div>

          {/* Spec rows */}
          {specs.map((spec, i) => (
            <div
              key={spec.label}
              className={`grid gap-0 border-b border-gray-100 ${i % 2 === 0 ? "bg-white" : "bg-gray-50"}`}
              style={{
                gridTemplateColumns: `200px repeat(${cars.length}, 1fr)`,
              }}
            >
              <div className="px-4 py-3 flex items-center">
                <span className="text-sm font-semibold text-gray-600">
                  {spec.label}
                </span>
              </div>
              {cars.map((car) => {
                const raw = spec.key ? car[spec.key] : null;
                const display = spec.format
                  ? spec.format(raw, car)
                  : raw || "N/A";
                return (
                  <div
                    key={car.id}
                    className="px-4 py-3 border-l border-gray-100 flex items-center"
                  >
                    <span className="text-sm text-gray-800">
                      {display || "N/A"}
                    </span>
                  </div>
                );
              })}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default CarCompare;
