"""
Laravel API client for interacting with backend
"""

import httpx
from config.settings import settings
from loguru import logger
from typing import Optional, Dict, Any


class LaravelAPI:
    """Client for Laravel backend API"""
    
    def __init__(self):
        self.base_url = settings.LARAVEL_API_URL
        self.api_key = settings.LARAVEL_API_KEY
        self.headers = {
            "Accept": "application/json",
            "Content-Type": "application/json"
        }
        if self.api_key:
            self.headers["Authorization"] = f"Bearer {self.api_key}"
    
    async def get_shipment(self, shipment_id: int) -> Optional[Dict[str, Any]]:
        """Get shipment details"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.get(
                    f"{self.base_url}/admin/crud/shipments/{shipment_id}",
                    headers=self.headers,
                    timeout=30.0
                )
                response.raise_for_status()
                return response.json()
        except Exception as e:
            logger.error(f"Error fetching shipment: {str(e)}")
            return None
    
    async def get_route(self, route_id: int) -> Optional[Dict[str, Any]]:
        """Get route details"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.get(
                    f"{self.base_url}/routes/{route_id}",
                    headers=self.headers,
                    timeout=30.0
                )
                response.raise_for_status()
                return response.json()
        except Exception as e:
            logger.error(f"Error fetching route: {str(e)}")
            return None
    
    async def create_quote(self, quote_data: Dict[str, Any]) -> Optional[Dict[str, Any]]:
        """Create quote in Laravel"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.post(
                    f"{self.base_url}/quotes",
                    json=quote_data,
                    headers=self.headers,
                    timeout=30.0
                )
                response.raise_for_status()
                return response.json()
        except Exception as e:
            logger.error(f"Error creating quote: {str(e)}")
            return None
    
    async def update_shipment(self, shipment_id: int, data: Dict[str, Any]) -> bool:
        """Update shipment"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.put(
                    f"{self.base_url}/admin/crud/shipments/{shipment_id}",
                    json=data,
                    headers=self.headers,
                    timeout=30.0
                )
                response.raise_for_status()
                return True
        except Exception as e:
            logger.error(f"Error updating shipment: {str(e)}")
            return False
    
    async def get_customer(self, customer_id: int) -> Optional[Dict[str, Any]]:
        """Get customer details"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.get(
                    f"{self.base_url}/admin/customers/{customer_id}",
                    headers=self.headers,
                    timeout=30.0
                )
                response.raise_for_status()
                return response.json()
        except Exception as e:
            logger.error(f"Error fetching customer: {str(e)}")
            return None
    
    async def get_historical_shipments(self, filters: Dict[str, Any] = None) -> list:
        """Get historical shipment data for ML training"""
        try:
            async with httpx.AsyncClient() as client:
                response = await client.get(
                    f"{self.base_url}/admin/crud/shipments",
                    params=filters or {},
                    headers=self.headers,
                    timeout=30.0
                )
                response.raise_for_status()
                data = response.json()
                return data.get('data', [])
        except Exception as e:
            logger.error(f"Error fetching historical shipments: {str(e)}")
            return []


# Singleton instance
laravel_api = LaravelAPI()
