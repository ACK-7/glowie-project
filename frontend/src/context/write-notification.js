const fs = require("fs");
const path = require("path");

const content = `import { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const POLL_INTERVAL = 30000;

const NotificationContext = createContext(null);

export const useNotifications = () => {
  const ctx = useContext(NotificationContext);
  if (!ctx) throw new Error('useNotifications must be used within NotificationProvider');
  return ctx;
};

export const NotificationProvider = ({ children }) => {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const prevShipmentStatuses = useRef({});
  const intervalRef = useRef(null);

  const getAuthHeaders = () => {
    const token = localStorage.getItem('customer_token');
    return token ? { Authorization: \`Bearer \${token}\` } : null;
  };

  const addNotification = useCallback((notification) => {
    const newNotif = { id: Date.now(), read: false, timestamp: new Date(), ...notification };
    setNotifications(prev => [newNotif, ...prev].slice(0, 20));
    setUnreadCount(prev => prev + 1);
  }, []);

  const markAllRead = useCallback(() => {
    setNotifications(prev => prev.map(n => ({ ...n, read: true })));
    setUnreadCount(0);
  }, []);

  const markRead = useCallback((id) => {
    setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: true } : n));
    setUnreadCount(prev => Math.max(0, prev - 1));
  }, []);

  const clearAll = useCallback(() => {
    setNotifications([]);
    setUnreadCount(0);
  }, []);

  const pollShipments = useCallback(async () => {
    const headers = getAuthHeaders();
    if (!headers) return;
    try {
      const response = await axios.get(\`\${API_BASE_URL}/customer/shipments\`, { headers });
      const shipments = response.data?.data || response.data || [];
      if (!Array.isArray(shipments)) return;
      shipments.forEach(shipment => {
        const id = shipment.id || shipment.tracking_number;
        const currentStatus = shipment.status;
        const previousStatus = prevShipmentStatuses.current[id];
        if (previousStatus !== undefined && previousStatus !== currentStatus) {
          addNotification({
            type: 'shipment_update',
            title: 'Shipment Status Update',
            message: \`Your shipment \${shipment.tracking_number || '#' + id} is now: \${currentStatus.replace(/_/g, ' ')}\`,
            shipmentId: id,
          });
        }
        prevShipmentStatuses.current[id] = currentStatus;
      });
    } catch {
      // silently fail
    }
  }, [addNotification]);

  useEffect(() => {
    const token = localStorage.getItem('customer_token');
    if (!token) return;
    pollShipments();
    intervalRef.current = setInterval(pollShipments, POLL_INTERVAL);
    return () => clearInterval(intervalRef.current);
  }, [pollShipments]);

  useEffect(() => {
    const onStorageChange = (e) => {
      if (e.key === 'customer_token') {
        clearInterval(intervalRef.current);
        if (e.newValue) {
          prevShipmentStatuses.current = {};
          pollShipments();
          intervalRef.current = setInterval(pollShipments, POLL_INTERVAL);
        }
      }
    };
    window.addEventListener('storage', onStorageChange);
    return () => window.removeEventListener('storage', onStorageChange);
  }, [pollShipments]);

  return (
    <NotificationContext.Provider value={{ notifications, unreadCount, markRead, markAllRead, clearAll, addNotification }}>
      {children}
    </NotificationContext.Provider>
  );
};

export default NotificationContext;`;

fs.writeFileSync(path.join(__dirname, "NotificationContext.jsx"), content);
console.log("✓ NotificationContext.jsx written successfully");
