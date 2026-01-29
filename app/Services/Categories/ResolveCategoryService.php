<?php

namespace App\Services\Categories;

use App\Models\Category;
use Illuminate\Support\Str;

class ResolveCategoryService
{
    public function resolve(?string $name): ?Category
    {
        if (! filled($name)) {
            return null;
        }

        $normalizedSearch = $this->normalize($name);
        $category = Category::query()
            ->whereNotNull('name')
            ->get()
            ->first(function ($category) use ($normalizedSearch) {
                return str_contains(
                    $this->normalize($category->name),
                    $normalizedSearch
                );
            });

        if ($category) {
            return $category;
        }

        return Category::create([
            'name'      => $name,
            'slug'      => Str::slug($name),
            'context'   => 'product',
            'status'    => true,
            'order'     => 0,
            'parent_id' => null,
        ]);
    }

    protected function normalize(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->trim()
            ->toString();
    }
}
