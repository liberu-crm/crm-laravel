<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        $articles = KnowledgeBaseArticle::latest()->paginate(10);
        return view('knowledge-base.index', compact('articles'));
    }

    public function show(KnowledgeBaseArticle $article)
    {
        return view('knowledge-base.show', compact('article'));
    }
}