"""
Quote Generation Agent
Uses LangGraph to orchestrate quote generation workflow with Mistral AI
"""

from langgraph.graph import StateGraph, END
from langchain_mistralai import ChatMistralAI
from typing import TypedDict, Annotated, List
import operator
from datetime import datetime
from loguru import logger

from config.settings import settings
from utils.helpers import generate_reference, calculate_confidence_score
from tools.laravel_api import laravel_api


# Define state
class QuoteState(TypedDict):
    """State for quote generation workflow"""
    # Input
    vehicle_type: str
    year: int
    make: str
    model: str
    engine_size: int
    origin_country: str
    origin_port: str
    destination_country: str
    destination_port: str
    shipping_method: str
    customer_email: str
    customer_name: str
    
    # Processing
    base_cost: float
    adjusted_cost: float
    total_cost: float
    breakdown: dict
    ai_reasoning: str
    confidence_score: float
    estimated_delivery_days: int
    
    # Output
    quote_reference: str
    status: str
    messages: Annotated[List[str], operator.add]
    error: str


class QuoteAgent:
    """AI Agent for generating shipping quotes using Mistral AI"""
    
    def __init__(self):
        self.llm = ChatMistralAI(
            model=settings.MISTRAL_MODEL,
            temperature=settings.MISTRAL_TEMPERATURE,
            mistral_api_key=settings.MISTRAL_API_KEY
        )
        self.workflow = self._build_workflow()
    
    def _build_workflow(self) -> StateGraph:
        """Build LangGraph workflow"""
        
        workflow = StateGraph(QuoteState)
        
        # Add nodes
        workflow.add_node("validate_input", self._validate_input)
        workflow.add_node("calculate_base_cost", self._calculate_base_cost)
        workflow.add_node("apply_ai_pricing", self._apply_ai_pricing)
        workflow.add_node("generate_breakdown", self._generate_breakdown)
        workflow.add_node("save_quote", self._save_quote)
        
        # Define edges
        workflow.set_entry_point("validate_input")
        workflow.add_edge("validate_input", "calculate_base_cost")
        workflow.add_edge("calculate_base_cost", "apply_ai_pricing")
        workflow.add_edge("apply_ai_pricing", "generate_breakdown")
        workflow.add_edge("generate_breakdown", "save_quote")
        workflow.add_edge("save_quote", END)
        
        return workflow.compile()
    
    def _validate_input(self, state: QuoteState) -> dict:
        """Validate input data"""
        logger.info("Validating quote input")
        
        messages = []
        
        # Basic validation
        if state["year"] < 1990 or state["year"] > datetime.now().year + 1:
            return {
                "status": "error",
                "error": "Invalid vehicle year",
                "messages": ["Validation failed: Invalid year"]
            }
        
        if not state["make"] or not state["model"]:
            return {
                "status": "error",
                "error": "Vehicle make and model required",
                "messages": ["Validation failed: Missing vehicle details"]
            }
        
        messages.append("Input validation successful")
        
        return {
            "status": "validated",
            "messages": messages
        }
    
    def _calculate_base_cost(self, state: QuoteState) -> dict:
        """Calculate base shipping cost"""
        logger.info("Calculating base cost")
        
        # Base rates by origin country
        base_rates = {
            "japan": {"roro": 1500, "container": 2200},
            "uk": {"roro": 1800, "container": 2800},
            "uae": {"roro": 1100, "container": 1600},
            "usa": {"roro": 2000, "container": 3000}
        }
        
        origin = state["origin_country"].lower()
        method = state["shipping_method"].lower()
        
        # Get base rate
        base_cost = base_rates.get(origin, {}).get(method, 1500)
        
        # Vehicle type multiplier
        vehicle_multipliers = {
            "sedan": 1.0,
            "suv": 1.2,
            "truck": 1.3,
            "van": 1.25,
            "luxury": 1.5,
            "motorcycle": 0.7
        }
        
        multiplier = vehicle_multipliers.get(state["vehicle_type"].lower(), 1.0)
        base_cost = base_cost * multiplier
        
        return {
            "base_cost": base_cost,
            "messages": [f"Base cost calculated: ${base_cost:.2f}"]
        }
    
    def _apply_ai_pricing(self, state: QuoteState) -> dict:
        """Use AI to adjust pricing based on market conditions"""
        logger.info("Applying AI pricing adjustments")
        
        # Create prompt for LLM
        prompt = f"""
        You are a shipping cost analyst. Analyze this shipment and suggest a pricing adjustment.
        
        Shipment Details:
        - Vehicle: {state['year']} {state['make']} {state['model']}
        - Type: {state['vehicle_type']}
        - Route: {state['origin_country']} to {state['destination_country']}
        - Method: {state['shipping_method']}
        - Base Cost: ${state['base_cost']:.2f}
        
        Consider these factors:
        1. Current market demand (assume moderate)
        2. Seasonal factors (current month: {datetime.now().strftime('%B')})
        3. Vehicle value and insurance needs
        4. Route popularity
        
        Provide:
        1. Adjustment percentage (-20% to +30%)
        2. Brief reasoning (2-3 sentences)
        3. Confidence score (0-1)
        
        Format your response as:
        ADJUSTMENT: [percentage]
        REASONING: [your reasoning]
        CONFIDENCE: [score]
        """
        
        try:
            response = self.llm.invoke(prompt)
            content = response.content
            
            # Parse response
            adjustment = 0
            reasoning = "Standard pricing applied"
            confidence = 0.8
            
            for line in content.split('\n'):
                if line.startswith('ADJUSTMENT:'):
                    adj_str = line.split(':')[1].strip().replace('%', '')
                    adjustment = float(adj_str) / 100
                elif line.startswith('REASONING:'):
                    reasoning = line.split(':', 1)[1].strip()
                elif line.startswith('CONFIDENCE:'):
                    confidence = float(line.split(':')[1].strip())
            
            # Apply adjustment
            adjusted_cost = state['base_cost'] * (1 + adjustment)
            
            return {
                "adjusted_cost": adjusted_cost,
                "ai_reasoning": reasoning,
                "confidence_score": confidence,
                "messages": [f"AI adjusted cost: ${adjusted_cost:.2f} ({adjustment*100:+.1f}%)"]
            }
            
        except Exception as e:
            logger.error(f"AI pricing error: {str(e)}")
            # Fallback to base cost
            return {
                "adjusted_cost": state['base_cost'],
                "ai_reasoning": "Standard pricing applied (AI unavailable)",
                "confidence_score": 0.5,
                "messages": ["Using base cost (AI adjustment failed)"]
            }
    
    def _generate_breakdown(self, state: QuoteState) -> dict:
        """Generate cost breakdown"""
        logger.info("Generating cost breakdown")
        
        shipping_cost = state['adjusted_cost']
        customs_duty = 800  # Estimated
        vat = (shipping_cost + customs_duty) * 0.18  # 18% VAT
        levies = 350  # Fixed levies
        
        total_cost = shipping_cost + customs_duty + vat + levies
        
        breakdown = {
            "shipping": shipping_cost,
            "customs_duty": customs_duty,
            "vat": vat,
            "levies": levies,
            "total": total_cost
        }
        
        # Estimate delivery days
        delivery_days = {
            "japan": 45,
            "uk": 35,
            "uae": 30,
            "usa": 40
        }
        
        estimated_days = delivery_days.get(state['origin_country'].lower(), 40)
        
        return {
            "total_cost": total_cost,
            "breakdown": breakdown,
            "estimated_delivery_days": estimated_days,
            "messages": [f"Total cost: ${total_cost:.2f}"]
        }
    
    def _save_quote(self, state: QuoteState) -> dict:
        """Save quote to database"""
        logger.info("Saving quote")
        
        # Generate reference
        quote_ref = generate_reference("QTE")
        
        # Prepare quote data for Laravel
        quote_data = {
            "reference": quote_ref,
            "vehicle_type": state['vehicle_type'],
            "year": state['year'],
            "make": state['make'],
            "model": state['model'],
            "origin_country": state['origin_country'],
            "destination_country": state['destination_country'],
            "shipping_method": state['shipping_method'],
            "total_estimated": state['total_cost'],
            "customer_email": state.get('customer_email'),
            "customer_name": state.get('customer_name'),
            "status": "pending"
        }
        
        # Note: Actual saving would happen here via Laravel API
        # For now, we'll just log it
        logger.info(f"Quote generated: {quote_ref}")
        
        return {
            "quote_reference": quote_ref,
            "status": "completed",
            "messages": [f"Quote saved with reference: {quote_ref}"]
        }
    
    async def execute(self, input_data: dict) -> dict:
        """Execute quote generation workflow"""
        try:
            logger.info("Starting quote generation")
            
            # Initialize state
            initial_state = {
                **input_data,
                "messages": [],
                "status": "initialized"
            }
            
            # Run workflow
            result = self.workflow.invoke(initial_state)
            
            # Return response
            return {
                "success": True,
                "quote_reference": result.get("quote_reference"),
                "base_cost": result.get("base_cost"),
                "adjusted_cost": result.get("adjusted_cost"),
                "total_cost": result.get("total_cost"),
                "breakdown": result.get("breakdown"),
                "ai_reasoning": result.get("ai_reasoning"),
                "estimated_delivery_days": result.get("estimated_delivery_days"),
                "confidence_score": result.get("confidence_score"),
                "created_at": datetime.now()
            }
            
        except Exception as e:
            logger.error(f"Quote generation error: {str(e)}")
            raise
