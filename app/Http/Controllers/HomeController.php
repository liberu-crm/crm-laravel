<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $knowledgeBaseArticles = KnowledgeBaseArticle::latest()->take(5)->get();

        return view('home', compact('knowledgeBaseArticles'));
    }
}
