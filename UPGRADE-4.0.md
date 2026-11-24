# Upgrade from 3.1 to 4.0

## Requirements

- PHP 8.4 minimum
- Symfony 7.4
- Doctrine ORM 3
- An Elasticsearch 8 or 9 server: the bundle accepts `elasticsearch/elasticsearch` `^8.19 || ^9.1`. Install the client of the same major version as your server (`composer require elasticsearch/elasticsearch:^8.19` for an Elasticsearch 8 server). An Elasticsearch 7 server must be upgraded.

## Update the dependencies

```diff
- "austral/elastic-search-bundle": "^3.1"
+ "austral/elastic-search-bundle": "^4.0"
```

All `austral/*` packages must be upgraded to `^4.0` at the same time.

The bundle now requires `symfony/http-client` and `nyholm/psr7`: since version 8, the Elasticsearch client sends its requests through a PSR-18 HTTP client and builds them with PSR-17 factories.

## Breaking changes

### Elasticsearch client 8 or 9

`elasticsearch/elasticsearch` goes from 7.17 to 8.19 or 9 (both have the same API for the bundle). The classes of the client changed namespace (`Elasticsearch\Client` → `Elastic\Elasticsearch\Client`), and its methods return a response object instead of an array.

```diff
- use Elasticsearch\Client;
+ use Elastic\Elasticsearch\Client;

  $response = $elasticSearch->getClient()->search($parameters);
- $hits = $response['hits'];
+ $hits = $response->asArray()['hits'];
```

Array access on the response (`$response['hits']`) still works; `asArray()` gives the whole body. See "Backward Incompatible Changes" in the [README of the client](https://github.com/elastic/elasticsearch-php).

Mapping types were removed in Elasticsearch 8: the bulk requests no longer send `_type`. A field declared with `ElasticSearchField::INDEX_TYPE` is indexed as a regular field. Recreate the index after the upgrade:

```bash
php bin/console austral:elastic-search --drop --create --hydrate
```

### Annotations replaced by attributes

The `@ElasticSearch` and `@ElasticSearchField` Doctrine annotations are replaced by PHP attributes, in the `Attribute` namespace. The `fieldAnnotations` argument is renamed `fieldAttributes`.

Before:

```php
use Austral\ElasticSearchBundle\Annotation as ElasticSearch;

/**
 * @ElasticSearch\ElasticSearch(name="page", fieldAnnotations={
 *   @ElasticSearch\ElasticSearchField(name="status", value="published")
 * })
 */
class Page
{
  /**
   * @ElasticSearch\ElasticSearchField(name="title", keyname="name")
   */
  protected ?string $name = null;
}
```

After:

```php
use Austral\ElasticSearchBundle\Attribute\ElasticSearch;
use Austral\ElasticSearchBundle\Attribute\ElasticSearchField;

#[ElasticSearch(name: 'page', fieldAttributes: [
  new ElasticSearchField(name: 'status', value: 'published'),
])]
class Page
{
  #[ElasticSearchField(name: 'title', keyname: 'name')]
  protected ?string $name = null;
}
```

`ElasticSearchMapping::addFieldAnnotation()` and `getFieldsAnnotations()` are renamed `addFieldAttribute()` and `getFieldsAttributes()`.

### Services: explicit definitions, private class ids

The services of the bundle are no longer autowired nor autoconfigured: each one is defined with its arguments and tags. In 3.1, `austral.elastic_search` and `austral.elastic_search.config` were the service ids and the class names were aliases. In 4.0 it is the other way round: the class names are the service ids, and they are **private**.

| Service | Id | Alias |
|---------|----|-------|
| Elasticsearch service | `Austral\ElasticSearchBundle\Services\ElasticSearch` | `austral.elastic_search` (public) |
| Configuration | `Austral\ElasticSearchBundle\Configuration\ElasticSearchConfiguration` | `austral.elastic_search.config` (public) |
| Entity mapping listener | `Austral\ElasticSearchBundle\Listener\EntityMappingListener` | none: `austral.elastic_search.entity_mapping.listener` is removed |
| Doctrine listener | `Austral\ElasticSearchBundle\Listener\DoctrineListener` | none: `austral.elastic_search.doctrine.listener` is removed |
| Select objects subscriber | `Austral\ElasticSearchBundle\EventSubscriber\ElasticSearchSelectObjectsEventSubscriber` | none: `austral.elastic_search.select_objects.event_subscriber` is removed |
| Command `austral:elastic-search` | `Austral\ElasticSearchBundle\Command\ElasticSearchCommand` | none: `austral.elastic_search.command` is removed |

Before:

```php
$elasticSearch = $container->get(\Austral\ElasticSearchBundle\Services\ElasticSearch::class);
```

After: inject the service, or fetch it by its public alias.

```yaml
App\Service\MyService:
  arguments:
    $elasticSearch: '@austral.elastic_search'
```

Autowiring `Austral\ElasticSearchBundle\Services\ElasticSearch` in a constructor of your application keeps working.

> **Warning:** a service of your application that decorates a listener or the command by its 3.1 id (`decorates: austral.elastic_search.doctrine.listener`, for example) makes the container compilation fail. Decorate the class id instead.

The XML namespace of the bundle configuration changes. In an XML configuration file:

```diff
- xmlns:austral-elastic-search="https://austral.app/schema/dic/austral_elastic_search"
+ xmlns:austral-elastic-search="https://austral.dev/schema/dic/austral_elastic_search"
```

### Configuration parameter renamed

The configuration of the bundle is stored in the `austral_elastic_search` parameter, like the other Austral bundles (`austral_http`, `austral_domain`…). In a service definition of your application:

```diff
- $config: '%austral_elastic_search_config%'
+ $config: '%austral_elastic_search%'
```

### Constructors

The classes no longer receive the service container. A class of your application that extends one of them must call the new constructor.

| Class | 3.1 | 4.0 |
|-------|-----|-----|
| `Services\ElasticSearch` | `ContainerInterface`, `EventDispatcherInterface`, `EntityManager`, `ElasticSearchConfiguration`, `Mapping` | `EventDispatcherInterface $eventDispatcher`, `EntityManager $entityManager`, `ElasticSearchConfiguration $elasticSearchConfiguration`, `Mapping $mapping`, `string $logPath` (`%kernel.logs_dir%`), `string $environment` (`%kernel.environment%`) |
| `Command\ElasticSearchCommand` | none (used the container) | `ElasticSearch $elasticSearch`, `RequestStack $requestStack` |
| `Listener\EntityMappingListener` | `ContainerInterface` | none |

The Elasticsearch client is built on first use: read it with `getClient()`, not with the `$client` property, which is `null` until then.

The client log file is named after the kernel environment: `var/log/elastic-search_<environment>.log`. In 3.1 it was `elastic-search_dev.log` when `kernel.debug` was true and `elastic-search_prod.log` otherwise.

### Doctrine listener

`DoctrineListener` no longer implements `Doctrine\Common\EventSubscriber`: it is registered with one `doctrine.event_listener` tag per event (`postPersist`, `postUpdate`, `preRemove`). Its methods receive `PostPersistEventArgs`, `PostUpdateEventArgs` and `PreRemoveEventArgs` instead of `LifecycleEventArgs`, and `getSubscribedEvents()`, `getEventAdapter()` and `getNamespace()` are removed. A class of your application that extends it must update the signatures of the methods it overrides.

### Events

`ElasticSearchSelectObjectsEvent::getQueryBuilder()` returns a `Doctrine\ORM\QueryBuilder` and `setQueryBuilder()` accepts one: they used `Austral\EntityBundle\ORM\AustralQueryBuilder`. A listener that types its query builder as `AustralQueryBuilder` must use `QueryBuilder`.

### Removed subscribers

`ElasticSearchEventSubscriber` (on `ElasticSearchEvent::EVENT_CREATE_INDEX`) and `ElasticSearchHydrateObjectEventSubscriber` (on `ElasticSearchHydrateObjectEvent::EVENT_HYDRATE`) are removed: their methods were empty. The events are still dispatched; listen to them in your application.
