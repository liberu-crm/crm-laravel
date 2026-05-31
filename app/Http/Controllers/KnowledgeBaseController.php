<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;

class KnowledgeBaseController extends Controller
{
    public function index(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $articles = KnowledgeBaseArticle::latest()->paginate(10);

        return view('knowledge-base.index', ['articles' => $articles]);
    }

    public function show(KnowledgeBaseArticle $article): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('knowledge-base.show', ['article' => $article]);
    }
}
