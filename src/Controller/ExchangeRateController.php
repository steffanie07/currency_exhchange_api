<?php
namespace App\Controller;

use App\Entity\ExchangeRate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Predis\Client as PredisClient;
use Doctrine\ORM\EntityManagerInterface;
use Predis\ClientInterface;
class ExchangeRateController extends AbstractController
{
    private $entityManager;
    private $redis;

    public function __construct(EntityManagerInterface $entityManager, PredisClient $redis)
    {
        $this->entityManager = $entityManager;
        $this->redis = $redis;
          $this->redis = $redis;
    }
  
    /**
     * @Route("/api/exchange-rates", methods={"GET"})
     */
    public function index(Request $request): JsonResponse
    {
        $baseCurrency = $request->query->get('base_currency');
        $targetCurrencies = explode(',', $request->query->get('target_currencies'));

        $rates = [];
        foreach ($targetCurrencies as $currency) {
            // Try to get the rate from Redis
            $rate = $this->redis->get("exchange_rate:$baseCurrency:$currency");

            // If the rate is not in Redis, fetch it from the database
            if (!$rate) {
                $exchangeRate = $this->entityManager->getRepository(ExchangeRate::class)->findOneBy([
                    'base_currency' => $baseCurrency,
                    'target_currency' => $currency,
                ]);

                if ($exchangeRate) {
                    $rate = $exchangeRate->getRate();

                    // Store the rate in Redis for future requests
                    $this->redis->set("exchange_rate:$baseCurrency:$currency", $rate);
                }
            }

            $rates[$currency] = $rate;
        }

        return new JsonResponse($rates);
    }
}
