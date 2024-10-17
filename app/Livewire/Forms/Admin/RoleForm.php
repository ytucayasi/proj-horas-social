<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Spatie\Permission\Models\Role;

class RoleForm extends Form
{
    public ?Role $role = null;
    #[Locked]
    public ?int $id = null;
    public $permission_id = null;
    public ?string $name = '';
    public ?int $escuela_id = null;
    public $permissions = [];
    public ?bool $activate_permission = false;
    public ?int $action_permission_id = 0;
    public $selected_permissions = [];
    public $grouped_permissions = [];
    public function mount()
    {
        $this->role = new Role();
    }
    public function getRole()
    {
        return $this->role;
    }
    public function changePermissions(int $action_id = null)
    {
        if ($action_id == 1) {
            // Listar permisos que NO están relacionados con el rol
            $this->permissions = Permission::whereNotIn('id', $this->role->permissions->pluck('id'))->get();
        } elseif ($action_id == 2) {
            // Listar permisos que SÍ están relacionados con el rol
            $this->permissions = $this->role->permissions;
        } else {
            $this->permissions = [];
        }
    }
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('roles')->ignore($this->id),
            ],
            'escuela_id' => "required|integer|exists:escuelas,id",
        ];
    }
    public function setRole(Role $role)
    {
        $this->role = $role;
        $this->id = $role->id;
        $this->name = $role->name;
        $this->escuela_id = $role->escuela_id;
    }
    function groupPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            $prefix = explode(' ', $permission->name)[0];
            if (!isset($this->grouped_permissions[$prefix])) {
                $this->grouped_permissions[$prefix] = [];
            }
            $this->grouped_permissions[$prefix][] = $permission;
        }
    }
    public function store()
    {
        $role = Role::create($this->all());
        $permissions = Permission::all();
        $role->syncPermissions($permissions);
    }
    public function update()
    {
        if ($this->action_permission_id == 1) {
            $this->role->givePermissionTo($this->selected_permissions);
        } elseif ($this->action_permission_id == 2) {
            foreach ($this->selected_permissions as $permission) {
                if ($this->role->hasPermissionTo($permission)) {
                    $this->role->revokePermissionTo($permission);
                }
            }
        }
        $this->role->update($this->only('name', 'escuela_id'));
    }
    public function delete()
    {
        $this->role->delete();
    }
}
