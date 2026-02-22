import React, { useState, useRef, useEffect } from 'react';
import { FaEllipsisV } from 'react-icons/fa';

const DropdownMenu = ({ items, onItemClick, className = '', buttonClassName = '' }) => {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      // Use a small delay to avoid closing immediately when opening
      const timeoutId = setTimeout(() => {
        document.addEventListener('mousedown', handleClickOutside);
      }, 10);
      
      return () => {
        clearTimeout(timeoutId);
        document.removeEventListener('mousedown', handleClickOutside);
      };
    }
  }, [isOpen]);

  const handleItemClick = (item, event) => {
    console.log('DropdownMenu: Item clicked', { label: item.label, hasOnClick: !!item.onClick });
    event.preventDefault();
    event.stopPropagation();
    if (item.onClick) {
      console.log('DropdownMenu: Calling item.onClick()');
      item.onClick();
    } else if (onItemClick) {
      console.log('DropdownMenu: Calling onItemClick()');
      onItemClick(item);
    }
    setIsOpen(false);
  };

  return (
    <div className={`relative inline-block text-left ${className}`} ref={dropdownRef}>
      <button
        type="button"
        className={`inline-flex items-center justify-center w-8 h-8 text-gray-400 hover:text-gray-300 hover:bg-gray-700/50 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors ${buttonClassName}`}
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          setIsOpen(!isOpen);
        }}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <FaEllipsisV className="w-4 h-4" />
      </button>

      {isOpen && (
        <div className="absolute right-0 z-[100] mt-2 w-48 origin-top-right bg-[#1a1f28] border border-gray-700 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
          <div className="py-1">
            {items.map((item, index) => (
              <button
                key={index}
                type="button"
                onClick={(e) => handleItemClick(item, e)}
                disabled={item.disabled}
                className={`
                  flex items-center w-full px-4 py-2 text-sm text-left transition-colors
                  ${item.disabled 
                    ? 'text-gray-500 cursor-not-allowed' 
                    : `text-gray-300 hover:bg-gray-700/50 hover:text-white ${item.className || ''}`
                  }
                  ${item.danger ? 'hover:bg-red-900/20 hover:text-red-400' : ''}
                `}
              >
                {item.icon && (
                  <span className="mr-3 flex-shrink-0">
                    {item.icon}
                  </span>
                )}
                {item.label}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default DropdownMenu;