#Installation
1. Clone the repository:
-git clone <repository_url>

2.Navigate to the project directory:
-cd currency-exchange-api

3.Install the dependencies:
-composer install

4.Configure the environment variables:
-Copy the .env.example file to .env and update the necessary configuration values such as database credentials, Redis connection details, and the Open Exchange Rates API key.

5.Set up the database:
-php bin/console doctrine:database:create
-php bin/console doctrine:migrations:migrate

6.Start the Redis server:
-redis-server

7.Start the Symfony development server:
symfony serve
The API will be accessible at http://localhost:8000.

#Usage
-Fetching Currency Exchange Rates
-To fetch the currency exchange rates and store them in the database, run the following command:

-php bin/console app:currency:rates [base_currency] [target_currency_1] [target_currency_2] ... [target_currency_n]
-Replace [base_currency] with the base currency (e.g., EUR) and [target_currency_x] with the target currencies (e.g., USD, GBP). This command will make a request to the Open Exchange Rates API, save the rates in the database, and cache them in Redis.

8.Retrieving Currency Exchange Rates
-To retrieve the exchange rates for a set of currencies, send a GET request to the following endpoint:

-GET /api/exchange-rates?base_currency=[base_currency]&target_currencies=[target_currency_1,target_currency_2,...,target_currency_n]
Replace [base_currency] with the base currency and [target_currency_x] with the target currencies. The API will first check Redis for the rates and if not found, fetch them from the database, store them in Redis, and return the rates.

Testing
To run the unit tests, execute the following command:

bash
Copy code
php bin/phpunit
Contributing
Contributions are welcome! If you find any issues or have suggestions for improvements, please open an issue or submit a pull request.

License
This project is licensed under the MIT License.

Feel free to update the README with additional information specific to your project.




