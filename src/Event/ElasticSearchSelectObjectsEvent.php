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
use Austral\ElasticSearchBundle\Model\ObjectToHydrate;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearchSelectObjects.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ElasticSearchSelectObjectsEvent extends Event
{
  public const string EVENT_QUERY_BUILDER = 'austral.elastic_search.select.objects.query_builder';
  public const string EVENT_OBJECTS = 'austral.elastic_search.select.objects';
  public const string EVENT_OBJECT = 'austral.elastic_search.select.object';

  /**
   * @var array<array-key, EntityInterface<object>>
   */
  private array $objects = [];

  /**
   * @var bool
   */
  private bool $isCount = false;

  /**
   * @var array<string, ObjectToHydrate>
   */
  private array $objectsToHydrate = [];

  /**
   * @var QueryBuilder|null
   */
  private ?QueryBuilder $queryBuilder = null;

  /**
   * ElasticSearchSelectObjectsEvent constructor.
   */
  public function __construct(private string $entityClass)
  {
  }

  /**
   * @return string
   */
  public function getEntityClass(): string
  {
    return $this->entityClass;
  }

  /**
   * @param string $entityClass
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setEntityClass(string $entityClass): ElasticSearchSelectObjectsEvent
  {
    $this->entityClass = $entityClass;

    return $this;
  }

  /**
   * @return array<array-key, EntityInterface<object>>
   */
  public function getObjects(): array
  {
    return $this->objects;
  }

  /**
   * @param array<array-key, EntityInterface<object>> $objects
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setObjects(array $objects): ElasticSearchSelectObjectsEvent
  {
    $this->objects = $objects;

    return $this;
  }

  /**
   * @return array<string, ObjectToHydrate>
   */
  public function getObjectsToHydrate(): array
  {
    return $this->objectsToHydrate;
  }

  /**
   * @param array<string, ObjectToHydrate> $objectsToHydrate
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setObjectsToHydrate(array $objectsToHydrate): ElasticSearchSelectObjectsEvent
  {
    $this->objectsToHydrate = $objectsToHydrate;

    return $this;
  }

  /**
   * @return bool
   */
  public function getIsCount(): bool
  {
    return $this->isCount;
  }

  /**
   * @param bool $isCount
   *
   * @return $this
   */
  public function setIsCount(bool $isCount): ElasticSearchSelectObjectsEvent
  {
    $this->isCount = $isCount;

    return $this;
  }

  /**
   * @return QueryBuilder|null
   */
  public function getQueryBuilder(): ?QueryBuilder
  {
    return $this->queryBuilder;
  }

  /**
   * @param QueryBuilder|null $queryBuilder
   *
   * @return ElasticSearchSelectObjectsEvent
   */
  public function setQueryBuilder(?QueryBuilder $queryBuilder): ElasticSearchSelectObjectsEvent
  {
    $this->queryBuilder = $queryBuilder;

    return $this;
  }
}
