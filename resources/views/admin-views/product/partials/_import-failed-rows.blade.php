@if(!empty($rows))
    <div class="mt-3">
        <h6 class="text-danger"><i class="tio-warning"></i> {{\App\CPU\translate('Failed / Skipped Rows')}} ({{ ($total ?? count($rows)) }})</h6>
        @if(($total ?? count($rows)) > count($rows))
            <small class="text-muted">{{\App\CPU\translate('Showing the first')}} {{ count($rows) }} {{\App\CPU\translate('of')}} {{ $total }}. {{\App\CPU\translate('See storage/logs for the rest.')}}</small>
        @endif
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th style="width:90px;">{{\App\CPU\translate('Row')}} #</th>
                        <th>{{\App\CPU\translate('Reason')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['row'] ?? '?' }}</td>
                            <td class="text-left text-danger">{{ $row['reason'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
