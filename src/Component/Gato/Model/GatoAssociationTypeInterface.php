<?php

declare(strict_types=1);

namespace Component\Gato\Model;

use Component\Resource\Model\CodeAwareInterface;
use Component\Resource\Model\ResourceInterface;
use Component\Resource\Model\TimestampableInterface;
use Component\Resource\Model\TranslatableInterface;
use Component\Resource\Model\TranslationInterface;

interface GatoAssociationTypeInterface extends
    CodeAwareInterface,
    TimestampableInterface,
    ResourceInterface,
    TranslatableInterface
{
    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void;

    /**
     * @param string|null $locale
     *
     * @return GatoAssociationTypeTranslationInterface
     */
    public function getTranslation(?string $locale = null): TranslationInterface;
}
