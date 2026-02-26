"""
Tests for Quote Agent
"""

import pytest
from agents.quote_agent import QuoteAgent


@pytest.fixture
def quote_agent():
    """Create quote agent instance"""
    return QuoteAgent()


@pytest.mark.asyncio
async def test_quote_generation(quote_agent):
    """Test basic quote generation"""
    input_data = {
        "vehicle_type": "sedan",
        "year": 2020,
        "make": "Toyota",
        "model": "Camry",
        "engine_size": 2500,
        "origin_country": "Japan",
        "origin_port": "Tokyo",
        "destination_country": "Uganda",
        "destination_port": "Port Bell",
        "shipping_method": "roro",
        "customer_email": "test@example.com",
        "customer_name": "Test Customer"
    }
    
    result = await quote_agent.execute(input_data)
    
    assert result["success"] is True
    assert "quote_reference" in result
    assert result["total_cost"] > 0
    assert result["base_cost"] > 0
    assert result["adjusted_cost"] > 0
    assert "breakdown" in result
    assert result["confidence_score"] > 0


@pytest.mark.asyncio
async def test_invalid_year(quote_agent):
    """Test validation with invalid year"""
    input_data = {
        "vehicle_type": "sedan",
        "year": 1980,  # Too old
        "make": "Toyota",
        "model": "Camry",
        "origin_country": "Japan",
        "shipping_method": "roro"
    }
    
    with pytest.raises(Exception):
        await quote_agent.execute(input_data)
