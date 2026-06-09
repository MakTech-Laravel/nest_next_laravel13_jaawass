<?php

namespace App\Services;

use App\Models\Article;

class ArticleService
{
    public function find(string $id): Article
    {
        return Article::findOrFail($id);
    }

    public function create(array $data): Article
    {
        return Article::create($data);
    }

    public function update(Article $article, array $data): Article
    {
        $article->update($data);

        return $article->fresh();
    }

    public function delete(Article $article): bool
    {
        return $article->delete();
    }
}
