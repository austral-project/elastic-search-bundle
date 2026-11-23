<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Listener;

use Austral\Contracts\Entity\EntityInterface;
use Austral\Contracts\Translate\TranslateChildInterface;
use Austral\ElasticSearchBundle\Configuration\ElasticSearchConfiguration;
use Austral\ElasticSearchBundle\Services\ElasticSearch;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;

/**
 * Austral Doctrine Listener.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class DoctrineListener
{
  /**
   * @var ElasticSearchConfiguration
   */
  protected ElasticSearchConfiguration $elasticSearchConfiguration;

  /**
   * @var ElasticSearch
   */
  protected ElasticSearch $elasticSearch;

  /**
   * DoctrineListener constructor.
   */
  public function __construct(ElasticSearch $elasticSearch, ElasticSearchConfiguration $elasticSearchConfiguration)
  {
    $this->elasticSearch = $elasticSearch;
    $this->elasticSearchConfiguration = $elasticSearchConfiguration;
  }

  /**
   * @param PostUpdateEventArgs $args
   *
   * @throws \Exception
   * @throws \Throwable
   */
  public function postUpdate(PostUpdateEventArgs $args): void
  {
    if ($object = $this->entityToIndex($args->getObject()))
    {
      $this->elasticSearch->createOrUpdateObject($object);
    }
  }

  /**
   * @param PostPersistEventArgs $args
   *
   * @throws \Exception
   * @throws \Throwable
   */
  public function postPersist(PostPersistEventArgs $args): void
  {
    if ($object = $this->entityToIndex($args->getObject()))
    {
      $this->elasticSearch->createOrUpdateObject($object);
    }
  }

  /**
   * @param PreRemoveEventArgs $args
   *
   * @throws \Exception
   * @throws \Throwable
   */
  public function preRemove(PreRemoveEventArgs $args): void
  {
    if ($object = $this->entityToIndex($args->getObject()))
    {
      $this->elasticSearch->deleteObject($object);
    }
  }

  /**
   * Returns the Austral entity to index (the master for a translation), or null when
   * Elasticsearch is disabled or the object is not an Austral entity.
   *
   * @param object $object
   *
   * @return EntityInterface<object>|null
   */
  private function entityToIndex(object $object): ?EntityInterface
  {
    if (!$this->elasticSearchConfiguration->get('enabled') || !$object instanceof EntityInterface)
    {
      return null;
    }

    return $object instanceof TranslateChildInterface ? $object->getMaster() : $object;
  }
}
