@extends('layouts.app')

@section('title', 'profile account')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-plus-circle text-primary"></i> Akun Profile Anda
        </h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-person-circle me-2"></i> Akun Anda
            </h5>
        </div>

        <div class="card-body">
            <div class="row mb-2">
                <div class="col-4 text-muted">Nama</div>
                <div class="col-8 fw-semibold">
                    {{ auth()->user()->name }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-4 text-muted">Email</div>
                <div class="col-8">
                    {{ auth()->user()->email }}
                </div>
            </div>
                    </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-cpu"></i> Edit Akun
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password"  class="form-control @error('password') is-invalid @enderror"
                            placeholder="Kosongkan jika tidak diganti">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>  

                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-danger">Reset</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>

    </div>
            <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-building text-primary"></i> Tambah Pengguna
    </h1>
    <div class="btn-group">
        <a href="{{ route('user.tambah') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Pengguna
        </a>
    </div>
</div>
            <div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-ul"></i> Semua Pengguna
            <span class="badge bg-secondary ms-2"> total {{ $jumlah }}</span>
        </h5>
    </div>
            <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>email</th>
                                    <th>phone</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($userData as $user)
                                    <tr>
                                        <td>
                                            <strong>{{ $user->name }}</strong><br>
                                        </td>
                                        <td>
                                            <strong>{{ $user->name }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ $user->email }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ $user->phone }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted text-center py-3">Belum ada data pembacaan suhu.</p>
            </div>
</div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // JSON validation for specifications
        document.getElementById('specifications').addEventListener('blur', function () {
            const value = this.value.trim();
            if (value && value !== '') {
                try {
                    JSON.parse(value);
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            }
        });
    </script>
@endpush