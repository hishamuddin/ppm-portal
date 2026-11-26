@extends('layouts.base')
@section('shell')
<div class="admin-shell"><aside class="sidebar"><a class="admin-brand" href="{{ route('admin.dashboard') }}"><span class="brand-tile">P</span><span>PPM Portal<small>CONTENT MANAGEMENT</small></span></a><div class="side-caption">WORKSPACE</div>
<nav aria-label="Administration">
@foreach(['dashboard'=>'Overview','contents.index'=>'Content','media.index'=>'Media library'] as $route=>$label)<a class="{{ request()->routeIs('admin.'.$route)?'active':'' }}" href="{{ route('admin.'.$route) }}">{{ $label }}</a>@endforeach
@if(auth()->user()->canPublish())<a href="{{ route('admin.categories') }}">Categories</a><a href="{{ route('admin.trash') }}">Trash</a>@endif
@if(auth()->user()->role==='admin')<div class="side-caption">MANAGE</div>@foreach(['menus'=>'Navigation','settings'=>'Portal settings','users'=>'People & roles','audit'=>'Activity log'] as $route=>$label)<a class="{{ request()->routeIs('admin.'.$route)?'active':'' }}" href="{{ route('admin.'.$route) }}">{{ $label }}</a>@endforeach @endif
<div class="side-caption">ACCOUNT</div><a href="{{ route('admin.profile') }}">My password</a><a href="/ms" target="_blank" rel="noopener">View portal ↗</a></nav><div class="sidebar-bottom"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div>{{ auth()->user()->name }}<small>{{ ucfirst(auth()->user()->role) }}</small></div><form action="{{ route('logout') }}" method="post">@csrf<button class="text-button" type="submit">Sign out</button></form></div></aside>
<div class="admin-body"><header class="admin-top"><span>Workspace <span class="muted"> / @yield('title','Overview')</span></span><span class="pill">Editorial CMS</span></header><main id="main" class="admin-main">@include('partials.messages')@yield('content')</main><footer class="admin-footer">PPM Portal · Malaysia Government Design System theme</footer></div></div>
@endsection
