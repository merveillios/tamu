<?php

namespace App\Http\Requests;

/**
 * Validasi perubahan akun staf; password boleh dikosongkan bila tidak diganti (USR-1).
 */
class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Password opsional; bila diisi berarti reset password oleh Super Admin.
     *
     * @return array<int, string>
     */
    protected function passwordRules(): array
    {
        return ['nullable', 'string', 'min:8', 'max:255', 'confirmed'];
    }
}
