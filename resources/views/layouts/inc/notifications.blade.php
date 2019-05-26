<!--NOTIFICATIONS-->
<li class="dropdown notifications-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
      <i class="fa fa-bell-o"></i>
    @if (Auth::user()->unreadNotifications->count()==0)
    <span></span>
    @else
      <span class="label label-warning">{{Auth::user()->unreadNotifications->count()}}</span>
    @endif
    </a>
    <ul class="dropdown-menu">
      <li class="header">
        @if(Auth::user()->unreadNotifications->count()==1)
        You have 1 notification
        @else
        You have {{Auth::user()->unreadNotifications->count()}} notifications
        @endif
      </li>
      <li>
        <ul class="menu">
          @if (Auth::user()->roles == 1)
            @if (Auth::user()->unreadNotifications->count()==0)
              <li><a href="#"><i class="fa fa-check-circle-o text-green"></i> No new notifications.</a></li>
            @else
              @foreach (Auth::user()->unreadNotifications as $notification)
              <li>
              <a href="{{ route('readnotification', $notification->id) }}">
                @if ($notification->data['cancel_status'] == 1)
                <i class="fa fa-calendar-minus-o text-orange"></i> Your reservation {{sprintf("%07d", $notification->data['form_id'])}} has been cancelled.
                @else
                  @if ($notification->data['status'] == 1)
                  <i class="fa fa-calendar-check-o text-success"></i> Your reservation {{sprintf("%07d", $notification->data['form_id'])}} has been approved.
                  @else
                  <i class="fa fa-calendar-times-o text-danger"></i> Your reservation {{sprintf("%07d", $notification->data['form_id'])}} has been denied.
                  @endif
                @endif
                  {{-- at {{$notification->updated_at->diffForHumans()}}  (para sa timestamp)--}}
              </a>
              </li>
              @endforeach
            @endif
          @else
              @if (Auth::user()->unreadNotifications->count()==0)
                <li><a href="#"><i class="fa fa-check-circle-o text-green"></i> No new notifications.</a></li>
              @else
                  @foreach (Auth::user()->unreadNotifications as $notification)
                  <li>
                  <a href="{{ route('readnotification', $notification->id) }}">
                      <i class="fa fa-clock-o text-orange"></i> Student {{$notification->data['user_id']}} has a new reservation.
                  </a>
                  </li>
                  @endforeach
              @endif
          @endif
        </ul>
      </li>
    </ul>
  </li>
  <!--END OF NOTIFICATIONS-->