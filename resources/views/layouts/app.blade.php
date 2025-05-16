<!DOCTYPE html>
<html lang="en">
<head>
    <title>Agentfire Timedoctor</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ asset('agentfire/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('agentfire/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('agentfire/css/toastr.min.css')}}">
</head>
<body>

    <!-- Header -->
    <header class="form-header-holder">
      <img src="{{ asset('agentfire/images/logo-white-small.png')}}" alt="">
    </header>

    <!-- Main Content Section -->
    <div class="container">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="text-center footer-bt mt-5">
        <p>&copy; {{date('Y')}} Agentfire. All rights reserved.</p>
    </footer>

    <script src="{{ asset('agentfire/js/jquery-3.6.0.min.js')}}"></script>
    <script src="{{ asset('agentfire/js/jquery.validate.min.js')}}"></script>
    <script src="{{ asset('agentfire/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('agentfire/js/toastr.min.js')}}"></script>
    <script src="{{ asset('agentfire/js/validation.js') }}"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "timeOut": "5000",
        };
    </script>
    @yield('scripts')
</body>
</html>
