@extends('layouts.back-end.app')

@section('title', 'Product Inquiries')

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">Product Inquiries</h1>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Inquiry List <span class="badge badge-soft-dark ml-2">{{ $leads->count() }}</span></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Customer Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Message Snippet</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leads as $key => $lead)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $lead->customer_name }}</td>
                                    <td>
                                        <a href="tel:{{ $lead->phone_number }}">{{ $lead->phone_number }}</a>
                                    </td>
                                    <td>
                                        @if($lead->email)
                                            <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                                        @else
                                            <span class="text-muted">No Email</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($lead->message, 40, '...') }}</td>
                                    <td>{{ $lead->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a class="btn btn-primary btn-sm" href="{{ route('admin.inquiries.view', $lead->id) }}">
                                            <i class="tio-visible"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(count($leads) == 0)
                <div class="text-center p-4">
                    <p class="mb-0">No inquiries found yet.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection