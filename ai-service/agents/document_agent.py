"""
Document Processing Agent
Uses AI to extract data from shipping documents
"""

from langchain_mistralai import ChatMistralAI
from loguru import logger
from config.settings import settings
import json
import re


class DocumentAgent:
    """AI Agent for document processing and OCR"""
    
    def __init__(self):
        self.llm = ChatMistralAI(
            model=settings.MISTRAL_MODEL,
            temperature=0.3,
            mistral_api_key=settings.MISTRAL_API_KEY
        )
        logger.info("DocumentAgent initialized with Mistral AI")
    
    async def execute(self, file, document_type: str) -> dict:
        """Execute document processing workflow"""
        try:
            # For now, we'll work with text content
            # In production, you'd use OCR library like pytesseract or cloud OCR
            document_text = file if isinstance(file, str) else ""
            
            logger.info(f"Processing {document_type} document")
            
            system_prompt = self._get_extraction_prompt(document_type)
            
            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": f"Extract information from this document:\n\n{document_text}"}
            ]
            
            response = self.llm.invoke(messages)
            extracted_data = self._parse_extraction(response.content, document_type)
            
            return {
                "success": True,
                "document_type": document_type,
                "extracted_data": extracted_data,
                "confidence_score": 0.85
            }
            
        except Exception as e:
            logger.error(f"Document processing error: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "message": "Document processing failed"
            }
    
    def _get_extraction_prompt(self, document_type: str) -> str:
        """Get extraction prompt based on document type"""
        
        prompts = {
            "vehicle_registration": """Extract: Vehicle Make, Model, Year, VIN, Registration Number, Owner Name, Registration Date, Engine Number, Color. Return as JSON.""",
            "bill_of_lading": """Extract: Shipper Name, Consignee Name, Vessel Name, Port of Loading, Port of Discharge, Container Number, Booking Number, Date. Return as JSON.""",
            "invoice": """Extract: Invoice Number, Date, Seller, Buyer, Total Amount, Currency, Items, Payment Terms. Return as JSON.""",
            "customs_declaration": """Extract: Declaration Number, Date, Importer, Exporter, Country of Origin, Destination, HS Code, Declared Value. Return as JSON."""
        }
        
        return prompts.get(document_type, "Extract all relevant information from this document as JSON.")
    
    def _parse_extraction(self, response_text: str, document_type: str) -> dict:
        """Parse AI response into structured data"""
        try:
            json_match = re.search(r'\{.*\}', response_text, re.DOTALL)
            if json_match:
                return json.loads(json_match.group())
            else:
                data = {}
                for line in response_text.split('\n'):
                    if ':' in line:
                        key, value = line.split(':', 1)
                        data[key.strip().lower().replace(' ', '_')] = value.strip()
                return data
        except Exception as e:
            logger.error(f"Parsing error: {str(e)}")
            return {"raw_response": response_text}
