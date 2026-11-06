<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Root-Template, das beim ersten Seitenaufruf geladen wird.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Ermittelt die aktuelle Asset-Version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props, die standardmäßig an alle Seiten übergeben werden.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Kurzmeldungen nach Aktionen (z. B. „Gespeichert“)
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
