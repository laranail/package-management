<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Simtabi\Laranail\Package\Management\ExtensionManager;
use Simtabi\Laranail\Package\Management\Installer\ExtensionInstaller;
use Simtabi\Laranail\Package\Management\Installer\RepositoryRef;
use Throwable;

/**
 * The optional management UI. Lists discovered extensions and drives the lifecycle
 * (enable / disable / install / update / remove) plus VCS install. The extension id is
 * posted in the body because ids may contain slashes (`vendor/name`) and can't be a path
 * segment.
 */
final readonly class ExtensionController
{
    public function __construct(
        private ExtensionManager $extensions,
        private ExtensionInstaller $installer,
    ) {}

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

    public function update(Request $request): RedirectResponse
    {
        return $this->act($request, fn (string $id) => $this->extensions->update($id), 'Updated');
    }

    public function remove(Request $request): RedirectResponse
    {
        return $this->act($request, fn (string $id) => $this->extensions->remove($id), 'Removed');
    }

    /** Install an extension from a VCS repository (the web equivalent of the install-from command). */
    public function installFrom(Request $request): RedirectResponse
    {
        $url = $request->string('url')->toString();
        $ref = $request->string('ref')->toString() ?: null;
        $as = $request->string('as')->toString() ?: null;
        $token = $request->string('token')->toString() ?: null;
        $default = (string) config('laranail.package-management.installer.default_provider', 'github');

        try {
            if ($as !== null && ! in_array($as, ['package', 'module', 'plugin'], true)) {
                throw new InvalidArgumentException('Role must be one of: package, module, plugin.');
            }

            $extension = $this->installer->install(
                RepositoryRef::parse($url, $ref, $token, $default),
                $as,
                $request->boolean('force'),
            );
            $status = "Installed [{$extension->id}] as a {$extension->role}.";
        } catch (Throwable $e) {
            $status = $e->getMessage();
        }

        return back()->with('status', $status);
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
