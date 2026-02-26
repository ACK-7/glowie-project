"""
Pydantic schemas for request/response validation
"""

from pydantic import BaseModel, Field, validator
from typing import Optional, List, Dict, Any
from datetime import datetime
from enum import Enum


# Enums
class VehicleType(str, Enum):
    SEDAN = "sedan"
    SUV = "suv"
    TRUCK = "truck"
    VAN = "van"
    LUXURY = "luxury"
    MOTORCYCLE = "motorcycle"


class ShippingMethod(str, Enum):
    RORO = "roro"
    CONTAINER = "container"


class DocumentType(str, Enum):
    BILL_OF_LADING = "bill_of_lading"
    VEHICLE_REGISTRATION = "vehicle_registration"
    CUSTOMS_DECLARATION = "customs_declaration"
    INSURANCE_CERTIFICATE = "insurance_certificate"


# Quote Agent Schemas
class QuoteRequest(BaseModel):
    vehicle_type: VehicleType
    year: int = Field(..., ge=1990, le=2030)
    make: str = Field(..., min_length=1, max_length=100)
    model: str = Field(..., min_length=1, max_length=100)
    engine_size: Optional[int] = None
    origin_country: str = Field(..., min_length=2, max_length=100)
    origin_port: Optional[str] = None
    destination_country: str = "Uganda"
    destination_port: Optional[str] = "Port Bell"
    shipping_method: ShippingMethod
    customer_email: Optional[str] = None
    customer_name: Optional[str] = None
    
    @validator('year')
    def validate_year(cls, v):
        current_year = datetime.now().year
        if v > current_year + 1:
            raise ValueError(f'Year cannot be more than {current_year + 1}')
        return v


class QuoteResponse(BaseModel):
    success: bool = True
    quote_reference: str
    base_cost: float
    adjusted_cost: float
    total_cost: float
    breakdown: Dict[str, float]
    ai_reasoning: Optional[str] = None
    estimated_delivery_days: Optional[int] = None
    confidence_score: Optional[float] = None
    created_at: datetime = Field(default_factory=datetime.now)


# Route Agent Schemas
class RouteRequest(BaseModel):
    shipment_id: int
    origin: str
    destination: str
    vehicle_type: Optional[VehicleType] = None
    priority: str = "standard"  # standard, express, economy


class RouteResponse(BaseModel):
    success: bool = True
    shipment_id: int
    optimization: Optional[Dict[str, Any]] = None
    error: Optional[str] = None


# Document Agent Schemas
class DocumentResponse(BaseModel):
    success: bool = True
    document_type: Optional[str] = None
    extracted_data: Optional[Dict[str, Any]] = None
    confidence_score: Optional[float] = None
    error: Optional[str] = None
    message: Optional[str] = None


# Support Agent Schemas
class SupportRequest(BaseModel):
    query: str = Field(..., min_length=1, max_length=1000)
    customer_id: int
    shipment_id: Optional[int] = None
    conversation_history: List[Dict[str, str]] = []


class SupportResponse(BaseModel):
    success: bool = True
    response: str
    confidence_score: float
    requires_human: bool = False
    suggested_actions: List[str] = []
    related_shipments: List[int] = []
    response_time_ms: Optional[int] = None


# Delay Prediction Agent Schemas
class DelayPredictionRequest(BaseModel):
    shipment_id: int
    origin: Optional[str] = "Japan"
    destination: Optional[str] = "Uganda"
    current_status: Optional[str] = "in_transit"
    current_location: Optional[str] = None
    expected_delivery: Optional[str] = None  # Date string


class DelayPredictionResponse(BaseModel):
    success: bool = True
    shipment_id: int
    prediction: Optional[Dict[str, Any]] = None
    analyzed_at: Optional[str] = None
    error: Optional[str] = None
    analyzed_at: datetime = Field(default_factory=datetime.now)


# Health Check Schema
class HealthResponse(BaseModel):
    status: str
    service: str
    version: str
    environment: str
    timestamp: datetime = Field(default_factory=datetime.now)


# Agent State Schemas (for LangGraph)
class AgentState(BaseModel):
    """Base state for all agents"""
    messages: List[str] = []
    status: str = "initialized"
    error: Optional[str] = None
    metadata: Dict[str, Any] = {}


class QuoteAgentState(AgentState):
    """State for Quote Agent"""
    vehicle_type: Optional[str] = None
    origin: Optional[str] = None
    destination: Optional[str] = None
    year: Optional[int] = None
    make: Optional[str] = None
    model: Optional[str] = None
    base_cost: Optional[float] = None
    adjusted_cost: Optional[float] = None
    quote_reference: Optional[str] = None


class RouteAgentState(AgentState):
    """State for Route Agent"""
    shipment_id: Optional[int] = None
    origin: Optional[str] = None
    destination: Optional[str] = None
    routes: List[Dict[str, Any]] = []
    selected_route: Optional[Dict[str, Any]] = None


class DocumentAgentState(AgentState):
    """State for Document Agent"""
    document_type: Optional[str] = None
    file_path: Optional[str] = None
    extracted_data: Dict[str, Any] = {}
    validation_results: Dict[str, Any] = {}
