@extends('backend.layouts.app')

@section('content')
<style>
.order-list-toolbar .order-popup-trigger{margin-left:auto;min-height:44px;font-size:14px!important;padding:10px 18px}
#orderFilterModal .modal-content{border:0;border-radius:16px;box-shadow:0 18px 60px #102e3429}
#orderFilterModal .modal-header,#orderFilterModal .modal-footer{padding:18px 20px}
#orderFilterModal .modal-title{font-size:19px;font-weight:700}
#orderFilterModal .modal-body{padding:8px 20px 20px}
#orderFilterModal .order-popup-field{margin-top:16px}
#orderFilterModal label{display:block;font-size:13px;font-weight:600;margin-bottom:7px;color:#24483e}
#orderFilterModal .form-control{width:100%;min-height:46px;font-size:16px;border-radius:9px}
#orderFilterModal .btn{min-height:44px;border-radius:9px}
#orderFilterModal .btn-primary{background:#086b59!important;border-color:#086b59!important;color:#fff!important}
#orderFilterModal .close{min-width:44px;min-height:44px;opacity:1;color:#24483e}
@media(max-width:575px){#orderFilterModal .modal-dialog{margin:12px}#orderFilterModal .modal-content{max-height:calc(100dvh - 24px);overflow-y:auto}#orderFilterModal .modal-footer{gap:6px}#orderFilterModal .modal-footer .btn{margin:0}}
</style>

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
    <form class="" action="" id="sort_orders" method="GET">
        <div class="card-header order-list-toolbar">
            <div class="order-filter-title">
                <h5 class="mb-md-0 h6">{{ translate('All Orders') }}</h5>
            </div>

            <div class="dropdown order-filter-bulk">
                <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                    {{translate('Bulk Action')}}
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" onclick="bulk_delete()"> {{translate('Delete selection')}}</a>
<!--                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#exampleModal">
                        <i class="las la-sync-alt"></i>
                        {{translate('Change Order Status')}}
                    </a>-->
                </div>
            </div>

            <!-- Change Status Modal -->
            

            <button type="button" class="btn btn-primary order-popup-trigger" data-toggle="modal" data-target="#orderFilterModal" aria-haspopup="dialog"><i class="las la-filter" aria-hidden="true"></i> {{ translate('Filter') }}</button>
        </div>

        <div class="card-body">
            @include('backend.sales.partials.order_list_table',['inhouseList'=>false])

            <div class="aiz-pagination">
                {{ $orders->appends(request()->input())->links() }}
            </div>

        </div>

<div class="modal fade" id="orderFilterModal" tabindex="-1" role="dialog" aria-labelledby="orderFilterTitle" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="orderFilterTitle">{{ translate('Filter orders') }}</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
  <div class="modal-body order-popup-fields">
            <div class="order-popup-field"><label for="order_filter_payment_status">Payment status</label><select class="form-control" id="order_filter_payment_status" name="payment_status"><option value="">{{ translate('Filter by Payment Status') }}</option>@foreach(['paid','unpaid'] as $payment)<option value="{{ $payment }}" @if($payment_status===$payment) selected @endif>{{ translate(ucfirst($payment)) }}</option>@endforeach</select></div>
            <div class="order-popup-field"><label for="order_filter_delivery_status">Delivery status</label>
                <select class="form-control" name="delivery_status" id="order_filter_delivery_status">
<option value="">{{translate('Filter by Delivery Status')}}</option>
 @foreach(\App\Services\OrderFilters::STATUSES as $status)<option value="{{ $status }}" @if($delivery_status===$status) selected @endif>{{ translate(ucwords(str_replace('_',' ',$status))) }}</option>@endforeach
 </select>
            </div>
            <div class="order-popup-field"><label for="order_filter_date">Date range</label>
                <div class="form-group mb-0">
                    <input type="text" class="aiz-date-range form-control" value="{{ $date }}" id="order_filter_date" name="date" placeholder="{{ translate('Filter by date') }}" data-format="DD-MM-YYYY" data-separator=" to " data-advanced-range="true" autocomplete="off">
                </div>
            </div>
            <div class="order-popup-field"><label for="order_filter_search">Order number, phone or customer name</label>
                <div class="form-group mb-0">
                    <input type="text" class="form-control" id="order_filter_search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="Order number, phone or customer name">
                </div>
            </div>

  </div>
  <div class="modal-footer"><a class="btn btn-light" href="{{ route('all_orders.index') }}">Reset</a><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Apply filters</button></div>
 </div></div>
</div>
    </form>
</div>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                {{translate('Choose an order status')}}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <select class="form-control aiz-selectpicker" onchange="change_status()" data-minimum-results-for-search="Infinity" id="update_delivery_status">
                                <option value="pending">{{translate('Pending')}}</option>
                                <option value="confirmed">{{translate('Confirmed')}}</option>
                                <option value="picked_up">{{translate('Picked Up')}}</option>
                                <option value="on_the_way">{{translate('On The Way')}}</option>
                                <option value="delivered">{{translate('Delivered')}}</option>
                                <option value="cancelled">{{translate('Cancel')}}</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </div>
            </div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        // Keep the calendar inside the dialog's keyboard focus boundary.
        $('#orderFilterModal').on('shown.bs.modal', function () {
            var picker = $('#order_filter_date').data('daterangepicker');
            if (picker) { picker.parentEl = $(this); picker.container.appendTo(this); }
        }).on('hide.bs.modal', function () {
            var picker = $('#order_filter_date').data('daterangepicker');
            if (picker) picker.hide();
        });
        $(document).on("change", ".check-all", function() {
            if(this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

//        function change_status() {
//            var data = new FormData($('#order_form')[0]);
//            $.ajax({
//                headers: {
//                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//                },
//                url: "{{route('bulk-order-status')}}",
//                type: 'POST',
//                data: data,
//                cache: false,
//                contentType: false,
//                processData: false,
//                success: function (response) {
//                    if(response == 1) {
//                        location.reload();
//                    }
//                }
//            });
//        }

        function bulk_delete() {
            var data = new FormData($('#sort_orders')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('bulk-order-delete')}}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function (response) {
                    if(response == 1) {
                        location.reload();
                    }
                }
            });
        }
    </script>
@endsection
