<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Savia\NubeFactIntegration\Keywords\Action;

use function array_merge;
use function json_encode;
use function sprintf;

final class GuzzleNubeFactService implements NubeFactService
{
    private const RootURI = '';

    private static GuzzleClient $client;

    public function __construct(string $baseURI, string $apiToken)
    {
        self::$client = self::createClient($baseURI, $apiToken);
    }

    public function call(Action $action, array $data): ResponseInterface
    {
        return $this->client()->post(
            self::RootURI,
            ['body' => json_encode(self::request($action, with: $data))]
        );
    }

    private static function client(): GuzzleClient
    {
        return self::$client;
    }

    private static function createClient(string $baseURI, string $apiToken): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $baseURI,
            'handler'  => HandlerStack::create(),
            'headers'  => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf("Token token=%s", $apiToken),
            ],
        ]);
    }

    private static function request(Action $action, array $with): array
    {
        return array_merge(['operacion' => $action->value], $with);
    }
}
