<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Model;

use Austral\Contracts\Entity\EntityInterface;
use Austral\ElasticSearchBundle\Attribute\ElasticSearchField;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral Result Model.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Result
{
  public const string INDEX_TYPE = 'index_type';
  public const string OBJECT_NAME = ElasticSearchField::OBJECT_NAME;

  public const string VALUE_TITLE = ElasticSearchField::NAME_TITLE;
  public const string VALUE_REF_H1 = ElasticSearchField::NAME_REF_H1;
  public const string VALUE_REF_TITLE = ElasticSearchField::NAME_REF_TITLE;
  public const string VALUE_REF_DESCRIPTION = ElasticSearchField::NAME_REF_DESCRIPTION;
  public const string VALUE_REF_URL = ElasticSearchField::NAME_REF_URL;
  public const string VALUE_STATUS = ElasticSearchField::NAME_STATUS;
  public const string VALUE_CONTENT = ElasticSearchField::NAME_CONTENT;
  public const string VALUE_LANGUAGE = ElasticSearchField::NAME_LANGUAGE;
  public const string VALUE_UPDATE = ElasticSearchField::NAME_UPDATE;
  public const string VALUE_EXTRA_CONTENT = ElasticSearchField::NAME_EXTRA_CONTENT;

  public const string VALUE_OBJECT_ID = 'object_id';
  public const string VALUE_OBJECT_CLASSNAME = 'object_classname';

  /**
   * @var string
   */
  protected string $_index;

  /**
   * @var string
   */
  protected string $_type;

  /**
   * @var string|null
   */
  protected ?string $_score = null;

  /**
   * @var array<string, mixed>
   */
  protected array $_source;

  /**
   * @var string
   */
  protected string $title;

  /**
   * @var string|null
   */
  protected ?string $content = null;

  /**
   * @var array<string, mixed>
   */
  protected array $extraContent = [];

  /**
   * @var string|null
   */
  protected ?string $refH1 = null;

  /**
   * @var string|null
   */
  protected ?string $refTitle = null;

  /**
   * @var string|null
   */
  protected ?string $refDescription = null;

  /**
   * @var string|null
   */
  protected ?string $refUrl = null;

  /**
   * @var int|string
   */
  protected string|int $objectId;

  /**
   * @var string
   */
  protected string $objectClassname;

  /**
   * @var string|null
   */
  protected ?string $language = null;

  /**
   * @var EntityInterface<object>|null
   */
  protected ?EntityInterface $object = null;

  /**
   * @var \DateTime
   */
  protected \DateTime $update;

  /**
   * @var array<string, mixed>
   */
  protected array $highlight = [];

  /**
   * Result constructor.
   *
   * @param array<string, mixed> $values
   *
   * @throws \Exception
   */
  public function __construct(array $values = [])
  {
    $this->initValues($values);
  }

  /**
   * @param array<string, mixed> $values
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function initValues(array $values): Result
  {
    $this->_index = AustralTools::valueByKey($values, '_index');
    $this->_type = AustralTools::valueByKey($values, '_type');
    $this->_score = AustralTools::valueByKey($values, '_score');
    $this->_source = AustralTools::valueByKey($values, '_source');

    $this->title = AustralTools::valueByKey($this->_source, self::VALUE_TITLE);
    $this->content = AustralTools::valueByKey($this->_source, self::VALUE_CONTENT);
    $this->extraContent = AustralTools::valueByKey($this->_source, self::VALUE_EXTRA_CONTENT, []);

    $this->refTitle = AustralTools::valueByKey($this->_source, self::VALUE_REF_TITLE);
    $this->refH1 = AustralTools::valueByKey($this->_source, self::VALUE_REF_H1);
    $this->refUrl = AustralTools::valueByKey($this->_source, self::VALUE_REF_URL);
    $this->refDescription = AustralTools::valueByKey($this->_source, self::VALUE_REF_DESCRIPTION);

    $this->objectClassname = AustralTools::valueByKey($this->_source, self::VALUE_OBJECT_CLASSNAME);
    $this->objectId = AustralTools::valueByKey($this->_source, self::VALUE_OBJECT_ID);

    $this->language = AustralTools::valueByKey($this->_source, self::VALUE_LANGUAGE);
    $this->update = new \DateTime()->setTimestamp(AustralTools::valueByKey($this->_source, 'update'));

    $hightLight = AustralTools::valueByKey($values, 'highlight');

    $hightLightContenu = AustralTools::valueByKey($hightLight, self::VALUE_CONTENT);
    $this->highlight = [
      self::VALUE_TITLE => AustralTools::first(AustralTools::valueByKey($hightLight, self::VALUE_TITLE, []), null),
      self::VALUE_REF_H1 => AustralTools::first(AustralTools::valueByKey($hightLight, self::VALUE_REF_H1, []), null),
      self::VALUE_REF_TITLE => AustralTools::first(AustralTools::valueByKey($hightLight, self::VALUE_REF_TITLE, []), null),
      self::VALUE_CONTENT => is_array($hightLightContenu) ? implode('... ', $hightLightContenu) : $hightLightContenu,
    ];

    return $this;
  }

  /**
   * @param string      $key
   * @param string|null $default
   *
   * @return mixed
   */
  public function getHightLightByKey(string $key, ?string $default = null): mixed
  {
    return AustralTools::valueByKey($this->highlight, $key, $default);
  }

  /**
   * @param string      $key
   * @param string|null $default
   *
   * @return string|null
   */
  public function getExtraContentByKey(string $key, ?string $default = null): ?string
  {
    return AustralTools::valueByKey($this->extraContent, $key, $default);
  }

  /**
   * @return string
   */
  public function getIndex(): string
  {
    return $this->_index;
  }

  /**
   * @param string $index
   *
   * @return $this
   */
  public function setIndex(string $index): Result
  {
    $this->_index = $index;

    return $this;
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->_type;
  }

  /**
   * @param string $type
   *
   * @return $this
   */
  public function setType(string $type): Result
  {
    $this->_type = $type;

    return $this;
  }

  /**
   * @return string
   */
  public function getScore(): string
  {
    return $this->_score;
  }

  /**
   * @param string $score
   *
   * @return $this
   */
  public function setScore(string $score): Result
  {
    $this->_score = $score;

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  public function getSource(): array
  {
    return $this->_source;
  }

  /**
   * @param array<string, mixed> $source
   *
   * @return $this
   */
  public function setSource(array $source): Result
  {
    $this->_source = $source;

    return $this;
  }

  /**
   * @return string
   */
  public function getTitle(): string
  {
    return $this->title;
  }

  /**
   * @param string $title
   *
   * @return $this
   */
  public function setTitle(string $title): Result
  {
    $this->title = $title;

    return $this;
  }

  /**
   * @return string
   */
  public function getContent(): string
  {
    return $this->content;
  }

  /**
   * @param string $content
   *
   * @return Result
   */
  public function setContent(string $content): Result
  {
    $this->content = $content;

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  public function getExtraContent(): array
  {
    return $this->extraContent;
  }

  /**
   * @param array<string, mixed> $extraContent
   *
   * @return Result
   */
  public function setExtraContent(array $extraContent): Result
  {
    $this->extraContent = $extraContent;

    return $this;
  }

  /**
   * @return string
   */
  public function getRefH1(): string
  {
    return $this->refH1;
  }

  /**
   * @param string $refH1
   *
   * @return $this
   */
  public function setRefH1(string $refH1): Result
  {
    $this->refH1 = $refH1;

    return $this;
  }

  /**
   * @return string
   */
  public function getRefTitle(): string
  {
    return $this->refTitle;
  }

  /**
   * @param string $refTitle
   *
   * @return $this
   */
  public function setRefTitle(string $refTitle): Result
  {
    $this->refTitle = $refTitle;

    return $this;
  }

  /**
   * @return string
   */
  public function getRefDescription(): string
  {
    return $this->refDescription;
  }

  /**
   * @param string $refDescription
   *
   * @return Result
   */
  public function setRefDescription(string $refDescription): Result
  {
    $this->refDescription = $refDescription;

    return $this;
  }

  /**
   * @return string
   */
  public function getRefUrl(): string
  {
    return $this->refUrl;
  }

  /**
   * @param string $refUrl
   *
   * @return $this
   */
  public function setRefUrl(string $refUrl): Result
  {
    $this->refUrl = $refUrl;

    return $this;
  }

  /**
   * @return string
   */
  public function getObjectClassname(): string
  {
    return $this->objectClassname;
  }

  /**
   * @param string $objectClassname
   *
   * @return Result
   */
  public function setObjectClassname(string $objectClassname): Result
  {
    $this->objectClassname = $objectClassname;

    return $this;
  }

  /**
   * @return int|string
   */
  public function getObjectId(): int|string
  {
    return $this->objectId;
  }

  /**
   * @param int|string $objectId
   *
   * @return $this
   */
  public function setObjectId(int|string $objectId): Result
  {
    $this->objectId = $objectId;

    return $this;
  }

  /**
   * @return string
   */
  public function getLanguage(): string
  {
    return $this->language;
  }

  /**
   * @param string $language
   *
   * @return $this
   */
  public function setLanguage(string $language): Result
  {
    $this->language = $language;

    return $this;
  }

  /**
   * @return EntityInterface<object>|null
   */
  public function getObject(): ?EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface<object>|null $object
   *
   * @return $this
   */
  public function setObject(?EntityInterface $object): Result
  {
    $this->object = $object;

    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getUpdate(): \DateTime
  {
    return $this->update;
  }

  /**
   * @param \DateTime $update
   *
   * @return $this
   */
  public function setUpdate(\DateTime $update): Result
  {
    $this->update = $update;

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  public function getHighlight(): array
  {
    return $this->highlight;
  }

  /**
   * @param array<string, mixed> $highlight
   *
   * @return $this
   */
  public function setHighlight(array $highlight = []): Result
  {
    $this->highlight = $highlight;

    return $this;
  }
}
