<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FuturasActivasScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder
            ->where('fecha_sesion', '>=', now()->subDays(7)->toDateString())
            ->whereNotIn('estatus_sesion', ['CANCELADA', 'CANCELADA_POR_TORNEO']);
    }
}
