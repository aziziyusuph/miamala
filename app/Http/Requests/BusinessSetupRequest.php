<?php

namespace App\Http\Requests;

use App\Http\Middleware\EnsureBusinessSetupAccess;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class BusinessSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $userId = $this->session()->get(EnsureBusinessSetupAccess::SESSION_KEY);
        $user = is_numeric($userId) ? User::query()->find($userId) : null;

        return $user !== null
            && $user->hasVerifiedEmail()
            && $user->business_id === null;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
        ];
    }
}
