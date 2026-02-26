"""
Helper utility functions
"""

import hashlib
import random
import string
from datetime import datetime
from typing import Optional


def generate_reference(prefix: str = "REF") -> str:
    """Generate unique reference number"""
    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
    random_str = ''.join(random.choices(string.ascii_uppercase + string.digits, k=6))
    return f"{prefix}-{timestamp}-{random_str}"


def generate_hash(text: str) -> str:
    """Generate SHA256 hash"""
    return hashlib.sha256(text.encode()).hexdigest()


def calculate_confidence_score(factors: dict) -> float:
    """
    Calculate confidence score based on multiple factors
    
    Args:
        factors: Dictionary of factor_name: score (0-1)
    
    Returns:
        Overall confidence score (0-1)
    """
    if not factors:
        return 0.5
    
    weights = {
        'data_quality': 0.3,
        'model_confidence': 0.4,
        'historical_accuracy': 0.3
    }
    
    weighted_sum = 0
    total_weight = 0
    
    for factor, score in factors.items():
        weight = weights.get(factor, 0.2)
        weighted_sum += score * weight
        total_weight += weight
    
    return weighted_sum / total_weight if total_weight > 0 else 0.5


def format_currency(amount: float, currency: str = "USD") -> str:
    """Format currency amount"""
    return f"{currency} {amount:,.2f}"


def calculate_distance(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    """
    Calculate distance between two coordinates using Haversine formula
    
    Returns:
        Distance in kilometers
    """
    from math import radians, sin, cos, sqrt, atan2
    
    R = 6371  # Earth's radius in kilometers
    
    lat1, lon1, lat2, lon2 = map(radians, [lat1, lon1, lat2, lon2])
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * atan2(sqrt(a), sqrt(1-a))
    
    return R * c


def parse_date(date_str: str) -> Optional[datetime]:
    """Parse date string to datetime object"""
    formats = [
        "%Y-%m-%d",
        "%Y-%m-%d %H:%M:%S",
        "%d/%m/%Y",
        "%m/%d/%Y"
    ]
    
    for fmt in formats:
        try:
            return datetime.strptime(date_str, fmt)
        except ValueError:
            continue
    
    return None
