<?php

namespace App\Http\Controllers\Notification;

use App\Models\Event;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventsController extends Controller
{
    use ImageTrait;

    public function showEvents(Request $request)
    {
        $data = $this->loadCommonData($request);

        return view('pages.Events.show', $data);
    }

    public function createEvents(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.Events.create', $data);
    }

    public function storeEvents(Request $request)
    {
        $data = $this->loadCommonData($request);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'location' => 'required|string',
            'date' => 'required|date',
            'startTime' => 'required|string',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $data['user'];
            $company = $data['company'];

            $eventId = Event::count() + 1;
            $slug = Str::slug($request->input('title'), '-') . '-' . $eventId;

            $eventData = [
                'title' => $request->input('title'),
                'slug' => $slug,
                'description' => $request->input('description'),
                'user_id' => $user->id,
                'location' => $request->input('location'),
                'date' => $request->input('date'),
                'startTime' => $request->input('startTime'),
                'company_id' => $company->id,
            ];

            $this->updateImage($request, $eventData, 'image');

            Event::create($eventData);

            return redirect()->back()->with('success', 'Event details created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create Event: ' . $e->getMessage());
        }
    }

    public function editEvents(Request $request, $dummy, $event = null)
    {
        $data = $this->loadCommonData($request);
        $decodedEventId = unserialize(base64_decode($event));

        $editEvent = Event::findOrFail($decodedEventId);

        return view('pages.Events.edit', compact('editEvent') + $data);
    }

    public function updateEvents(Request $request, $dummy, $event)
    {
        $data = $this->loadCommonData($request);
        $company = $data['company'];

        try {
            $decodedEventId = unserialize(base64_decode($event));
            $updateEvent = Event::findOrFail($decodedEventId);

            $imagePaths = [];
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    $imagePath = 'storage/' . $image->store('EventImages', 'public');
                    $imagePaths[] = $imagePath;
                }
            }

            $eventData = [
                'title' => $request->input('title'),
                'slug' => Str::slug($request->input('title'), '-') . '-' . $decodedEventId, // Updated to use decodedEventId
                'description' => $request->input('description'),
                'image' => json_encode($imagePaths),
                'price' => $request->input('price'),
                'location' => $request->input('location'),
                'date' => $request->input('date'),
                'startTime' => $request->input('startTime'),
                'company_id' => $company->id,
            ];

            $updateEvent->update($eventData);

            return redirect()->back()->with('success', 'Event details updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update event details: ' . $e->getMessage());
        }
    }

    public function deleteEvents($dummy, Event $event)
    {
        try {
            $event->delete();
            return redirect()->back()->with('success', 'Event deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete event: ' . $e->getMessage());
        }
    }

}
