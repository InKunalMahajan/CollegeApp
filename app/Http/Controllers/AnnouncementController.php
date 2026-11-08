<?php

namespace App\Http\Controllers;

use App\Models\SchoolAnnouncement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $role = auth()->user()->role;
        return view('announcements.index', [
            'announcements'=>SchoolAnnouncement::with('author')
                ->whereNotNull('published_at')->where('published_at','<=',now())
                ->whereIn('audience',['all',$role])->latest('published_at')->paginate(20),
            'drafts'=>$role==='admin'
                ? SchoolAnnouncement::whereNull('published_at')->latest()->get()
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'title'=>['required','string','max:200'],
            'body'=>['required','string','max:10000'],
            'audience'=>['required',Rule::in(['all','admin','staff','teacher','student'])],
        ]);
        SchoolAnnouncement::create($data + ['author_id'=>auth()->id()]);
        return to_route('announcements.index')->with('success','Announcement draft saved.');
    }

    public function publish(SchoolAnnouncement $announcement): RedirectResponse
    {
        if ($announcement->published_at !== null) abort(409);
        $announcement->update(['published_at'=>now()]);
        return to_route('announcements.index')->with('success','Announcement published.');
    }
}
