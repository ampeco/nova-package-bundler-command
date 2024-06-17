<?php

namespace Fidum\NovaPackageBundler\Http\Middleware;

use Closure;
use Fidum\NovaPackageBundler\Contracts\Services\BundlerInterface;
use Fidum\NovaPackageBundler\Contracts\Services\ScriptAssetService;
use Fidum\NovaPackageBundler\Contracts\Services\StyleAssetService;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;

class OverrideNovaPackagesMiddleware
{
    public function __construct(
        protected ScriptAssetService $scriptAssetService,
        protected StyleAssetService $styleAssetService,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (!$this->isEnabled()) {
            return $next($request);
        }

        Nova::$scripts = $this->scriptAssetService->excluded()->toArray();
        Nova::$styles = $this->styleAssetService->excluded()->toArray();

        Nova::remoteScript(mix($this->scriptAssetService->outputPath()));
        Nova::remoteStyle(mix($this->styleAssetService->outputPath()));

        return $next($request);
    }

    private function isEnabled()
    {
        $enabled = config('nova-package-bundler-command.enabled', true);

        if (is_bool($enabled) && $enabled) {
            return true;
        }

        $classImplementsInterface = in_array(BundlerInterface::class, class_implements($enabled));

        if ($classImplementsInterface && app($enabled)->isEnabled()) {
            return true;
        }

        return false;
    }
}
