<?php

namespace MigrateToFlarum\Redirects;

use Illuminate\Support\Arr;
use Psr\Http\Message\UriInterface;

class Rule
{
    protected $if;
    protected $uri;
    protected $matches = [];

    const REGEX_DELIMITER = '~';

    public function __construct(string $if, UriInterface $uri)
    {
        if (!starts_with($if, ['/', 'http://', 'https://', '//'])) {
            $if = '//' . $if;
        }

        $this->if = parse_url($if);
        $this->uri = $uri;
    }

    protected function createWildcardRegex(string $pattern, string $replace = '([a-zA-Z0-9_-]+)'): string
    {
        return self::REGEX_DELIMITER . '^' . implode($replace, array_map(function ($part) {
                return preg_quote($part, self::REGEX_DELIMITER);
            }, explode('*', $pattern))) . '$' . self::REGEX_DELIMITER;
    }

    protected function recordMatches($matches)
    {
        // Skip the first one which will contain the full subject
        for ($i = 1; $i < count($matches); $i++) {
            $this->matches[] = $matches[$i];
        }
    }

    protected function matchesScheme(): bool
    {
        return !Arr::has($this->if, 'scheme') || Arr::get($this->if, 'scheme') === $this->uri->getScheme();
    }

    protected function matchesHost(): bool
    {
        if (!Arr::has($this->if, 'host')) {
            return true;
        }

        $result = preg_match(
            $this->createWildcardRegex(Arr::get($this->if, 'host')),
            $this->uri->getHost(),
            $matches
        );

        if ($result !== 1) {
            return false;
        }

        $this->recordMatches($matches);

        return true;
    }

    protected function matchesPort(): bool
    {
        return !Arr::has($this->if, 'port') || Arr::get($this->if, 'port') === $this->uri->getPort();
    }

    protected function matchesPath(): bool
    {
        if (!Arr::has($this->if, 'path')) {
            return true;
        }

        $result = preg_match(
            $this->createWildcardRegex(Arr::get($this->if, 'path')),
            $this->uri->getPath(),
            $matches
        );

        if ($result !== 1) {
            return false;
        }

        $this->recordMatches($matches);

        return true;
    }

    protected function matchesQuery(): bool
    {
        if (!Arr::has($this->if, 'query')) {
            return true;
        }

        parse_str(Arr::get($this->if, 'query'), $ifQuery);

        // If there's a query in the rule but none in the current request
        // we fail the rule immediately to prevent unneeded parsing and looping
        if (!$this->uri->getQuery()) {
            return false;
        }

        parse_str($this->uri->getQuery(), $uriQuery);

        // This does not currently support array parameters
        foreach ($ifQuery as $name => $value) {
            $compareTo = Arr::get($uriQuery, $name);

            if (!is_string($value) || !is_string($compareTo)) {
                return false;
            }

            $result = preg_match(
                $this->createWildcardRegex($value),
                $compareTo,
                $matches
            );

            if ($result !== 1) {
                return false;
            }

            $this->recordMatches($matches);
        }

        return true;
    }

    public function matches(): bool
    {
        if ($this->if === false) {
            return false;
        }

        return $this->matchesScheme()
            && $this->matchesHost()
            && $this->matchesPort()
            && $this->matchesPath()
            && $this->matchesQuery();
    }

    public function getValues(): array
    {
        return $this->matches;
    }

    public function substituteUrl(string $url): string
    {
        $search = [
            '$scheme',
            '$host',
            '$port',
            '$path',
            '$query',
        ];

        $replace = [
            $this->uri->getScheme(),
            $this->uri->getHost(),
            $this->uri->getPort(),
            $this->uri->getPath(),
            $this->uri->getQuery(),
        ];

        foreach ($this->getValues() as $index => $value) {
            $search[] = '$' . ($index + 1);
            $replace[] = $value;
        }

        return str_replace($search, $replace, $url);
    }
}
