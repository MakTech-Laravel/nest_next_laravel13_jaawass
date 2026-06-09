<?php

namespace App\Actions\Api\V1\Auth;

use App\Models\User;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class DeletePassportTokensForUserAction
{
    public function handle(User $user): void
    {
        $tokenIds = Token::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        if (count($tokenIds) > 0) {
            RefreshToken::query()
                ->whereIn('access_token_id', $tokenIds)
                ->delete();
        }

        Token::query()->where('user_id', $user->id)->delete();
    }
}
