<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    private ?string $submittedEmail = null;

    public function authorize(): bool
    {
        return true;
    }

    public function submittedEmail(): ?string
    {
        return $this->submittedEmail;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->submittedEmail = trim($email);
            $this->merge(['email' => Str::lower($this->submittedEmail)]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
