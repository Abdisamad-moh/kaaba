<?php 
namespace App\Model;

enum JobStatusEnum: string
{
    case POSTED = 'posted';
    case PAUSED = 'paused';
    case DRAFT = 'draft';
    case DELETED = 'deleted';
    case CLOSED = 'closed';

    // Function to get encoded status
    public function getEncoded(): string
    {
        return match($this) {
            self::POSTED => base64_encode(self::POSTED->value),
            self::PAUSED => base64_encode(self::PAUSED->value),
            self::DRAFT => base64_encode(self::DRAFT->value),
            self::DELETED => base64_encode(self::DELETED->value),
            self::CLOSED => base64_encode(self::CLOSED->value),
        };
    }

    // Static function to decode status
    public static function fromEncoded(string $encodedStatus): ?self
    {
        return match($encodedStatus) {
            base64_encode(self::POSTED->value) => self::POSTED,
            base64_encode(self::PAUSED->value) => self::PAUSED,
            base64_encode(self::DRAFT->value) => self::DRAFT,
            base64_encode(self::DELETED->value) => self::DELETED,
            base64_encode(self::CLOSED->value) => self::CLOSED,
            default => null,
        };
    }
}
