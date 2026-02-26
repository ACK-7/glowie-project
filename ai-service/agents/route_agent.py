"""
Route Optimization Agent
Suggests optimal shipping routes using AI
"""

from langchain_mistralai import ChatMistralAI
from loguru import logger
from config.settings import settings


class RouteAgent:
    """AI Agent for route optimization"""
    
    def __init__(self):
        self.llm = ChatMistralAI(
            model=settings.MISTRAL_MODEL,
            temperature=0.7,
            mistral_api_key=settings.MISTRAL_API_KEY
        )
        logger.info("RouteAgent initialized with Mistral AI")
    
    async def execute(self, input_data: dict) -> dict:
        """Execute route optimization workflow"""
        try:
            shipment_id = input_data.get('shipment_id')
            origin = input_data.get('origin', 'Japan')
            destination = input_data.get('destination', 'Uganda')
            priority = input_data.get('priority', 'standard')
            vehicle_type = input_data.get('vehicle_type', 'sedan')
            
            logger.info(f"Optimizing route for shipment {shipment_id}")
            
            prompt = f"""Suggest the optimal shipping route for this shipment:

Shipment Details:
- Origin: {origin}
- Destination: {destination}
- Priority: {priority}
- Vehicle Type: {vehicle_type}

Consider:
1. Shipping time (faster vs economical)
2. Cost efficiency
3. Port congestion
4. Seasonal factors
5. Route reliability

Provide:
1. Recommended Route (port to port)
2. Estimated Transit Time (days)
3. Estimated Cost Range
4. Alternative Routes (1-2 options)
5. Reasoning for recommendation

Format as JSON with keys: recommended_route, transit_time_days, cost_range, alternative_routes, reasoning, confidence_score"""

            response = self.llm.invoke(prompt)
            optimization = self._parse_optimization(response.content, origin, destination)
            
            return {
                "success": True,
                "shipment_id": shipment_id,
                "optimization": optimization
            }
            
        except Exception as e:
            logger.error(f"Route optimization error: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "optimization": self._get_fallback_route(input_data)
            }
    
    def _parse_optimization(self, response_text: str, origin: str, destination: str) -> dict:
        """Parse AI response into structured route data"""
        import json
        import re
        
        try:
            json_match = re.search(r'\{.*\}', response_text, re.DOTALL)
            if json_match:
                return json.loads(json_match.group())
        except Exception as e:
            logger.error(f"Parsing error: {str(e)}")
        
        # Fallback
        return {
            "recommended_route": f"{origin} → Port Bell, Uganda",
            "transit_time_days": 40,
            "cost_range": "$2,500 - $3,500",
            "alternative_routes": [],
            "reasoning": "Standard route based on typical shipping patterns",
            "confidence_score": 0.6
        }
    
    def _get_fallback_route(self, input_data: dict) -> dict:
        """Fallback route when AI is unavailable"""
        origin = input_data.get('origin', 'Japan')
        
        routes = {
            "Japan": {
                "route": "Yokohama/Tokyo → Mombasa → Port Bell",
                "days": 40,
                "cost": "$2,500 - $3,500"
            },
            "UK": {
                "route": "Southampton → Mombasa → Port Bell",
                "days": 35,
                "cost": "$3,000 - $4,000"
            },
            "UAE": {
                "route": "Dubai → Mombasa → Port Bell",
                "days": 30,
                "cost": "$2,000 - $3,000"
            }
        }
        
        route_data = routes.get(origin, routes["Japan"])
        
        return {
            "recommended_route": route_data["route"],
            "transit_time_days": route_data["days"],
            "cost_range": route_data["cost"],
            "alternative_routes": [],
            "reasoning": "Standard route for this origin",
            "confidence_score": 0.7
        }
