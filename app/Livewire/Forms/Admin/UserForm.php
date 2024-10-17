<?php

namespace App\Livewire\Forms\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Spatie\Permission\Models\Role;

class UserForm extends Form
{
    public ?User $user;
    #[Locked]
    public ?int $id = null;
    public $name = '';
    public $email = '';
    public ?string $password = '';
    public ?string $confirm_password = '';
    public $selected_roles = [];
    public function mount()
    {
        $this->user = new User();
    }
    public function getUser()
    {
        return $this->user;
    }
    public function getRolesIds()
    {
        return $this->user->roles()->pluck('id')->toArray();
    }
    public function rules()
    {
        $passwordRule = $this->id ? 'sometimes|nullable|min:6' : 'required|min:6';
        $confirmPasswordRule = $this->id && $this->password ? 'required|same:password' : 'nullable|same:password';
        return [
            'name' => [
                'required',
                Rule::unique('users')->ignore($this->id),
            ],
            'email' => [
                'required',
                Rule::unique('users')->ignore($this->id),
            ],
            'password' => $passwordRule,
            'confirm_password' => $confirmPasswordRule,
            'selected_roles' => 'required'
        ];
    }
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        /* $this->password = $user->password; */
    }
    public function store(): User
    {
        // Almacenar el arreglo validado
        $data = $this->all();

        // Modifica el elemento que necesites
        $data['email'] = $data['email'] . '@upeu.edu.pe';

        // Registrar Usuario modificado
        return User::create($data);
    }
    public function removeAllRoles()
    {
        foreach ($this->user->roles as $role) {
            $this->user->removeRole($role->name);
        }
    }
    public function asignRole($user)
    {
        foreach ($this->selected_roles as $role) {
            $role = Role::findOrFail($role);
            $user->assignRole($role->name);
        }
    }
    public function update()
    {
        // Almacenar el arreglo validado
        $data = $this->only(['name', 'email']);

        // Modifica el elemento que necesites
        $data['email'] = $data['email'] . '@upeu.edu.pe';

        // Si existe un usuario se puede cambiar el password
        if ($this->id && !empty($this->password) && $this->password === $this->confirm_password) {
            $data['password'] = Hash::make($this->password);
        }

        // Actualizar usuario
        $this->user->update($data);

        return $this->user;
    }
    public function delete()
    {
        $this->user->delete();
    }
    public function clearEmail($email)
    {
        // Dividir el email en partes usando el símbolo '@'
        $parts = explode('@', $email);

        // Obtener solo la parte antes del '@'
        return $parts[0];
    }
}