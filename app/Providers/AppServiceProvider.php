<?php

namespace App\Providers;

use App\Helpers\PermissionHelper;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Blade::directive('canPermission', function (string $expression) {
            $params = explode(',', $expression);
            $model = trim($params[0] ?? "''");
            $action = trim($params[1] ?? "'view'");
            return "<?php if(auth()->check() && \App\Helpers\PermissionHelper::can(auth()->user(), {$model}, {$action})): ?>";
        });

        Blade::directive('endcanPermission', function () {
            return '<?php endif; ?>';
        });

        $this->registerAuditListeners();
    }

    protected function registerAuditListeners(): void
    {
        Event::listen('eloquent.created: *', function (string $event, array $payload) {
            AuditLogger::log($payload[0], 'created');
        });

        Event::listen('eloquent.updated: *', function (string $event, array $payload) {
            AuditLogger::log($payload[0], 'updated');
        });

        Event::listen('eloquent.deleted: *', function (string $event, array $payload) {
            AuditLogger::log($payload[0], 'deleted');
        });

        Event::listen('eloquent.restored: *', function (string $event, array $payload) {
            AuditLogger::log($payload[0], 'restored');
        });
    }
}
