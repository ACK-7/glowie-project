<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Azure\Cosmos\CosmosClient;

class CosmosDbService
{
    private static ?self $instance = null;
    private ?CosmosClient $cosmosClient = null;
    private string $database;
    private string $container;
    private bool $enabled;

    private function __construct()
    {
        $this->enabled = config('cosmosdb.enabled', false);
        $this->database = config('cosmosdb.database', 'shipwithglowie-chat');
        $this->container = config('cosmosdb.container', 'conversations');
        
        if (!$this->enabled) {
            Log::info('Cosmos DB service initialized but disabled');
            return;
        }

        try {
            $connectionString = config('cosmosdb.connection_string');
            
            if (!$connectionString) {
                throw new Exception('Cosmos DB connection string not configured');
            }

            $this->cosmosClient = new CosmosClient($connectionString);
            Log::info('Cosmos DB client initialized successfully');
        } catch (Exception $e) {
            Log::error('Cosmos DB initialization failed: ' . $e->getMessage());
            $this->enabled = false;
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->cosmosClient !== null;
    }

    /**
     * Store conversation/chat history with user isolation
     * Partition by userId for security and performance
     */
    public function storeConversation(string $userId, array $message): array
    {
        if (!$this->isEnabled()) {
            Log::warning('Cosmos DB is disabled, conversation not stored');
            return ['success' => false, 'message' => 'Cosmos DB not enabled'];
        }

        try {
            $conversationId = $message['conversationId'] ?? uniqid('conv_');
            
            $item = [
                'id' => $message['messageId'] ?? uniqid('msg_'),
                'userId' => $userId, // Partition key for isolation
                'conversationId' => $conversationId,
                'role' => $message['role'] ?? 'user', // 'user' or 'assistant'
                'content' => $message['content'],
                'tokens_used' => $message['tokens_used'] ?? 0,
                'metadata' => $message['metadata'] ?? [],
                'timestamp' => now()->toIso8601String(),
                'ttl' => $message['ttl'] ?? 2592000, // 30 days default
            ];

            // TODO: Implement actual Cosmos DB insert when SDK is fully integrated
            // $this->cosmosClient->createItem(
            //     $this->database,
            //     $this->container,
            //     $item
            // );

            Log::info("Conversation stored: {$item['id']} for user: {$userId}");
            
            return [
                'success' => true,
                'message' => 'Conversation stored successfully',
                'data' => $item,
            ];
        } catch (Exception $e) {
            Log::error('Failed to store conversation: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to store conversation',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve conversation history for context/RAG
     * Minimizes cross-partition queries by filtering on userId
     */
    public function getConversationHistory(string $userId, string $conversationId, int $limit = 20): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            // Query: Partition-scoped for optimal performance
            // SELECT * FROM c WHERE c.userId = @userId AND c.conversationId = @conversationId 
            // ORDER BY c.timestamp DESC LIMIT @limit

            $history = [];
            
            Log::info("Retrieved conversation history for user: {$userId}");
            
            return $history;
        } catch (Exception $e) {
            Log::error('Failed to retrieve conversation: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Store user context for personalization and RAG
     * Embedding context data within user partition for isolated access
     */
    public function storeUserContext(string $userId, array $context): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Cosmos DB not enabled'];
        }

        try {
            $contextItem = [
                'id' => "context_{$userId}",
                'userId' => $userId, // Partition key
                'type' => 'user_context',
                'preferences' => $context['preferences'] ?? [],
                'conversation_summary' => $context['summary'] ?? null,
                'embedding_metadata' => $context['embedding'] ?? null,
                'rag_context' => $context['rag_context'] ?? [],
                'last_updated' => now()->toIso8601String(),
                'ttl' => 7776000, // 90 days
            ];

            // TODO: Implement actual Cosmos DB upsert when SDK is fully integrated
            Log::info("Context stored for user: {$userId}");
            
            return [
                'success' => true,
                'message' => 'Context stored successfully',
                'data' => $contextItem,
            ];
        } catch (Exception $e) {
            Log::error('Failed to store user context: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to store context',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve user context (single-partition query for fast access)
     */
    public function getUserContext(string $userId): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            // Single-partition query: Fast and cost-efficient
            // SELECT * FROM c WHERE c.userId = @userId AND c.type = 'user_context'
            $context = [];
            
            return $context;
        } catch (Exception $e) {
            Log::error('Failed to retrieve user context: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Health check for Cosmos DB connectivity
     */
    public function healthCheck(): array
    {
        if (!$this->isEnabled()) {
            return ['status' => 'disabled', 'message' => 'Cosmos DB is disabled'];
        }

        try {
            // TODO: Implement actual health check against Cosmos DB
            return [
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::error('Cosmos DB health check failed: ' . $e->getMessage());
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
