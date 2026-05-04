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
            <li class="breadcrumb-item" aria-current="page">{{\App\CPU\translate('Add_Country')}}</li>
        </ol>
    </nav>

    <!-- Content Row -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{ \App\CPU\translate('Add_Country')}}
                </div>
                <div class="card-body" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                    <form action="{{route('admin.country.store')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-5">
                                <div class="form-group">
                                    <label class="input-label" for="name">Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="New Country">
                                    @error('name')
                                        <small style="color: red;font-size:12px;">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary float-right">{{\App\CPU\translate('submit')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')

{{-- <script>
    $(".lang_link").click(function(e) {
        e.preventDefault();
        $(".lang_link").removeClass('active');
        $(".lang_form").addClass('d-none');
        $(this).addClass('active');

        let form_id = this.id;
        let lang = form_id.split("-")[0];
        console.log(lang);
        $("#" + lang + "-form").removeClass('d-none');
        if (lang == '{{$default_lang}}') {
            $(".from_part_2").removeClass('d-none');
        } else {
            $(".from_part_2").addClass('d-none');
        }
    });

    $(document).ready(function() {
        $('#dataTable').DataTable();
    });

</script>

<script>
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#viewer').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#customFileEg1").change(function() {
        readURL(this);
    });

</script> --}}
@endpush
