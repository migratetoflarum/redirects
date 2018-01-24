<?php

namespace MigrateToFlarum\Redirects\Listeners;

use Flarum\Event\SettingWasSet;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher;
use MigrateToFlarum\Redirects\Middlewares\RedirectMiddleware;

class ClearCache
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(SettingWasSet::class, [$this, 'settingWasSet']);
    }

    public function settingWasSet(SettingWasSet $event)
    {
        if ($event->key === 'migratetoflarum-redirects.rules') {
            /**
             * @var $cache Store
             */
            $cache = app(Store::class);

            $cache->forget(RedirectMiddleware::CACHE_KEY_ACTIVE);
            $cache->forget(RedirectMiddleware::CACHE_KEY_PASSIVE);
        }
    }
}
