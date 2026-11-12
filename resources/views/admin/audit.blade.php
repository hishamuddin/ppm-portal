@extends('layouts.admin')
@section('title','Activity log')
@section('content')<h1>Activity log</h1><div class="panel table-scroll"><table><thead><tr><th>Time (MYT)</th><th>Staff ID</th><th>Action</th><th>Subject ID</th></tr></thead><tbody>@forelse($items as $item)<tr><td>{{ $item->created_at }}</td><td>{{ $item->user_id }}</td><td>{{ $item->action }}</td><td>{{ $item->subject }}</td></tr>@empty<tr><td colspan="4">No activity recorded.</td></tr>@endforelse</tbody></table></div>{{ $items->links('partials.pagination') }}@endsection
