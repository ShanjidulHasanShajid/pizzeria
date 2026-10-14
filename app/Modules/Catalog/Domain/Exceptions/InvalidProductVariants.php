<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\DomainException;

/**
 * A product's variants break a rule (docs/business-rules.md sections 1, 5 and 6).
 */
final class InvalidProductVariants extends DomainException
{
    public static function noVariants(): self
    {
        return new self('A product needs at least one variant.');
    }

    public static function cannotRemoveLastVariant(): self
    {
        return new self('A product needs at least one variant, so the last one cannot be removed.');
    }

    public static function notExactlyOneDefault(): self
    {
        return new self('Exactly one variant must be the default.');
    }

    public static function duplicateLabel(string $label): self
    {
        return new self("There is already a variant called \"{$label}\".");
    }

    public static function comboNeedsExactlyOneVariant(): self
    {
        return new self('A combo has exactly one variant: the bundle price.');
    }

    public static function builderVariantNeedsSizeTier(string $label): self
    {
        return new self("Choose a size for \"{$label}\": a build-your-own pizza has one variant per size.");
    }

    public static function sizeTierUsedTwice(): self
    {
        return new self('A build-your-own pizza can have only one variant per size.');
    }

    public static function notFound(string $label): self
    {
        return new self("This product has no variant called \"{$label}\".");
    }
}
