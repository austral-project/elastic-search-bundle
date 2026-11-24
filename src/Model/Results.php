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

use Austral\ToolsBundle\AustralTools;

/**
 * Austral Results Model.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Results
{
  /**
   * @var float
   */
  protected float $nbResults = 0;

  /**
   * @var float
   */
  protected float $maxScore;

  /**
   * @var array<string, mixed>
   */
  protected array $shards = [];

  /**
   * @var list<Result>
   */
  protected array $searchObjects = [];

  /**
   * SearchResults constructor.
   */
  public function __construct()
  {
  }

  /**
   * @param array<string, mixed> $values
   *
   * @return $this
   *
   * @throws \Exception
   */
  public function initValues(array $values): Results
  {
    $this->shards = AustralTools::valueByKey($values, '_shards', []);
    $hitsMaster = AustralTools::valueByKey($values, 'hits', []);
    $this->nbResults = AustralTools::valueByKey(AustralTools::valueByKey($hitsMaster, 'total', []), 'value', 0);
    $this->maxScore = AustralTools::valueByKey($hitsMaster, 'max_score', 0);

    if ($this->nbResults > 0)
    {
      foreach (AustralTools::valueByKey($hitsMaster, 'hits', []) as $hit)
      {
        $searchObject = new Result($hit);
        $this->searchObjects[] = $searchObject;
      }
    }

    return $this;
  }

  /**
   * Get nbResults
   *
   * @return float
   */
  public function getNbResults(): float
  {
    return $this->nbResults;
  }

  /**
   * Set nbResults
   *
   * @param float $nbResults
   *
   * @return Results
   */
  public function setNbResults(float $nbResults): Results
  {
    $this->nbResults = $nbResults;

    return $this;
  }

  /**
   * Get maxScore
   *
   * @return float
   */
  public function getMaxScore(): float
  {
    return $this->maxScore;
  }

  /**
   * Set maxScore
   *
   * @param float $maxScore
   *
   * @return Results
   */
  public function setMaxScore(float $maxScore): Results
  {
    $this->maxScore = $maxScore;

    return $this;
  }

  /**
   * Get _shards
   *
   * @return array<string, mixed>
   */
  public function getShards(): array
  {
    return $this->shards;
  }

  /**
   * Set _shards
   *
   * @param array<string, mixed> $shards
   *
   * @return Results
   */
  public function setShards(array $shards): Results
  {
    $this->shards = $shards;

    return $this;
  }

  /**
   * Get searchObjects
   *
   * @return list<Result>
   */
  public function getSearchObjects(): array
  {
    return $this->searchObjects;
  }

  /**
   * Set searchObjects
   *
   * @param list<Result> $searchObjects
   *
   * @return Results
   */
  public function setSearchObjects(array $searchObjects): Results
  {
    $this->searchObjects = $searchObjects;

    return $this;
  }
}
