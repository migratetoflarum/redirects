<?php

namespace MigrateToFlarum\Redirects\Middlewares;

use Exception;
use Flarum\Forum\UrlGenerator;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use MigrateToFlarum\Redirects\Rule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class RedirectMiddleware implements MiddlewareInterface
{
    protected $cache;

    const CACHE_KEY_ACTIVE = 'migratetoflarum-redirects-rules-active';
    const CACHE_KEY_PASSIVE = 'migratetoflarum-redirects-rules-passive';

    const DEFAULT_ENABLED = false;
    const DEFAULT_ACTIVE = false;
    const DEFAULT_TYPE = 301;
    const DEFAULT_EXTERNAL = false;

    public function __construct(Store $cache)
    {
        $this->cache = $cache;
    }

    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        foreach ($this->getActiveRules() as $rule) {
            $check = new Rule(Arr::get($rule, 'condition', '/'), $request->getUri());

            if ($check->matches()) {
                return $this->redirectResponse(
                    $response,
                    $check,
                    $rule
                );
            }
        }

        try {
            $response = $out($request, $response);
        } catch (Exception $exception) {
            if (
                ($exception instanceof RouteNotFoundException)
                || ($exception instanceof ModelNotFoundException)
            ) {
                foreach ($this->getPassiveRules() as $rule) {
                    $check = new Rule(Arr::get($rule, 'condition', '/'), $request->getUri());

                    if ($check->matches()) {
                        return $this->redirectResponse(
                            $response,
                            $check,
                            $rule
                        );
                    }
                }
            }

            throw $exception;
        }

        return $response;
    }

    protected function redirectResponse(ResponseInterface $response, Rule $rule, array $settings): ResponseInterface
    {
        $url = $rule->substituteUrl(Arr::get($settings, 'redirect', '/'));

        if (!Arr::get($settings, 'external', self::DEFAULT_EXTERNAL)) {
            /**
             * @var $generator UrlGenerator
             */
            $generator = app(UrlGenerator::class);

            // Remove starting slash if it exists in the url because one will be added by toPath()
            $url = $generator->toPath(starts_with($url, '/') ? substr($url, 1) : $url);
        }

        // Use '' for default reason phrase as null isn't a valid default for the underlying PSR implementation
        return $response->withStatus(Arr::get($settings, 'type', self::DEFAULT_TYPE), '')->withHeader('Location', $url);
    }

    protected function getRules(bool $active): array
    {
        /**
         * @var $settings SettingsRepositoryInterface
         */
        $settings = app(SettingsRepositoryInterface::class);

        $rules = json_decode($settings->get('migratetoflarum-redirects.rules'), true);

        if (is_null($rules)) {
            $rules = [];
        }

        return array_filter($rules, function ($rule) use ($active) {
            return Arr::get($rule, 'enabled', self::DEFAULT_ENABLED)
                && Arr::get($rule, 'active', self::DEFAULT_ACTIVE) === $active;
        });
    }

    protected function getActiveRules(): array
    {
        $rules = $this->cache->get(self::CACHE_KEY_ACTIVE);

        if ($rules) {
            return $rules;
        }

        $rules = $this->getRules(true);

        $this->cache->forever(self::CACHE_KEY_ACTIVE, $rules);

        return $rules;
    }

    protected function getPassiveRules(): array
    {
        $rules = $this->cache->get(self::CACHE_KEY_PASSIVE);

        if ($rules) {
            return $rules;
        }

        $rules = $this->getRules(false);

        $this->cache->forever(self::CACHE_KEY_PASSIVE, $rules);

        return $rules;
    }
}
