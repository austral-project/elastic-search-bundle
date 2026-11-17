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
 * @final
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ElasticSearchField extends AustralEntityAttribute
{

  const INDEX_TYPE = "index_type";
  const OBJECT_NAME = "object_type";

  const NAME_TITLE = "title";
  const NAME_REF_H1 = "ref_h1";
  const NAME_REF_TITLE = "ref_title";
  const NAME_REF_DESCRIPTION = "ref_description";
  const NAME_REF_URL = "ref_url";
  const NAME_STATUS = "status";
  const NAME_CONTENT = "content";
  const NAME_LANGUAGE = "language";
  const NAME_UPDATE = "update";
  const NAME_EXTRA_CONTENT = "extra_content";

  /**
   * @param string|null $name
   * @param string|null $keyname
   * @param string|null $value
   * @param string|null $mappingType
   */
  public function __construct(
    public ?string $name = null,
    ?string $keyname = null,
    public ?string $value = null,
    public ?string $mappingType = null
  ) {
    if($keyname) {
      $this->keyname = $keyname;
    }
  }

}
