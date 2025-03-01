@extends('layouts.app')
@if(Auth()->user()->roles == 1)
  @section('title') Dashboard @endsection
@else
  @section('title') Reservation History @endsection
@endif

@section('script')
@if (Auth()->User()->roles == 1)
  <script>
    $(window).on('load',function(){
    if (!sessionStorage.getItem('shown-modal')){
      $('#welcomeFAQModal').modal('show');
      sessionStorage.setItem('shown-modal', 'true');
      }
    });
  </script>
@endif

  <script>
    $.fn.dataTable.ext.search.push(
      function (settings, data, dataIndex) {   
        var valid = true;
        [startDate, endDate] = ($('#dateRange').val()) ? $('#dateRange').val().split(' - ') : [null, null];
        var min = moment(startDate);
        if (!min.isValid()) { 
          min = null; 
        }
        var max = moment(endDate).add(1, 'days');
        if (!max.isValid()) { 
          max = null; 
        }
        if (min == null && max == null) {
          valid = true;
        }
        else {
          var startIndex = ({{ Auth()->user()->roles }} == 1) ? 1 : 3;

          $.each(settings.aoColumns, function (i) {
            if (i == startIndex) {
              var cDate = moment(data[i]);

              if (cDate.isValid()) {
                if (max !== null && max.isBefore(cDate)) {
                  valid = false;
                }
                if (min !== null && cDate.isBefore(min)) {
                  valid = false;
                }
              }
              else {
                valid = false;
              }
            }
          });
      }
      return valid;
    });

    $(document).ready(function () {
      $("#dateRange").on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        $('#clearDates').prop('disabled', false);
        $('#overallHistory').DataTable().draw();
      });

      $("#dateRange").on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
      });

      $('#clearDates').click(function(e) {
        $("#dateRange").val('');
        $('#clearDates').prop('disabled', true);
        $('#overallHistory').DataTable().draw();
      });

      $('#dateRange').daterangepicker({
        opens: 'left',
        autoUpdateInput: false
      });

      $('#overallHistory').DataTable({
        dom: 'Bfrtip',
        order: [[ 0, "desc" ]],
        language: {
          "zeroRecords": "No records found.",
          "emptyTable": "Nothing to see here yet!"
        },
        columnDefs: [
          {
            @if(Auth()->user()->roles == 1)
              "targets": [ 1,2,3,4,6 ],
            @else
              "targets": [ 3,4,5,6 ],
            @endif
            "visible": false
          },
          {
            @if(Auth()->user()->roles == 0)
              "targets": [ 5,6 ],
            @elseif(Auth()->user()->roles == 1)
              "targets": [ 3,4,6 ],
            @else
              "targets": [ 5,6 ],
            @endif
            "searchable": false
          },
          @if(Auth()->user()->roles == 1)
          { 
            "orderable": false, 
            "targets": 10
          },
          @else
          { 
            "orderable": false, 
            "targets": 12
          },
          @endif
          @if(Auth()->user()->roles == 1)
          { width: 70, targets: 10 }
          @else
          { width: 70, targets: 12 }
          @endif
        ],
        lengthMenu: [
          [ 10, 25, 50, -1 ],
          [ '10 rows', '25 rows', '50 rows', 'Show all' ]
        ],
        buttons: [
          'pageLength',
          {
            extend: 'pdfHtml5',
            text: 'Export as PDF',
            orientation: 'landscape',
            exportOptions: {
              @if(Auth()->user()->roles==1)
              columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ]
              @else
              columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ]
              @endif
            },
            download: 'open'
          },
          @if(Auth()->user()->roles == 0)
          {
            extend: 'csvHtml5',
            text: 'Export as CSV',
            exportOptions: {
              columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 ]
            }
          }
          @endif
        ],
        select: true,
        drawCallback: function () {
          var table = $('#overallHistory').DataTable();
          if (table.data().length == 0 ||  table.rows( {search:'applied'} ).count() == 0) {
            table.buttons('.buttons-html5').disable();
          }
          else {
            table.buttons('.buttons-html5').enable();
          }
        }
      });
    });
  </script>
@endsection

@section('menu')
<div class="collapse navbar-collapse pull-left" id="navbar-collapse">
  <ul class="nav navbar-nav">
    @if (Auth()->user()->roles == 0)
      <li class="#"><a href={{ URL::route('Dashboard') }}>Dashboard</a></li>
      <li class="#"><a href={{URL::route('Reserve')}}>Room Management</a></li>
      <li class="active"><a href={{URL::route('History')}}>Reservation History</a></li>
      <li class="#"><a id="faqBtn" data-toggle="modal" data-target="#welcomeFAQModal">FAQ</a></li>
    @elseif (Auth()->user()->roles == 1)
      <li class="active"><a href={{ URL::route('Dashboard') }}>Dashboard</a></li>
      <li class="#"><a href={{URL::route('Reserve')}}>Room Reservation</a></li>
      <li class="#"><a id="faqBtn" data-toggle="modal" data-target="#welcomeFAQModal">FAQ</a></li>
    @else
      <li class="#"><a href={{ URL::route('Dashboard') }}>Room Overview</a></li>
      <li class="active"><a href={{URL::route('History')}}>Reservation History</a></li>
      <li class="#"><a id="faqBtn" data-toggle="modal" data-target="#welcomeFAQModal">FAQ</a></li>
    @endif
  </ul>      
</div>
@endsection

@section('content')
    <!--CONTENT WRAPPER-->
    <div class="content-wrapper">
        @include('layouts.inc.faq')
        <!--PAGE TITLE AND BREADCRUMB-->
        <section class="content-header">
            <h1>@yield('title')</h1>
          <ol class="breadcrumb">
            @if (Auth()->user()->roles == 0)
            <li><a href={{ URL::route('Dashboard') }}><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li class="active"> @yield('title')</li>
            @elseif(Auth()->user()->roles == 2)
            <li><a href={{ URL::route('Dashboard') }}><i class="fa fa-building"></i> Room Overview</a></li>
            <li class="active"> @yield('title')</li>
            @else 
            <li class="active"><i class="fa fa-building"></i> @yield('title')</a></li>
            <li class="#"><a id="faqBtn" data-toggle="modal" data-target="#welcomeFAQModal">FAQ</a></li>
            @endif
          </ol>
        </section>

        <!--ACTUAL CONTENT-->
        <section class="content container-fluid">
          @if(Auth()->User()->roles == 0)
            @include('layouts.alerts.successAlert', ['redirectMessageName' => 'approvedAlert'])
            @include('layouts.alerts.dangerAlert', ['redirectMessageName' => 'rejectedAlert'])
            @include('layouts.alerts.dangerAlert', ['redirectMessageName' => 'cancelledAlert'])
            @include('layouts.modals.infoModal', ['forms' => $reservations, 'isOverall' => true, 'isApproval' => true])
          @elseif(Auth()->User()->roles == 1)
            @include('layouts.modals.infoModal', ['forms' => $studentReservations, 'isOverall' => false, 'isApproval' => false])
            @include('layouts.modals.infoModal', ['forms' => $upcomingReservations, 'isOverall' => false, 'isApproval' => false])
            @include('layouts.alerts.dangerAlert', ['redirectMessageName' => 'cancelledAlert'])
          @else
            @include('layouts.modals.infoModal', ['forms' => $reservations, 'isOverall' => true, 'isApproval' => false])
          @endif
          <div class="row">
            @if(Auth()->User()->roles == 1)
            <div class="col-lg-4">
              <div class="box box-widget widget-user-2">
                <div class="widget-user-header bg-aqua-active">
                  <div class="widget-user-image">
                    <img class="img-circle" src="img/user.png" alt="User Avatar">
                  </div>
                  <h3 class="widget-user-username">{{Auth()->user()->name}}</h3>
                  <h5 class="widget-user-desc">{{Auth()->user()->user_id}}</h5>
                </div>
                <div class="box-footer no-padding">
                  <ul class="nav nav-stacked">
                    <li><a>Total Reservations Submitted <span class="pull-right badge bg-blue">{{ $studentReservations->count() }}</span></a></li>
                    <li><a>Reservations Approved <span class="pull-right badge bg-green">{{ $approvedCount }}</span></a></li>
                    <li><a>Reservations Cancelled <span class="pull-right badge bg-red">{{ $cancelledCount }}</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="row">
              <div class="col-lg-6">
                <!-- small box -->
                <div class="small-box bg-green">
                  <div class="inner">
                    <h3>{{$upcomingReservations->count()}}</h3>
                    <p><b>Confirmed</b><br>This Week</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-checkmark-circled"></i>
                  </div>
                  <a href="#" class="small-box-footer">
                  </a>
                </div>
              </div>
              <div class="col-lg-6">
                <!-- small box -->
                <div class="small-box bg-yellow">
                  <div class="inner">
                    <h3>{{$pendingCount}}</h3>
                    <p><b>Pending</b><br>Awaiting Approval</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-clock"></i>
                  </div>
                  <a href="#" class="small-box-footer">
                  </a>
                </div>
              </div>
              </div>

              <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title">Upcoming Reservations</h3>
                </div>

                <div class="box-body">
                  <div class="table-responsive">
                    <table class="table no-margin table-bordered table-striped table-hover">
                      <thead>
                        <tr>
                          <th>Reservation Period</th>
                          <th>Room</th>
                          @if(Auth()->user()->roles == 2)
                          <th>Name</th>
                          @endif
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        @if($upcomingReservations->isEmpty())
                          <tr>
                            <td colspan="6" class="text-center">No upcoming reservations so far!</td>
                          </tr>
                        @else
                          @foreach($upcomingReservations as $form)
                          <tr data-toggle="modal" data-target="#reqInfo{{$form->form_id}}" style="cursor: pointer">
                            <td>{{ Carbon::parse($form->stime_res)->format('M d, Y h:i A') }} - {{ Carbon::parse($form->etime_res)->format('M d, Y h:i A') }}</td>
                            <td>{{$form->room_id}}</td>
                            @if(Auth()->user()->roles == 2)
                            <td>{{$form->user->name}}</td>
                            @endif
                            <td class="text-center">
                              @if(Carbon::parse($form->stime_res)->isPast())
                                <span class="label label-success">Ongoing</span>
                              @else
                                <span class="label label-warning">Upcoming</span>
                              @endif
                            </td>
                          </tr>
                          @endforeach
                        @endif
                      </tbody>
                    </table>
                  </div>
                </div><!--END OF BOX-BODY-->
                <div class="box-footer clearfix">
                </div>
              </div><!--END OF CONTENT BOX-->
            </div>
            <div class="col-md-8">
            @else
            <div class="col-md-12">
            @endif
              <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title">Over-all History</h3>
                    <div class="box-tools">
                      <div class="input-group input-group-sm pull-right" style="width: 200px">
                        <input type="text" class="form-control" id="dateRange" placeholder="Filter by start date">
                        <span class="input-group-btn">
                          <button class="btn btn-default" type="button" id="clearDates" disabled>Clear</button>
                        </span>
                      </div>
                    </div>
                </div>
                <div class="box-body">
                  <div class="table-responsive">
                    <table id="overallHistory" class="table table-bordered table-striped table-hover">
                      <thead>
                        <tr>
                          <th>Request ID</th>
                          @if(Auth()->user()->roles != 1)
                          <th>ID</th>
                          <th>Name</th>
                          @endif
                          <th>Start Date and Time</th>
                          <th>End Date and Time</th>
                          <th>People Involved</th>
                          <th>Purpose</th>
                          <th>Room</th>
                          <th>Type</th>
                          <th>Submission Date</th>
                          <th>Response Date</th>
                          <th>Status</th>
                          <th></th>
                        </tr>
                      </thead> 

                      <tbody>
                        @if(Auth()->user()->roles == 1)
                          @foreach($studentReservations as $reservation)
                            <tr>
                              <td>{{ sprintf("%07d", $reservation->form_id) }}</td>
                              <td><time datetime="{{ $reservation->stime_res }}">{{ Carbon::parse($reservation->stime_res)->format('M d, Y h:i A') }}</time></td>
                              <td><time datetime="{{ $reservation->etime_res }}">{{ Carbon::parse($reservation->etime_res)->format('M d, Y h:i A') }}</time></td>
                              <td>@if($reservation->users_involved!=NULL){{$reservation->users_involved}} @else N/A @endif</td>
                              <td>{{$reservation->purpose}}</td>
                              <td>{{$reservation->room_id}}</td>
                              @if ($reservation->room->isSpecial)
                                <td><span class="label label-info">Special Room</span></td>
                              @else
                                <td><span class="label label-primary">Normal Room</span></td>
                              @endif
                              <td>{{ Carbon::parse($reservation->created_at)->toFormattedDateString() }}</td>
                              @if ($reservation->isApproved==0)
                                <td>N/A</td> 
                              @else
                                <td>{{ Carbon::parse($reservation->updated_at)->toFormattedDateString() }}</td>
                              @endif
                              @if($reservation->isCancelled == 1)
                                <td><span class="label label-warning">Cancelled</span></td>
                              @else
                                @if($reservation->isApproved == 1)
                                  <td><span class="label label-success">Approved</span></td>
                                @elseif($reservation->isApproved == 2)
                                  <td><span class="label label-danger">Rejected</span></td>
                                @else
                                  <td><span class="label label-info">Pending</span></td>
                                @endif
                              @endif
                              <td class="text-center"><button class="btn btn-primary btn-xs" data-toggle="modal" data-target="#reqInfo{{$reservation->form_id}}">See More</button></td>
                            </tr>
                            @endforeach
                        @else
                          @foreach($reservations as $reservation)
                            <tr>
                              <td>{{ sprintf("%07d", $reservation->form_id) }}</td>
                              <td>{{$reservation->user_id}}</td>
                              <td>{{$reservation->user->name}}</td>
                              <td><time datetime="{{ $reservation->stime_res }}">{{ Carbon::parse($reservation->stime_res)->format('M d, Y h:i A') }}</time></td>
                              <td><time datetime="{{ $reservation->etime_res }}">{{ Carbon::parse($reservation->etime_res)->format('M d, Y h:i A') }}</time></td>
                              <td>@if($reservation->users_involved!=NULL){{$reservation->users_involved}} @else N/A @endif</td>
                              <td>{{$reservation->purpose}}</td>
                              <td>{{$reservation->room_id}}</td>
                              @if ($reservation->room->isSpecial)
                                <td><span class="label label-info">Special Room</span></td>
                              @else
                                <td><span class="label label-primary">Normal Room</span></td>
                              @endif
                              <td>{{ Carbon::parse($reservation->created_at)->toFormattedDateString() }}</td>
                              @if ($reservation->isApproved==0)
                                <td>N/A</td> 
                              @else
                                <td>{{ Carbon::parse($reservation->updated_at)->toFormattedDateString() }}</td>
                              @endif
                              @if($reservation->isCancelled == 1)
                              <td><span class="label label-warning">Cancelled</span></td>
                              @else
                                @if($reservation->isApproved == 1)
                                <td><span class="label label-success">Approved</span></td>
                                @elseif($reservation->isApproved == 2)
                                  <td><span class="label label-danger">Rejected</span></td>
                                @else
                                  <td><span class="label label-info">Pending</span></td>
                                @endif
                              @endif
                              <td class="text-center"><button class="btn btn-primary btn-xs" data-toggle="modal" data-target="#reqInfo{{$reservation->form_id}}">See More</button></td>
                            </tr>
                          @endforeach
                        @endif
                      </tbody>
                    </table>
                  </div>
                </div><!--END OF BOX-BODY-->
              </div><!--END OF CONTENT BOX-->
            </div><!--END OF COLUMN-->
          </div>
        </section><!--END OF ACTUAL CONTENT-->
      </div><!--END OF CONTENT WRAPPER-->
@endsection
        