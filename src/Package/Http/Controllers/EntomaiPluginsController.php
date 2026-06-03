<?php

namespace Botble\CaptchaPro\Package\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\CaptchaPro\Package\PrivateUpdater\PluginUpdateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Throwable;

class EntomaiPluginsController extends BaseController
{
    public function index()
    {
        $this->pageTitle('Entomai Plugins');

        $registry = new PluginUpdateRegistry;
        $allPlugins = $registry->plugins();

        $cards = $allPlugins->map(function (array $plugin): array {
            $isFree = (bool) ($plugin['is_free'] ?? false);
            $prefix = $plugin['license_prefix'];
            $slug = str_replace('_', '-', $prefix);

            // Free plugins have no license, no activation routes, no update server
            if ($isFree) {
                return array_merge($plugin, [
                    'activated' => false,
                    'client_email' => '',
                    'masked_license_code' => '',
                    'has_update' => false,
                    'update_version' => '',
                    'update_id' => '',
                    'last_checked_at' => '',
                    'routes' => [],
                ]);
            }

            $activated = filled($plugin['license_data']);
            $licenseCode = (string) setting("{$prefix}_client_license_code", '');

            $hasUpdate = (bool) filter_var(
                setting("{$prefix}_last_update_available", false),
                FILTER_VALIDATE_BOOLEAN
            );
            $updateVersion = (string) setting("{$prefix}_last_update_version", '');
            $updateId = (string) setting("{$prefix}_last_update_id", '');
            $lastCheckedAt = (string) setting("{$prefix}_last_update_checked_at", '');

            if ($hasUpdate && $updateVersion) {
                $hasUpdate = version_compare(
                    ltrim(trim($updateVersion), 'vV'),
                    ltrim(trim($plugin['version']), 'vV'),
                    '>'
                );
            }

            $routePrefix = "{$slug}.client-activation";
            $routes = [];

            foreach (['start', 'deactivate', 'force-deactivate', 'verify', 'check-update'] as $action) {
                $name = "{$routePrefix}.{$action}";
                $routes[$action] = Route::has($name) ? route($name) : null;
            }

            if (Route::has('entomai.private-updater.update')) {
                $routes['install'] = route('entomai.private-updater.update', ['plugin' => $plugin['path']]);
            }

            return array_merge($plugin, [
                'activated' => $activated,
                'client_email' => (string) setting("{$prefix}_client_email", ''),
                'masked_license_code' => $this->maskLicenseCode($licenseCode),
                'has_update' => $hasUpdate,
                'update_version' => $updateVersion,
                'update_id' => $updateId,
                'last_checked_at' => $lastCheckedAt,
                'routes' => $routes,
            ]);
        })->values()->all();

        $hasCatalogServer = $allPlugins->contains(
            fn (array $p): bool => filled($p['server'] ?? '') && filled($p['api_key'] ?? '')
        );

        return view()->file(
            __DIR__.'/../../resources/views/plugins/index.blade.php',
            [
                'cards' => $cards,
                'catalogProxyUrl' => $hasCatalogServer && Route::has('entomai.plugins.catalog')
                    ? route('entomai.plugins.catalog')
                    : null,
            ]
        );
    }

    public function catalog(Request $request): JsonResponse
    {
        $registry = new PluginUpdateRegistry;

        $plugin = $registry->plugins()
            ->first(fn (array $p): bool => filled($p['server'] ?? '') && filled($p['api_key'] ?? ''));

        if (! $plugin) {
            return response()->json(['status' => false, 'data' => [], 'message' => 'No catalog server configured.'], 404);
        }

        try {
            $url = rtrim($plugin['server'], '/').'/api/external/catalog';
            $query = array_filter(['featured' => $request->query('featured')]);

            $response = Http::withHeaders([
                'X-API-KEY' => $plugin['api_key'],
                'X-API-URL' => $plugin['activation_url'] ?: url('/'),
                'X-API-IP' => $request->ip() ?: '127.0.0.1',
                'X-API-LANGUAGE' => app()->getLocale(),
            ])
                ->acceptJson()
                ->withoutVerifying()
                ->timeout(15)
                ->get($url, $query);

            return response()->json($response->json() ?: ['status' => false, 'data' => []], $response->status());
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'data' => [], 'message' => $e->getMessage()], 500);
        }
    }

    protected function maskLicenseCode(string $code): string
    {
        if (strlen($code) <= 4) {
            return $code;
        }

        return str_repeat('*', max(strlen($code) - 4, 4)).substr($code, -4);
    }
}
