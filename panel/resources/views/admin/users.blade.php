@extends('layouts.app')

@section('content')
<h1 class="mb-5 text-3xl font-bold">Manage Users</h1>
<form method="POST" action="{{ route('admin.users.store') }}" class="zy-card mb-6 grid gap-3 p-4 md:grid-cols-5">
    @csrf
    <input class="zy-input" name="name" placeholder="Name" required>
    <input class="zy-input" type="email" name="email" placeholder="Email" required>
    <input class="zy-input" name="password" placeholder="Password" required>
    <select class="zy-input" name="role_id">@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select>
    <button class="zy-btn-primary">Create</button>
</form>
<div class="zy-card overflow-x-auto">
    <table class="zy-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Update</th></tr></thead>
        <tbody class="divide-y divide-line">
            @foreach($users as $user)
                <tr>
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf @method('PATCH')
                        <td><input class="zy-input" name="name" value="{{ $user->name }}"></td>
                        <td><input class="zy-input" type="email" name="email" value="{{ $user->email }}"></td>
                        <td><select class="zy-input" name="role_id">@foreach($roles as $role)<option value="{{ $role->id }}" @selected($role->id === $user->role_id)>{{ $role->name }}</option>@endforeach</select></td>
                        <td><input type="checkbox" name="is_active" value="1" @checked($user->is_active)></td>
                        <td><input type="password" name="password" class="zy-input mb-2" placeholder="New password"><button class="zy-btn-secondary">Save</button></td>
                    </form>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $users->links() }}</div>
@endsection
