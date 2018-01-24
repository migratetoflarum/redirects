<?php

namespace MigrateToFlarum\Redirects;

use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events) {
    $events->subscribe(Listeners\AddClientAssets::class);
    $events->subscribe(Listeners\AddMiddlewares::class);
    $events->subscribe(Listeners\ClearCache::class);
};
