@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="form-box">
            <h1>Agentfire Timedoctor</h1>
            <form action="{{route('timedoctor.handle')}}" method="post" id="timedoctorForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="form_id" id="form_id" value="{{ @$getTimedoctor->id }}">

                <div class="form-group">
                    <label for="timedoctor_company_id" class="form-label">Timedoctor Company Id</label>
                    <input class="form-control" id="timedoctor_company_id" value="{{@$getTimedoctor->timedoctor_company_id}}" type="text" name="timedoctor_company_id">
                </div>
                <div class="form-group">
                    <label for="google_sheet_id" class="form-label">Google Sheet Id</label>
                    <input class="form-control" id="google_sheet_id" value="{{@$getTimedoctor->google_sheet_id}}" type="text" name="google_sheet_id">
                </div>
                <div class="form-group">
                    <label for="google_sheet_range" class="form-label">Google Sheet Range</label>
                    <input class="form-control" id="google_sheet_range" value="{{$getTimedoctor->google_sheet_range ?? 0}}" type="text" name="google_sheet_range">
                </div>
                <div class="form-group">
                    <label for="google_service_credentials" class="form-label">Upload Google Service Credentials</label>
                    <input class="form-control" id="google_service_credentials" type="file" name="google_service_credentials" accept=".json">
                </div>
                <input class="btn btn-primary" type="submit" value="Save" />
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @if (session('success'))
        <script>
            toastr.success('{{ session('success') }}');
        </script>
    @endif

    @if (session('error'))
        <script>
            toastr.error('{{ session('error') }}');
        </script>
    @endif
@endsection
