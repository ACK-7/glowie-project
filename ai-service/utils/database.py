"""
Database connection utilities
"""

from sqlalchemy import create_engine
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker, declarative_base
from config.settings import settings
from loguru import logger

# Create base class for models
Base = declarative_base()

# Database URL
DATABASE_URL = f"mysql+pymysql://{settings.DB_USER}:{settings.DB_PASSWORD}@{settings.DB_HOST}:{settings.DB_PORT}/{settings.DB_NAME}"

# Create engine
engine = None
SessionLocal = None


async def init_db():
    """Initialize database connection"""
    global engine, SessionLocal
    
    try:
        engine = create_engine(
            DATABASE_URL,
            pool_pre_ping=True,
            pool_recycle=3600,
            echo=settings.DEBUG
        )
        
        SessionLocal = sessionmaker(
            autocommit=False,
            autoflush=False,
            bind=engine
        )
        
        logger.info("Database connection initialized")
    except Exception as e:
        logger.error(f"Database initialization error: {str(e)}")
        raise


async def close_db():
    """Close database connection"""
    global engine
    
    if engine:
        engine.dispose()
        logger.info("Database connection closed")


def get_db():
    """Get database session"""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
