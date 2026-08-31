<?php

namespace App\Policies;

use App\Models\TradeBody;
use App\Models\User;

class TradeBodyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_trade_bodies');
    }

    public function view(User $user, TradeBody $tradeBody): bool
    {
        return $user->can('view_trade_bodies');
    }

    public function create(User $user): bool
    {
        return $user->can('create_trade_bodies');
    }

    public function update(User $user, TradeBody $tradeBody): bool
    {
        return $user->can('edit_trade_bodies');
    }

    public function delete(User $user, TradeBody $tradeBody): bool
    {
        return $user->can('delete_trade_bodies');
    }

    public function forceDelete(User $user, TradeBody $tradeBody): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_trade_bodies');
    }

    public function restore(User $user, TradeBody $tradeBody): bool
    {
        return $user->can('edit_trade_bodies');
    }
}
