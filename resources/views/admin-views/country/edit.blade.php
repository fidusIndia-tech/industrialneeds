@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Country'))

@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a>
            </li>
            <li class="breadcrumb-item" aria-current="page">{{\App\CPU\translate('Country_Edit')}}</li>
        </ol>
    </nav>

    <!-- Content Row -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ \App\CPU\translate('Country_Edit')}}
                </div>
                <div class="card-body" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                    <form action="{{route('admin.country.update', $country->id)}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="col-12 col-md-5">
                                <div class="form-group">
                                    <label class="input-label" for="name">Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Country" value="{{$country->name}}">
                                    @error('name')
                                        <small style="color: red;font-size:12px;">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary float-right">{{\App\CPU\translate('update')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
@endpush
