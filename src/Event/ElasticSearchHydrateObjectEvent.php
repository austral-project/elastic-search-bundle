<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Event;

use Austral\Contracts\Entity\EntityInterface;
use Austral\ToolsBundle\AustralTools;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearchHydrateObject.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ElasticSearchHydrateObjectEvent extends Event
{
  public const string EVENT_HYDRATE = 'austral.elastic_search.hydrate.object';

  /**
   * ElasticSearchHydrateObjectEvent constructor.
   *
   * @param EntityInterface<object> $object
   * @param array<string, mixed>    $indexParameters
   * @param array<string, mixed>    $valuesParameters
   */
  public function __construct(
    private EntityInterface $object,
    private array $indexParameters = [],
    private array $valuesParameters = []
  ) {
  }

  /**
   * @return array<string, mixed>
   */
  public function getIndexParameters(): array
  {
    return $this->indexParameters;
  }

  /**
   * @param array<string, mixed> $indexParameters
   *
   * @return ElasticSearchHydrateObjectEvent
   */
  public function setIndexParameters(array $indexParameters): ElasticSearchHydrateObjectEvent
  {
    $this->indexParameters = $indexParameters;

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
   * @return ElasticSearchHydrateObjectEvent
   */
  public function setObject(EntityInterface $object): ElasticSearchHydrateObjectEvent
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
   * @param array<string, mixed> $valuesParameters
   *
   * @return ElasticSearchHydrateObjectEvent
   */
  public function setValuesParameters(array $valuesParameters = []): ElasticSearchHydrateObjectEvent
  {
    $this->valuesParameters = $valuesParameters;

    return $this;
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @return ElasticSearchHydrateObjectEvent
   */
  public function addValuesParameter(string $key, mixed $value): ElasticSearchHydrateObjectEvent
  {
    $this->valuesParameters[$key] = $value;

    return $this;
  }

  /**
   * @param string $key
   * @param mixed  $default
   *
   * @return mixed
   */
  public function getValueParameterByKey(string $key, mixed $default = null): mixed
  {
    return AustralTools::valueByKey($this->getValuesParameters(), $key, $default);
  }
}
