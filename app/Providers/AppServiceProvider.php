<?php

namespace App\Providers;

use App\Helpers\PermissionHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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
    }
}
