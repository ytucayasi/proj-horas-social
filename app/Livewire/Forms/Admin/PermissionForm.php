<?php

namespace App\Livewire\Forms\Admin;

use Illuminate\Validation\Rule;
use Livewire\Form;
use App\Models\Permission;

class PermissionForm extends Form
{
    public ?Permission $permission;
    public ?int $id = null;
    public ?int $action_id = null;
    public ?int $module_id = null;
    public ?string $name = '';
    public function mount()
    {
        $this->permission = new Permission();
    }
    public function getPermission()
    {
        $this->permission;
    }
    public function rules()
    {
        return [
            'name' => ['required', 'string', Rule::unique('permissions')->ignore($this->id)],
            'action_id' => 'required|integer',
            'module_id' => 'required|integer',
        ];
    }
    public function setPermission(Permission $permission)
    {
        $this->permission = $permission;
        $this->id = $permission->id;
        $this->name = $permission->name;

        /* Aqui debemode obtener los id de cada uno */
        $action = $this->getAction(null, $this->name);
        $module = $this->getModule(null, $this->name);

        $this->action_id = $action ? $action['id'] : null;
        $this->module_id = $module ? $module['id'] : null;
    }
    public function getAction(?int $id = null, ?string $name = null)
    {
        $actions = config('admin.actions');
        foreach ($actions as $action) {
            if ($action['id'] === $id || ($name && strpos($name, $action['ab']) !== false)) {
                return $action;
            }
        }
        return null;
    }
    public function active()
    {
        $action = $this->getAction(null, $this->name);
        $module = $this->getModule(null, $this->name);
        Permission::onlyTrashed()->where('name', $action['ab'] . ' ' . $module['ab'])->restore();
    }
    public function getModule(?int $id = null, ?string $name = null)
    {
        $modules = config('admin.modules');
        foreach ($modules as $module) {
            if ($module['id'] === $id || ($name && strpos($name, $module['ab']) !== false)) {
                return $module;
            }
        }
        return null;
    }
    public function verify_exist_permission(): bool
    {
        $action = $this->getAction($this->action_id, null);
        $module = $this->getModule($this->module_id, null);
        if ($action && isset($action['ab']) && $module && isset($module['ab'])) {
            $permission = Permission::withTrashed()->where('name', $action['ab'] . ' ' . $module['ab'])->first();
            if ($permission) {
                return $permission->trashed() ? true : false;
            }
        }
        return false;
    }
    public function store()
    {
        Permission::create($this->only('name'));
    }
    public function update()
    {
        $this->permission->update($this->only('name'));
    }
    public function delete()
    {
        $this->permission->delete();
    }
}