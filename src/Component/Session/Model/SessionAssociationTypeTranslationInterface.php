<?php

declare(strict_types=1);

namespace Component\Session\Model;

use Component\Resource\Model\ResourceInterface;
use Component\Resource\Model\TranslationInterface;

interface SessionAssociationTypeTranslationInterface extends ResourceInterface, TranslationInterface
{
    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void;
}
