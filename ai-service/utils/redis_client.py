"""
Redis client for caching and state management
"""

import redis.asyncio as redis
from config.settings import settings
from loguru import logger
import json
from typing import Optional, Any

# Redis client instance
redis_client: Optional[redis.Redis] = None


async def init_redis():
    """Initialize Redis connection"""
    global redis_client
    
    try:
        redis_client = redis.Redis(
            host=settings.REDIS_HOST,
            port=settings.REDIS_PORT,
            password=settings.REDIS_PASSWORD,
            db=settings.REDIS_DB,
            decode_responses=True
        )
        
        # Test connection
        await redis_client.ping()
        logger.info("Redis connection initialized")
    except Exception as e:
        logger.warning(f"Redis not available (optional): {str(e)}")
        logger.info("Service will continue without caching")
        # Don't raise - Redis is optional
        redis_client = None


async def close_redis():
    """Close Redis connection"""
    global redis_client
    
    if redis_client:
        await redis_client.close()
        logger.info("Redis connection closed")


async def cache_set(key: str, value: Any, expire: int = 3600):
    """Set cache value"""
    if not redis_client:
        return False
    
    try:
        serialized = json.dumps(value)
        await redis_client.setex(key, expire, serialized)
        return True
    except Exception as e:
        logger.error(f"Cache set error: {str(e)}")
        return False


async def cache_get(key: str) -> Optional[Any]:
    """Get cache value"""
    if not redis_client:
        return None
    
    try:
        value = await redis_client.get(key)
        if value:
            return json.loads(value)
        return None
    except Exception as e:
        logger.error(f"Cache get error: {str(e)}")
        return None


async def cache_delete(key: str):
    """Delete cache value"""
    if not redis_client:
        return False
    
    try:
        await redis_client.delete(key)
        return True
    except Exception as e:
        logger.error(f"Cache delete error: {str(e)}")
        return False


def get_redis_client() -> Optional[redis.Redis]:
    """Get Redis client instance"""
    return redis_client
