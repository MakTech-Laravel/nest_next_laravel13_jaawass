<?php

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use App\Enums\MailTemplate;
use App\Services\Mailing\MailingService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        protected MailingService $mailingService,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'terms_condition' => ['required', 'accepted'],
        ])->validate();

        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => UserRole::BUYER->value,
            'agreed_to_terms' => true,
        ]);

        $this->mailingService->send($user->email, MailTemplate::Welcome, [
            'firstName' => trim($user->first_name) !== '' ? trim($user->first_name) : 'there',
        ]);

        return $user;
    }
}
