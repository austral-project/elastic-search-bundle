# Changelog

All notable changes to this bundle are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). All Austral bundles share the same major and minor version.

## [Unreleased]

### Added

- PHP attributes `#[ElasticSearch]` and `#[ElasticSearchField]` (`Austral\ElasticSearchBundle\Attribute\…`), which replace the Doctrine annotations.
- `tests/DependencyInjection/ContainerTest.php`: compiles the container, creates every service, and checks the public aliases, the tags and the command.

### Changed

- Sources moved to `src/` (PSR-4 `Austral\ElasticSearchBundle\` → `src/`).
- **BREAKING** Requires PHP 8.4, Symfony 7.4, Doctrine ORM 3, `austral/contracts` and `elasticsearch/elasticsearch` `^8.19 || ^9.1` (it was `^7.17`). Use the client of the same major version as the Elasticsearch server (8 or 9).
- `symfony/http-client` and `nyholm/psr7` are required: since version 8, the Elasticsearch client sends its requests through a PSR-18 HTTP client and builds them with PSR-17 factories. Client 8 does not install them.
- **BREAKING** The configuration of the bundle is stored in the `austral_elastic_search` parameter (it was `austral_elastic_search_config`), like the other Austral bundles.
- **BREAKING** `ElasticSearch` receives its services through the constructor: `EventDispatcherInterface`, `EntityManager`, `ElasticSearchConfiguration`, `Mapping`, the logs directory (`string $logPath`) and the kernel environment (`string $environment`). It no longer receives the service container.
- The Elasticsearch client is built on first use (`ElasticSearch::getClient()`), no longer when the service is created: saving an entity no longer builds a client when the bundle is disabled.
- The client log file is named after the kernel environment, `elastic-search_<environment>.log` (it was `elastic-search_dev.log` when `kernel.debug` was true, `elastic-search_prod.log` otherwise).
- **BREAKING** `ElasticSearchCommand` receives `ElasticSearch` and `RequestStack` through its constructor; it no longer uses the service container. The command keeps its name, `austral:elastic-search`.
- **BREAKING** `EntityMappingListener` has no constructor argument (it received the service container).
- **BREAKING** `DoctrineListener` is a Doctrine event listener (tags `doctrine.event_listener` on `postPersist`, `postUpdate` and `preRemove`) and no longer implements `Doctrine\Common\EventSubscriber`. Its methods receive `PostPersistEventArgs`, `PostUpdateEventArgs` and `PreRemoveEventArgs`.
- **BREAKING** `ElasticSearchMapping::addFieldAnnotation()` and `getFieldsAnnotations()` are renamed `addFieldAttribute()` and `getFieldsAttributes()`.
- **BREAKING** `ElasticSearchSelectObjectsEvent::getQueryBuilder()` and `setQueryBuilder()` use `Doctrine\ORM\QueryBuilder` (they used `Austral\EntityBundle\ORM\AustralQueryBuilder`).
- **BREAKING** The bulk requests no longer send `_type`: mapping types were removed in Elasticsearch 8. An `ElasticSearchField::INDEX_TYPE` field is indexed as a regular field.
- **BREAKING** Services are defined explicitly (no autowiring, no autoconfiguration). In 3.1, `austral.elastic_search` and `austral.elastic_search.config` were the service ids and the class names were aliases. In 4.0, the class names are the service ids and are private; `austral.elastic_search` and `austral.elastic_search.config` are public aliases.
- **BREAKING** The XML namespace of the bundle configuration is `https://austral.dev/schema/dic/austral_elastic_search` (it was `https://austral.app/schema/dic/austral_elastic_search`).
- `indices()->exists()` returns a response object with client 8+: the index is checked with its status code before it is dropped or created.
- `ElasticSearch::deleteObject()` finds the translations with `TranslateMasterInterface` (contracts) instead of the mapping of entity-translate-bundle.

### Fixed

- `ElasticSearch::deleteObject()` no longer calls `indices()->create()` with the delete parameters before deleting (the call always failed silently).
- `DoctrineListener` ignores the objects that are not Austral entities. They were passed to `createOrUpdateObject()` and `deleteObject()`, which only accept an `EntityInterface`: saving any other entity threw a `TypeError` when the bundle was enabled.

### Removed

- **BREAKING** The `Austral\ElasticSearchBundle\Annotation\ElasticSearch` and `Annotation\ElasticSearchField` annotations: use the attributes. The `fieldAnnotations` argument is renamed `fieldAttributes`.
- **BREAKING** `ElasticSearchEventSubscriber` and `ElasticSearchHydrateObjectEventSubscriber` (their methods were empty), with their aliases `austral.elastic_search.event_subscriber` and `austral.elastic_search.hydrate_object.event_subscriber`.
- **BREAKING** The aliases of the listeners and of the subscriber, which are called by the dispatchers and never fetched from the container: `austral.elastic_search.entity_mapping.listener`, `austral.elastic_search.doctrine.listener`, `austral.elastic_search.select_objects.event_subscriber`.
- **BREAKING** The `austral.elastic_search.command` service id: the command id is `Austral\ElasticSearchBundle\Command\ElasticSearchCommand`.
- **BREAKING** `DoctrineListener::getSubscribedEvents()`, `getEventAdapter()` and `getNamespace()`.
- `Results::$_shards`, which was never read (the shards are stored in `$shards`).

## [3.1.0] - 2022-09-17

- Updated bundles version to 3.1
- Changed the method check if object is a TranslateChildInterface`

## [3.0.0] - 2021-02-10

- Created new repository and new branch in to Github
- Changed the project License to the MIT
- Writing and publish documentation
- Add to Packagist Service for installation with composer

## 2.x and 1.x

These versions were not open source and have no public documentation.

[Unreleased]: https://github.com/austral-project/elastic-search-bundle/compare/v3.1.2...4.x
[3.1.0]: https://github.com/austral-project/elastic-search-bundle/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/austral-project/elastic-search-bundle/releases/tag/v3.0.0
