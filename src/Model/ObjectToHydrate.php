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
use Austral\ToolsBundle\AustralTools;

/**
 * Austral ElasticSearch ObjectToHydrate Model.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ObjectToHydrate
{
  /**
   * @var string
   */
  protected string $elasticSearchId;

  /**
   * @var EntityInterface<object>
   */
  protected EntityInterface $object;

  /**
   * @var array<string, mixed>
   */
  protected array $valuesParameters = [];

  /**
   * @param string                  $elasticSearchId
   * @param EntityInterface<object> $object
   *
   * @return ObjectToHydrate
   */
  public static function create(string $elasticSearchId, EntityInterface $object): ObjectToHydrate
  {
    return new self($elasticSearchId, $object);
  }

  /**
   * ObjectToHydrate constructor.
   *
   * @param string                  $elasticSearchId
   * @param EntityInterface<object> $object
   */
  public function __construct(string $elasticSearchId, EntityInterface $object)
  {
    $this->elasticSearchId = $elasticSearchId;
    $this->object = $object;
  }

  /**
   * @return string
   */
  public function getElasticSearchId(): string
  {
    return $this->elasticSearchId;
  }

  /**
   * @param string $elasticSearchId
   *
   * @return ObjectToHydrate
   */
  public function setElasticSearchId(string $elasticSearchId): ObjectToHydrate
  {
    $this->elasticSearchId = $elasticSearchId;

    return $this;
  }

  /**
   * @return EntityInterface<object>
   */
  public function getObject(): EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface<object> $object
   *
   * @return ObjectToHydrate
   */
  public function setObject(EntityInterface $object): ObjectToHydrate
  {
    $this->object = $object;

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  public function getValuesParameters(): array
  {
    return $this->valuesParameters;
  }

  /**
   * @param string $valueKey
   * @param mixed  $default
   *
   * @return mixed
   */
  public function getValuesParametersByKey(string $valueKey, mixed $default = null): mixed
  {
    return AustralTools::valueByKey($this->valuesParameters, $valueKey, $default);
  }

  /**
   * @param string $valueKey
   * @param mixed  $valuesParameter
   *
   * @return ObjectToHydrate
   */
  public function addValuesParameters(string $valueKey, mixed $valuesParameter): ObjectToHydrate
  {
    $this->valuesParameters[$valueKey] = $valuesParameter;

    return $this;
  }

  /**
   * @param array<string, mixed> $valuesParameters
   *
   * @return ObjectToHydrate
   */
  public function setValuesParameters(array $valuesParameters): ObjectToHydrate
  {
    $this->valuesParameters = $valuesParameters;

    return $this;
  }
}
