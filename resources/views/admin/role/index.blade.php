@extends('layouts.admin.admin_design')
@section('content')
    <div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
        <div class="row">
            <ol class="breadcrumb">
                <li><a href="#">
                        <em class="fa fa-home"></em>
                    </a></li>
                <li><a href="{{ url('/dashboard')}}">Dashboard</a></li>
                <li class="active">Role</li>
            </ol>
        </div><!--/.row-->

        <div class="row">
            <div class="col-lg-12">
                <div class="login-panel panel panel-default"
                     style="height: 50px; padding-top: 10px; margin-bottom: 50px">
                    <div style="display: inline;">
                        <a style="font-size: 20px; margin-left: 20px;" href="{{ route('role.create') }}">Create</a>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success" style="margin-top: 8px">
                            <p>{{ $message }}</p>
                        </div>
                    @endif
                </div>
                <div style="margin-bottom: 20px">
                    <h4 style="display: inline; margin-left: 10px">Roles
                        <h5 style="display: inline">
                            from </h5> {{$roles->firstItem()}}
                        <h5 style="display: inline"> to</h5> {{$roles->lastItem()}} // {{$roles->total()}}
                    </h4>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>STT</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($roles as $key => $role)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{$role->name}}</td>
                            <td>
                                {!! Form::open(['route' => ['role.destroy',$role->id], 'method' => 'POST']) !!}
                                <a class="btn btn-info" href="{{ route('role.edit',$role->id) }}">Edit</a>
                                @csrf
                                <input name="_method" type="hidden" value="DELETE">
                                {!! Form::submit('Delete',['class' => 'btn btn-danger']) !!}
                                {!! Form::close() !!}

                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {!! $roles->links() !!}
            </div>
        </div>
    </div><!--/.row-->
@endsection
