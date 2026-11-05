<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Violation d'une règle de gestion. Rendue en 422 avec un code d'erreur stable
 * que le frontend peut traduire : { "error": "already_checked_in_today", "message": "...", "details": {...} }
 */
class BusinessRuleException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
            'details' => (object) $this->details,
        ], $this->status);
    }
}
