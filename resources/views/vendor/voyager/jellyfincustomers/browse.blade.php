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
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover">
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
                                                    @if($row->field == 'jellyfinpackage_id')
                                                     @if(!empty(@$data->jellyfinpackage->name))
                                                        <span>{{ $data->jellyfinpackage->name }}</span>
                                                     @else
                                                        <span>Sin Paquete</span>
                                                     @endif
                                                    @else
                                                     <span>{{ $data->{$row->field} }}</span>
                                                    @endif
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
                        </div>
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


    <div class="modal modal-success fade" tabindex="-1" id="extend_subscription" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Extender Membresia</h4>
                </div>
                <form action="{{route('extend_membership_jellyfin')}}" method="POST">
                    @method('POST')
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="jellyfin_customer_id">
                        <div class="form-group">
                            <label for="">Duracion:</label>
                            <select name="duration_id" class="form-control">
                                <option value="">Seleccione</option>
                                @foreach($durations as $duration)
                                    <option value="{{$duration->id}}" data-months="{{$duration->months}}">{{$duration->name}} ({{$duration->months}} Meses)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Fecha Hasta Actual:</label>
                            <input type="date" id="current_date_to" readonly class="form-control" />
                        </div>
                        <div class="form-group">
                            <label for="">Nueva Fecha Hasta:</label>
                            <input type="date" name="date_to" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Extender</button>
                        <button type="button" class="btn btn-danger pull-right" id="close_modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-success fade" tabindex="-1" id="change_password" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cambiar Clave</h4>
                </div>
                <form action="{{route('jellyfin_customer_change_password')}}" method="POST">
                    @method('POST')
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="change_password_jellyfin_customer_id" name="change_password_jellyfin_customer_id">
                        <div class="form-group">
                            <label for="">Nueva Clave:</label>
                            <input type="text" required name="new_password" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Cambiar</button>
                        <button type="button" class="btn btn-danger pull-right" id="change_password_close_modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal modal-success fade" tabindex="-1" id="view_sessions" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Actividad del Usuario (<span id="current_user">Usuario</span>)</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <th>Nombre</th>
                            <th>Direccion</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Severidad</th>
                        </thead>
                        <tbody id="loadSessions"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger pull-right" id="view_sessions_close_modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-success fade" tabindex="-1" id="change_server" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Cambiar de Servidor</h4>
                </div>
                <form action="{{route('jellyfin_change_server')}}" method="POST">
                    @method('POST')
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="change_server_jellyfin_customer_id" name="change_server_jellyfin_customer_id">
                        <div class="form-group">
                            <label for="">Nuevo Servidor:</label>
                            <select name="jellyfin_server_id" required class="form-control">
                                <option value="">Seleccione</option>
                                @foreach($servers as $server)
                                <option value="{{$server->id}}" id="server_{{$server->id}}" data-packages='{{json_encode($server->packages)}}'>{{$server->name}} (@if($server->status == 1) Activo @else Inactivo @endif)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Nuevo Package:</label>
                            <select name="jellyfin_package_id" class="form-control">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Cambiar</button>
                        <button type="button" class="btn btn-danger pull-right" id="change_server_close_modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Modal Activate in Device-->
    <div class="modal modal-success fade" tabindex="-1" id="activate_device" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Activar en Dispositivo</h4>
                </div>
                <form method="POST" action="{{route('jellyfin_activate_device')}}">
                    @method('POST')
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="activate_device_id" />
                        <input type="hidden" name="type" value="customer" />
                        <div class="form-group">
                            <label>Codigo:</label>
                            <input type="text" name="code" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Activar</button>
                        <a class="btn btn-danger" id="close_activate_device" href="#">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--Modal Activate in Device-->
    <div class="modal modal-success fade" tabindex="-1" id="asing-to-user-modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Cambiar de Usuario</h4>
                </div>
                <form method="POST" action="{{route('jellyfin_change_user')}}">
                    @method('POST')
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="asing-to-user_id" />
                        <div class="form-group">
                            <label>Usuario:</label>
                            <select class="form-control" name="user_id">
                                <option>Seleccione</option>
                                @php 
                                    $users = \App\Models\User::whereIn('role_id',[3,5])->get();
                                @endphp

                                @foreach($users as $user)
                                    <option value="{{$user->id}}">{{$user->name}} ({{$user->role->name}})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success">Cambiar</button>
                        <a class="btn btn-danger" id="close-asing-to-user-modal" href="#">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop

@section('css')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
    <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
@stop

@section('javascript')
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var currentServer;
        $(document).ready(function () {
            @php
                $host = request()->getHttpHost();
            @endphp

            $("body").on('click','a.enable-disable-user', function(){
                let id = $(this).data("id");
                let status = parseInt($(this).data("status"));
                let message = "Estas seguro de inactivar este cliente?";

                if(!status){
                    message = "Estas seguro de activar este cliente?";
                }

                if(confirm(message)){
                    $.post("{{route('disable_enable_customer')}}", { id:id, status:status }, function(response){
                        if(response){
                            location.reload();
                        }
                    });
                }
            });

            $("body").on('click','a.asing-to-user', function(){
                let id = $(this).data("id");
                $("#asing-to-user_id").val(id);
                $("#asing-to-user-modal").modal({"backdrop": 'static', "keyboard":false}, "show");
            });

            $("#close-asing-to-user-modal").click(function(){
                $("#asing-to-user-modal").modal("hide");
            });

            $("body").on('click','a.connect-device', function(){
                let id = $(this).data("id");
                $("#activate_device_id").val(id);
                $("#activate_device").modal({"backdrop": 'static', "keyboard":false}, "show");
            });

            $("#close_activate_device").click(function(){
                $("#activate_device").modal("hide");
            });

            $("body").on('click','a.change-server', function(){
                currentServer = $(this).data('server_id');
                $("#server_"+currentServer).hide();
                $("#change_server_jellyfin_customer_id").val($(this).data("jellyfin_user_id"));
                $("#change_server").modal({keyboard:false, backdrop: 'static'}, 'show');
            });

            $("#change_server_close_modal").click(function(){
                $("#server_"+currentServer).show();
                $("#change_server").modal('hide');
            });

            $("select[name='jellyfin_server_id']").change(function(){
                let packages = $(this).children("option:selected").attr("data-packages");
                $("select[name='jellyfin_package_id']").empty();
                if(packages){
                    packages = JSON.parse(packages);
                    if(packages.length > 0){
                        $("select[name='jellyfin_package_id']").html("<option value=''>Seleccione</option>");
                        $.each(packages, function(v,e){
                            $("select[name='jellyfin_package_id']").append("<option value='"+e.id+"'>"+e.name+"</option>");
                        });

                        return;
                    }
                }

                $("select[name='jellyfin_package_id']").html("<option value=''>Seleccione</option>");
            });

            $("body").on('click','a.view-active-sessions', function(){
                let jellyfin_user = $(this).data("jellyfin_user");
                $("#current_user").text(jellyfin_user);
                let server_id = $(this).data("server_id");
                let jellyfin_user_id = $(this).data('jellyfin_user_id');
                $.post("{{route('view_sessions_by_user_jellyfin')}}", { server_id:server_id, jellyfin_user_id:jellyfin_user_id }, function(response){
                    $("#loadSessions").html("<tr><td colspan='5' align='center'>Cargando...</td></tr>");
                    if(response.length > 0){
                        $("#loadSessions").empty();
                        $.each(response, function(i,e){
                            $("#loadSessions").append("<tr><td>"+e.Name+"</td><td>"+(e.ShortOverview ? e.ShortOverview : 'Sin Direccion')+"</td><td>"+e.Type+"</td><td>"+e.Date+"</td><td>"+e.Severity+"</td></tr>");
                        });
                    }else{
                        $("#loadSessions").html("<tr><td colspan='5' align='center'>Sin Datos</td></tr>");
                    }
                });

                $("#view_sessions").modal({backdrop:'static',keyboard:false},'show');
            });

            $("#view_sessions_close_modal").click(function(){
                $("#view_sessions").modal('hide');
            });

            $("body").on("click","a.extend-subscription", function(){
                let id = $(this).attr("data-id");
                let current_date_to = $(this).attr("data-date_to");
                $("input[name='jellyfin_customer_id']").val(id);
                $("#current_date_to").val(current_date_to);
                $("#extend_subscription").modal({backdrop:'static',keyboard:false},'show');
            });

            $("body").on('click','a.change-password', function(){
                $("#change_password_jellyfin_customer_id").val($(this).data("id"));
                $("#change_password").modal({keyboard: false, backdrop: 'static'}, "show");
            });

            $("#change_password_close_modal").click(function(){
                $("#change_password").modal("hide");
            });

            $("#close_modal").click(function(){
                $("select[name='duration_id'], input[name='date_to'], #current_date_to").val("");
                $("#extend_subscription").modal('hide');
            });

            $("select[name='duration_id']").change(function(){
                let id = $(this).val();
                let months = $(this).children(":selected").attr("data-months");
                var date_to = "{{date('Y-m-d')}}";
                let current_date_to = $("#current_date_to").val();
                if(id){
                   let validateDate = validateDates(current_date_to);

                   if(validateDate){
                        date_to = current_date_to;
                   }

                   $.get("/api/get-extend-month-durations/"+date_to+"/"+months+"/", function(response){
                        let data = response;
                        $("input[name='date_to']").val(data.date);

                   }); 
               }else{
                $("input[name='date_to']").val("");
               }
                
            });

            @if (!$dataType->server_side)
                var table = $('#dataTable').DataTable({!! json_encode(
                    array_merge([
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

            @if(Session::get('modal'))
                @php 
                    $data = Session::get('modal');
                @endphp
                Swal.fire({
                  title: 'Estos son los datos que debes darle al cliente!!',
                  icon: 'info',
                  html:'<textarea id="field_copy" class="form-control" style="height: 150px; width: 403px;" readonly>Panel: {{$host}}\n**Jellyfin**\nServidor: {{$data->jellyfinserver->host}}\nNombre de Usuario: {{$data->name}}\nClave: {{$data->password}}\nInicios de Sesion: {{$data->screens}}\nFecha de Vencimiento: {{date("d-m-Y",strtotime($data->date_to))}}</textarea>',
                  confirmButtonColor: '#5cb85c',
                  confirmButtonText: 'Copiar y Salir',
                  allowOutsideClick:false
                }).then((result) => {
                  if (result.isConfirmed) {
                    $("#field_copy").select();
                    document.execCommand('copy');
                  }
                });
            @endif
        });


        var deleteFormAction;
        $('td').on('click', '.delete', function (e) {
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

        function validateDates(parameterDate, currentDate = new Date()) {
            var currentDateObj = new Date(currentDate);
            var parameterDateObj = new Date(parameterDate);
            if (isNaN(currentDateObj) || isNaN(parameterDateObj)) {
                return false;
            }
            if (parameterDateObj >= currentDateObj) {
                return true;
            } else {
                return false;
            }
        }
    </script>
@stop
