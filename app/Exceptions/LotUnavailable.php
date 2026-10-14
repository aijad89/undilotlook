<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * No lot could be drawn for a registration, and why.
 *
 * The message is written for the participant, because the participant is who
 * usually reads it — LotAllocator is called straight from the confirm button.
 * The admin's bulk run catches the same exception and files the message against
 * the company in its waiting list, so both sides give the same reason for the
 * same situation instead of one saying nothing at all.
 */
class LotUnavailable extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function sizeExhausted(): self
    {
        return new self(
            'No lot of the size set for you is available. Please contact admin.',
            'size_exhausted',
        );
    }

    public static function noneForCode(): self
    {
        return new self(
            'No lots available for your category. Please contact admin.',
            'none_for_code',
        );
    }

    public static function presetMissing(): self
    {
        return new self(
            'Your pre-assigned lot no longer exists. Please contact admin.',
            'preset_missing',
        );
    }

    public static function presetWrongEvent(): self
    {
        return new self(
            'Your pre-assigned lot belongs to a different event. Please contact admin.',
            'preset_wrong_event',
        );
    }

    public static function alreadyDrawn(): self
    {
        return new self(
            'You have already confirmed your lot.',
            'already_drawn',
        );
    }

    /**
     * A company can be in the draw before its account exists, and a drawn lot is
     * recorded against the account. Only the bulk run can meet this — a
     * participant pressing Confirm has an account by definition.
     */
    public static function noAccount(): self
    {
        return new self(
            'This company has no participant account to hold a lot.',
            'no_account',
        );
    }
}
