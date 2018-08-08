<?php

namespace Productsup\Command;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function GuzzleHttp\Promise\coroutine;
use function GuzzleHttp\Promise\each_limit;
use function Productsup\array_get;

class ImportCoroutineCommand extends Command
{
    const JSONWHOIS_API_KEY = '__INSERT__KEY__HERE__';

    /**
     * @var Client
     */
    private $client;

    protected function configure()
    {
        $this
            ->setName('import-coroutine')
            ->setDescription('')
        ;

        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'PUP browser 0.1',
            ]
        ]);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urls = [
            'http://yandex.ru',
            'http://google.com',
            'http://non-existing-domain.com',
            'http://duckduckgo.com',
            'http://productsup.com',
            'http://zalando.de',
            'http://amazon.de',
            'http://idealo.de',
        ];

        $queryGenerator = function ($urls) {
            foreach ($urls as $url) {
                yield coroutine(function () use ($url) {
                    $pageInfo = yield from $this->getPageInfo($url);

                    try {
                        $geoInfo = yield from $this->getGeoInfo($pageInfo['ip_address']);
                    } catch (Exception $exception) {
                        // Just skip geo info if it's not available
                    }

                    yield array_merge($pageInfo, $geoInfo);
                });
            }
        };

        each_limit(
            $queryGenerator($urls),
            3,
            function ($data) use ($output) {
                $output->writeln(<<<INFO
<info>${data['url']}</info> (<comment>${data['ip_address']}, ${data['country_code']}, ${data['country_name']}</comment>) title: 
<comment>${data['title']}</comment>
INFO
                );
            },
            function ($error) use ($output) {
                $message = $error instanceof Exception ? $error->getMessage() : '';

                $output->writeln(<<<ERROR
Error getting the site info! <error>$message</error>
ERROR
                );
            }
        )->wait();
    }

    private function getPageInfo($url)
    {
        $data = new \ArrayObject();
        $data['url'] = $url;

        $response = yield $this->client->getAsync($url, [
            'on_stats' => function (TransferStats $stats) use ($data) {
                $data['ip_address'] = $stats->getHandlerStats()['primary_ip'];

                return null;
            }
        ]);

        $body = $response->getBody()->getContents();

        preg_match_all('#<title>(.*)</title>#mi', $body, $matches);

        $data['title'] = $matches[1][0];

        return $data->getArrayCopy();
    }

    private function getGeoInfo($ip)
    {
        $response = yield $this->client->getAsync('https://api.jsonwhois.io/geo', [
            'query' => [
                'key' => self::JSONWHOIS_API_KEY,
                'ip_address' => $ip,
            ]
        ]);

        $body = $response->getBody()->getContents();

        $serviceData = json_decode($body, true);

        return array_get($serviceData, 'country_code', 'country_name', 'city');
    }
}
