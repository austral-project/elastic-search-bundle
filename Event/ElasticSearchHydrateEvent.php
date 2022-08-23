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

use Austral\EntityBundle\Entity\EntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearchHydrate.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ElasticSearchHydrateEvent extends Event
{

  const EVENT_HYDRATE = "austral.elastic_search.hydrate";

  /**
   * @var EntityInterface
   */
  private EntityInterface $object;

  /**
   * @var array
   */
  private array $indexParameters;

  /**
   * @var array
   */
  private array $valuesParameters;


  /**
   * EntityDuplicateEvent constructor.
   *
   * @param EntityInterface $object
   * @param array $indexParameters
   * @param array $valuesParameters
   */
  public function __construct(EntityInterface $object, array $indexParameters = array(), array $valuesParameters = array())
  {
    $this->object = $object;
    $this->indexParameters = $indexParameters;
    $this->valuesParameters = $valuesParameters;
  }

  /**
   * @return array
   */
  public function getIndexParameters(): array
  {
    return $this->indexParameters;
  }

  /**
   * @param array $indexParameters
   *
   * @return ElasticSearchHydrateEvent
   */
  public function setIndexParameters(array $indexParameters): ElasticSearchHydrateEvent
  {
    $this->indexParameters = $indexParameters;
    return $this;
  }

  /**
   * @return EntityInterface
   */
  public function getObject(): EntityInterface
  {
    return $this->object;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ElasticSearchHydrateEvent
   */
  public function setObject(EntityInterface $object): ElasticSearchHydrateEvent
  {
    $this->object = $object;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getValuesParameters()
  {
    return $this->valuesParameters;
  }

  /**
   * @param mixed $valuesParameters
   *
   * @return ElasticSearchHydrateEvent
   */
  public function setValuesParameters($valuesParameters): ElasticSearchHydrateEvent
  {
    $this->valuesParameters = $valuesParameters;
    return $this;
  }

}