<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Throwable;

/**
 * The optional management UI. Lists discovered extensions and drives the lifecycle
 * (enable / disable / install / remove). The extension id is posted in the body
 * because ids may contain slashes (`vendor/name`) and can't be a path segment.
 */
final readonly class ExtensionController
{
    public function __construct(private ExtensionManager $extensions) {}

    public function index(): View
    {
        return view('package-management::extensions.index', [
            'extensions' => $this->extensions->all(),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        return $this->act($request, fn (string $id) => $this->extensions->enable($id), 'Enabled');
    }

    public function disable(Request $request): RedirectResponse
    {
        return $this->act($request, fn (string $id) => $this->extensions->disable($id), 'Disabled');
    }

    public function install(Request $request): RedirectResponse
    {
        return $this->act($request, fn (string $id) => $this->extensions->install($id), 'Installed');
    }

    public function remove(Request $request): RedirectResponse
    {
        return $this->act($request, fn (string $id) => $this->extensions->remove($id), 'Removed');
    }

    private function act(Request $request, callable $action, string $label): RedirectResponse
    {
        $id = $request->string('id')->toString();

        try {
            $action($id);
            $status = "{$label} [{$id}].";
        } catch (Throwable $e) {
            $status = $e->getMessage();
        }

        return back()->with('status', $status);
    }
}
