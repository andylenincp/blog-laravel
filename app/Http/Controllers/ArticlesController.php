<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Tag;
use App\Article;
use App\Image;
use Laracast\Flash\Flash;

class ArticlesController extends Controller
{
    public function index(Request $request)
    {
        $articles = Article::search($request->title)->orderBy('id', 'DESC')->paginate(5);
        $articles->each(function($articles){
            $articles->category;
            $articles->user;
        });
        return view('admin.articles.index')->with('articles', $articles);   
    }
    
    public function create()
    {
        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id');
        $categories = Category::orderBy('name', 'ASC')->pluck('name', 'id');
        return view('admin.articles.create')->with('categories', $categories)->with('tags', $tags);
    }

    public function store(Request $request)
    {
        //Manipulación de imágenes

        $validatedData = $request->validate([
            'title'         =>  'required|min:8|max:250|unique:articles',
            'category_id'   =>  'required',
            'content'       =>  'min:60|required',
            'image'         =>  'image|required'
        ]);
        
        if ($request->file('image')) {
            $file = $request->file('image');
            $name = 'innovbec_' . time() . '.' . $file->getClientOriginalExtension();
            $path = public_path() . '/img/articles/';
            $file->move($path, $name);
        }
        
        $article = new Article($request->all());
        $article->user_id = \Auth::user()->id;
        $article->save();

        $article->tags()->sync($request->tags);

        $image = new Image();
        $image->name = $name;
        $image->article()->associate($article);
        $image->save();

        flash('Registro exitoso del artículo '.$article->title)->success()->important();
        return redirect()->route('articles.index');
    }

    public function edit($id)
    {
        $article = Article::find($id);
        $categories = Category::orderBy('name', 'DESC')->pluck('name', 'id');
        $tags = Tag::orderBy('name', 'DESC')->pluck('name', 'id');
        return view ('admin.articles.edit')->with('categories', $categories)->with('article', $article)->with('tags', $tags);
    }

    public function update(Request $request, $id)
    {
        $article = Article::find($id);
        $article->fill($request->all());
        $article->save();

        $article->tags()->sync($request->tags);

        flash('Actualización exitosa del artículo '.$article->title)->warning()->important();

        return redirect()->route('articles.index');
    }

    public function destroy($id)
    {
        $article = Article::find($id);
        $article->delete();
        flash('Artículo '. $article->title . ' eliminado con éxito')->error()->important();
        return redirect()->route('articles.index');
    }

    public function __construct()
    {
        $this->middleware('auth');
    }
}
