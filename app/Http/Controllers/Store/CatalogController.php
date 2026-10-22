<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = (string) $request->query('categoria', '');
        $sort = in_array($request->query('orden'), ['recientes', 'precio_asc', 'precio_desc', 'nombre'], true)
            ? $request->query('orden')
            : 'recientes';
        $minPrice = $request->query('precio_min');
        $maxPrice = $request->query('precio_max');
        $availableOnly = $request->boolean('disponible');

        $products = Product::query()
            ->visibleInStore()
            ->withReserved()
            ->with('category')
            ->search($search)
            ->when($categorySlug !== '', fn ($query) => $query
                ->whereHas('category', fn ($q) => $q->where('slug', $categorySlug)))
            ->when(is_numeric($minPrice) && $minPrice !== '', fn ($q) => $q->where('products.price', '>=', (float) $minPrice))
            ->when(is_numeric($maxPrice) && $maxPrice !== '', fn ($q) => $q->where('products.price', '<=', (float) $maxPrice))
            ->when($availableOnly, function ($q) {
                $q->whereRaw('(SELECT COALESCE(SUM(order_items.quantity), 0) FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE orders.status = ? AND (orders.expires_at IS NULL OR orders.expires_at > ?) AND order_items.product_id = products.id) < products.stock', ['pending', now()]);
            })
            ->when($sort === 'precio_asc', fn ($q) => $q->orderBy('products.price'))
            ->when($sort === 'precio_desc', fn ($q) => $q->orderByDesc('products.price'))
            ->when($sort === 'nombre', fn ($q) => $q->orderBy('products.name'))
            ->when($sort === 'recientes', fn ($q) => $q->latest('products.created_at'))
            ->simplePaginate(12)
            ->withQueryString();

        $categories = Category::active()
            ->whereHas('products', fn ($q) => $q->visibleInStore())
            ->orderBy('name')
            ->get();

        // El carrusel de destacados solo se muestra en la portada del catǭlogo.
        $featured = collect();

        if ($search === '' && $categorySlug === '' && $minPrice === null && $maxPrice === null && ! $availableOnly && (int) $request->query('page', 1) <= 1) {
            $featured = Product::query()
                ->visibleInStore()
                ->featured()
                ->withReserved()
                ->latest('products.updated_at')
                ->limit(10)
                ->get();
        }

        return view('store.index', compact('products', 'categories', 'search', 'categorySlug', 'sort', 'featured', 'minPrice', 'maxPrice', 'availableOnly'));
    }

    public function show(string $slug)
    {
        $product = Product::visibleInStore()
            ->withReserved()
            ->with('category')
            ->where('products.slug', $slug)
            ->firstOrFail();

        return view('store.show', compact('product'));
    }
}
