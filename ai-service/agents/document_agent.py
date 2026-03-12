"""
Document Processing Agent
Uses AI to extract data from shipping documents with OCR support
"""

from langchain_mistralai import ChatMistralAI
from loguru import logger
from config.settings import settings
import json
import re
import io
from typing import Optional
from PIL import Image
import pytesseract
from pdf2image import convert_from_bytes
from fastapi import UploadFile


class DocumentAgent:
    """AI Agent for document processing and OCR"""
    
    def __init__(self):
        self.llm = ChatMistralAI(
            model=settings.MISTRAL_MODEL,
            temperature=0.3,
            mistral_api_key=settings.MISTRAL_API_KEY
        )
        logger.info("DocumentAgent initialized with Mistral AI and OCR support")
    
    async def execute(self, file: UploadFile, document_type: str) -> dict:
        """Execute document processing workflow with OCR"""
        try:
            logger.info(f"Processing {document_type} document: {file.filename}")
            
            # Step 1: Extract text from document using OCR
            document_text = await self._extract_text_from_file(file)
            
            if not document_text or len(document_text.strip()) < 10:
                return {
                    "success": False,
                    "error": "Could not extract text from document",
                    "message": "The document appears to be empty or unreadable"
                }
            
            logger.info(f"Extracted {len(document_text)} characters from document")
            
            # Step 2: Use AI to extract structured data
            system_prompt = self._get_extraction_prompt(document_type)
            
            messages = [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": f"Extract information from this document:\n\n{document_text[:4000]}"}  # Limit to 4000 chars
            ]
            
            response = self.llm.invoke(messages)
            extracted_data = self._parse_extraction(response.content, document_type)
            
            # Step 3: Calculate confidence score based on extracted fields
            confidence_score = self._calculate_confidence(extracted_data, document_type)
            
            return {
                "success": True,
                "document_type": document_type,
                "extracted_data": extracted_data,
                "confidence_score": confidence_score,
                "raw_text_length": len(document_text),
                "message": "Document processed successfully"
            }
            
        except Exception as e:
            logger.error(f"Document processing error: {str(e)}")
            return {
                "success": False,
                "error": str(e),
                "message": "Document processing failed"
            }
    
    async def _extract_text_from_file(self, file: UploadFile) -> str:
        """Extract text from PDF or image file using OCR"""
        try:
            content = await file.read()
            filename = file.filename.lower()
            
            # Reset file pointer for potential re-reading
            await file.seek(0)
            
            if filename.endswith('.pdf'):
                logger.info("Processing PDF document")
                return self._extract_from_pdf(content)
            elif filename.endswith(('.png', '.jpg', '.jpeg', '.tiff', '.bmp', '.gif')):
                logger.info("Processing image document")
                return self._extract_from_image(content)
            else:
                raise ValueError(f"Unsupported file type: {filename}")
                
        except Exception as e:
            logger.error(f"Text extraction error: {str(e)}")
            raise
    
    def _extract_from_pdf(self, pdf_content: bytes) -> str:
        """Extract text from PDF using OCR"""
        try:
            # Convert PDF pages to images
            images = convert_from_bytes(pdf_content, dpi=300)
            
            # Extract text from each page
            text_parts = []
            for i, image in enumerate(images):
                logger.info(f"Processing PDF page {i + 1}/{len(images)}")
                page_text = pytesseract.image_to_string(image, lang='eng')
                text_parts.append(page_text)
            
            full_text = '\n\n'.join(text_parts)
            logger.info(f"Extracted {len(full_text)} characters from {len(images)} PDF pages")
            
            return full_text
            
        except Exception as e:
            logger.error(f"PDF extraction error: {str(e)}")
            raise
    
    def _extract_from_image(self, image_content: bytes) -> str:
        """Extract text from image using OCR"""
        try:
            image = Image.open(io.BytesIO(image_content))
            
            # Convert to RGB if necessary
            if image.mode != 'RGB':
                image = image.convert('RGB')
            
            # Extract text using Tesseract
            text = pytesseract.image_to_string(image, lang='eng')
            
            logger.info(f"Extracted {len(text)} characters from image")
            
            return text
            
        except Exception as e:
            logger.error(f"Image extraction error: {str(e)}")
            raise
    
    def _get_extraction_prompt(self, document_type: str) -> str:
        """Get extraction prompt based on document type"""
        
        prompts = {
            "passport": """You are an expert at extracting data from passport documents. 
Extract the following information and return as JSON:
- passport_number
- full_name
- date_of_birth
- nationality
- gender
- issue_date
- expiry_date
- place_of_birth
- issuing_authority

Return ONLY valid JSON. If a field is not found, use null.""",

            "license": """You are an expert at extracting data from driving license documents.
Extract the following information and return as JSON:
- license_number
- full_name
- date_of_birth
- address
- issue_date
- expiry_date
- license_class
- restrictions

Return ONLY valid JSON. If a field is not found, use null.""",

            "vehicle_registration": """You are an expert at extracting data from vehicle registration documents.
Extract the following information and return as JSON:
- registration_number
- vin (Vehicle Identification Number)
- make
- model
- year
- color
- engine_number
- owner_name
- registration_date

Return ONLY valid JSON. If a field is not found, use null.""",

            "bill_of_lading": """You are an expert at extracting data from Bill of Lading documents.
Extract the following information and return as JSON:
- bl_number
- shipper_name
- shipper_address
- consignee_name
- consignee_address
- vessel_name
- port_of_loading
- port_of_discharge
- container_number
- booking_number
- date_of_shipment

Return ONLY valid JSON. If a field is not found, use null.""",

            "invoice": """You are an expert at extracting data from invoice documents.
Extract the following information and return as JSON:
- invoice_number
- invoice_date
- seller_name
- seller_address
- buyer_name
- buyer_address
- total_amount
- currency
- payment_terms
- items (array of item descriptions)

Return ONLY valid JSON. If a field is not found, use null.""",

            "insurance": """You are an expert at extracting data from insurance documents.
Extract the following information and return as JSON:
- policy_number
- insured_name
- vehicle_details
- coverage_type
- issue_date
- expiry_date
- premium_amount
- insurance_company

Return ONLY valid JSON. If a field is not found, use null.""",

            "customs": """You are an expert at extracting data from customs declaration documents.
Extract the following information and return as JSON:
- declaration_number
- declaration_date
- importer_name
- exporter_name
- country_of_origin
- destination_country
- hs_code
- declared_value
- currency
- description_of_goods

Return ONLY valid JSON. If a field is not found, use null.""",

            "other": """You are an expert at extracting data from documents.
Extract all relevant information and return as JSON with appropriate field names.
Return ONLY valid JSON."""
        }
        
        return prompts.get(document_type, prompts["other"])
    
    def _parse_extraction(self, response_text: str, document_type: str) -> dict:
        """Parse AI response into structured data"""
        try:
            # Try to find JSON in the response
            json_match = re.search(r'\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}', response_text, re.DOTALL)
            
            if json_match:
                json_str = json_match.group()
                data = json.loads(json_str)
                logger.info(f"Successfully parsed JSON with {len(data)} fields")
                return data
            else:
                # Fallback: parse line by line
                logger.warning("Could not find JSON, parsing line by line")
                data = {}
                for line in response_text.split('\n'):
                    if ':' in line:
                        parts = line.split(':', 1)
                        if len(parts) == 2:
                            key = parts[0].strip().lower().replace(' ', '_').replace('-', '_')
                            value = parts[1].strip()
                            # Remove quotes and clean up
                            value = value.strip('"\'').strip()
                            if value and value.lower() not in ['null', 'n/a', 'none', '']:
                                data[key] = value
                
                return data if data else {"raw_response": response_text}
                
        except json.JSONDecodeError as e:
            logger.error(f"JSON parsing error: {str(e)}")
            return {"raw_response": response_text, "parse_error": str(e)}
        except Exception as e:
            logger.error(f"Parsing error: {str(e)}")
            return {"raw_response": response_text, "error": str(e)}
    
    def _calculate_confidence(self, extracted_data: dict, document_type: str) -> float:
        """Calculate confidence score based on extracted fields"""
        if not extracted_data or "raw_response" in extracted_data:
            return 0.3
        
        # Define expected fields for each document type
        expected_fields = {
            "passport": ["passport_number", "full_name", "date_of_birth", "nationality"],
            "license": ["license_number", "full_name", "date_of_birth"],
            "vehicle_registration": ["registration_number", "make", "model", "year"],
            "bill_of_lading": ["bl_number", "shipper_name", "consignee_name"],
            "invoice": ["invoice_number", "total_amount", "seller_name"],
            "insurance": ["policy_number", "insured_name", "expiry_date"],
            "customs": ["declaration_number", "importer_name", "declared_value"],
            "other": []
        }
        
        expected = expected_fields.get(document_type, [])
        
        if not expected:
            # For 'other' type, base confidence on number of fields extracted
            return min(0.5 + (len(extracted_data) * 0.05), 0.95)
        
        # Calculate percentage of expected fields found
        found_fields = sum(1 for field in expected if field in extracted_data and extracted_data[field])
        confidence = found_fields / len(expected) if expected else 0.5
        
        # Bonus for additional fields
        extra_fields = len(extracted_data) - len(expected)
        if extra_fields > 0:
            confidence = min(confidence + (extra_fields * 0.02), 0.98)
        
        return round(confidence, 2)
