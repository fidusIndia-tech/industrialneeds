@extends('layouts.back-end.app')

@section('title', 'Inquiry Details')

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title">Inquiry Details</h1>
        <a href="{{ route('admin.inquiries.list') }}" class="btn btn-primary">
            Back to List
        </a>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-md-8">
            <!-- Customer Info Card -->
            <div class="card mb-3 mb-lg-5">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label text-dark">Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="{{ $lead->customer_name }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label text-dark">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="{{ $lead->phone_number }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label text-dark">Email</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="{{ $lead->email ?? 'No email provided' }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label text-dark">Received On</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="{{ $lead->created_at->format('d M Y, h:i A') }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Card -->
            <div class="card mb-3 mb-lg-5">
                <div class="card-header">
                    <h5 class="mb-0">Full Message</h5>
                </div>
                <div class="card-body">
                    <!-- white-space: pre-wrap preserves paragraph breaks if they pressed Enter -->
                    <p style="white-space: pre-wrap;" class="text-dark">{{ $lead->message }}</p>
                </div>
            </div>
            
        </div>
    </div>
</div>
@endsection