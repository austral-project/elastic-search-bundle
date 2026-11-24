<?php

/*
 * This file is part of the Austral ElasticSearch Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ElasticSearchBundle\Command;

use Austral\ElasticSearchBundle\Services\ElasticSearch;
use Austral\ToolsBundle\AustralTools;
use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Command\Exception\CommandException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Austral Initialise ElasticSearch Command.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ElasticSearchCommand extends Command
{
  /**
   * @var string
   */
  protected string $titleCommande = 'Elastic Search command';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void
  {
    $this
      ->setDefinition([
        new InputOption('--drop', '-d', InputOption::VALUE_NONE, 'Drop Elastic Search index'),
        new InputOption('--create', '-c', InputOption::VALUE_NONE, 'Create Elastic Search index'),
        new InputOption('--hydrate', '', InputOption::VALUE_NONE, 'Hydrate Elastic Search index'),
        new InputOption('--search', '', InputOption::VALUE_REQUIRED, 'Hydrate Elastic Search index'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(
        <<<'EOF'
          The <info>%command.name%</info> command to Elastic Search index

            <info>php %command.full_name% --drop</info>
            <info>php %command.full_name% --create</info>

            <info>php %command.full_name% -d</info>
            <info>php %command.full_name% -c</info>
          EOF
      )
    ;
  }

  /**
   * ElasticSearchCommand constructor
   *
   * @param ElasticSearch $elasticSearch
   * @param RequestStack  $requestStack
   * @param string|null   $name
   * @param callable|null $code
   */
  public function __construct(
    protected ElasticSearch $elasticSearch,
    protected RequestStack $requestStack,
    ?string $name = null,
    ?callable $code = null
  ) {
    parent::__construct($name, $code);
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @throws CommandException
   * @throws \Exception
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    try
    {
      if ($this->io instanceof SymfonyStyle)
      {
        $this->elasticSearch->setIo($this->io);
      }
      $this->elasticSearch->createRequest($this->requestStack);
      if ($input->getOption('drop'))
      {
        $this->elasticSearch->dropIndex();
      }
      if ($input->getOption('create'))
      {
        $this->elasticSearch->createIndex();
      }
      if ($input->getOption('hydrate'))
      {
        $this->elasticSearch->hydrate();
      }
      if ($query = $input->getOption('search'))
      {
        $searchResult = $this->elasticSearch->searchByQuery($query);
        AustralTools::dump($searchResult);
      }
    }
    catch (\Exception $e)
    {
      throw new \Exception("Elastic search error -> {$e->getMessage()} !!!");
    }
  }
}
