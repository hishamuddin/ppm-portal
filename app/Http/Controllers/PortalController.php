<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function home(string $locale)
    {
        app()->setLocale($locale);

        return view('portal.home', ['locale' => $locale, 'latest' => Content::published()->where('locale', $locale)->where('type', '!=', 'page')->with(['category', 'media'])->latest('published_at')->limit(6)->get()]);
    }

    public function index(Request $r, string $locale)
    {
        app()->setLocale($locale);
        $filter = $r->validate(['q' => 'nullable|string|max:150', 'type' => 'nullable|in:post,speech,statement,gallery,page', 'category' => 'nullable|integer|exists:categories,id']);
        $q = Content::published()->where('locale', $locale)->with(['category', 'media']);
        if ($term = $filter['q'] ?? null) {
            $q->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('excerpt', 'like', '%'.$term.'%'));
        }
        if ($type = $filter['type'] ?? null) {
            $q->where('type', $type);
        }
        if ($category = $filter['category'] ?? null) {
            $q->where('category_id', $category);
        }

        return view('portal.index', ['locale' => $locale, 'items' => $q->latest('published_at')->paginate(12)->withQueryString(), 'categories' => Category::orderBy('name')->get()]);
    }

    public function show(string $locale, string $slug)
    {
        app()->setLocale($locale);
        $content = Content::published()->where(compact('locale', 'slug'))->with(['category', 'media'])->firstOrFail();

        return view('portal.show', compact('locale', 'content'));
    }
}
