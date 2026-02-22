/**
 * Tests for safe rendering utilities
 */

import { 
  safeRender, 
  safeCurrency, 
  safePercentage, 
  safeDate, 
  safeArray, 
  safeStatus,
  isSafeToRender 
} from '../safeRender';

describe('safeRender', () => {
  test('handles null and undefined', () => {
    expect(safeRender(null)).toBe('N/A');
    expect(safeRender(undefined)).toBe('N/A');
    expect(safeRender(null, 'Custom')).toBe('Custom');
  });

  test('handles primitive types', () => {
    expect(safeRender('hello')).toBe('hello');
    expect(safeRender(42)).toBe('42');
    expect(safeRender(true)).toBe('Yes');
    expect(safeRender(false)).toBe('No');
  });

  test('handles arrays', () => {
    expect(safeRender([])).toBe('None');
    expect(safeRender(['a', 'b', 'c'])).toBe('a, b, c');
    expect(safeRender([1, 2, 3])).toBe('1, 2, 3');
  });

  test('handles objects safely', () => {
    expect(safeRender({})).toBe('[Object]');
    expect(safeRender({ a: 1, b: 2 })).toBe('a: 1, b: 2');
    expect(safeRender({ a: 1, b: 2, c: 3, d: 4 })).toBe('[Object]'); // Too many keys
  });

  test('handles Date objects', () => {
    const date = new Date('2023-01-01');
    expect(safeRender(date)).toBe(date.toLocaleDateString());
  });
});

describe('safeCurrency', () => {
  test('formats currency correctly', () => {
    expect(safeCurrency(1000)).toBe('$1,000');
    expect(safeCurrency('1500')).toBe('$1,500');
    expect(safeCurrency(null)).toBe('$0');
    expect(safeCurrency('invalid')).toBe('$0');
    expect(safeCurrency(1000, '€')).toBe('€1,000');
  });
});

describe('safePercentage', () => {
  test('formats percentage correctly', () => {
    expect(safePercentage(50)).toBe('50%');
    expect(safePercentage('75')).toBe('75%');
    expect(safePercentage(null)).toBe('0%');
    expect(safePercentage('invalid')).toBe('0%');
  });
});

describe('safeDate', () => {
  test('formats dates correctly', () => {
    expect(safeDate('2023-01-01')).toMatch(/Jan/);
    expect(safeDate(null)).toBe('N/A');
    expect(safeDate('invalid')).toBe('Invalid Date');
  });
});

describe('safeArray', () => {
  test('formats arrays correctly', () => {
    expect(safeArray([])).toBe('None');
    expect(safeArray(['a', 'b'])).toBe('a, b');
    expect(safeArray(['a', 'b', 'c', 'd', 'e'])).toBe('a, b, c (+2 more)');
  });
});

describe('safeStatus', () => {
  test('returns status configuration', () => {
    const active = safeStatus('active');
    expect(active.text).toBe('Active');
    expect(active.className).toBe('text-green-400');
    
    const unknown = safeStatus('unknown_status');
    expect(unknown.text).toBe('unknown_status');
    expect(unknown.className).toBe('text-gray-400');
  });
});

describe('isSafeToRender', () => {
  test('identifies safe values', () => {
    expect(isSafeToRender('string')).toBe(true);
    expect(isSafeToRender(42)).toBe(true);
    expect(isSafeToRender(true)).toBe(true);
    expect(isSafeToRender(null)).toBe(true);
    expect(isSafeToRender(undefined)).toBe(true);
    expect(isSafeToRender({})).toBe(false);
    expect(isSafeToRender([])).toBe(false);
  });
});

// Test cases that would cause React object rendering errors
describe('React object rendering prevention', () => {
  test('prevents object rendering errors', () => {
    const problematicData = {
      user: { name: 'John', email: 'john@example.com' },
      items: ['item1', 'item2'],
      metadata: { created: new Date(), count: 5 }
    };

    // These should all return strings, not objects
    expect(typeof safeRender(problematicData.user)).toBe('string');
    expect(typeof safeRender(problematicData.items)).toBe('string');
    expect(typeof safeRender(problematicData.metadata)).toBe('string');
  });

  test('handles nested objects safely', () => {
    const nested = {
      level1: {
        level2: {
          level3: 'deep value'
        }
      }
    };

    const result = safeRender(nested);
    expect(typeof result).toBe('string');
    expect(result).not.toBe('[object Object]');
  });
});