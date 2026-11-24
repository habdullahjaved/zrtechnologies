<?php

namespace App\Enums;

/**
 * Outcome of checking a postcode against our collection area.
 */
enum CoverageStatus: string
{
    /** We collect from this postcode. */
    case Covered = 'covered';

    /** A real postcode, but outside every active service area. */
    case OutOfArea = 'out_of_area';

    /** Not a UK postcode (bad format, or postcodes.io says it does not exist). */
    case Invalid = 'invalid';
}
