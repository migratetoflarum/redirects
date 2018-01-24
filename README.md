# Redirects extension by MigrateToFlarum

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/migratetoflarum/redirects/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/migratetoflarum/redirects.svg)](https://packagist.org/packages/migratetoflarum/redirects) [![Total Downloads](https://img.shields.io/packagist/dt/migratetoflarum/redirects.svg)](https://packagist.org/packages/migratetoflarum/redirects) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

This extension allows you to configure redirects to and from your Flarum. More settings are coming soon.

## Installation

**This extension requires PHP7 or above**

Use [Bazaar](https://discuss.flarum.org/d/5151-flagrow-bazaar-the-extension-marketplace) or install manually:

```bash
composer require migratetoflarum/redirects
```

## Updating

```bash
composer update migratetoflarum/redirects
php flarum cache:clear
```

## Documentation

You can configure rules that trigger a redirect when they match the current request.
A rule has the following settings:

| Name | Default value | Description |
| --- | --- | --- | --- |
| Condition | | A pattern that will be matched against the current request. You can specify a full URI or just part of it. You can include the protocol, hostname, port, path and query string. You can insert a wildcard `*` to replace a part of a subdomain, path or query string. The wildcard doesn't match `.` or `/` so it can't match a full domain name or full path.  Query strings are matched separately, even if they are in a different order in the actual request. Query parameters present in the request but not in the condition are ignored and don't make the rule fail. If you don't specify a host, it's better to always start the path with `/` to prevent the rule from confusing host and path. |
| Redirect To | | The url to redirect to. The value matched by wildcards in **Condition** can be used as `$1`, `$2` and so on. Special values `$scheme`, `$host` and `$path` will be replaced with the values extracted from the original request (`$scheme` is `http` or `https` and `$path` includes the starting `/`) |
| Override Flarum | No | By default Rules are only run when Flarum encounters a 404 error to prevent slowing down the forum when rendering non-ruled routes. But if your rule also matches a route defined by Flarum or an extension, you'll need to enable this option to run the rule on every request. Be careful as you can lock yourself out of the forum if you match login or administrative routes ! |
| Redirect is external | No | By default the **Redirect To** values gets the Flarum base url prepended to it. It's a safety feature that can prevent some unwanted redirects. But if you're using a different hostname on purpose as a redirect, you need to enable this option so your input is used as-it as the `Location` value for the redirect |
| Redirect Type | 301 | Choose the HTTP status to send along with the redirect. `301` indicates a permanent redirect and will be cached by browsers and search engines. `302` is a temporary redirect that won't be cached by browsers and usually has no meaning for search engines. Using 302 while testing your settings is easier as you won't have to clear your browser cache if something goes wrong. |
| Enable rule | | As the name suggests, this allows you to activate or disable a rule. A rule can only be activated once you set a **Condition** and a **Redirect To** value. |

Please note that as Flarum beta7, any rule starting with `/admin` will only work for users with administrative privileges, because the redirect script can't run before the auth check there.

You can only redirect urls for domains that resolve to your Flarum installation.
If you'd like to manage redirects from other domains, check your hosting dashboard or webserver documentation on how to add aliases.

## Caching

The list of rules is saved to the cache so no additional database query is needed while running.

This list is cleared every time you edit the rules in the admin panel. If rules are not updating, you can try running `php flarum cache:clear` to clear the whole cache.

## Examples

> Condition: `example.com`

Matches `https://example.com/`, `https://example.com/tags`, `http://example.com/?forum=1`

Doesn't match `https://forum.example.com/`, `https://example.net/`

> Condition: `https://*.example.com`

Matches `https://forum.example.com/`, `https://www.example.com/forum`

Doesn't match `https://example.com/`, `http://test.example.com/`

> Condition: `example.com/`

Matches `https://example.com/`

Doesn't match `https://example.com/tags`

> Condition: `/forum`

Matches `https://www.example.com/forum`, `https://example.net/forum`

Doesn't match `https://example.com/admin`

## Real life examples

### Redirect your forum to HTTPS

You need to enable **Override Flarum** for this rule.

- Condition: `http://example.com`
- Redirect To: `$path` (or `https://$host$path` with **Redirect is external** enabled to stay on the subdomain even if it is different than the one in Flarum config)

### Redirect from www. to apex

You need to enable **Override Flarum** for this rule.

- Condition: `www.example.com`
- Redirect To: `$path` (or `$scheme://exemple.com$path` with **Redirect is external** enabled to redirect to a domain different from the one in Flarum config)

### Redirect an old forum homepage to Flarum

- Condition: `/forum`
- Redirect To: `/`

<!-- -->

- Condition: `/index.php`
- Redirect To: `/`

### Redirect discussions from an old forum

- Condition: `/index.php?discussion=*`
- Redirect To: `/d/$1`

### Redirect unknown discussions to homepage

- Condition: `/d/*` (with **Override Flarum** disabled, otherwise any discussion will be redirected)
- Redirect To: `/`

### Rickroll every admin (including you)

You need to enable **Override Flarum** and **Redirect is external** for this rule.

- Condition: `/admin`
- Redirect To: `https://www.youtube.com/watch?v=dQw4w9WgXcQ`

PS: If you have not guessed it already, this will lock you out of the admin panel.
You'll need to edit/delete the `migratetoflarum-redirects.rules` setting in your Flarum database to restore access to the admin panel.

## A MigrateToFlarum extension

This is a free extension by MigrateToFlarum, an online forum migration tool (launching soon).
Follow us on Twitter for updates https://twitter.com/MigrateToFlarum
