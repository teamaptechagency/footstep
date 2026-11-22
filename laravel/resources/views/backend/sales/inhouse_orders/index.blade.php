@extends('backend.layouts.app')

@section('content')
@if(Auth::user()->user_type==='admin')<div class="mb-3 text-right"><a class="btn btn-primary" href="{{ route('admin-order-entry.index') }}"><i class="las la-plus"></i> Create order / নতুন অর্ডার</a></div>@endif
<link rel="stylesheet" href="{{ static_asset('assets/css/order-list-ui.css') }}?v=20261120">
<script src="{{ static_asset('assets/js/order-list-protection.js') }}?v=2.4.4" defer></script>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->has('protection'))<div class="alert alert-warning">{{ $errors->first('protection') }}</div>@endif
@php
$orderProtectionRows=Auth::user()->user_type==='admin'?\App\Services\OrderProtection::forOrders($orders->items()):[];
$returningCustomers=\App\Services\ReturningCustomers::forOrders($orders->items());
@endphp
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

<div class="card order-list-card">
    <form class="" id="sort_orders" action="" method="GET">
      <div class="card-header order-list-toolbar">
        <div class="order-filter-title">
          <h5 class="mb-md-0 h6">{{ translate('Inhouse Orders') }}</h5>
        </div>
        <div class="order-filter-field">
            <div class="form-group mb-0">
                <input type="text" class="aiz-date-range form-control" value="{{ $date }}" name="date" placeholder="{{ translate('Filter by date') }}" data-format="DD-MM-YYYY" data-separator=" to " data-advanced-range="true" autocomplete="off">
            </div>
        </div>
          <div class="order-filter-field">
            <select class="form-control aiz-selectpicker" name="payment_status" id="payment_status">
                <option value="">{{translate('Filter by Payment Status')}}</option>
                <option value="paid"  @isset($payment_status) @if($payment_status == 'paid') selected @endif @endisset>{{translate('Paid')}}</option>
                <option value="unpaid"  @isset($payment_status) @if($payment_status == 'unpaid') selected @endif @endisset>{{translate('Un-Paid')}}</option>
            </select>
          </div>

          <div class="order-filter-field">
            <select class="form-control aiz-selectpicker" name="delivery_status" id="delivery_status">
<option value="">{{translate('Filter by Delivery Status')}}</option>
 @foreach(\App\Services\OrderFilters::STATUSES as $status)<option value="{{ $status }}" @if($delivery_status===$status) selected @endif>{{ translate(ucwords(str_replace('_',' ',$status))) }}</option>@endforeach
 </select>
          </div>
          <div class="order-filter-field">
            <div class="form-group mb-0">
              <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="Order number, phone or customer name">
            </div>
          </div>
          <div class="order-filter-actions">
            <div class="form-group mb-0">
              <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button> <a class="btn btn-light" href="{{ route('inhouse_orders.index') }}">Reset</a>
            </div>
          </div>
      </div>
    </form>

    <div class="card-body">
        @include('backend.sales.partials.order_list_table',['inhouseList'=>true])
        <div class="aiz-pagination">
            {{ $orders->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        function sort_orders(el){
            $('#sort_orders').submit();
        }
    </script>
@endsection
