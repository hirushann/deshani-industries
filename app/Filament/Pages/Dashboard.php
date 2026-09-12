<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'My Dashboard';

    protected static ?string $title = 'My Dashboard';

    protected static string $routePath = '/';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole(['Manager', 'manager'])) {
            return false;
        }

        return true;
    }

    public function mountCanAuthorizeAccess(): void
    {
        if (! static::canAccess()) {
            $firstAccessibleUrl = null;

            foreach (filament()->getNavigation() as $group) {
                foreach ($group->getItems() as $item) {
                    $url = $item->getUrl();
                    if ($url && $url !== url()->current() && $url !== filament()->getUrl()) {
                        $firstAccessibleUrl = $url;
                        break 2;
                    }
                }
            }

            if ($firstAccessibleUrl) {
                redirect()->to($firstAccessibleUrl);
                return;
            }

            abort(403);
        }
    }
}
