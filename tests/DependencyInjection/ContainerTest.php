<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\ElasticSearchBundle\Tests\DependencyInjection;

use Austral\ElasticSearchBundle\Command\ElasticSearchCommand;
use Austral\ElasticSearchBundle\Configuration\ElasticSearchConfiguration;
use Austral\ElasticSearchBundle\DependencyInjection\AustralElasticSearchExtension;
use Austral\ElasticSearchBundle\EventSubscriber\ElasticSearchSelectObjectsEventSubscriber;
use Austral\ElasticSearchBundle\Listener\DoctrineListener;
use Austral\ElasticSearchBundle\Listener\EntityMappingListener;
use Austral\ElasticSearchBundle\Services\ElasticSearch;
use Austral\EntityBundle\EntityManager\EntityManager;
use Austral\EntityBundle\Mapping\Mapping;
use Elastic\Elasticsearch\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The services are defined explicitly: a missing or misnamed argument only shows up when the container
 * is compiled or the service is created. This test loads the extension, compiles the container and
 * creates every service of the bundle.
 */
final class ContainerTest extends TestCase
{
  /**
   * Public aliases: the supported way to get a service from the container.
   * Listeners, the subscriber and the command are called by the dispatchers or the console, never fetched.
   */
  private const array PUBLIC_ALIASES = [
    'austral.elastic_search',
    'austral.elastic_search.config',
  ];

  /**
   * @return iterable<string, array{class-string}>
   */
  public static function serviceIds(): iterable
  {
    foreach (self::bundleServices() as $class)
    {
      yield $class => [$class];
    }
  }

  /**
   * @param class-string $class
   */
  #[DataProvider('serviceIds')]
  public function testEveryServiceCanBeCreated(string $class): void
  {
    self::assertInstanceOf($class, $this->compiledContainer()->get($class));
  }

  public function testTheBundleDefinesNoOtherService(): void
  {
    $ids = [];
    foreach (self::loadExtension()->getDefinitions() as $id => $definition)
    {
      if (str_starts_with($id, 'Austral\\ElasticSearchBundle\\'))
      {
        $ids[] = $id;
      }
    }
    sort($ids);

    $expected = self::bundleServices();
    sort($expected);

    self::assertSame($expected, $ids);
  }

  public function testOnlyTheDocumentedAliasesArePublic(): void
  {
    $container = self::loadExtension();

    foreach ($container->getDefinitions() as $id => $definition)
    {
      if (str_starts_with($id, 'Austral\\ElasticSearchBundle\\'))
      {
        self::assertFalse($definition->isPublic(), sprintf('The service "%s" must be private.', $id));
      }
    }

    $aliases = [];
    foreach ($container->getAliases() as $id => $alias)
    {
      if (str_starts_with($id, 'austral.elastic_search'))
      {
        $aliases[$id] = (string) $alias;
        self::assertTrue($alias->isPublic(), sprintf('The alias "%s" must be public.', $id));
      }
    }
    ksort($aliases);

    self::assertSame([
      'austral.elastic_search' => ElasticSearch::class,
      'austral.elastic_search.config' => ElasticSearchConfiguration::class,
    ], $aliases);
    self::assertSame(self::PUBLIC_ALIASES, array_keys($aliases));
  }

  public function testTheConfigurationReceivesTheDefaultValues(): void
  {
    $configuration = $this->compiledContainer()->get(ElasticSearchConfiguration::class);
    self::assertInstanceOf(ElasticSearchConfiguration::class, $configuration);

    self::assertFalse($configuration->get('enabled'));
    self::assertSame('elastic-search', $configuration->get('index_name'));
  }

  /**
   * The client needs an HTTP client: it is built on first use, never when the service is created
   * (the Doctrine listener creates the service on every flush, even when Elasticsearch is disabled).
   */
  public function testTheClientIsBuiltOnFirstUse(): void
  {
    $elasticSearch = $this->compiledContainer()->get(ElasticSearch::class);
    self::assertInstanceOf(ElasticSearch::class, $elasticSearch);

    $client = new \ReflectionProperty(ElasticSearch::class, 'client');
    self::assertNull($client->getValue($elasticSearch));

    $built = $elasticSearch->getClient();
    self::assertInstanceOf(Client::class, $built);
    self::assertSame($built, $elasticSearch->getClient());
  }

  public function testTheCommandIsRegisteredWithItsDependencies(): void
  {
    $container = $this->compiledContainer();

    $tags = $container->findTaggedServiceIds('console.command');
    self::assertArrayHasKey(ElasticSearchCommand::class, $tags);
    self::assertSame('austral:elastic-search', $tags[ElasticSearchCommand::class][0]['command'] ?? null);

    $command = $container->get(ElasticSearchCommand::class);
    self::assertInstanceOf(ElasticSearchCommand::class, $command);
    self::assertInstanceOf(EventDispatcherInterface::class, new \ReflectionProperty($command, 'eventDispatcher')->getValue($command));
    self::assertInstanceOf(RequestStack::class, new \ReflectionProperty($command, 'requestStack')->getValue($command));
    self::assertInstanceOf(ElasticSearch::class, new \ReflectionProperty($command, 'elasticSearch')->getValue($command));
  }

  public function testTheDoctrineListenerIsTagged(): void
  {
    $tags = self::loadExtension()->getDefinition(DoctrineListener::class)->getTag('doctrine.event_listener');

    self::assertSame([
      ['event' => 'postPersist', 'connection' => 'default'],
      ['event' => 'postUpdate', 'connection' => 'default'],
      ['event' => 'preRemove', 'connection' => 'default'],
    ], $tags);
  }

  public function testTheEntityMappingListenerIsTagged(): void
  {
    $tags = self::loadExtension()->getDefinition(EntityMappingListener::class)->getTag('kernel.event_listener');

    self::assertSame([
      ['event' => 'austral.event.entity.initialise.mapping', 'method' => 'mapping'],
    ], $tags);
  }

  /**
   * No autoconfiguration: the subscriber is tagged once, in the YAML.
   */
  public function testTheSubscriberIsTaggedOnce(): void
  {
    $definition = self::loadExtension()->getDefinition(ElasticSearchSelectObjectsEventSubscriber::class);

    self::assertSame([[]], $definition->getTag('kernel.event_subscriber'));
    self::assertFalse($definition->isAutoconfigured());
    self::assertFalse($definition->isAutowired());
  }

  /**
   * @return list<class-string>
   */
  private static function bundleServices(): array
  {
    return [
      ElasticSearch::class,
      ElasticSearchConfiguration::class,
      EntityMappingListener::class,
      DoctrineListener::class,
      ElasticSearchSelectObjectsEventSubscriber::class,
      ElasticSearchCommand::class,
    ];
  }

  /**
   * The services that the bundle needs from the application and from entity-bundle are added as minimal definitions.
   */
  private static function loadExtension(): ContainerBuilder
  {
    $container = new ContainerBuilder();
    $container->setParameter('kernel.logs_dir', sys_get_temp_dir());
    $container->setParameter('kernel.environment', 'test');
    $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
    $container->setDefinition('request_stack', new Definition(RequestStack::class));
    $container->setDefinition('austral.entity_manager', new Definition(EntityManager::class)->setSynthetic(true)->setPublic(true));
    $container->setDefinition('austral.entity.mapping', new Definition(Mapping::class)->setSynthetic(true)->setPublic(true));
    new AustralElasticSearchExtension()->load([], $container);

    return $container;
  }

  private function compiledContainer(): ContainerBuilder
  {
    $container = self::loadExtension();
    foreach ($container->getDefinitions() as $id => $definition)
    {
      if (str_starts_with($id, 'Austral\\ElasticSearchBundle\\'))
      {
        $definition->setPublic(true);
      }
    }
    $container->compile();
    // EntityManager and Mapping are only stored by the services of the bundle: instances without their own dependencies are enough.
    $container->set('austral.entity_manager', new \ReflectionClass(EntityManager::class)->newInstanceWithoutConstructor());
    $container->set('austral.entity.mapping', new \ReflectionClass(Mapping::class)->newInstanceWithoutConstructor());

    return $container;
  }
}
