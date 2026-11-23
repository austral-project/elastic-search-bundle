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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Event ElasticSearch.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ElasticSearchEvent extends Event
{
  public const string EVENT_CREATE_INDEX = 'austral.elastic_search.create';
  public const string EVENT_FILTER = 'austral.elastic_search.filter';

  /**
   * @param string               $indexName
   * @param array<string, mixed> $elasticSearchParameters
   * @param string|null          $keyname
   */
  public function __construct(
    private readonly string $indexName,
    private array $elasticSearchParameters = [],
    private readonly ?string $keyname = null
  ) {
  }

  /**
   * @return string
   */
  public function getIndexName(): string
  {
    return $this->indexName;
  }

  /**
   * @return string|null
   */
  public function getKeyname(): ?string
  {
    return $this->keyname;
  }

  /**
   * @return array<string, mixed>
   */
  public function getElasticSearchParameters(): array
  {
    return $this->elasticSearchParameters;
  }

  /**
   * @param array<string, mixed> $elasticSearchParameters
   *
   * @return ElasticSearchEvent
   */
  public function setElasticSearchParameters(array $elasticSearchParameters): ElasticSearchEvent
  {
    $this->elasticSearchParameters = $elasticSearchParameters;

    return $this;
  }
}
