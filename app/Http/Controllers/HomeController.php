<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $knowledgeBaseArticles = KnowledgeBaseArticle::latest()->take(5)->get();
        return view('home', compact('knowledgeBaseArticles'));
    }
}