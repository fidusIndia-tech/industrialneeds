@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('feedback List'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">  <!-- Page Heading -->
        <div class="row" style="margin-top: 20px">
            <div class="col-md-12">
                    <div class="card-body" style="padding: 0">
                        <div class="table-responsive">
                            <table id="datatable"
                                   class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                                   style="width: 100%">
                                <thead class="thead-light">
                                <tr>
                                    <th>{{\App\CPU\translate('SL#')}}</th>
                                    <th>{{\App\CPU\translate('full_name')}}</th>
                                    <th>{{\App\CPU\translate('company_name')}}</th>
                                    <th>{{\App\CPU\translate('Phone_no')}}</th>
                                    <th>{{\App\CPU\translate('feedback')}}</th>
                                    <th>{{\App\CPU\translate('status')}} </th>
                                </tr>
                                </thead>
                                <tbody>
                                    @foreach($feedback as $key=>$feedbacks)
                                 <tr>
                                        <th scope="row">{{ $key+1 }}</th>
                                        <td> {{$feedbacks->full_name}}</td>
                                        <td>{{$feedbacks->company_name}}</td>
                                        <td>{{$feedbacks->phone_no}}</td>
                                         <td>{{ substr($feedbacks->feedback, 0 , 60) }}</td>
                                       <td>
												<input type="checkbox" id="clientagree" onchange="is_clientagree(this)" idData="{{$feedbacks->id}}" {{@$feedbacks->status == 1 ? 'checked' : ''}}>
												
												
                                        </td>
                                     
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                       
                    </div>
                </div>
            </div>
        </div>
    </div>
	
	    <script>
	function is_clientagree(e){
            var feedback_id = $(e).attr('idData');
            var isActive = $(e).prop('checked');
            var id = $(e).attr('id');
            if(isActive){
                Swal.fire({
                    title: 'Are you sure?',
                    text: " feedback active?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, agree it!'
                }).then((result) => {
					
				var url = '{{url("admin/feedbacksapprovel")}}/'+ feedback_id;
                    if (result.value) {
                        $.ajax({
                            type:'GET',
                            url: url,
                                    success:function(data){
                                    var resp = jQuery.parseJSON(data);

                                if (resp.currentStatus == 1) {
                                    toastr.success('', 'feedback active.');
                                }
                                else{
                                    toastr.success('', 'feedback active.');
                                }
                            }
                        });
                    }
                    else{
                        e.checked = false;
                    }
                });
            }
            else{
                Swal.fire({
                    title: 'Are you sure?',
                    text: "feedback Inactive?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, disagree it!'
                }).then((result) => {
				var url = '{{url("admin/feedbackreject")}}/' + feedback_id;
                    if (result.value) {
                        $.ajax({
                            type:'GET',
                            url: url,
                            success:function(data){
                                    var resp = jQuery.parseJSON(data);

                                if (resp.currentStatus == 1) {
                                    toastr.success('', 'feedback Inactive.');
                                }
                                else{
                                    toastr.success('', 'feedback Inactive.');
                                }
                            },
							error:function(err){
							alert(err);
							}
                        });
                    }
                    else{
                        e.checked = true;
                    }
                });
            }
        }
	</script>

@endsection

@push('script')
    <!-- Page level plugins -->
    <script src="{{asset('public/assets/back-end')}}/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="{{asset('public/assets/back-end')}}/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <!-- Page level custom scripts -->

   
@endpush
