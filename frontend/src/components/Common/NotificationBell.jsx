import { useState, useRef, useEffect } from "react";
import { FaBell, FaTimes, FaShip, FaCheck, FaTrash } from "react-icons/fa";
import { useNotifications } from "../../context/NotificationContextImpl";
import { Link } from "react-router-dom";

const NotificationBell = () => {
  const [isOpen, setIsOpen] = useState(false);
  const { notifications, unreadCount, markRead, markAllRead, clearAll } =
    useNotifications();
  const panelRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (panelRef.current && !panelRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const getIcon = (type) => {
    if (type === "shipment_update") return <FaShip className="text-blue-500" />;
    return <FaBell className="text-gray-500" />;
  };

  const formatTime = (timestamp) => {
    const d = new Date(timestamp);
    return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  };

  return (
    <div className="relative" ref={panelRef}>
      <button
        onClick={() => setIsOpen((prev) => !prev)}
        className="relative p-2 rounded-full hover:bg-gray-100 transition-colors"
        aria-label="Notifications"
      >
        <FaBell className="text-xl text-gray-700" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 top-full mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
            <div className="flex items-center gap-2">
              <FaBell />
              <span className="font-semibold">Notifications</span>
              {unreadCount > 0 && (
                <span className="bg-white/30 text-white text-xs px-2 py-0.5 rounded-full">
                  {unreadCount} new
                </span>
              )}
            </div>
            <div className="flex gap-2">
              {notifications.length > 0 && (
                <>
                  <button
                    onClick={markAllRead}
                    title="Mark all read"
                    className="hover:text-white/70 transition-colors"
                  >
                    <FaCheck className="text-sm" />
                  </button>
                  <button
                    onClick={clearAll}
                    title="Clear all"
                    className="hover:text-white/70 transition-colors"
                  >
                    <FaTrash className="text-sm" />
                  </button>
                </>
              )}
              <button
                onClick={() => setIsOpen(false)}
                className="hover:text-white/70 transition-colors"
              >
                <FaTimes />
              </button>
            </div>
          </div>

          {/* Notifications List */}
          <div className="max-h-80 overflow-y-auto">
            {notifications.length === 0 ? (
              <div className="px-4 py-8 text-center text-gray-500">
                <FaBell className="text-3xl mx-auto mb-2 text-gray-300" />
                <p className="text-sm">No notifications yet</p>
                <p className="text-xs text-gray-400 mt-1">
                  Shipment updates will appear here
                </p>
              </div>
            ) : (
              notifications.map((notif) => (
                <div
                  key={notif.id}
                  onClick={() => markRead(notif.id)}
                  className={`px-4 py-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors ${
                    !notif.read ? "bg-blue-50" : ""
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <div className="mt-0.5">{getIcon(notif.type)}</div>
                    <div className="flex-1 min-w-0">
                      <p
                        className={`text-sm font-medium ${!notif.read ? "text-blue-700" : "text-gray-800"}`}
                      >
                        {notif.title}
                      </p>
                      <p className="text-xs text-gray-600 mt-0.5 leading-relaxed">
                        {notif.message}
                      </p>
                      <p className="text-xs text-gray-400 mt-1">
                        {formatTime(notif.timestamp)}
                      </p>
                    </div>
                    {!notif.read && (
                      <div className="w-2 h-2 bg-blue-500 rounded-full mt-1.5 flex-shrink-0" />
                    )}
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div className="px-4 py-2 border-t border-gray-100 bg-gray-50">
              <Link
                to="/portal/dashboard"
                onClick={() => setIsOpen(false)}
                className="text-xs text-blue-600 hover:text-blue-700 font-medium"
              >
                View all in portal →
              </Link>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
