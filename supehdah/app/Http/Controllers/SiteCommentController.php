<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteComment;

class SiteCommentController extends Controller
{
    /**
     * Store a new site comment.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        SiteComment::create($data);

        return back()->with('success', 'Thank you — your comment was submitted.');
    }
}
