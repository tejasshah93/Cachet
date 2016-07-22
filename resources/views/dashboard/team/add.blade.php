@extends('layout.dashboard')

@section('content')
    <div class="header">
        <div class="sidebar-toggler visible-xs">
            <i class="ion ion-navicon"></i>
        </div>
        <span class="uppercase">
            <i class="ion ion-ios-people-outline"></i> {{ trans('dashboard.team.team') }}
        </span>
    </div>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-sm-12">
                @include('dashboard.partials.errors')
                <form name="UserForm" class="form-vertical" role="form" action="/dashboard/team/add" method="POST">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <fieldset>
                        <div class="form-group">
                            <label>{{ trans('forms.user.email') }}</label>
                            <input type="email" class="form-control" name="email" value="{{ Binput::old('email') }}" required>
                        </div>
                        @if($current_user->isAdmin)
                        <div class="form-group">
                            <label>{{ trans('forms.user.user_level') }}</label>
                            <select name="level" class="form-control">
                                <option value="3" selected>{{ trans('forms.user.levels.user') }}</option>
                                <option value="2">{{ trans('forms.user.levels.super_user') }}</option>
                                <option value="1">{{ trans('forms.user.levels.admin') }}</option>
                            </select>
                        </div>
                        @endif
                    </fieldset>

                    <div class="form-group">
                        <div class='btn-group'>
                            <button type="submit" class="btn btn-success">{{ trans('forms.add') }}</button>
                            <a class="btn btn-default" href="{{ route('dashboard.team.index') }}">{{ trans('forms.cancel') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
