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
      <li class="header">You have {{Auth::user()->unreadNotifications->count()}} notifications</li>
      <li>
        <ul class="menu">
          @if (Auth::user()->roles == 1)
            @if (Auth::user()->unreadNotifications->count()==0)
              <li><a href="#"><i class="fa fa-check-circle-o text-green"></i> No new notifications.</a></li>
            @else
              @foreach (Auth::user()->unreadNotifications as $notification)
              <li>
              <a href="{{ route('readnotification', $notification->id) }}">
                  Your reservation {{sprintf("%07d", $notification->data['form_id'])}} has been
                      @if ($notification->data['cancel_status'] == 1)
                          cancelled.
                          <p><i class="fa fa-clock-o text-orange"></i><small> {{$notification->updated_at->diffForHumans()}}</small></p>
                      @else
                        @if ($notification->data['status'] == 1)
                            approved.
                            <p><i class="fa fa-clock-o text-orange"></i><small> {{$notification->updated_at->diffForHumans()}}</small></p>
                        @else
                            denied.
                            <p><i class="fa fa-clock-o text-orange"></i><small> {{$notification->updated_at->diffForHumans()}}</small></p>
                        @endif
                      @endif
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
                      <i class="fa fa-clock-o text-orange"></i> Student {{$notification->data['user_id']}} has a new reservation
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