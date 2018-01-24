<?php

namespace MigrateToFlarum\Redirects\Tests;

use MigrateToFlarum\Redirects\Rule;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Uri;

class RuleTest extends TestCase
{
    protected function createRule(string $rule, string $uri)
    {
        return new Rule($rule, new Uri($uri));
    }

    protected function assertRuleMatches(string $rule, string $uri)
    {
        $test = $this->createRule($rule, $uri);

        $this->assertTrue($test->matches(), "$rule should match $uri");
    }

    protected function assertRuleDoesntMatch(string $rule, string $uri)
    {
        $test = $this->createRule($rule, $uri);

        $this->assertFalse($test->matches(), "$rule should not match $uri");
    }

    public function test_simple_host()
    {
        $this->assertRuleMatches('example.com', 'https://example.com/');
        $this->assertRuleMatches('example.com', 'http://example.com/');
        $this->assertRuleDoesntMatch('example.com', 'https://test.example.com/');

        // The path shouldn't matter unless it is part of the rule
        $this->assertRuleMatches('example.com', 'https://example.com/hello');
        $this->assertRuleDoesntMatch('example.com/', 'https://example.com/hello');
    }

    public function test_simple_scheme()
    {
        $this->assertRuleMatches('https://example.com', 'https://example.com/');
        $this->assertRuleDoesntMatch('https://example.com', 'http://www.example.com/');
    }

    public function test_simple_port()
    {
        $this->assertRuleMatches('example.com:8080', 'http://example.com:8080/');
        $this->assertRuleDoesntMatch('example.com:8080', 'http://example.com:8888/');
    }

    public function test_simple_path()
    {
        $this->assertRuleMatches('/test', 'https://example.com/test');
        $this->assertRuleMatches('example.com/test', 'https://example.com/test');
        $this->assertRuleMatches('https://example.com/test', 'https://example.com/test');
    }

    public function test_simple_query()
    {
        $this->assertRuleMatches('/?test=1', 'https://example.com/?test=1');
        $this->assertRuleMatches('/?test=1', 'https://example.com/?test=1&hello=2');
        $this->assertRuleMatches('/?test=1', 'https://example.com/?hello=2&test=1');
        $this->assertRuleMatches('/?test=1&hello=2', 'https://example.com/?test=1&hello=2');
        $this->assertRuleDoesntMatch('/?test=1', 'https://example.com/');
        $this->assertRuleDoesntMatch('/?test=1', 'https://example.com/?test=12');
        $this->assertRuleDoesntMatch('/?test=1&hello=2', 'https://example.com/?test=1');
    }

    public function test_wildcard_host()
    {
        $this->assertRuleMatches('*.example.com', 'https://test.example.com/');
        $this->assertRuleMatches('*.example.com', 'https://test-domain.example.com/');
        $this->assertRuleDoesntMatch('*.example.com', 'https://example.com/');
        $this->assertRuleDoesntMatch('*.example.com', 'https://test.test.example.com/');

        $this->assertRuleMatches('*.*.example.com', 'https://test.test.example.com/');
        $this->assertRuleMatches('https://*.*/test', 'https://example.localhost/test');
    }

    public function test_wildcard_path()
    {
        $this->assertRuleMatches('example.com/*', 'https://example.com/hello');
        $this->assertRuleDoesntMatch('example.com/*', 'https://example.com/hello/test');
        $this->assertRuleMatches('example.com/*/*', 'https://example.com/hello/test');

        $this->assertRuleMatches('example.com/*/other', 'https://example.com/test/other');
        $this->assertRuleMatches('example.com/test*/other', 'https://example.com/test2/other');
        $this->assertRuleDoesntMatch('example.com/test*/other', 'https://example.com/test/other');
    }

    public function test_wildcard_query()
    {
        $this->assertRuleMatches('/?test=*', 'https://example.com/?test=1');
        $this->assertRuleMatches('/?test=*', 'https://example.com/?test=2');
        $this->assertRuleMatches('/?test=hello:*', 'https://example.com/?test=hello:2');
        $this->assertRuleMatches('/?action=1&test=*', 'https://example.com/?action=1&test=2');
    }

    public function test_matches()
    {
        $rule = $this->createRule('example.com', 'https://example.com/');
        $rule->matches();
        $this->assertCount(0, $rule->getValues());

        $rule = $this->createRule('*.example.com', 'https://test.example.com/');
        $rule->matches();
        $this->assertCount(1, $rule->getValues());
        $this->assertEquals('test', $rule->getValues()[0]);

        $rule = $this->createRule('*.example.com/discussion/*', 'https://test.example.com/discussion/20');
        $rule->matches();
        $this->assertCount(2, $rule->getValues());
        $this->assertEquals('test', $rule->getValues()[0]);
        $this->assertEquals('20', $rule->getValues()[1]);

        $rule = $this->createRule('/?action=*&test=*', 'https://example.com/?action=view&test=hello');
        $rule->matches();
        $this->assertCount(2, $rule->getValues());
        $this->assertEquals('view', $rule->getValues()[0]);
        $this->assertEquals('hello', $rule->getValues()[1]);

        // Values should be returned in the order of the rule, whatever order they have in the actual url
        $rule = $this->createRule('/?action=*&test=*', 'https://example.com/?test=hello&action=view');
        $rule->matches();
        $this->assertCount(2, $rule->getValues());
        $this->assertEquals('view', $rule->getValues()[0]);
        $this->assertEquals('hello', $rule->getValues()[1]);
    }

    public function test_substitute()
    {
        $rule = $this->createRule('/?action=*&test=*', 'https://example.com/?action=view&test=hello');
        $rule->matches();
        $this->assertEquals('https://example.com/actions/view?test=hello', $rule->substituteUrl('https://example.com/actions/$1?test=$2'));

        $rule = $this->createRule('*.example.com', 'https://test.example.com/hello');
        $rule->matches();
        $this->assertEquals('https://example.com/domain/test', $rule->substituteUrl('https://example.com/domain/$1'));

        // Using output in different order
        $rule = $this->createRule('/*/*', 'https://example.com/1/2');
        $rule->matches();
        $this->assertEquals('https://example.com/2/1', $rule->substituteUrl('https://example.com/$2/$1'));

    }
}
