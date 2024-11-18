@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                @if($edit)
                    @if( strtotime($dataTypeContent->date_to) < strtotime(now()) )
                        <div class="alert alert-danger text-center" style="font-weight: bold;">Este registro esta caducado</div>
                    @endif
                @endif
                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form"
                            class="form-edit-add"
                            action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                            method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if($edit)
                            {{ method_field("PUT") }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="panel-body">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                            @endphp

                            @foreach($dataTypeRows as $row)
                                <!-- GET THE DISPLAY OPTIONS -->
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif

                                <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif>
                                    {{ $row->slugify }}
                                    <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if ($add && isset($row->details->view_add))
                                        @include($row->details->view_add, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'add', 'options' => $row->details])
                                    @elseif ($edit && isset($row->details->view_edit))
                                        @include($row->details->view_edit, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'view' => 'edit', 'options' => $row->details])
                                    @elseif (isset($row->details->view))
                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                                    @elseif ($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', ['options' => $row->details])
                                    @else
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @endif

                                    @if($row->field == "password")
                                        @if(!$edit)
                                            <a class="btn btn-success" id="generate-user-and-password" href="#">Generar Usuario y Clave</a>
                                        @endif
                                    @endif

                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach
                        </div><!-- panel-body -->

                        <div class="panel-footer">
                            @section('submit-buttons')
                                <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                            @stop
                            @yield('submit-buttons')
                        </div>
                    </form>

                    <div style="display:none">
                        <input type="hidden" id="upload_url" value="{{ route('voyager.upload') }}">
                        <input type="hidden" id="upload_type_slug" value="{{ $dataType->slug }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->
@stop

@section('javascript')
    <script>
        var params = {};
        var $file;
        var currentScreens = $("select[name='screens']").html();
        
        function deleteHandler(tag, isMulti) {
          return function() {
            $file = $(this).siblings(tag);

            params = {
                slug:   '{{ $dataType->slug }}',
                filename:  $file.data('file-name'),
                id:     $file.data('id'),
                field:  $file.parent().data('field-name'),
                multi: isMulti,
                _token: '{{ csrf_token() }}'
            }

            $('.confirm_delete_name').text(params.filename);
            $('#confirm_delete_modal').modal('show');
          };
        }

        function generateAndSetCredentials() {
            const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            const specialCharacters = "!@#$%^&*()-_=+[]{}|;:',.<>?/";

            // Generate random username (10 characters long)
            const user = Array.from({ length: 10 }, () => characters.charAt(Math.floor(Math.random() * characters.length))).join('');

            // Generate random password (minimum 8 characters: letters, numbers, special chars)
            const passwordLength = 8;
            const password = Array.from({ length: passwordLength }, (_, i) => {
                if (i < 2) return specialCharacters.charAt(Math.floor(Math.random() * specialCharacters.length));
                if (i < 4) return characters.charAt(Math.floor(Math.random() * 10)); // Ensure numbers
                return characters.charAt(Math.floor(Math.random() * characters.length));
            }).sort(() => Math.random() - 0.5).join('');

            // Set the values in the form fields
            $('[name="name"]').val(user);
            $('[name="password"]').val(password);
        }

        $('document').ready(function () {
            
            $("#generate-user-and-password").click(function(){
                generateAndSetCredentials();
            });

            @if(Auth::user()->role_id == 3 || Auth::user()->role_id == 5)
                @if(setting('admin.max_reseller_screen'))
                    $("select[name='screens']").empty();
                    let screes = parseInt("{{setting('admin.max_reseller_screen')}}");
                    for(let i=1;i<=screes;i++){
                        $("select[name='screens']").append("<option value='"+i+"'>"+i+"</option>");
                    }
                @endif
            @endif

            $('.toggleswitch').bootstrapToggle();
            @if(!in_array(Auth::user()->role_id, [4,1]))
                $("input[name='date_to']").attr("readonly","readonly");
            @endif
            
            $("select[name='jellyfinserver_id']").change(function(){
                let id = $(this).val();
                if(id){
                    $("select[name='jellyfinpackage_id']").empty();
                    $.get('/admin/get-jellyfin-packages-by-server/'+id, function(response){
                        $("select[name='jellyfinpackage_id']").append("<option value=''>Seleccione</option>");
                        if(response.length > 0){
                            $.each(response, function(v,e){
                                $("select[name='jellyfinpackage_id']").append("<option value='"+e.id+"'>"+e.name+"</option>");
                            });
                        }
                    });  
                }
                
            });

            @if($edit)
                $("select[name='jellyfinpackage_id']").empty();
                $.get('/admin/get-jellyfin-packages-by-server/{{$dataTypeContent->jellyfinserver_id}}', function(response){
                    $("select[name='jellyfinpackage_id']").append("<option value=''>Seleccione</option>");
                    if(response.length > 0){
                        var selected = {{$dataTypeContent->jellyfinpackage_id}};
                        $.each(response, function(v,e){
                            if(e.id == selected){
                                $("select[name='jellyfinpackage_id']").append("<option selected='selected' value='"+e.id+"'>"+e.name+"</option>");
                            }else{
                                $("select[name='jellyfinpackage_id']").append("<option value='"+e.id+"'>"+e.name+"</option>");
                            }
                            
                        });
                    }
                }); 
            @endif

            $("select[name='duration_id']").change(function(){
                let id = $(this).val();
                if(id){
                   $.get("/api/get-months-duration/"+id, function(response){
                        let data = response;
                        if(data.screes){
                            $("select[name='screens']").empty();
                            $("select[name='screens']").append('<option value="'+data.screes+'" selected="selected">'+data.screes+'</option>');
                        }else{
                            $("select[name='screens']").html(currentScreens);
                        }
                        $("input[name='date_to']").val(data.new_date);

                   }); 
               }else{
                $("input[name='date_to']").val("");
               }
                
            });

            //Init datepicker for date fields if data-datepicker attribute defined
            //or if browser does not handle date inputs
            $('.form-group input[type=date]').each(function (idx, elt) {
                if (elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                } else if (elt.type != 'date') {
                    elt.type = 'text';
                    $(elt).datetimepicker({
                        format: 'L',
                        extraFormats: [ 'YYYY-MM-DD' ]
                    }).datetimepicker($(elt).data('datepicker'));
                }
            });

            @if ($isModelTranslatable)
                $('.side-body').multilingual({"editing": true});
            @endif

            $('.side-body input[data-slug-origin]').each(function(i, el) {
                $(el).slugify();
            });

            $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
            $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
            $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
            $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

            $('#confirm_delete').on('click', function(){
                $.post('{{ route('voyager.'.$dataType->slug.'.media.remove') }}', params, function (response) {
                    if ( response
                        && response.data
                        && response.data.status
                        && response.data.status == 200 ) {

                        toastr.success(response.data.message);
                        $file.parent().fadeOut(300, function() { $(this).remove(); })
                    } else {
                        toastr.error("Error removing file.");
                    }
                });

                $('#confirm_delete_modal').modal('hide');
            });
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@stop
