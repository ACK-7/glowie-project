"""
Customer Support Agent
Handles customer inquiries about shipping, tracking, and general support
"""

from langchain_mistralai import ChatMistralAI
from loguru import logger
from config.settings import settings


class SupportAgent:
    """AI Agent for customer support"""
    
    def __init__(self):
        self.llm = ChatMistralAI(
            model=settings.MISTRAL_MODEL,
            temperature=0.7,
            mistral_api_key=settings.MISTRAL_API_KEY
        )
        logger.info("SupportAgent initialized with Mistral AI")
    
    async def execute(self, input_data: dict) -> dict:
        """Execute support query workflow"""
        try:
            query = input_data.get('query', '')
            context = input_data.get('context', 'general')
            customer_id = input_data.get('customer_id', 0)
            
            logger.info(f"Processing support query: {query[:50]}...")
            
            # Create system prompt with company knowledge
            system_prompt = """You are a helpful customer support agent for ShipWithGlowie, a car shipping company that ships vehicles from Japan, UK, and UAE to Uganda.

Company Information:
- We ship cars, SUVs, trucks, motorcycles, and luxury vehicles
- Shipping methods: RoRo (Roll-on/Roll-off) and Container shipping
- Origins: Japan, UK, UAE
- Destination: Uganda (Port Bell, Kampala)
- Shipping time: 25-50 days depending on origin
- We handle customs clearance, documentation, and inland transport

Pricing (approximate):
- Japan RoRo: $1,500-2,000 base
- UK RoRo: $1,800-2,300 base
- UAE RoRo: $1,100-1,600 base
- Container shipping: +$500
- Additional fees: Customs duty (~$800), VAT (18%), Levies (~$350)

Services:
- Free quote generation
- Real-time shipment tracking
- Full insurance coverage
- Customs clearance assistance
- Inland transport to any location in Uganda
- Document processing support

Be friendly, professional, and helpful. Provide specific information when possible. If you don't know something, suggest they contact support or request a quote."""

            # Generate response
            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": query}
            ]
            
            response = self.llm.invoke(messages)
            response_text = response.content
            
            # Determine if human assistance is needed
            requires_human = any(keyword in query.lower() for keyword in [
                'complaint', 'problem', 'issue', 'urgent', 'emergency', 
                'speak to', 'talk to', 'human', 'manager', 'refund'
            ])
            
            return {
                "success": True,
                "response": response_text,
                "confidence_score": 0.85,
                "requires_human": requires_human,
                "suggestions": self._get_suggestions(query)
            }
            
        except Exception as e:
            logger.error(f"Support agent error: {str(e)}")
            return {
                "success": False,
                "response": self._get_fallback_response(input_data.get('query', '')),
                "confidence_score": 0.5,
                "requires_human": False
            }
    
    def _get_suggestions(self, query: str) -> list:
        """Get suggested follow-up questions"""
        query_lower = query.lower()
        
        if 'cost' in query_lower or 'price' in query_lower:
            return [
                "How long does shipping take?",
                "What documents do I need?",
                "Can I get a quote?"
            ]
        elif 'time' in query_lower or 'long' in query_lower:
            return [
                "How much does shipping cost?",
                "Can I track my shipment?",
                "What's included in the service?"
            ]
        elif 'track' in query_lower:
            return [
                "How do I get a tracking number?",
                "What if my shipment is delayed?",
                "Can I change delivery location?"
            ]
        else:
            return [
                "How much does shipping cost?",
                "How long does shipping take?",
                "What documents do I need?"
            ]
    
    def _get_fallback_response(self, query: str) -> str:
        """Fallback response when AI is unavailable"""
        query_lower = query.lower()
        
        if 'cost' in query_lower or 'price' in query_lower:
            return "Shipping costs vary based on vehicle type and origin. Typical range is $2,500-$4,500. Get an instant quote on our website!"
        elif 'time' in query_lower or 'long' in query_lower:
            return "Shipping takes 25-50 days: Japan (40-45 days), UK (30-35 days), UAE (25-30 days). This includes customs clearance."
        elif 'track' in query_lower:
            return "You can track your shipment in real-time using your tracking number on our Track Shipment page."
        elif 'document' in query_lower:
            return "You'll need: Vehicle registration, Bill of sale, Valid ID, Import permit. We'll guide you through the process!"
        else:
            return "Thank you for contacting ShipWithGlowie! For detailed assistance, please visit our FAQ page or request a quote. You can also reach us at support@shipwithglowie.com or +256 700 000 000."

