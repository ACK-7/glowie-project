"""
ShipWithGlowie AI Service - Main Application
FastAPI server with LangGraph agent orchestration
"""

from fastapi import FastAPI, HTTPException, Depends, UploadFile, File
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
    allow_origins=["http://localhost:5173", "http://localhost:8000"],
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
    document_type: str = "bill_of_lading",
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
        logger.info(f"Processing document: {file.filename}")
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


# Error handler
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
