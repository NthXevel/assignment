@extends('layouts.app')

@section('content')
    <div class="page-title">
        <h1>User Management</h1>
        <p class="page-subtitle">Manage users across branches</p>
    </div>

    <div class="filter-add-container">
        {{-- Filter Form --}}
        <form method="GET" action="{{ route('users.index') }}" class="filter-form">
            {{-- Branch Filter (Admin only) --}}
            @if(auth()->user()->role === 'admin')
                <select name="branch" onchange="this.form.submit()">
                    <option value="">-- All Branches --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            {{-- Role Filter --}}
            <select name="role" onchange="this.form.submit()">
                <option value="">-- All Roles --</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $role)) }}
                    </option>
                @endforeach
            </select>

            {{-- Search --}}
            <input type="text" name="search" placeholder="Search username or email..." value="{{ request('search') }}">

            <button type="submit" class="filter-btn">Filter</button>

            {{-- Add User (Admin + Branch Manager only) --}}
            @if(in_array(auth()->user()->role, ['admin', 'branch_manager']))
                <a href="{{ route('users.create') }}" class="filter-btn">
                    <i class="fas fa-plus"></i> Add User
                </a>
            @endif
        </form>
    </div>

    <div class="table-container">
        <h3 class="chart-title"><i class="fas fa-users"></i> All Users</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        @if(in_array(auth()->user()->role, ['admin', 'branch_manager']))
                            <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>#{{ $user->id }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                            <td>{{ $user->branch->name ?? '-' }}</td>

                            {{-- Only admin & branch_manager can edit/delete --}}
                            @if(in_array(auth()->user()->role, ['admin', 'branch_manager']))
                                <td style="display: flex; gap: 8px; align-items: center;">
                                    <a href="{{ route('users.edit', $user->id) }}" 
                                       class="btn-theme btn-theme-primary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                          style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="btn-theme btn-theme-danger btn-sm"
                                                onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3 flex justify-between items-center">
            <div class="space-x-1">
                @if (!$users->onFirstPage())
                    <a href="{{ $users->previousPageUrl() }}" class="btn btn-primary">« Previous</a>
                @endif

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="btn btn-primary">Next »</a>
                @endif
            </div>
            <div>
                Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
            </div>
        </div>
    </div>

    {{-- Styles (same as your product page) --}}
    <style>
        .pagination {
            font-size: 0.875rem;
        }

        .pagination li a,
        .pagination li span {
            padding: 0.25rem 0.5rem;
            min-width: auto;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            max-height: 700px;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.4);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.6);
        }

        .table th,
        .table td {
            padding: 10px 12px;
        }

        .btn-theme {
            padding: 8px 15px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-theme-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-theme-primary:hover,
        .btn-theme-danger:hover {
            opacity: 0.9;
        }

        .btn-theme-danger {
            background: #ef4444;
            color: white;
        }

        .filter-add-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .filter-form select,
        .filter-form input,
        .filter-form button,
        .filter-form a.filter-btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid rgba(102, 126, 234, 0.3);
            outline: none;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-form button,
        .filter-form a.filter-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-form button:hover,
        .filter-form a.filter-btn:hover {
            opacity: 0.9;
        }
    </style>
@endsection
