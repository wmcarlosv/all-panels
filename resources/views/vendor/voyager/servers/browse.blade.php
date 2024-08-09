@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
        </h1>
        @can('add', app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete', app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
        @can('edit', app($dataType->model_name))
            @if(!empty($dataType->order_column) && !empty($dataType->order_display_column))
                <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
        @endcan

        @can('add', app($dataType->model_name))
            <a href="#" id="button_masive_server" class="btn btn-info"><i class="voyager-tv"></i> Cambio Masivo de Servidores</a>
        @endcan

        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes" data-toggle="toggle" data-on="{{ __('voyager::bread.soft_deletes_off') }}" data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach($actions as $action)
            @if (method_exists($action, 'massAction'))
                @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
            @endif
        @endforeach
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        @if ($isServerSide)
                            <form method="get" class="form-search">
                                <div id="search-input">
                                    <div class="col-2">
                                        <select id="search_key" name="key">
                                            @foreach($searchNames as $key => $name)
                                                <option value="{{ $key }}" @if($search->key == $key || (empty($search->key) && $key == $defaultSearchKey)) selected @endif>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <select id="filter" name="filter">
                                            <option value="contains" @if($search->filter == "contains") selected @endif>{{ __('voyager::generic.contains') }}</option>
                                            <option value="equals" @if($search->filter == "equals") selected @endif>=</option>
                                        </select>
                                    </div>
                                    <div class="input-group col-md-12">
                                        <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}" name="s" value="{{ $search->value }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info btn-lg" type="submit">
                                                <i class="voyager-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                @if (Request::has('sort_order') && Request::has('order_by'))
                                    <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                                    <input type="hidden" name="order_by" value="{{ Request::get('order_by') }}">
                                @endif
                            </form>
                        @endif
                        <!--<div class="table-responsive">-->
                            <table id="dataTable" class="table table-hover display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        @if($showCheckboxColumn)
                                            <th class="dt-not-orderable">
                                                <input type="checkbox" class="select_all">
                                            </th>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                        <th>
                                            @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                            @endif
                                            {{ $row->getTranslatedAttribute('display_name') }}
                                            @if ($isServerSide)
                                                @if ($row->isCurrentSortField($orderBy))
                                                    @if ($sortOrder == 'asc')
                                                        <i class="voyager-angle-up pull-right"></i>
                                                    @else
                                                        <i class="voyager-angle-down pull-right"></i>
                                                    @endif
                                                @endif
                                                </a>
                                            @endif
                                        </th>
                                        @endforeach
                                        <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataTypeContent as $data)
                                    <tr>
                                        @if($showCheckboxColumn)
                                            <td>
                                                <input type="checkbox" name="row_id" id="checkbox_{{ $data->getKey() }}" value="{{ $data->getKey() }}">
                                            </td>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                            @php
                                            if ($data->{$row->field.'_browse'}) {
                                                $data->{$row->field} = $data->{$row->field.'_browse'};
                                            }
                                            @endphp
                                            <td>
                                                @if (isset($row->details->view_browse))
                                                    @include($row->details->view_browse, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'view' => 'browse', 'options' => $row->details])
                                                @elseif (isset($row->details->view))
                                                    @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'action' => 'browse', 'view' => 'browse', 'options' => $row->details])
                                                @elseif($row->type == 'image')
                                                    <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:100px">
                                                @elseif($row->type == 'relationship')
                                                    @include('voyager::formfields.relationship', ['view' => 'browse','options' => $row->details])
                                                @elseif($row->type == 'select_multiple')
                                                    @if(property_exists($row->details, 'relationship'))

                                                        @foreach($data->{$row->field} as $item)
                                                            {{ $item->{$row->field} }}
                                                        @endforeach

                                                    @elseif(property_exists($row->details, 'options'))
                                                        @if (!empty(json_decode($data->{$row->field})))
                                                            @foreach(json_decode($data->{$row->field}) as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif
                                                    @endif

                                                    @elseif($row->type == 'multiple_checkbox' && property_exists($row->details, 'options'))
                                                        @if (@count(json_decode($data->{$row->field}, true)) > 0)
                                                            @foreach(json_decode($data->{$row->field}) as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif

                                                @elseif(($row->type == 'select_dropdown' || $row->type == 'radio_btn') && property_exists($row->details, 'options'))

                                                    {!! $row->details->options->{$data->{$row->field}} ?? '' !!}

                                                @elseif($row->type == 'date' || $row->type == 'timestamp')
                                                    @if ( property_exists($row->details, 'format') && !is_null($data->{$row->field}) )
                                                        {{ \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($row->details->format) }}
                                                    @else
                                                        {{ $data->{$row->field} }}
                                                    @endif
                                                @elseif($row->type == 'checkbox')
                                                    @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                                        @if($data->{$row->field})
                                                            <span class="label label-info">{{ $row->details->on }}</span>
                                                        @else
                                                            <span class="label label-primary">{{ $row->details->off }}</span>
                                                        @endif
                                                    @else
                                                    {{ $data->{$row->field} }}
                                                    @endif
                                                @elseif($row->type == 'color')
                                                    <span class="badge badge-lg" style="background-color: {{ $data->{$row->field} }}">{{ $data->{$row->field} }}</span>
                                                @elseif($row->type == 'text')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                @elseif($row->type == 'text_area')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                @elseif($row->type == 'file' && !empty($data->{$row->field}) )
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    @if(json_decode($data->{$row->field}) !== null)
                                                        @foreach(json_decode($data->{$row->field}) as $file)
                                                            <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}" target="_blank">
                                                                {{ $file->original_name ?: '' }}
                                                            </a>
                                                            <br/>
                                                        @endforeach
                                                    @else
                                                        <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($data->{$row->field}) }}" target="_blank">
                                                            {{ __('voyager::generic.download') }}
                                                        </a>
                                                    @endif
                                                @elseif($row->type == 'rich_text_box')
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <div>{{ mb_strlen( strip_tags($data->{$row->field}, '<b><i><u>') ) > 200 ? mb_substr(strip_tags($data->{$row->field}, '<b><i><u>'), 0, 200) . ' ...' : strip_tags($data->{$row->field}, '<b><i><u>') }}</div>
                                                @elseif($row->type == 'coordinates')
                                                    @include('voyager::partials.coordinates-static-image')
                                                @elseif($row->type == 'multiple_images')
                                                    @php $images = json_decode($data->{$row->field}); @endphp
                                                    @if($images)
                                                        @php $images = array_slice($images, 0, 3); @endphp
                                                        @foreach($images as $image)
                                                            <img src="@if( !filter_var($image, FILTER_VALIDATE_URL)){{ Voyager::image( $image ) }}@else{{ $image }}@endif" style="width:50px">
                                                        @endforeach
                                                    @endif
                                                @elseif($row->type == 'media_picker')
                                                    @php
                                                        if (is_array($data->{$row->field})) {
                                                            $files = $data->{$row->field};
                                                        } else {
                                                            $files = json_decode($data->{$row->field});
                                                        }
                                                    @endphp
                                                    @if ($files)
                                                        @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                            @foreach (array_slice($files, 0, 3) as $file)
                                                            <img src="@if( !filter_var($file, FILTER_VALIDATE_URL)){{ Voyager::image( $file ) }}@else{{ $file }}@endif" style="width:50px">
                                                            @endforeach
                                                        @else
                                                            <ul>
                                                            @foreach (array_slice($files, 0, 3) as $file)
                                                                <li>{{ $file }}</li>
                                                            @endforeach
                                                            </ul>
                                                        @endif
                                                        @if (count($files) > 3)
                                                            {{ __('voyager::media.files_more', ['count' => (count($files) - 3)]) }}
                                                        @endif
                                                    @elseif (is_array($files) && count($files) == 0)
                                                        {{ trans_choice('voyager::media.files', 0) }}
                                                    @elseif ($data->{$row->field} != '')
                                                        @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                            <img src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif" style="width:50px">
                                                        @else
                                                            {{ $data->{$row->field} }}
                                                        @endif
                                                    @else
                                                        {{ trans_choice('voyager::media.files', 0) }}
                                                    @endif
                                                @else
                                                    @include('voyager::multilingual.input-hidden-bread-browse')
                                                    <span>{{ $data->{$row->field} }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="no-sort no-click bread-actions">
                                            @foreach($actions as $action)
                                                @if (!method_exists($action, 'massAction'))
                                                    @include('voyager::bread.partials.actions', ['action' => $action])
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        <!--</div>-->
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">{{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}</div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                    's' => $search->value,
                                    'filter' => $search->filter,
                                    'key' => $search->key,
                                    'order_by' => $orderBy,
                                    'sort_order' => $sortOrder,
                                    'showSoftDeleted' => $showSoftDeleted,
                                ])->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm" value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div class="modal fade modal-success" id="active-sessions-modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Sesiones Activas</h4>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <th>Cover</th>
                            <th>Titulo</th>
                            <th>Dispostivo</th>
                            <th>Usuario</th>
                        </thead>
                        <tbody id="load-sessions">
                            
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancel-active-sessions">Salir</button>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade modal-success" id="update-libraries-modal">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-hidden="true">&times;</button>
                <h4 class="modal-title">Refrescar Librerias "<span id="server_name"></span>"</h4>
            </div>

            <div class="modal-body">
                <ul class="list-group" id="load_libraries"></ul>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="save-update-libraries">Actualizar</button>
                <button type="button" class="btn btn-danger" id="cancel-update-libraries">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-success" id="modal-change-masive-server">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-hidden="true">&times;</button>
                <h4 class="modal-title">Cambio Masivo de Servidor</h4>
            </div>

            <div class="modal-body">
                <div class="form-group col-md-6">
                    <label for="">Servidor desde:</label>
                    <select id="id_server_from" class="form-control">
                        <option value="">Seleccione</option>
                        @foreach($servers as $server)
                            <option value="{{$server->id}}" data-server-id="{{$server->id}}" data-server-name="{{$server->name_and_local_name}}">{{$server->name_and_local_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="">Servidor hasta:</label>
                    <select id="cms_new_server" class="form-control">
                        <option value="">Seleccione</option>
                        @foreach($servers as $server)
                            <option value="{{$server->id}}" data-packages='{{json_encode($server->packages)}}'>{{$server->name_and_local_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label for="">Generar:</label>
                    <select id="generate_new_email" class="form-control">
                        <option value="new_account">Cuenta Nueva</option>
                        <option value="same_account" selected>Misma Cuenta</option>
                    </select>
                </div>
                <!--<div class="form-group col-md-6">
                    <label for="">Eliminar el viejo servidor y sus cuentas una vez finalizado el proceso?:</label>
                    <select id="delete_old_server" class="form-control">
                        <option value="Y">Si</option>
                        <option value="N" selected>No</option>
                    </select>
                </div>-->
                <div class="form-group col-md-6">
                    <label for="">Como se majeran los paquetes?:</label>
                    <select id="how_set_package" class="form-control">
                        <option value="no_package">Sin paquetes</option>
                        <option value="compare" selected>Comparar con servidor actual</option>
                        <option value="default_package">Paquete por defecto</option>
                    </select>
                </div>
                <div id="col_package_id" style="display:none;" class="form-group col-md-12">
                    <label for="">Paquete:</label>
                    <select id="package_id" class="form-control">
                        <option value="">Seleccione</option>
                    </select>
                </div>
                <div class="col-md-12" style="display: none;" id="content_prefix_email">
                    <div class="form-group">
                        <label for="prefix_email">Prefijo Cuenta Nueva:</label>
                        <input type="text" class="form-control" id="prefix_email" />
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="server_is_baned">Servidor Baneado?</label>
                        <select id="server_is_baned" class="form-control">
                            <option value="Y">Si</option>
                            <option value="N" selected>No</option>
                        </select>
                    </div>
                </div>
                <table class="table table-bordered table-striped">
                    <thead>
                        <th><input type="checkbox" id="all_select" /></th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Paq. Actual</th>
                        <th>Nuevo Email</th>
                        <th>Paq. Nuevo</th>
                        <th>Estatus</th>
                    </thead>
                    <tbody id="load-customers">
                        
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="save-change-masive-server">Cambiar</button>
                <button type="button" class="btn btn-danger" id="cancel-change-masive-server">Salir</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
    <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
@stop

@section('javascript')
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var theInterval;
        var indexCustomer = 0;
        $(document).ready(function () {
            var selected_server_id;

            $("#generate_new_email").change(function(){
                let value = $(this).val();
                if(value == "new_account"){
                    $("#content_prefix_email").show();
                }else{
                    $("#content_prefix_email").hide();
                    $("#prefix_email").val("");
                }
            });

            $("#save-change-masive-server").click(function(){
                var customers = $("input[name='new_customers[]']:checked");
                let server_from = $("#id_server_from").val();
                let server_to = $("#cms_new_server").val();
                

                if(server_from === server_to){
                    alert("Los Servidores seleccionados deben ser distintos!!");
                    return;
                }

                if(!server_from || !server_to || customers.length <= 0){
                    alert("Para la importacion debe seleccionar, el servidor desde, servidor hasta y al menos seleccionar un cliente!!");
                    return;
                }

                if(confirm("Esta seguro de realizar el movimiento masivo de cuentas?")){
                    $("#save-change-masive-server, #cancel-change-masive-server").attr("disabled", true);
                    theInterval = setInterval(function(){
                            if(indexCustomer >= customers.length){
                                clearInterval(theInterval);
                                $("#save-change-masive-server, #cancel-change-masive-server").attr("disabled", false);
                                alert("Proceso de migracion Finalizado..");
                            }else{
                                $("#status_"+customers[indexCustomer].value).html("<p>Cargando...</p>");
                                movementAccount(customers[indexCustomer].value);
                            }
                            indexCustomer++;
                        }, 10000);
                }
            });

            function movementAccount(customer_selected){
                let server_from = $("#id_server_from").val();
                let server_to = $("#cms_new_server").val();
                let generate_new_email = $("#generate_new_email").val();
                let delete_old_server = null; //$("#delete_old_server").val();
                let how_set_package = $("#how_set_package").val();
                let package_id = $("#package_id").val();
                let server_is_baned = $("#server_is_baned").val();
                let customer_id = customer_selected;
                let prefix_email = $("#prefix_email").val();

                $.post("{{route('move_customers_massive')}}", { server_from_id: server_from, server_to_id: server_to, customer_id: customer_id, generate_new_email:generate_new_email, delete_old_server:delete_old_server, how_set_package:how_set_package, package_id:package_id, server_is_baned:server_is_baned, prefix_email:prefix_email  }, function(response){
                    let data = response;
                    if(response.success){
                        $("#status_"+customer_selected).html("<p style='font-weight:bold; color:green;'>Listo</p>");
                        $("#checked_"+customer_selected).removeAttr("checked").hide();
                        $("#new_email_"+response.customer.id).html(response.customer.email);

                        if(response.customer.package_id){
                            $("#package_"+response.customer.id).html(response.customer.package.name);
                        }
                    }else{
                         $("#status_"+customer_selected).html("<p style='font-weight:bold; color:red;' title='"+response.error+"'>Error</p>");
                    }
                });
            }

            $("#all_select").click(function(){
                let cuentas = $("input[name='new_customers[]']");
                if($(this).prop("checked")){
                    if(cuentas.length > 0){
                        $.each(cuentas, function(v,e){
                            e.setAttribute("checked", true);
                        });
                    }
                }else{
                    if(cuentas.length > 0){
                       $.each(cuentas, function(v,e){
                            e.removeAttribute("checked");
                        }); 
                   }
                }
            });

            $("#how_set_package").change(function(){
                let value = $(this).val();
                if(value == "default_package"){
                    let packages = JSON.parse($("#cms_new_server").children("option:selected").attr("data-packages"));
                    if(packages.length > 0){
                        $("#package_id").html("<option value=''>Seleccione</option>");
                        $.each(packages, function(v,e){
                            $("#package_id").append("<option value='"+e.id+"'>"+e.name+"</option>");
                        });
                    }else{
                        $("#package_id").html("<option value=''>Seleccione</option>");
                    }
                        
                    $("#col_package_id").show();
                }else{
                    $("#col_package_id").hide();
                }

                $("#package_id").val("");
            });

            $("body").on("change","select#id_server_from", function(e){
                e.preventDefault();
                let server_id = $(this).children("option:selected").data("server-id");
                let server_name = $(this).children("option:selected").data('server-name');
                if(server_id){
                    $("#server_from").val(server_name);
                    $("#load-customers").empty();
                    $("#load-customers").html("<tr><td colspan='7' align='center'>Cargando...</td></tr>");
                    $.get('/api/get-customers-by-server/'+server_id, function(response){
                        let data = response;
                        if(data.length > 0){
                            $("#load-customers").empty();
                            $.each(data, function(v,e){
                                $("#load-customers").append("<tr><td><input type='checkbox' id='checked_"+e.id+"' name='new_customers[]' value='"+e.id+"' /></td><td>"+e.plex_user_name+"</td><td>"+e.email+"</td><td>"+(e.package ? e.package.name : 'Sin Paquete')+"</td><td id='new_email_"+e.id+"'></td><td id='package_"+e.id+"'></td><td id='status_"+e.id+"'>Listo para migrar</td></tr>");  
                            });
                        }else{
                            $("#load-customers").html("<tr><td colspan='7' align='center'>Sin Datos</td></tr>");
                        }
                    });
                }

            });

            $("#button_masive_server").click(function(){
                $("#modal-change-masive-server").modal({backdrop:'static', keyboard: false}, "show");
            });

            $("#cancel-change-masive-server").click(function(){
                location.reload();
                //$("#modal-change-masive-server").modal("hide");
            });

            $("body").on("click","a.view-refresh-server-libraries", function(){
                let id = $(this).attr("data-id");
                let name = $(this).attr("data-name");
                $("#server_name").text(name);
                selected_server_id = id;
                $("#load_libraries").html('<li class="list-group-item">Cargando...</li>');
                $.post("{{route('get_libraries')}}",{server_id: id}, function(response){
                    let data = response.response;
                    if(data.length > 0){
                        $("#load_libraries").empty();
                        $.each(data, function(v,e){
                            $("#load_libraries").append('<li class="list-group-item"><input type="checkbox" name="libraries[]" checked value="'+e.Section.key+'"> '+e.Section.title+'</li>');
                        });
                    }else{
                        $("#load_libraries").html('<li class="list-group-item">Sin Librerias</li>');
                    }
                });

                $("#update-libraries-modal").modal({backdrop:'static', keyboard:false},'show');
            });

            $("#save-update-libraries").click(function(){
                let libraries = $("input[name='libraries[]']");
                var cont = 0;
                libraries.each(function(){
                    if($(this).prop("checked")){
                        cont++;
                    }
                });

                if(cont > 0){
                    $("#update-libraries-modal").modal('hide');
                    Swal.fire({
                      title: 'Advertencia',
                      text: "Estamos Realizando el Cambio!!",
                      icon: 'warning',
                      showConfirmButton:false,
                      allowOutsideClick: false,
                      confirmButtonText: 'Yes, delete it!'
                    });

                    $.ajax({
                        url:"/admin/update-libraries/"+selected_server_id,
                        type: "POST",
                        data: $("input[name='libraries[]']:checked").serialize(),
                        success: function(response){
                        let data = response;
                            if(data.success){
                                Swal.fire({
                                  title: 'Notificacion',
                                  text: data.message,
                                  icon: 'success',
                                  showConfirmButton:true,
                                  allowOutsideClick:false,
                                  confirmButtonText: 'OK'
                                });
                            }else{
                                 Swal.fire({
                                  title: 'Notificacion',
                                  text: data.message,
                                  icon: 'error',
                                  showConfirmButton:true,
                                  confirmButtonText: 'OK'
                                });
                            }
                        }
                    });
                }else{
                    alert("Debes seleccionar al menos una libreria!!");
                }
            });

            $("#cancel-update-libraries").click(function(){
                $("#update-libraries-modal").modal("hide");
            });

            $("body").on('click','a.view-active-sessions', function(){
                let server_id = $(this).attr("data-id");
                let html = "";
                $("#load-sessions").html("<tr><td colspan='4'><center>Cargando Sesiones...</center></td></tr>");
                
                $.get("/api/get-active-sessions/"+server_id, function(response){
                    let sessions = response;
                    if(parseInt(sessions.length) > 0){
                        for(let i=0;i < sessions.length;i++){
                            html+="<tr><td><img src='"+sessions[i].media.cover+"' class='img-thumbnail' style='width:150px; height:150px;' /></td><td><b>"+sessions[i].media.title+"</b></td><td>"+sessions[i].player.ip+" / "+sessions[i].player.device+"</td><td><img src='"+sessions[i].user.avatar+"' style='width:50px; height:50px;' /> "+sessions[i].user.name+"</td></tr>";
                        }
                        $("#load-sessions").html(html);
                    }else{
                        $("#load-sessions").html("<tr><td colspan='4'><center>No se encontraron sesiones activas en este Servidor</center></td></tr>");
                    }
                });

                $("#active-sessions-modal").modal({backdrop:'static', keyboard:false}, 'show');
            });

            $("#cancel-active-sessions").click(function(){
                $("#active-sessions-modal").modal("hide");
            });

            @if (!$dataType->server_side)
                var table = $('#dataTable').DataTable({!! json_encode(
                    array_merge([
                        "responsive"=>true,
                        "order" => $orderColumn,
                        "language" => __('voyager::datatable'),
                        "columnDefs" => [
                            ['targets' => 'dt-not-orderable', 'searchable' =>  false, 'orderable' => false],
                        ],
                    ],
                    config('voyager.dashboard.data_tables', []))
                , true) !!});
            @else
                $('#search-input select').select2({
                    minimumResultsForSearch: Infinity
                });
            @endif

            @if ($isModelTranslatable)
                $('.side-body').multilingual();
                //Reinitialise the multilingual features when they change tab
                $('#dataTable').on('draw.dt', function(){
                    $('.side-body').data('multilingual').init();
                })
            @endif
            $('.select_all').on('click', function(e) {
                $('input[name="row_id"]').prop('checked', $(this).prop('checked')).trigger('change');
            });
        });


        var deleteFormAction;
        $('body').on('click', '.delete', function (e) {
            $('#delete_form')[0].action = '{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}'.replace('__id', $(this).data('id'));
            $('#delete_modal').modal('show');
        });

        @if($usesSoftDeletes)
            @php
                $params = [
                    's' => $search->value,
                    'filter' => $search->filter,
                    'key' => $search->key,
                    'order_by' => $orderBy,
                    'sort_order' => $sortOrder,
                ];
            @endphp
            $(function() {
                $('#show_soft_deletes').change(function() {
                    if ($(this).prop('checked')) {
                        $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true)) }}"></a>');
                    }else{
                        $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true)) }}"></a>');
                    }

                    $('#redir')[0].click();
                })
            })
        @endif
        $('input[name="row_id"]').on('change', function () {
            var ids = [];
            $('input[name="row_id"]').each(function() {
                if ($(this).is(':checked')) {
                    ids.push($(this).val());
                }
            });
            $('.selected_ids').val(ids);
        });
    </script>
@stop
