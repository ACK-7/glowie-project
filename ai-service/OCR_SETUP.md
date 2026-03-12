# OCR Setup Guide for Document Agent

The Document Agent now supports OCR (Optical Character Recognition) to extract text from PDF and image files.

## Prerequisites

### 1. Install Tesseract OCR

**Windows:**
1. Download Tesseract installer from: https://github.com/UB-Mannheim/tesseract/wiki
2. Run the installer (tesseract-ocr-w64-setup-5.3.3.exe or latest)
3. During installation, note the installation path (default: `C:\Program Files\Tesseract-OCR`)
4. Add Tesseract to your system PATH:
   - Right-click "This PC" → Properties → Advanced System Settings
   - Click "Environment Variables"
   - Under "System Variables", find "Path" and click "Edit"
   - Click "New" and add: `C:\Program Files\Tesseract-OCR`
   - Click OK on all dialogs

**macOS:**
```bash
brew install tesseract
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get update
sudo apt-get install tesseract-ocr
sudo apt-get install libtesseract-dev
```

### 2. Install Poppler (for PDF processing)

**Windows:**
1. Download Poppler from: https://github.com/oschwartz10612/poppler-windows/releases
2. Extract to `C:\Program Files\poppler`
3. Add to PATH: `C:\Program Files\poppler\Library\bin`

**macOS:**
```bash
brew install poppler
```

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install poppler-utils
```

### 3. Install Python Dependencies

```bash
cd ai-service
pip install -r requirements.txt
```

## Verify Installation

### Test Tesseract:
```bash
tesseract --version
```

Should output something like:
```
tesseract 5.3.3
```

### Test Poppler:
```bash
pdftoppm -v
```

Should output version information.

## Configuration

If Tesseract is not in your PATH, you can specify the path in your code:

```python
import pytesseract

# Windows
pytesseract.pytesseract.tesseract_cmd = r'C:\Program Files\Tesseract-OCR\tesseract.exe'

# macOS/Linux (usually not needed if installed via package manager)
# pytesseract.pytesseract.tesseract_cmd = '/usr/local/bin/tesseract'
```

## Testing the Document Agent

1. **Start the AI Service:**
   ```bash
   cd ai-service
   python main.py
   ```

2. **Upload a document** through the admin panel

3. **Click "Extract Data (AI)"** from the document dropdown menu

4. **View the extracted data** with confidence scores

## Supported Document Types

- **passport** - Extracts passport information
- **license** - Extracts driving license details
- **vehicle_registration** - Extracts vehicle registration data
- **bill_of_lading** - Extracts shipping document details
- **invoice** - Extracts invoice information
- **insurance** - Extracts insurance certificate data
- **customs** - Extracts customs declaration data
- **other** - Generic document extraction

## Supported File Formats

- PDF (.pdf)
- Images (.png, .jpg, .jpeg, .tiff, .bmp, .gif)

## Troubleshooting

### "Tesseract not found" error:
- Verify Tesseract is installed: `tesseract --version`
- Check PATH environment variable includes Tesseract directory
- Restart your terminal/IDE after adding to PATH

### "Poppler not found" error:
- Verify Poppler is installed: `pdftoppm -v`
- Check PATH includes Poppler bin directory
- On Windows, ensure you added `Library\bin` subdirectory to PATH

### Low confidence scores:
- Ensure document images are high quality (300 DPI recommended)
- Check that text is clear and not handwritten
- Try preprocessing images (contrast adjustment, noise reduction)

### Slow processing:
- Large PDFs with many pages will take longer
- Consider reducing DPI for faster processing (trade-off with accuracy)
- Implement caching for frequently accessed documents

## Performance Tips

1. **Image Quality**: Higher DPI = better accuracy but slower processing
2. **Preprocessing**: Clean images before OCR (remove noise, adjust contrast)
3. **Caching**: Cache extracted text to avoid re-processing
4. **Async Processing**: Process documents in background for large files

## Next Steps

- Add document preprocessing (image enhancement)
- Implement caching layer for extracted text
- Add support for more languages
- Implement batch processing for multiple documents
- Add confidence thresholds for auto-approval
