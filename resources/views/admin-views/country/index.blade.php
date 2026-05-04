@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Country_List'))

@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a>
            </li>
            <li class="breadcrumb-item" aria-current="page">{{\App\CPU\translate('category_List')}}</li>
        </ol>
    </nav>

    <!-- Content Row -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ \App\CPU\translate('category_List')}}
                </div>
                <div class="card-body" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                    <div class="table-responsive">
                        <table style="text-align: left;" class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr class="text-center">
                                    <th style="width: 100px">#</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($countries as $key=>$country)
                                <tr class="text-center">
                                    <th>{{$key+1}}</th>
                                    <td>{{$country->name}}</td>
                                    <td>
                                        <a class="btn btn-primary btn-sm edit" style="cursor: pointer;" title="Edit" href="{{route('admin.country.edit', $country->id)}}">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <a class="btn btn-danger btn-sm delete" style="cursor: pointer;" title="Delete" href="{{route('admin.country.delete', $country->id)}}">
                                            <i class="tio-add-to-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

@endpush
