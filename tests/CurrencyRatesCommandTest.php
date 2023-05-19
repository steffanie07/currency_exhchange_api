<?php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use App\Entity\ExchangeRate;

class CurrencyRatesCommandTest extends WebTestCase
{
    public function testCurrencyRatesCommand()
{
    self::bootKernel();
    $application = new Application();
    $command = static::getContainer()->get('App\Command\CurrencyRatesCommand');
    $application->add($command);

    $command = $application->find('app:currency:rates');
    $commandTester = new CommandTester($command);

    $arguments = [
        'base_currency' => 'EUR',
        'target_currencies' => ['USD', 'GBP'],
    ];

    $commandTester->execute($arguments);

    $this->assertSame(0, $commandTester->getStatusCode());
    $this->assertStringContainsString('Exchange rates updated successfully!', $commandTester->getDisplay());

    $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    $exchangeRateRepository = $entityManager->getRepository(ExchangeRate::class);
    $exchangeRate = $exchangeRateRepository->findOneBy([
        'base_currency' => 'USD',
        'target_currency' => 'EUR',
    ]);
    $this->assertInstanceOf(ExchangeRate::class, $exchangeRate);
}
    public function testExchangeRatesEndpoint()
    {
        $client = static::createClient();

        $client->request('GET', '/api/exchange-rates', [
            'query' => [
                'base_currency' => 'EUR',
                'target_currencies' => 'USD,GBP',
            ],
        ]);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}
