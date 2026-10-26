<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Tilapia Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Customer Registration</h4>
                </div>
                <div class="card-body">
                    
                    {{-- 1. Paparan Mesej Ralat Validation --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST">
                        {{-- 2. Security Token Wajib Laravel --}}
                        @csrf

                        {{-- 3. Input Username --}}
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required>
                        </div>

                        {{-- 4. Input Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                        </div>

                        {{-- 5. Input Device MAC Address --}}
                        <div class="mb-3">
                            <label for="mac_address" class="form-label">Device MAC Address</label>
                            <input type="text" name="mac_address" id="mac_address" class="form-control" placeholder="e.g. AA:BB:CC:DD:EE:FF" value="{{ old('mac_address') }}" required>
                            <div class="form-text">Masukkan MAC Address peranti penderia yang didaftarkan.</div>
                        </div>

                        {{-- 6. Input Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        {{-- 7. Input Confirm Password --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Register Account</button>
                        </div>
                    </form>

                    {{-- 8. Pautan Semula ke Skrin Login --}}
                    <div class="mt-3 text-center">
                        <p>Already have an account? <a href="{{ url('/admin/login') }}">Login Here</a></p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>