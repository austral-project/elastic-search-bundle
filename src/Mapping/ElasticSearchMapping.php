<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Mapping;

use Austral\ElasticSearchBundle\Attribute\ElasticSearchField;
use Austral\EntityBundle\Attribute\AustralEntityAttributeInterface;
use Austral\EntityBundle\Mapping\EntityClassMapping;

/**
 * Austral ElasticSearchMapping.
 *
 * @extends EntityClassMapping<object>
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
final class ElasticSearchMapping extends EntityClassMapping
{
  /**
   * @var array<string, AustralEntityAttributeInterface|list<AustralEntityAttributeInterface>>
   */
  protected array $fieldsAttributes = [];

  /**
   * @var string|null
   */
  protected ?string $name = null;

  /**
   * Constructor.
   */
  public function __construct(?string $name = null)
  {
    $this->name = $name;
  }

  /**
   * @param AustralEntityAttributeInterface $entityAttribute
   *
   * @return ElasticSearchMapping
   */
  public function addFieldAttribute(AustralEntityAttributeInterface $entityAttribute): ElasticSearchMapping
  {
    if (ElasticSearchField::NAME_EXTRA_CONTENT === $entityAttribute->getKeyname())
    {
      if (!array_key_exists($entityAttribute->getKeyname(), $this->fieldsAttributes))
      {
        $this->fieldsAttributes[$entityAttribute->getKeyname()] = [];
      }
      $this->fieldsAttributes[$entityAttribute->getKeyname()][] = $entityAttribute;
    }
    else
    {
      $this->fieldsAttributes[$entityAttribute->getKeyname()] = $entityAttribute;
    }

    return $this;
  }

  /**
   * @return array<string, AustralEntityAttributeInterface|list<AustralEntityAttributeInterface>>
   */
  public function getFieldsAttributes(): array
  {
    return $this->fieldsAttributes;
  }

  /**
   * @return string|null
   */
  public function getName(): ?string
  {
    return $this->name;
  }
}
