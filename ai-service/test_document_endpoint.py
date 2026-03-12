"""
Test script for document extraction endpoint
Run this after starting the AI service to verify it's working
"""

import requests
import sys

def test_document_endpoint():
    """Test the document extraction endpoint"""
    
    # Test with a simple text file
    test_content = b"PASSPORT\nPassport Number: AB123456\nFull Name: John Doe\nDate of Birth: 1990-01-15\nNationality: USA"
    
    files = {
        'file': ('test_passport.txt', test_content, 'text/plain')
    }
    
    data = {
        'document_type': 'passport'
    }
    
    try:
        print("Testing AI service document endpoint...")
        print("URL: http://localhost:8001/agents/document")
        
        response = requests.post(
            'http://localhost:8001/agents/document',
            files=files,
            data=data,
            timeout=30
        )
        
        print(f"\nStatus Code: {response.status_code}")
        print(f"Response: {response.text[:500]}")
        
        if response.status_code == 200:
            print("\n✅ SUCCESS! AI service is working correctly")
            return True
        else:
            print(f"\n❌ FAILED! Status code: {response.status_code}")
            return False
            
    except requests.exceptions.ConnectionError:
        print("\n❌ ERROR: Could not connect to AI service")
        print("Make sure the AI service is running on http://localhost:8001")
        return False
    except Exception as e:
        print(f"\n❌ ERROR: {str(e)}")
        return False

if __name__ == "__main__":
    success = test_document_endpoint()
    sys.exit(0 if success else 1)
