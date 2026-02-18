<?php

namespace App\Domain;

/**
 * Defines allowed data sources for PostIndex records.
 *
 * This is a domain-level invariant used to distinguish:
 * - manually created records (must be preserved)
 * - imported records (can be synchronized and cleaned)
 */
final class PostIndexSource
{
    public const MANUAL = 'manual';
    public const IMPORT = 'import';
}