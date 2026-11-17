<?php
/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\ElasticSearchBundle\Attribute;

use Austral\EntityBundle\Attribute\AustralEntityAttribute;

/**
 * Austral ElasticSearch attribute.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ElasticSearch extends AustralEntityAttribute
{

  /**
   * @param string|null $name
   * @param array<ElasticSearchField> $fieldAttributes
   */
  public function __construct(
    public ?string $name = null,
    public array $fieldAttributes = []
  ) {
  }

}
