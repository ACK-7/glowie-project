"""
ShipWithGlowie AI Service - Main Application
FastAPI server with LangGraph agent orchestration
"""

from fastapi import FastAPI, HTTPException, Depends, UploadFile, File, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from contextlib import asynccontextmanager
import uvicorn
from loguru import logger

from config.settings import settings
from agents.quote_agent import QuoteAgent
from agents.route_agent import RouteAgent
from agents.document_agent import DocumentAgent
from agents.support_agent import SupportAgent
from agents.delay_agent import DelayAgent
from agents.notification_agent import NotificationAgent
from utils.database import init_db, close_db
from utils.redis_client import init_redis, close_redis
from models.schemas import (
    QuoteRequest,
    QuoteResponse,
    RouteRequest,
    RouteResponse,
    SupportRequest,
    SupportResponse,
    DocumentResponse,
    DelayPredictionRequest,
    DelayPredictionResponse,
    HealthResponse
)


# Lifespan context manager for startup/shutdown
@asynccontextmanager
async def lifespan(app: FastAPI):
    """Handle startup and shutdown events"""
    # Startup
    logger.info("Starting AI Service...")
    await init_db()
    await init_redis()
    logger.info("AI Service started successfully")
    
    yield
    
    # Shutdown
    logger.info("Shutting down AI Service...")
    await close_db()
    await close_redis()
    logger.info("AI Service shut down successfully")


# Initialize FastAPI app
app = FastAPI(
    title=settings.APP_NAME,
    description="AI-powered automation service for car shipping logistics",
    version="1.0.0",
    lifespan=lifespan
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:5173",
        "http://localhost:5174",
        "http://localhost:5175",
        "http://localhost:5176",
        "http://localhost:3000",
        "http://localhost:8000",
        "http://127.0.0.1:5173",
        "http://127.0.0.1:5174",
        "http://127.0.0.1:8000",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize agents (lazy loading)
_quote_agent = None
_route_agent = None
_document_agent = None
_support_agent = None
_delay_agent = None
_notification_agent = None


def get_quote_agent() -> QuoteAgent:
    """Get or create quote agent instance"""
    global _quote_agent
    if _quote_agent is None:
        _quote_agent = QuoteAgent()
    return _quote_agent


def get_route_agent() -> RouteAgent:
    """Get or create route agent instance"""
    global _route_agent
    if _route_agent is None:
        _route_agent = RouteAgent()
    return _route_agent


def get_document_agent() -> DocumentAgent:
    """Get or create document agent instance"""
    global _document_agent
    if _document_agent is None:
        _document_agent = DocumentAgent()
    return _document_agent


def get_support_agent() -> SupportAgent:
    """Get or create support agent instance"""
    global _support_agent
    if _support_agent is None:
        _support_agent = SupportAgent()
    return _support_agent


def get_delay_agent() -> DelayAgent:
    """Get or create delay prediction agent instance"""
    global _delay_agent
    if _delay_agent is None:
        _delay_agent = DelayAgent()
    return _delay_agent


def get_notification_agent() -> NotificationAgent:
    """Get or create notification agent instance"""
    global _notification_agent
    if _notification_agent is None:
        _notification_agent = NotificationAgent()
    return _notification_agent


# Health check endpoint
@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": settings.APP_NAME,
        "version": "1.0.0",
        "environment": settings.APP_ENV
    }


# Quote Generation Agent
@app.post("/agents/quote", response_model=QuoteResponse)
async def generate_quote(
    request: QuoteRequest,
    agent: QuoteAgent = Depends(get_quote_agent)
):
    """
    Generate shipping quote using AI
    
    This agent:
    - Validates vehicle and route information
    - Calculates base shipping costs
    - Uses AI to adjust pricing based on market conditions
    - Generates professional quote documents
    """
    try:
        logger.info(f"Generating quote for {request.make} {request.model}")
        result = await agent.execute(request.dict())
        return result
    except Exception as e:
        logger.error(f"Quote generation error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Route Optimization Agent
@app.post("/agents/route", response_model=RouteResponse)
async def optimize_route(
    request: RouteRequest,
    agent: RouteAgent = Depends(get_route_agent)
):
    """
    Optimize shipping route using AI
    
    This agent:
    - Analyzes multiple route options
    - Considers traffic, weather, and port conditions
    - Calculates cost vs. time trade-offs
    - Suggests optimal route with reasoning
    """
    try:
        logger.info(f"Optimizing route for shipment {request.shipment_id}")
        result = await agent.execute(request.dict())
        return result
    except Exception as e:
        logger.error(f"Route optimization error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Document Processing Agent
@app.post("/agents/document", response_model=DocumentResponse)
async def process_document(
    file: UploadFile = File(...),
    document_type: str = Form("bill_of_lading"),
    agent: DocumentAgent = Depends(get_document_agent)
):
    """
    Process and extract document data using AI OCR
    
    This agent:
    - Processes uploaded documents (PDF, images)
    - Extracts key information using OCR
    - Validates extracted data
    - Flags inconsistencies for human review
    """
    try:
        logger.info(f"Processing document: {file.filename}, type: {document_type}")
        result = await agent.execute(file, document_type)
        return result
    except Exception as e:
        logger.error(f"Document processing error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Customer Support Agent
@app.post("/agents/support", response_model=SupportResponse)
async def support_query(
    request: SupportRequest,
    agent: SupportAgent = Depends(get_support_agent)
):
    """
    Handle customer support query using AI
    
    This agent:
    - Understands natural language queries
    - Retrieves relevant shipment information
    - Generates helpful responses
    - Escalates complex issues to humans
    """
    try:
        logger.info(f"Processing support query for customer {request.customer_id}")
        result = await agent.execute(request.dict())
        return result
    except Exception as e:
        logger.error(f"Support query error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Delay Prediction Agent
@app.post("/agents/delay-prediction", response_model=DelayPredictionResponse)
async def predict_delays(
    request: DelayPredictionRequest,
    agent: DelayAgent = Depends(get_delay_agent)
):
    """
    Predict potential shipment delays using AI
    
    This agent:
    - Analyzes risk factors (weather, traffic, port congestion)
    - Predicts potential delays with confidence scores
    - Generates proactive alerts
    - Suggests mitigation strategies
    """
    try:
        logger.info(f"Predicting delays for shipment {request.shipment_id}")
        result = await agent.execute(request.dict())
        return result
    except Exception as e:
        logger.error(f"Delay prediction error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Notification Agent
@app.post("/agents/notify")
async def send_notification(
    event_type: str,
    data: dict,
    agent: NotificationAgent = Depends(get_notification_agent)
):
    """
    Send intelligent notifications
    
    This agent:
    - Determines notification priority
    - Selects appropriate channels (email, SMS, push)
    - Personalizes message content
    - Handles delivery failures with retries
    """
    try:
        logger.info(f"Sending notification for event: {event_type}")
        result = await agent.execute(event_type, data)
        return result
    except Exception as e:
        logger.error(f"Notification error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))



# Vehicle Description Parser (Natural Language → Form Fields)
@app.post("/agents/parse-description")
async def parse_vehicle_description(payload: dict):
    """
    Parse a natural language vehicle description into structured form fields.
    e.g. "2018 BMW X5 from Japan" → {year, make, model, vehicleType, originCountry}
    """
    from langchain_mistralai import ChatMistralAI
    from config.settings import settings

    description = payload.get("description", "").strip()
    if not description:
        raise HTTPException(status_code=422, detail="description is required")

    llm = ChatMistralAI(
        model=settings.MISTRAL_MODEL,
        temperature=0,
        mistral_api_key=settings.MISTRAL_API_KEY
    )

    prompt = f"""
    Extract vehicle and shipping details from this text: "{description}"

    Return ONLY a JSON object with these fields (use empty string if unknown):
    - year (4-digit number as string, e.g. "2018")
    - make (brand name, e.g. "Toyota")
    - model (model name, e.g. "Land Cruiser")
    - vehicleType (one of: sedan, suv, truck, van, luxury, motorcycle, hatchback, wagon, coupe, convertible — pick best match)
    - engineSize (numeric cc, e.g. "2500", empty if unknown)
    - originCountry (one of: japan, uk, uae, usa — or empty if unknown)

    Return only valid JSON, no explanation.
    """

    try:
        response = llm.invoke(prompt)
        import json, re
        # Extract JSON even if wrapped in markdown fences
        content = response.content.strip()
        match = re.search(r'\{.*\}', content, re.DOTALL)
        parsed = json.loads(match.group(0) if match else content)
        return {"success": True, "data": parsed}
    except Exception as e:
        logger.error(f"Parse description error: {str(e)}")
        return {"success": False, "data": {}, "error": str(e)}


# Vehicle Type & Engine Suggester (Make+Model → suggestions)
@app.post("/agents/suggest-vehicle")
async def suggest_vehicle_details(payload: dict):
    """
    Given a make and model, suggest vehicle type, typical engine size, and origin country.
    e.g. {make: "Toyota", model: "Land Cruiser"} → {vehicleType: "suv", engineSize: "4500", originCountry: "japan"}
    """
    from langchain_mistralai import ChatMistralAI
    from config.settings import settings

    make = payload.get("make", "").strip()
    model = payload.get("model", "").strip()
    if not make:
        raise HTTPException(status_code=422, detail="make is required")

    llm = ChatMistralAI(
        model=settings.MISTRAL_MODEL,
        temperature=0,
        mistral_api_key=settings.MISTRAL_API_KEY
    )

    prompt = f"""
    For the vehicle: {make} {model}

    Return ONLY a JSON object with:
    - vehicleType (one of: sedan, suv, truck, van, luxury, motorcycle, hatchback, wagon, coupe, convertible)
    - engineSize (typical engine displacement in cc as a number string, e.g. "2000")
    - originCountry (most common country this model is imported from to Africa — one of: japan, uk, uae, usa — or empty)
    - confidence (0.0 to 1.0 — how confident you are)

    Return only valid JSON, no explanation.
    """

    try:
        response = llm.invoke(prompt)
        import json, re
        content = response.content.strip()
        match = re.search(r'\{.*\}', content, re.DOTALL)
        parsed = json.loads(match.group(0) if match else content)
        return {"success": True, "data": parsed}
    except Exception as e:
        logger.error(f"Suggest vehicle error: {str(e)}")
        return {"success": False, "data": {}, "error": str(e)}


# Quote Preview (live estimate without saving)
@app.post("/agents/quote-preview")
async def quote_preview(
    request: dict,
    agent: QuoteAgent = Depends(get_quote_agent)
):
    """
    Generate a quick cost estimate without saving to DB.
    Used for the live preview panel on the Get Quote form.
    Requires: vehicle_type, year, make, origin_country, shipping_method
    """
    from models.schemas import VehicleType, ShippingMethod
    try:
        # Provide defaults for missing optional fields
        request.setdefault("model", "")
        request.setdefault("engine_size", None)
        request.setdefault("destination_country", "Uganda")
        request.setdefault("destination_port", "Port Bell")

        vt = request.get("vehicle_type", "sedan")
        sm = request.get("shipping_method", "roro")

        # Validate enums permissively
        try:
            VehicleType(vt)
        except ValueError:
            request["vehicle_type"] = "sedan"
        try:
            ShippingMethod(sm)
        except ValueError:
            request["shipping_method"] = "roro"

        logger.info(f"Generating quote preview for {request.get('make')} {request.get('model')}")
        result = await agent.execute(request)
        # Strip the reference so it's clearly a preview
        result.pop("quote_reference", None)
        result["is_preview"] = True
        return result
    except Exception as e:
        logger.error(f"Quote preview error: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Field Consistency Validator
@app.post("/agents/validate-vehicle")
async def validate_vehicle_fields(payload: dict):
    """
    Check for inconsistencies in vehicle fields and return soft warnings.
    e.g. high mileage on a new vehicle, unlikely engine size for a make/model.
    """
    from langchain_mistralai import ChatMistralAI
    from config.settings import settings

    llm = ChatMistralAI(
        model=settings.MISTRAL_MODEL,
        temperature=0,
        mistral_api_key=settings.MISTRAL_API_KEY
    )

    prompt = f"""
    Check these vehicle details for obvious inconsistencies or errors:
    - Year: {payload.get('year', '')}
    - Make: {payload.get('make', '')}
    - Model: {payload.get('model', '')}
    - Vehicle Type: {payload.get('vehicleType', '')}
    - Engine Size: {payload.get('engineSize', '')} cc
    - Color: {payload.get('color', '')}

    If everything looks reasonable, return: {{"valid": true, "warnings": []}}
    If there are issues, return a JSON with: {{"valid": false, "warnings": ["warning 1", "warning 2"]}}

    Keep warnings short (under 15 words each). Only flag real problems.
    Return only valid JSON, no explanation.
    """

    try:
        response = llm.invoke(prompt)
        import json, re
        content = response.content.strip()
        match = re.search(r'\{.*\}', content, re.DOTALL)
        parsed = json.loads(match.group(0) if match else content)
        return {"success": True, **parsed}
    except Exception as e:
        logger.error(f"Validate vehicle error: {str(e)}")
        return {"success": True, "valid": True, "warnings": []}



@app.exception_handler(Exception)
async def global_exception_handler(request, exc):
    """Global exception handler"""
    logger.error(f"Unhandled exception: {str(exc)}")
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": "Internal server error",
            "detail": str(exc) if settings.DEBUG else "An error occurred"
        }
    )


if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host=settings.HOST,
        port=settings.PORT,
        reload=settings.DEBUG,
        log_level=settings.LOG_LEVEL.lower()
    )
