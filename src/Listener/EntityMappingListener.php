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

use Austral\ElasticSearchBundle\Attribute\ElasticSearch;
use Austral\ElasticSearchBundle\Attribute\ElasticSearchField;
use Austral\ElasticSearchBundle\Mapping\ElasticSearchMapping;
use Austral\EntityBundle\Event\EntityMappingEvent;
use Austral\EntityBundle\Mapping\EntityMapping;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Austral EntityMapping Listener.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class EntityMappingListener
{
  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @param ContainerInterface $container
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
  }

  /**
   * @param EntityMappingEvent<object> $entityMappingEvent
   *
   * @return void
   *
   * @throws \Exception
   */
  public function mapping(EntityMappingEvent $entityMappingEvent): void
  {
    foreach ($entityMappingEvent->getEntitiesAttributes()->all() as $entityAttribute)
    {
      $classAttributes = $entityAttribute->getClassAttributes();
      if (array_key_exists(ElasticSearch::class, $classAttributes))
      {
        if (!$entityMapping = $entityMappingEvent->getMapping()->getEntityMapping($entityAttribute->getClassname()))
        {
          $entityMapping = new EntityMapping($entityAttribute->getClassname(), $entityAttribute->getSlugger());
        }

        /** @var ElasticSearch $elasticSearchAttribute */
        $elasticSearchAttribute = $classAttributes[ElasticSearch::class];
        $elasticSearchMapping = new ElasticSearchMapping($elasticSearchAttribute->name);

        foreach ($elasticSearchAttribute->fieldAttributes as $fieldAttribute)
        {
          $elasticSearchMapping->addFieldAttribute($fieldAttribute);
        }

        foreach ($entityAttribute->getFieldsAttributes() as $fieldAttributes)
        {
          if (array_key_exists(ElasticSearchField::class, $fieldAttributes))
          {
            $elasticSearchMapping->addFieldAttribute($fieldAttributes[ElasticSearchField::class]);
          }
        }
        $entityMapping->addEntityClassMapping($elasticSearchMapping);
        $entityMappingEvent->getMapping()->addEntityMapping($entityAttribute->getClassname(), $entityMapping);
      }
    }
  }
}
