<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

       
        $locale = $request->query('locale') ?? app()->getLocale();

        ['title' => $title, 'content' => $content, 'excerpt' => $excerpt] =
            $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'title' => $title,
            'slug' => $this->slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'content_format' => $this->content_format ?? 'html',
            'tags' => $this->tags,
            'author' => $this->author,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'archived_at' => $this->archived_at,
            'views' => $this->views,
            'creator_id' => $this->creator_id,
            'article_category_id' => $this->article_category_id,
            'image' => storage_url($this->article_image),
            'image_url' => storage_url($this->article_image),
            'category' => new ArticleCategoryResource($this->whenLoaded('category')),
            'creator' => new UserResource($this->whenLoaded('creator')), 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
