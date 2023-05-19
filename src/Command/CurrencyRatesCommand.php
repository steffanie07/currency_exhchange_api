<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Predis\Client as RedisClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Predis\PredisException;
use App\Entity\ExchangeRate;




   
#[AsCommand(
    name: 'app:currency:rates',
    description: 'Add a short description for your command',
)]
class CurrencyRatesCommand extends Command
{
    private $entityManager;
    private $redisClient;
    private $openExchangeRatesApiKey;

    private $httpClient;
    private $redis;
    private $container;
   
    public function __construct(
        EntityManagerInterface $entityManager,
        Client $httpClient,
        RedisClient $redisClient,
        ContainerInterface $container
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
        $this->redis = $redisClient;
        $this->container = $container;
        $this->openExchangeRatesApiKey = $container->getParameter('open_exchange_rates_api_key');
    }
    


    protected function configure(): void
    {
        $this
        ->setDescription('Fetches currency exchange rates.')
        ->setHelp('This command fetches currency exchange rates for given currencies from Open Exchange Rates API...')
        ->addArgument('base_currency', InputArgument::REQUIRED, 'The base currency')
        ->addArgument('target_currencies', InputArgument::IS_ARRAY, 'The target currencies')

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $baseCurrency = $input->getArgument('base_currency');
        $targetCurrencies = $input->getArgument('target_currencies');
        $client = new Client(['base_uri' => 'https://openexchangerates.org/api/']);

        try {
            $response = $client->request('GET', 'latest.json', [
                'query' => [
                    'app_id' => $this->openExchangeRatesApiKey,
                    'base' => $baseCurrency,
                    'symbols' => implode(',', $targetCurrencies),
                ]
            ]);
        } catch (GuzzleException $e) {
            $output->writeln("<error>Failed to fetch rates from API: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
     
        $rates = json_decode($response->getBody(), true)['rates'];
         
        foreach ($rates as $currency => $rate) {
            // Check if the rate is already in the database
            try {
                $exchangeRate = $this->entityManager->getRepository(ExchangeRate::class)->findOneBy([
                    'base_currency' => $baseCurrency,
                    'target_currency' => $currency,
                ]);
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to fetch rates from the database: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }

            // If not, create a new entity
            if (!$exchangeRate) {
                $exchangeRate = new ExchangeRate();
                $exchangeRate->setBaseCurrency($baseCurrency);
                $exchangeRate->setTargetCurrency($currency);
            }

            // Update the rate and save it to the database
            $exchangeRate->setRate($rate);
            try {
                $this->entityManager->persist($exchangeRate);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to save rates to the database: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }

                      // Also save the rate to Redis
                      try {
                        $this->redis->set("exchange_rate:$baseCurrency:$currency", $rate);
                    } catch (PredisException $e) {
                        $output->writeln("<error>Failed to save rates to Redis: {$e->getMessage()}</error>");
                        return Command::FAILURE;
                    }
                }
        
                $output->writeln('Exchange rates updated successfully!');
        
                return Command::SUCCESS;
            }
        }
        