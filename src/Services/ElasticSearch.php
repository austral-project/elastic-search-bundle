<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Services;

use Austral\Contracts\Entity\EntityInterface;
use Austral\Contracts\Translate\TranslateMasterInterface;
use Austral\ElasticSearchBundle\Attribute\ElasticSearchField;
use Austral\ElasticSearchBundle\Configuration\ElasticSearchConfiguration;
use Austral\ElasticSearchBundle\Event\ElasticSearchEvent;
use Austral\ElasticSearchBundle\Event\ElasticSearchHydrateObjectEvent;
use Austral\ElasticSearchBundle\Event\ElasticSearchSelectObjectsEvent;
use Austral\ElasticSearchBundle\Mapping\ElasticSearchMapping;
use Austral\ElasticSearchBundle\Model\DataHydrate;
use Austral\ElasticSearchBundle\Model\ObjectToHydrate;
use Austral\ElasticSearchBundle\Model\Result;
use Austral\ElasticSearchBundle\Model\Results;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Traits\IoTrait;
use Doctrine\ORM\QueryBuilder;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Austral ElasticSearch Services.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ElasticSearch
{
  use IoTrait;

  /**
   * @var Client|null
   */
  protected ?Client $client = null;

  /**
   * @param EventDispatcherInterface   $eventDispatcher
   * @param EntityManager<object>      $entityManager
   * @param ElasticSearchConfiguration $elasticSearchConfiguration
   * @param Mapping<object>            $mapping
   * @param string                     $logPath
   * @param string                     $environment
   */
  public function __construct(
    protected EventDispatcherInterface $eventDispatcher,
    protected EntityManager $entityManager,
    protected ElasticSearchConfiguration $elasticSearchConfiguration,
    protected Mapping $mapping,
    protected string $logPath,
    protected string $environment
  ) {
  }

  /**
   * getClient
   *
   * @return Client
   */
  public function getClient(): Client
  {
    return $this->client ??= $this->buildClient();
  }

  /**
   * @return Client
   *
   * @throws \Elastic\Elasticsearch\Exception\AuthenticationException
   */
  protected function buildClient(): Client
  {
    $clientBuilder = ClientBuilder::create()->setHosts($this->elasticSearchConfiguration->get('hosts'));
    if ($this->elasticSearchConfiguration->get('logger.enabled'))
    {
      $logger = new Logger('log');
      $logger->pushHandler(new StreamHandler(sprintf('%s/elastic-search_%s.log', $this->logPath, $this->environment), Level::Debug));
      $clientBuilder->setLogger($logger);
    }

    return $clientBuilder->build();
  }

  /**
   * createRequest
   *
   * @param RequestStack $requestStack
   *
   * @return $this
   */
  public function createRequest(RequestStack $requestStack): static
  {
    $newRequest = new Request();
    $newRequest->setSession(new Session());
    $requestStack->push($newRequest);

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  protected function elasticSearchParameters(): array
  {
    return [
      'index' => $this->elasticSearchConfiguration->get('index_name'),
    ];
  }

  /**
   * @return $this
   */
  public function dropIndex(): ElasticSearch
  {
    if ($this->indexExists())
    {
      try
      {
        $this->getClient()->indices()->delete($this->elasticSearchParameters());
        $this->viewMessage('Elastic Search - Drop index success');
      }
      catch (\Exception $e)
      {
        $this->viewMessage("Elastic Search - Drop index error : {$e->getMessage()}", 'error');
      }
    }

    return $this;
  }

  /**
   * @return $this
   */
  public function createIndex(): ElasticSearch
  {
    $elasticSearchParameters = $this->elasticSearchParameters();
    if (!$this->indexExists())
    {
      $elasticSearchParameters['body'] = [
        'settings' => [
          'analysis' => [
            'analyzer' => [
              'default' => [
                'tokenizer' => 'standard',
                'filter' => ['asciifolding', 'lowercase', '3_5_edgegrams'],
              ],
            ],
            'filter' => [
              '3_5_edgegrams' => [
                'type' => 'edge_ngram',
                'min_gram' => 3,
                'max_gram' => 5,
              ],
            ],
          ],
        ],
      ];
      $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get('index_name'), $elasticSearchParameters);
      $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_CREATE_INDEX);
      try
      {
        $this->getClient()->indices()->create($elasticSearchEvent->getElasticSearchParameters());
        $this->viewMessage('Elastic Search - Create index success');
      }
      catch (\Exception $e)
      {
        $this->viewMessage("Elastic Search - Create index error : {$e->getMessage()}", 'error');
      }
    }

    return $this;
  }

  /**
   * @return ElasticSearch
   *
   * @throws \Exception
   */
  public function hydrate(): ElasticSearch
  {
    $entityManager = $this->entityManager;

    foreach ($this->mapping->getEntitiesMapping() as $entityMapping)
    {
      $elasticSearchMapping = $entityMapping->getEntityClassMapping(ElasticSearchMapping::class);
      if ($elasticSearchMapping instanceof ElasticSearchMapping)
      {
        $elasticSearchMappingProperties = [];
        foreach ($elasticSearchMapping->getFieldsAttributes() as $fieldAttribute)
        {
          if ($fieldAttribute instanceof ElasticSearchField && $fieldAttribute->mappingType)
          {
            $elasticSearchMappingProperties[$fieldAttribute->name] = [
              'type' => $fieldAttribute->mappingType,
            ];
          }
        }
        if ($elasticSearchMappingProperties)
        {
          $this->getClient()->indices()->putMapping([
            'index' => $this->elasticSearchConfiguration->get('index_name'),
            'body' => [
              'properties' => $elasticSearchMappingProperties,
            ],
          ]);
        }

        $eventDispatcher = $this->eventDispatcher;
        $eventQuery = new ElasticSearchSelectObjectsEvent($entityMapping->entityClass);

        $countObject = $entityManager->getRepository($entityMapping->entityClass)->countAll(function (QueryBuilder $queryBuilder) use ($eventQuery, $eventDispatcher): void {
          $eventQuery->setQueryBuilder($queryBuilder);
          $eventQuery->setIsCount(true);
          $eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_QUERY_BUILDER);
        });

        $offset = 0;
        while ($offset != $countObject)
        {
          $eventQuery = new ElasticSearchSelectObjectsEvent($entityMapping->entityClass);
          $elasticSearchParametersToObject = $this->elasticSearchParameters();
          $entityManager->clear();
          $objects = $entityManager->getRepository($entityMapping->entityClass)->selectByClosure(function (QueryBuilder $queryBuilder) use ($eventQuery, $eventDispatcher, $offset): void {
            $eventQuery->setQueryBuilder($queryBuilder);
            $eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_QUERY_BUILDER);
            $queryBuilder->setMaxResults(1000)
              ->setFirstResult($offset);
          });
          $offset += count($objects);
          $this->viewMessage("Elastic Search - Objects {$offset} / {$countObject}");

          $eventQuery->setObjects($objects);
          $eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_OBJECTS);

          /** @var ObjectToHydrate $objectToHydrate */
          foreach ($eventQuery->getObjectsToHydrate() as $objectToHydrate)
          {
            $this->hydrateObject(
              $elasticSearchParametersToObject,
              $elasticSearchMapping,
              $objectToHydrate->getObject(),
              $objectToHydrate->getElasticSearchId(),
              $objectToHydrate->getValuesParameters()
            );
          }
          $this->hydratePush($elasticSearchParametersToObject);
        }
      }
    }

    return $this;
  }

  /**
   * @param array<string, mixed>    $elasticSearchParameters
   * @param ElasticSearchMapping    $elasticSearchMapping
   * @param EntityInterface<object> $object
   * @param string                  $elasticSearchObjectId
   * @param array<string|mixed>     $valuesParameters
   *
   * @return $this
   *
   * @throws \Exception
   */
  protected function hydrateObject(
    array &$elasticSearchParameters,
    ElasticSearchMapping $elasticSearchMapping,
    EntityInterface $object,
    string $elasticSearchObjectId,
    array $valuesParameters = []
  ): ElasticSearch {
    if (method_exists($object, 'getElasticSearchValues'))
    {
      $elasticSearchValues = $object->getElasticSearchValues();
      if ($elasticSearchValues instanceof DataHydrate)
      {
        $valuesParameters = array_merge($valuesParameters, $elasticSearchValues->toArray());
      }
    }

    $indexType = '_doc';
    foreach ($elasticSearchMapping->getFieldsAttributes() as $fieldAttribute)
    {
      if (!$fieldAttribute instanceof ElasticSearchField)
      {
        continue;
      }
      if (ElasticSearchField::INDEX_TYPE === $fieldAttribute->name)
      {
        $indexType = $fieldAttribute->value ?? $indexType;
      }
      $valuesParameters[$fieldAttribute->name] = $fieldAttribute->value ?? $elasticSearchMapping->getObjectValue($object, $fieldAttribute->keyname);
    }

    $valuesParameters[Result::OBJECT_NAME] = $elasticSearchMapping->getName();
    $valuesParameters[Result::VALUE_OBJECT_ID] = $object->getId();
    $valuesParameters[Result::VALUE_OBJECT_CLASSNAME] = $object->getClassnameForMapping();

    $objectUpdate = method_exists($object, 'getUpdate') ? $object->getUpdate() : new \DateTime();
    $valuesParameters[ElasticSearchField::NAME_UPDATE] = $objectUpdate->getTimestamp();

    $elasticSearchHydrateEvent = new ElasticSearchHydrateObjectEvent($object, [
      'index' => [
        '_index' => $this->elasticSearchConfiguration->get('index_name'),
        '_id' => $elasticSearchObjectId,
      ],
    ], $valuesParameters);

    $this->eventDispatcher->dispatch($elasticSearchHydrateEvent, ElasticSearchHydrateObjectEvent::EVENT_HYDRATE);
    $elasticSearchParameters['body'][] = $elasticSearchHydrateEvent->getIndexParameters();
    $elasticSearchParameters['body'][] = $elasticSearchHydrateEvent->getValuesParameters();

    return $this;
  }

  /**
   * @param EntityInterface<object> $object
   *
   * @return ElasticSearch
   *
   * @throws \Exception
   * @throws \Throwable
   */
  public function createOrUpdateObject(EntityInterface $object): ElasticSearch
  {
    $entityMapping = $this->mapping->getEntityMapping($object->getClassnameForMapping());
    $elasticSearchMapping = $entityMapping?->getEntityClassMapping(ElasticSearchMapping::class);
    if (!$elasticSearchMapping instanceof ElasticSearchMapping)
    {
      return $this;
    }

    $elasticSearchParameters = $this->elasticSearchParameters();
    $eventQuery = new ElasticSearchSelectObjectsEvent($entityMapping->entityClass);

    $eventQuery->setObjects([$object]);
    $this->eventDispatcher->dispatch($eventQuery, ElasticSearchSelectObjectsEvent::EVENT_OBJECTS);

    /** @var ObjectToHydrate $objectToHydrate */
    foreach ($eventQuery->getObjectsToHydrate() as $objectToHydrate)
    {
      $this->hydrateObject(
        $elasticSearchParameters,
        $elasticSearchMapping,
        $objectToHydrate->getObject(),
        $objectToHydrate->getElasticSearchId(),
        $objectToHydrate->getValuesParameters()
      );
    }
    $this->hydratePush($elasticSearchParameters);

    return $this;
  }

  /**
   * @param array<string, mixed> $elasticSearchParameters
   *
   * @return $this
   */
  protected function hydratePush(array $elasticSearchParameters = []): ElasticSearch
  {
    $response = null;
    try
    {
      if (count($elasticSearchParameters) > 0)
      {
        $response = $this->getClient()->bulk($elasticSearchParameters);
        if ($response['errors'])
        {
          throw new \Exception("Hydrate is failed -> {$response['errors']}");
        }

        $nbItemCreated = 0;
        $nbItemUpdated = 0;
        $nbItems = count($response['items']);
        foreach ($response['items'] as $item)
        {
          if ('created' === $item['index']['result'])
          {
            ++$nbItemCreated;
          }
          elseif ('updated' === $item['index']['result'])
          {
            ++$nbItemUpdated;
          }
        }
        $this->viewMessage("Elastic Search - Hydrate success -> {$nbItems} item(s) / {$nbItemCreated} item(s) created / {$nbItemUpdated} item(s) updated");
      }
      else
      {
        $this->viewMessage('Elastic Search - Hydrate success -> 0 item');
      }
    }
    catch (\Exception $e)
    {
      AustralTools::dump($response, $e);
      $this->viewMessage("Elastic Search - Hydrate error : {$e->getMessage()}", 'error');
    }

    return $this;
  }

  /**
   * @param EntityInterface<object> $object
   *
   * @return ElasticSearch
   *
   * @throws \Throwable
   */
  public function deleteObject(EntityInterface $object): ElasticSearch
  {
    $entityMapping = $this->mapping->getEntityMapping($object->getClassnameForMapping());
    $elasticSearchMapping = $entityMapping?->getEntityClassMapping(ElasticSearchMapping::class);
    if (!$elasticSearchMapping instanceof ElasticSearchMapping)
    {
      return $this;
    }

    $elasticSearchParameters = [];
    if ($object instanceof TranslateMasterInterface)
    {
      foreach ($object->getTranslates() as $translate)
      {
        $elasticSearchParameters[] = [
          'index' => $this->elasticSearchConfiguration->get('index_name'),
          'id' => sprintf('%s_%s_%s', $object->getSluggerClassname(), $object->getId(), $translate->getLanguage()),
        ];
      }
    }
    else
    {
      $elasticSearchParameters[] = [
        'index' => $this->elasticSearchConfiguration->get('index_name'),
        'id' => sprintf('%s_%s', $object->getSluggerClassname(), $object->getId()),
      ];
    }

    $nbItemsDeleted = 0;
    $nbItems = count($elasticSearchParameters);
    try
    {
      foreach ($elasticSearchParameters as $parametersIndex)
      {
        $this->getClient()->delete($parametersIndex);
        ++$nbItemsDeleted;
      }
      $this->viewMessage("Elastic Search - Delete success -> {$nbItemsDeleted}/{$nbItems} item(s) deleted");
    }
    catch (\Exception $e)
    {
      $this->viewMessage("Elastic Search - Delete error : {$e->getMessage()}", 'error');
    }

    return $this;
  }

  /**
   * @param string                   $query
   * @param int                      $offset
   * @param int                      $limit
   * @param string|null              $language
   * @param string|null              $keyname
   * @param array<int|string, mixed> $sort
   * @param array<string, mixed>     $body
   *
   * @return Results
   *
   * @throws \Exception
   */
  public function searchByQuery(
    string $query,
    int $offset = 0,
    int $limit = 20,
    ?string $language = null,
    ?string $keyname = null,
    array $sort = [],
    array $body = []
  ): Results {
    $filters = [
      'bool' => [
        'must' => [
          'query_string' => [
            'query' => "({$query})",
            'default_operator' => 'AND',
          ],
        ],
      ],
    ];

    if ($language)
    {
      $filters['bool']['filter'] = [
        'match' => [
          'language' => $language,
        ],
      ];
    }

    return $this->searchByFiltres($filters, $offset, $limit, $keyname, $sort, $body);
  }

  /**
   * @param array<string, mixed>     $filtres
   * @param int                      $offset
   * @param int                      $limit
   * @param string|null              $keyname
   * @param array<int|string, mixed> $sort
   * @param array<string, mixed>     $body
   *
   * @return Results
   *
   * @throws \Exception
   */
  public function searchByFiltres(
    array $filtres = [],
    int $offset = 0,
    int $limit = 20,
    ?string $keyname = null,
    array $sort = [],
    array $body = []
  ): Results {
    $count = $this->countByFilter($filtres, $keyname, $body);
    if (!array_key_exists('highlight', $body))
    {
      $body['highlight'] = [
        'pre_tags' => ["<span class='highlight-search'><span class='value'>"],
        'post_tags' => ['</span></span>'],
        'fields' => [
          ElasticSearchField::NAME_TITLE => ['force_source' => true, 'fragment_size' => 300, 'no_match_size' => 300, 'number_of_fragments' => 1],
          ElasticSearchField::NAME_REF_H1 => ['force_source' => true, 'fragment_size' => 300, 'no_match_size' => 300, 'number_of_fragments' => 1],
          ElasticSearchField::NAME_REF_TITLE => ['force_source' => true, 'fragment_size' => 300, 'no_match_size' => 300, 'number_of_fragments' => 1],
          ElasticSearchField::NAME_CONTENT => ['force_source' => true, 'fragment_size' => 90, 'number_of_fragments' => 3, 'no_match_size' => 270],
        ],
      ];
    }

    if ($filtres)
    {
      $body['query'] = $filtres;
    }

    if ($sort)
    {
      $body['sort'] = $sort;
    }

    $parameters = [
      'index' => $this->elasticSearchConfiguration->get('index_name'),
      'from' => $offset,
      'size' => $limit,
      'body' => $body,
    ];

    $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get('index_name'), $parameters, $keyname);
    $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_FILTER);

    $results = $this->getClient()->search($elasticSearchEvent->getElasticSearchParameters());

    $searchResult = new Results();
    $searchResult->initValues($results->asArray());

    $searchResult->setNbResults($count);

    return $searchResult;
  }

  /**
   * @param array<string, mixed> $filtres
   * @param string|null          $keyname
   * @param array<string, mixed> $body
   *
   * @return int
   *
   * @throws \Exception
   */
  public function countByFilter(array $filtres = [], ?string $keyname = null, array $body = []): int
  {
    if ($filtres)
    {
      $body['query'] = $filtres;
    }
    if (array_key_exists('highlight', $body))
    {
      unset($body['highlight']);
    }
    $parameters = [
      'index' => $this->elasticSearchConfiguration->get('index_name'),
      'body' => $body,
    ];
    $elasticSearchEvent = new ElasticSearchEvent($this->elasticSearchConfiguration->get('index_name'), $parameters, $keyname);
    $this->eventDispatcher->dispatch($elasticSearchEvent, ElasticSearchEvent::EVENT_FILTER);
    $countResponse = $this->getClient()->count($elasticSearchEvent->getElasticSearchParameters());

    return $countResponse['count'];
  }

  /**
   * exists() answers with a response object (always truthy): the status code tells whether the index exists.
   *
   * @return bool
   */
  private function indexExists(): bool
  {
    $response = $this->getClient()->indices()->exists($this->elasticSearchParameters());

    return $response instanceof ElasticsearchResponse && $response->asBool();
  }
}
