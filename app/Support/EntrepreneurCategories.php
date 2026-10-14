<?php

namespace App\Support;

/**
 * What kind of body a company is — the scheme it registers under.
 *
 * The best behaved of these lists before it moved: already a const, already
 * validated with `Rule::in()` rather than an `in:` string, which matters here
 * more than anywhere because one of its values contains a comma. It lived on
 * Company all the same, which meant a category could only be added by a deploy.
 *
 * @see EXTERNAL_ENTITY — two of these are not craft entrepreneurs at all, and
 *      the difference decides whether the lot draw warns about them.
 */
class EntrepreneurCategories extends ManagedList
{
    protected const KEY = 'entrepreneur_categories';

    public const DEFAULTS = [
        'One District One Industry - Craft Products',
        'Micro, Small & Medium (MSM)',
        'Craft Incubator Production Scheme',
        'Government Agency (*Registration under external agency name)',
        'Non-Governmental Organisation (NGO)',
        'Private Agency',
        'No Category',
    ];

    /**
     * The categories that are not craft entrepreneurs.
     *
     * An admin reserves a lot for these before polling opens — the draw must not
     * place them — and their registration code has no reason to match the craft
     * category coded into that lot. So a code mismatch on their preset is
     * expected, and warning about it would put a dialog in front of the routine
     * case.
     *
     * Deliberately NOT editable in Settings: the list above says what may be
     * recorded, this says what the draw treats differently, and that is a rule rather than a
     * dropdown. Renaming one of these two in Settings without changing it here
     * would quietly start warning about every government booth.
     *
     * @var array<int, string>
     */
    public const EXTERNAL_ENTITY = [
        'Government Agency (*Registration under external agency name)',
        'Non-Governmental Organisation (NGO)',
    ];
}
