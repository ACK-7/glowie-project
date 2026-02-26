"""
Delay Prediction Agent
Predicts potential shipment delays using AI
"""

from langchain_mistralai import ChatMistralAI
from loguru import logger
from config.settings import settings
from datetime import datetime, timedelta


class DelayAgent:
    """AI Agent for predicting shipment delays"""
    
    def __init__(self):
        self.llm = ChatMistralAI(
            model=settings.MISTRAL_MODEL,
            temperature=0.7,
            mistral_api_key=settings.MISTRAL_API_KEY
        )
        logger.info("DelayAgent initialized with Mistral AI")
    
    async def execute(self, input_data: dict) -> dict:
        """Execute delay prediction workflow"""
        try:
            shipment_id = input_data.get('shipment_id')
            origin = input_data.get('origin', 'Japan')
            destination = input_data.get('destination', 'Uganda')
            current_status = input_data.get('current_status', 'in_transit')
            expected_delivery = input_data.get('expected_delivery')
            current_location = input_data.get('current_location', 'Unknown')
            
            logger.info(f"Predicting delays for shipment {shipment_id}")
            
            # Create analysis prompt
            prompt = f"""Analyze this shipment for potential delays:

Shipment Details:
- Origin: {origin}
- Destination: {destination}
- Current Status: {current_status}
- Current Location: {current_location}
- Expected Delivery: {expected_delivery}
- Current Date: {datetime.now().strftime('%Y-%m-%d')}

Consider these factors:
1. Typical shipping times ({origin} to {destination})
2. Current month and seasonal factors
3. Port congestion patterns
4. Customs clearance times
5. Weather conditions (general for this time of year)

Provide:
1. Delay Risk Level (Low/Medium/High)
2. Estimated Delay Days (0-10)
3. Main Risk Factors (list 2-3)
4. Recommended Actions (list 2-3)
5. Confidence Score (0-1)

Format as JSON with keys: risk_level, estimated_delay_days, risk_factors, recommended_actions, confidence_score, reasoning"""

            response = self.llm.invoke(prompt)
            prediction = self._parse_prediction(response.content)
            
            return {
                "success": True,
                "shipment_id": shipment_id,
                "prediction": prediction,
                "analyzed_at": datetime.now().isoformat()
            }
            
        except Exception as e:
            logger.error(f"Delay prediction error: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "prediction": self._get_fallback_prediction(input_data)
            }
    
    def _parse_prediction(self, response_text: str) -> dict:
        """Parse AI response into structured prediction"""
        import json
        import re
        
        try:
            json_match = re.search(r'\{.*\}', response_text, re.DOTALL)
            if json_match:
                data = json.loads(json_match.group())
                return {
                    "risk_level": data.get("risk_level", "Medium"),
                    "estimated_delay_days": data.get("estimated_delay_days", 0),
                    "risk_factors": data.get("risk_factors", []),
                    "recommended_actions": data.get("recommended_actions", []),
                    "confidence_score": data.get("confidence_score", 0.7),
                    "reasoning": data.get("reasoning", "Analysis based on current shipping patterns")
                }
        except Exception as e:
            logger.error(f"Parsing error: {str(e)}")
        
        # Fallback parsing
        risk_level = "Medium"
        if "high" in response_text.lower():
            risk_level = "High"
        elif "low" in response_text.lower():
            risk_level = "Low"
        
        return {
            "risk_level": risk_level,
            "estimated_delay_days": 2,
            "risk_factors": ["Standard shipping variations"],
            "recommended_actions": ["Monitor shipment status regularly"],
            "confidence_score": 0.6,
            "reasoning": response_text[:200]
        }
    
    def _get_fallback_prediction(self, input_data: dict) -> dict:
        """Fallback prediction when AI is unavailable"""
        return {
            "risk_level": "Low",
            "estimated_delay_days": 0,
            "risk_factors": ["No significant risks detected"],
            "recommended_actions": ["Continue monitoring shipment"],
            "confidence_score": 0.5,
            "reasoning": "Standard prediction based on typical shipping patterns"
        }
