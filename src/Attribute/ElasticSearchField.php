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
 * Austral ElasticSearchField attribute.
 *
 * Placed on a property or a method, the keyname is the property/method name.
 * Used inside #[ElasticSearch(fieldAttributes: [...])], the keyname must be given.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ElasticSearchField extends AustralEntityAttribute
{
  public const string INDEX_TYPE = 'index_type';
  public const string OBJECT_NAME = 'object_type';
  public const string NAME_TITLE = 'title';
  public const string NAME_REF_H1 = 'ref_h1';
  public const string NAME_REF_TITLE = 'ref_title';
  public const string NAME_REF_DESCRIPTION = 'ref_description';
  public const string NAME_REF_URL = 'ref_url';
  public const string NAME_STATUS = 'status';
  public const string NAME_CONTENT = 'content';
  public const string NAME_LANGUAGE = 'language';
  public const string NAME_UPDATE = 'update';
  public const string NAME_EXTRA_CONTENT = 'extra_content';

  /**
   * @param string|null $name
   * @param string|null $keyname
   * @param string|null $value
   * @param string|null $mappingType
   */
  public function __construct(
    public ?string $name = null,
    public ?string $keyname = null,
    public ?string $value = null,
    public ?string $mappingType = null
  ) {
  }
}
