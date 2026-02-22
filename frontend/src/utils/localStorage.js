/**
 * Safe localStorage utilities with error handling
 */

export const safeGetItem = (key) => {
  try {
    const item = localStorage.getItem(key);
    if (!item || item === 'undefined' || item === 'null') {
      return null;
    }
    return item;
  } catch (error) {
    console.error(`Error getting localStorage item '${key}':`, error);
    return null;
  }
};

export const safeGetJSON = (key) => {
  try {
    const item = safeGetItem(key);
    if (!item) {
      return null;
    }
    return JSON.parse(item);
  } catch (error) {
    console.error(`Error parsing JSON from localStorage '${key}':`, error);
    // Clear corrupted data
    localStorage.removeItem(key);
    return null;
  }
};

export const safeSetItem = (key, value) => {
  try {
    localStorage.setItem(key, value);
    return true;
  } catch (error) {
    console.error(`Error setting localStorage item '${key}':`, error);
    return false;
  }
};

export const safeSetJSON = (key, value) => {
  try {
    const jsonString = JSON.stringify(value);
    return safeSetItem(key, jsonString);
  } catch (error) {
    console.error(`Error stringifying JSON for localStorage '${key}':`, error);
    return false;
  }
};

export const safeRemoveItem = (key) => {
  try {
    localStorage.removeItem(key);
    return true;
  } catch (error) {
    console.error(`Error removing localStorage item '${key}':`, error);
    return false;
  }
};

export const clearCorruptedData = () => {
  const keysToCheck = ['customer_token', 'customer_data', 'admin_token', 'admin_data'];
  
  keysToCheck.forEach(key => {
    const item = localStorage.getItem(key);
    if (item === 'undefined' || item === 'null') {
      console.warn(`Clearing corrupted localStorage item: ${key}`);
      localStorage.removeItem(key);
    }
  });
};

// Initialize cleanup on module load
clearCorruptedData();