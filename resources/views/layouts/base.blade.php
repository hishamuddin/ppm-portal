<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'PPM Portal')</title><meta name="description" content="@yield('description','PPM Portal — news, speeches and government information')">@if(config('portal.demo') || request()->is('admin*') || request()->is('login'))<meta name="robots" content="noindex,nofollow">@endif
@vite(['resources/css/app.css','resources/js/app.js'])
@stack('head')</head>
<body><a class="skip-link" href="#main">{{ ($locale ?? 'en')==='ms'?'Langkau ke kandungan':'Skip to content' }}</a>
@yield('shell')
</body></html>
