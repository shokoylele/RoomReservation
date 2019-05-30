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
	    return redirect()->back()->with('roomAlert',"Room ".$request->room_id." has been successfully added to the database and scheduler!");
    }

    public function reserve(Request $request)
    {
        $request->validate([
            'room_id' => 'required',
            'users_involved' => 'nullable',
            'stime_res' => 'required',
            'etime_res' => 'required',
            'purpose' => 'required'
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
        //admin override
        if($checkExisting>='1' && Auth()->user()->roles != 0){ 
            return redirect()->back()->with('existingErr', "The room you've chosen is not available on the selected period.");
        }
        elseif($checkSameUserPending>='1' && Auth()->user()->roles == 1){
            return redirect()->back()->with('roomErr', "You've already submitted a request for the same room on the selected period! Please wait for the admin to confirm your request.");
        }
        else if($request->get('stime_res')==$request->get('etime_res')){
            return redirect()->back()->with('existingErr', "The start and end of the reservation cannot be the same.");
        }
        else if($checkAdminExisting>='1' && Auth()->user()->roles == 0){
            return redirect()->back()->with('existingErr', "You have an existing reservation for the same room on the selected period.");
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
                    /* $container = $form;
                    $container->isCancelled = '1';
                    $reservedForm = RegForm::where('user_id', '!=', 'admin')
                                        ->where('room_id', $request->get('room_id'))
                                        ->where('stime_res', '<', $request->get('etime_res'))
                                        ->where('etime_res', '>', $request->get('stime_res'))
                                        ->get()->first();
                    $user = User::where('user_id', $reservedForm->user_id)->get()->first();
                    $user->notify(new RoomStatus($container)); */

                    return redirect()->back()->with('roomAlert',"Your reservation has been approved and added to the calendar and database! 
                                                    Requests for the same room with similar reservation period have been overriden.");
                }
                else{
                    $form->save();
                    $user = User::where('user_id', 'admin')->first();
                    if($roomType->isSpecial=='1'){
                        $user->notify(new RoomStatus($form));
                    }
                    return redirect()->back()->with('roomAlert',"Sit back and relax! Your reservation has been received and is subject for approval.");
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
                    /* $cancelSameRange = RegForm::where('user_id', '!=', 'admin')
                                            ->where('room_id', $request->get('room_id'))
                                            ->where('stime_res', '<', $request->get('etime_res'))
                                            ->where('etime_res', '>', $request->get('stime_res'))
                                            ->where('isApproved', '1')
                                            ->update(['isCancelled' => true]); */

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
                /* $container = $form;
                $container->isCancelled = '1'; */
                if(Auth()->user()->roles == 0){
                    /* $reservedForm = RegForm::where('user_id', '!=', 'admin')
                                        ->where('room_id', $request->get('room_id'))
                                        ->where('stime_res', '<', $request->get('etime_res'))
                                        ->where('etime_res', '>', $request->get('stime_res'))
                                        ->get()->first();
                    $user = User::where('user_id', $reservedForm->user_id)->get()->first();
                    $user->notify(new RoomStatus($container)); */
                    return redirect()->back()->with('roomAlert',"Your reservation has been approved and added to the calendar and database!
                                                    Requests for the same room with similar reservation period have been overriden.");
                }
                else {
                    return redirect()->back()->with('roomAlert',"Your reservation has been approved and added to the calendar and database!");
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

        return redirect()->back()->with('approvedAlert', "The request has been approved and added to the scheduler! 
                                        Any pending requests for this room number with similar reservation period will 
                                        automatically be rejected.");
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

            return redirect()->back()->with('cancelledAlert', "The request/reservation has been cancelled.");
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
        return redirect()->back()->with('roomErr','Room '.$request->room_id.' has been successfully deleted from the database and scheduler.');
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
