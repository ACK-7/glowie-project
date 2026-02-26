"""
Setup script for AI Service
"""

import os
import sys
from pathlib import Path


def create_env_file():
    """Create .env file from example"""
    env_example = Path(".env.example")
    env_file = Path(".env")
    
    if env_file.exists():
        print("✓ .env file already exists")
        return
    
    if env_example.exists():
        env_file.write_text(env_example.read_text())
        print("✓ Created .env file from .env.example")
        print("⚠ Please edit .env and add your API keys")
    else:
        print("✗ .env.example not found")


def create_logs_directory():
    """Create logs directory"""
    logs_dir = Path("logs")
    logs_dir.mkdir(exist_ok=True)
    print("✓ Created logs directory")


def check_python_version():
    """Check Python version"""
    if sys.version_info < (3, 11):
        print("✗ Python 3.11 or higher is required")
        sys.exit(1)
    print(f"✓ Python {sys.version_info.major}.{sys.version_info.minor} detected")


def main():
    """Run setup"""
    print("Setting up AI Service...\n")
    
    check_python_version()
    create_env_file()
    create_logs_directory()
    
    print("\n✓ Setup complete!")
    print("\nNext steps:")
    print("1. Edit .env and add your OPENAI_API_KEY")
    print("2. Install dependencies: pip install -r requirements.txt")
    print("3. Run service: uvicorn main:app --reload --port 8001")


if __name__ == "__main__":
    main()
