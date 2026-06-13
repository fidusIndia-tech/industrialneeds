@extends('layouts.back-end.app')

@section('title', 'Chat Leads')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">{{ \App\CPU\translate('Chat Leads') }}</h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">{{ \App\CPU\translate('Leads') }}
                        <span class="badge badge-soft-dark ml-2">{{ $leads->total() }}</span>
                        @if($engagedCount)
                            <small class="text-muted ml-2">{{ $engagedCount }} {{ \App\CPU\translate('opened chat (no details)') }}</small>
                        @endif
                    </h5>
                    <form method="GET" action="{{ route('admin.chat-leads.list') }}">
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">{{ \App\CPU\translate('Active leads') }}</option>
                            @foreach(['lead','contacted','closed','engaged'] as $s)
                                <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ \App\CPU\translate('Name') }}</th>
                                    <th>{{ \App\CPU\translate('Phone') }}</th>
                                    <th>{{ \App\CPU\translate('Looking for') }}</th>
                                    <th>{{ \App\CPU\translate('Status') }}</th>
                                    <th>{{ \App\CPU\translate('When') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($leads as $lead)
                                <tr>
                                    <td>{{ $lead->id }}</td>
                                    <td>{{ $lead->name ?? '—' }}</td>
                                    <td>@if($lead->phone)<a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>@else <span class="text-muted">—</span> @endif</td>
                                    <td>{{ \Illuminate\Support\Str::limit($lead->product ?? $lead->intent ?? '—', 28) }}@if($lead->quantity) <small class="text-muted">×{{ $lead->quantity }}</small>@endif</td>
                                    <td><span class="badge badge-soft-info text-capitalize">{{ $lead->status }}</span></td>
                                    <td>{{ $lead->created_at->diffForHumans() }}</td>
                                    <td><a class="btn btn-primary btn-sm" href="{{ route('admin.chat-leads.show', $lead->id) }}"><i class="tio-visible"></i> {{ \App\CPU\translate('View') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">{{ \App\CPU\translate('No chat leads yet.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">{!! $leads->links() !!}</div>
            </div>
        </div>
    </div>
</div>
@endsection
