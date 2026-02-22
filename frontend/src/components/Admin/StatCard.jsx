import React from 'react';
import { FaArrowUp, FaArrowDown } from 'react-icons/fa';

const StatCard = ({ 
  icon: Icon, 
  title, 
  value, 
  trend, 
  trendLabel,
  color = 'blue'
}) => {
  const colorConfig = {
    blue: {
      bg: 'bg-blue-900/30',
      icon: 'text-blue-400',
      trend: trend > 0 ? 'text-green-400' : trend < 0 ? 'text-red-400' : 'text-gray-400'
    },
    green: {
      bg: 'bg-green-900/30',
      icon: 'text-green-400',
      trend: trend > 0 ? 'text-green-400' : trend < 0 ? 'text-red-400' : 'text-gray-400'
    },
    purple: {
      bg: 'bg-purple-900/30',
      icon: 'text-purple-400',
      trend: trend > 0 ? 'text-green-400' : trend < 0 ? 'text-red-400' : 'text-gray-400'
    },
    yellow: {
      bg: 'bg-yellow-900/30',
      icon: 'text-yellow-400',
      trend: trend > 0 ? 'text-green-400' : trend < 0 ? 'text-red-400' : 'text-gray-400'
    }
  };

  const config = colorConfig[color] || colorConfig.blue;

  return (
    <div className="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-6 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-400 mb-1">{title}</p>
          <p className="text-3xl font-bold text-white">{value}</p>
          {trend !== undefined && trendLabel && (
            <div className="flex items-center mt-2">
              {trend > 0 ? (
                <FaArrowUp className="h-3 w-3 text-green-400 mr-1" />
              ) : trend < 0 ? (
                <FaArrowDown className="h-3 w-3 text-red-400 mr-1" />
              ) : null}
              <span className={`text-sm font-medium ${config.trend}`}>
                {trend > 0 ? '+' : ''}{trend}%
              </span>
              <span className="text-sm text-gray-500 ml-1">{trendLabel}</span>
            </div>
          )}
        </div>
        <div className={`${config.bg} p-3 rounded-full`}>
          <Icon className={`h-6 w-6 ${config.icon}`} />
        </div>
      </div>
    </div>
  );
};

export default StatCard;
