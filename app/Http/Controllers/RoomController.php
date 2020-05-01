<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Room;
use App\User;
use App\RegForm;
use Illuminate\Http\Request;
use App\Http\Requests\RoomRequest;
use App\Notifications\RoomStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;


class RoomController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function show(Room $room)
    {
        //
    }

    public function store(RoomRequest $request)
    {
        Room::create($request->validated()); 
        
        return redirect()->back()->with('roomAlert',["Room ".$request->room_id." has been successfully added!", 
        "This room will be now available for reservation."]);
    }

    public function reserve(Request $request)
    {
        $request->validate([
            'room_id' => 'required',
            'users_involved' => 'nullable',
            'stime_res' => 'required',
            'etime_res' => 'required',
            'purpose' => 'required|max:255'
        ]);

        if($request->has('users_involved')){
            $usersInvolved = $request->input('users_involved');
            $usersInvolved = implode(', ', $usersInvolved);
        }
        else {
            $usersInvolved = NULL;
        }

        $roomType = Room::where('room_id', $request->get('room_id'))->first();

        $checkExisting = RegForm::where('room_id', $request->get('room_id'))
                                ->where('stime_res', '<',  $request->get('etime_res'))
                                ->where('etime_res', '>', $request->get('stime_res'))
                                ->where('isApproved', '1')
                                ->where('isCancelled', '0')
                                ->count();

        $checkSameUserPending = RegForm::where('user_id', Auth()->user()->user_id)
                                        ->where('room_id', $request->get('room_id'))
                                        ->where('stime_res', '<',  $request->get('etime_res'))
                                        ->where('etime_res', '>', $request->get('stime_res'))
                                        ->where('isApproved', '0')
                                        ->count();

        $checkAdminExisting = RegForm::where('user_id','admin')
                                     ->where('room_id', $request->get('room_id'))
                                     ->where('stime_res', '<',  $request->get('etime_res'))
                                     ->where('etime_res', '>', $request->get('stime_res'))
                                     ->where('isApproved', '1')
                                     ->where('isCancelled', '0')
                                     ->count();

        //max. requests a day = 5
        $checkMaxReserve = RegForm::where('user_id', auth()->user()->user_id)
                                  ->whereDate('created_at', Carbon::today())
                                  ->where('isApproved', '!=', '-1')
                                  ->count();

        if($checkMaxReserve>=5 && auth()->user()->roles == 1){
            return redirect()->back()->with('roomErr', ["Oops! You have reached your maximum daily requests.",
            "As a precautionary measure against spam, please try again tomorrow or book under another user."]);
        }
        elseif($checkExisting>='1' && Auth()->user()->roles == 1){ 
            return redirect()->back()->with('existingErr', ["The room you've selected is taken!", 
            "The room you've chosen is not available on the selected period."]);
        }
        elseif($checkSameUserPending>='1' && Auth()->user()->roles == 1){
            return redirect()->back()->with('roomErr', ["Duplicate submission detected!",
                "You've already submitted a request for the same room on the selected period! Please wait for the admin to confirm your request."]);
        }
        else if($request->get('stime_res')==$request->get('etime_res')){
            return redirect()->back()->with('existingErr', ["Invalid reservation period!", "The start and end of the reservation cannot be the same."]);
        }
        else if($checkAdminExisting>='1' && Auth()->user()->roles == 0){
            return redirect()->back()->with('existingErr', ["Same confirmed reservation already exists!", 
            "You have an existing reservation for the same room on the selected period."]);
        }
        else {
            if($roomType->isSpecial=='1'){
                $form = new RegForm([
                    'user_id' =>  Auth()->user()->user_id,
                    'room_id' => $request->get('room_id'),
                    'users_involved' => $usersInvolved,
                    'stime_res' => $request->get('stime_res'),
                    'etime_res' => $request->get('etime_res'),
                    'purpose' => $request->get('purpose')
                ]);

                if(Auth()->user()->roles == 0){

                    /* $rejectSameRange = RegForm::where('user_id', '!=', 'admin')
                                              ->where('room_id', $request->get('room_id'))
                                              ->where('stime_res', '<', $request->get('etime_res'))
                                              ->where('etime_res', '>', $request->get('stime_res'))
                                              ->where('isApproved', '0')
                                              ->update(['isApproved' => '2']); */

                    /* $cancelSameRange = RegForm::where('user_id', '!=', 'admin')
                                              ->where('room_id', $request->get('room_id'))
                                              ->where('stime_res', '<', $request->get('etime_res'))
                                              ->where('etime_res', '>', $request->get('stime_res'))
                                              ->where('isApproved', '1')
                                              ->update(['isCancelled' => true]); */

                    $sameRange = RegForm::where('user_id', '!=', 'admin')
                                        ->where('room_id', $request->get('room_id'))
                                        ->where('stime_res', '<', $request->get('etime_res'))
                                        ->where('etime_res', '>', $request->get('stime_res'))
                                        ->where('isApproved', '0')
                                        ->get();
                    $cancelSameRange = RegForm::where('user_id', '!=', 'admin')
                                              ->where('room_id', $request->get('room_id'))
                                              ->where('stime_res', '<', $request->get('etime_res'))
                                              ->where('etime_res', '>', $request->get('stime_res'))
                                              ->where('isApproved', '1')
                                              ->first();

                    if(!empty($cancelSameRange)){
                        $user = User::where('user_id',$cancelSameRange->user_id)->first();
                        $cancelSameRange->isCancelled = '1';
                        $cancelSameRange->save();
                        $user->notify(new RoomStatus($cancelSameRange));
                    }

                    if(!empty($sameRange)){
                        foreach($sameRange as $same){
                            $user = User::where('user_id',$same->user_id)->first();
                            $same->isApproved = '2';
                            $same->save();
                            $user->notify(new RoomStatus($same));
                        }
                    }
                    $form->isApproved = '1';
                    $form->save();

                    return redirect()->back()->with('adminRoomAlert', ["Your reservation is now confirmed!",
                    "Requests for the same room with similar reservation period have been overriden. User/s affected will be notified. 
                    To cancel this reservation, just click on your reservation in the dashboard or scheduler."]);
                }
                else{
                    $form->save();
                    $user = User::where('user_id', 'admin')->first();
                    if($roomType->isSpecial=='1'){
                        $user->notify(new RoomStatus($form));
                    }
                    return redirect()->back()->with('roomAlert',["Your special room request has been received.",
                    "Sit back and relax! Your request is now subject for approval. You will receive a notification once its status has been updated."]);
                }
            }
            else {
                $form = new RegForm([
                    'user_id' =>  Auth()->user()->user_id,
                    'room_id' => $request->get('room_id'),
                    'users_involved' => $usersInvolved,
                    'stime_res' => $request->get('stime_res'),
                    'etime_res' => $request->get('etime_res'),
                    'purpose' => $request->get('purpose')
                ]);
                
                if(Auth()->user()->roles == 0){
                    $cancelSameRange = RegForm::where('user_id', '!=', 'admin')
                                                ->where('room_id', $request->get('room_id'))
                                                ->where('stime_res', '<', $request->get('etime_res'))
                                                ->where('etime_res', '>', $request->get('stime_res'))
                                                ->where('isApproved', '1')
                                                ->first();
                    if(!empty($cancelSameRange)){
                        $user = User::where('user_id',$cancelSameRange->user_id)->first();
                        $cancelSameRange->isCancelled = '1';
                        $cancelSameRange->save();
                        $user->notify(new RoomStatus($cancelSameRange));
                    }
                }

                $form->isApproved = '1';
                $form->save();

                if(Auth()->user()->roles == 0){
                    return redirect()->back()->with('adminRoomAlert', ["Your reservation is now confirmed!",
                    "Requests for the same room with similar reservation period have been overriden. User/s affected will be notified. 
                    To cancel this reservation, just click on your reservation in the dashboard or scheduler."]);
                }
                else {
                    return redirect()->back()->with('roomAlert',["Your reservation is now confirmed!",
                    "Your reservation has been approved and added to the calendar! To cancel this reservation, 
                    just click on your reservation in the dashboard or scheduler."]);
                }
            }
        }
    }

    public function approve($id)
    {
        if(Auth()->user()->roles == 0){
            $specialRequest = RegForm::find($id);
            
            $specialRequest->isApproved = '1';
            $sameRange = RegForm::where('user_id', '!=', 'admin')
                                ->where('room_id', $specialRequest->room_id)
                                ->where('stime_res', '<', $specialRequest->etime_res)
                                ->where('etime_res', '>', $specialRequest->stime_res)
                                ->where('isApproved', '0')
                                ->get();
            $specialRequest->save();

            if(!empty($sameRange)){
                foreach($sameRange as $same){
                    if($same->user_id != $specialRequest->user_id){
                        $user = User::where('user_id',$same->user_id)->first();
                        $same->isApproved = '2';
                        $same->save();
                        $user->notify(new RoomStatus($same));
                    }
                }
            }

            $user = User::where('user_id', $specialRequest->user_id)->first();
            $user->notify(new RoomStatus($specialRequest));

            return redirect()->back()->with('approvedAlert', ["The request has been approved and added to the scheduler!", 
            "Any pending requests for this room number with similar reservation period will 
            automatically be rejected and notified."]);
        }
        else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function reject($id)
    {
        if(Auth()->user()->roles == 0){
            $specialRequest = RegForm::find($id);
            $specialRequest->isApproved = '2';
            $specialRequest->save();

            $user = User::where('user_id', $specialRequest->user_id)->first();
            $user->notify(new RoomStatus($specialRequest));

            return redirect()->back()->with('rejectedAlert', "The request has been rejected.");
        }
        else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function historyList()
    {
        $reservations = RegForm::get();
        $users = User::get();
        $rooms = Room::get();
        $studentReservations = RegForm::where('user_id', Auth()->User()->user_id)->get();

        return view('pages.history')->with("reservations", $reservations)
                                    ->with("users", $users)
                                    ->with("rooms", $rooms)
                                    ->with("studentReservations", $studentReservations);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function edit(Room $room)
    {
        //
    }

    public function cancel($id)
    {
        $cancelRequest = RegForm::find($id);

        if(Auth()->user()->user_id == $cancelRequest->user_id || Auth()->user()->roles == 0){
            $cancelRequest->isCancelled = '1';
            $cancelRequest->save();

            if (Auth()->user()->roles == 0 and Auth()->user()->user_id != $cancelRequest->user_id){
                $user = User::where('user_id', $cancelRequest->user_id)->first();
                $user->notify(new RoomStatus($cancelRequest));
            }

            if(Auth()->user()->roles == 0) {
                return redirect()->back()->with('cancelledAlert', ["The request/reservation is now cancelled.", 
                "User/s affected will be notified. Reservation details may still be accessed through the over-all reservation history."]);
            }
            else {
                return redirect()->back()->with('cancelledAlert', ["Your request/reservation is now cancelled.", 
                "Reservation details may still be accessed through your reservation history."]);
            }
        }
        else {
            abort(403, 'Unauthorized action.');
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Room  $room
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $delete = Room::where('room_id',$request->room_id)->first();
        $delete->delete();
        return redirect()->back()->with('roomErr',["Room ".$request->room_id." has been successfully deleted.",
        "Any confirmed and pending reservations are now automatically cancelled."]);
    }

    public function list()
    {
        $forms = RegForm::where('isApproved', '1')->get();
        $rooms = Room::orderByRaw('LENGTH(room_desc)', 'asc')
                    ->orderBy('room_desc', 'asc')
                    ->get();
        $descriptions = Room::groupBy('room_desc')
                            ->orderByRaw('LENGTH(room_desc)', 'asc')
                            ->orderBy('room_desc', 'asc')
                            ->pluck('room_desc');
        $users = User::orderBy('name','asc')
                     ->where('isActive', true)
                     ->get();

        $roomList = [];
        foreach($descriptions as $description){
            $roomList[] = [
                $description => []
            ];
        }

        foreach($rooms as $room){
            if(isset($room->room_name)) {
                $roomList[$room->room_desc][$room->room_id] = $room->room_id." (".$room->room_name.")";
            }
            else {
                $roomList[$room->room_desc][$room->room_id] = $room->room_id;
            }
        }

        foreach($descriptions as $description){
            $spaces = '/\s+/';
            $replace = '-';
            $string = $description;
            $trimmedDesc = preg_replace($spaces, $replace, strtolower($string));

            $roomListJson = json_encode($roomList[$description], JSON_PRETTY_PRINT);
            file_put_contents(public_path($trimmedDesc.'.json'), stripslashes($roomListJson));
        }

        return view('pages.reservation')->with("forms", $forms)
                                        ->with("rooms", $rooms)
                                        ->with("descriptions", $descriptions)
                                        ->with("users", $users);
    }

    public function readNotif($id)
    {
        $notification = DatabaseNotification::where('id',$id)->first();
        $notification->markAsRead();

        return redirect()->route('Dashboard');
    }

    public function readAllNotif()
    {
        $user = User::where('user_id', Auth()->user()->user_id)->first();
        $user->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
