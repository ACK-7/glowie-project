/**
 * Safe rendering utilities to prevent React object rendering errors
 */

/**
 * Safely renders any value as a string for React components
 * @param {any} value - The value to render
 * @param {string} fallback - Fallback string if value is null/undefined
 * @returns {string} - Safe string representation
 */
export const safeRender = (value, fallback = 'N/A') => {
  // Handle null/undefined
  if (value === null || value === undefined) {
    return fallback;
  }
  
  // Handle boolean values
  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No';
  }
  
  // Handle numbers
  if (typeof value === 'number') {
    return value.toString();
  }
  
  // Handle strings
  if (typeof value === 'string') {
    return value;
  }
  
  // Handle arrays
  if (Array.isArray(value)) {
    if (value.length === 0) return 'None';
    return value.map(item => safeRender(item, '')).join(', ');
  }
  
  // Handle objects
  if (typeof value === 'object') {
    // Check for common object patterns that should be rendered as strings
    if (value.toString && typeof value.toString === 'function' && value.toString() !== '[object Object]') {
      return value.toString();
    }
    
    // Handle Date objects
    if (value instanceof Date) {
      return value.toLocaleDateString();
    }
    
    // For plain objects, try to extract meaningful data
    try {
      // If it's a small object, show key-value pairs
      const keys = Object.keys(value);
      if (keys.length <= 3) {
        return keys.map(key => `${key}: ${safeRender(value[key], '')}`).join(', ');
      }
      
      // For larger objects, just show the type
      return '[Object]';
    } catch (e) {
      return '[Object]';
    }
  }
  
  // Fallback for any other type
  try {
    return String(value);
  } catch (e) {
    return fallback;
  }
};

/**
 * Safely renders a currency value
 * @param {number|string} value - The currency value
 * @param {string} currency - Currency symbol (default: $)
 * @returns {string} - Formatted currency string
 */
export const safeCurrency = (value, currency = '$') => {
  if (value === null || value === undefined || value === '') {
    return `${currency}0`;
  }
  
  const numValue = typeof value === 'string' ? parseFloat(value) : value;
  
  if (isNaN(numValue)) {
    return `${currency}0`;
  }
  
  return `${currency}${numValue.toLocaleString()}`;
};

/**
 * Safely renders a percentage value
 * @param {number|string} value - The percentage value
 * @returns {string} - Formatted percentage string
 */
export const safePercentage = (value) => {
  if (value === null || value === undefined || value === '') {
    return '0%';
  }
  
  const numValue = typeof value === 'string' ? parseFloat(value) : value;
  
  if (isNaN(numValue)) {
    return '0%';
  }
  
  return `${numValue}%`;
};

/**
 * Safely renders a date value
 * @param {string|Date} value - The date value
 * @param {object} options - Formatting options
 * @returns {string} - Formatted date string
 */
export const safeDate = (value, options = {}) => {
  if (!value) return 'N/A';
  
  try {
    const date = new Date(value);
    if (isNaN(date.getTime())) return 'Invalid Date';
    
    const defaultOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      ...options
    };
    
    return date.toLocaleDateString('en-US', defaultOptions);
  } catch (e) {
    return 'Invalid Date';
  }
};

/**
 * Safely renders an array as a comma-separated list
 * @param {Array} value - The array value
 * @param {number} maxItems - Maximum items to show (default: 3)
 * @returns {string} - Formatted array string
 */
export const safeArray = (value, maxItems = 3) => {
  if (!Array.isArray(value) || value.length === 0) {
    return 'None';
  }
  
  const items = value.slice(0, maxItems).map(item => safeRender(item, ''));
  const result = items.join(', ');
  
  if (value.length > maxItems) {
    return `${result} (+${value.length - maxItems} more)`;
  }
  
  return result;
};

/**
 * Safely renders a status with appropriate styling classes
 * @param {string} status - The status value
 * @returns {object} - Object with text and className
 */
export const safeStatus = (status) => {
  const statusStr = safeRender(status, 'unknown').toLowerCase();
  
  const statusConfig = {
    active: { text: 'Active', className: 'text-green-400' },
    inactive: { text: 'Inactive', className: 'text-gray-400' },
    pending: { text: 'Pending', className: 'text-yellow-400' },
    approved: { text: 'Approved', className: 'text-green-400' },
    rejected: { text: 'Rejected', className: 'text-red-400' },
    completed: { text: 'Completed', className: 'text-blue-400' },
    cancelled: { text: 'Cancelled', className: 'text-red-400' },
    suspended: { text: 'Suspended', className: 'text-red-400' },
    verified: { text: 'Verified', className: 'text-green-400' },
    unverified: { text: 'Unverified', className: 'text-yellow-400' }
  };
  
  return statusConfig[statusStr] || { text: statusStr, className: 'text-gray-400' };
};

/**
 * Checks if a value is safe to render in React
 * @param {any} value - The value to check
 * @returns {boolean} - True if safe to render
 */
export const isSafeToRender = (value) => {
  if (value === null || value === undefined) return true;
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') return true;
  // Check if it's a React element without importing React
  if (value && typeof value === 'object' && value.$$typeof) return true;
  return false;
};

/**
 * Validates and safely renders React children
 * @param {any} children - The children to render
 * @returns {string|any} - Safe children
 */
export const safeChildren = (children) => {
  if (isSafeToRender(children)) {
    return children;
  }
  
  return safeRender(children);
};